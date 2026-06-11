# Business Flows

This document describes the main business workflows of the Bus Ticket Booking API.

The purpose is to explain how the system behaves in real booking scenarios, not only at the CRUD level.

---

## 1. Roles

### Customer

Customer can:

* Register and login
* View available routes and trips
* Select seats
* Create booking
* Pay for booking
* Cancel pending booking
* Request refund
* View notifications
* View personal dashboard

### Admin

Admin can:

* Manage routes, bus types, buses, and trips
* Manage bookings
* Manage refund requests
* Manage users
* Start and complete trips
* View passenger list
* Check in passengers
* Verify tickets by booking code
* View admin dashboard

---

## 2. Booking Flow

### Goal

Allow customer to select seats and temporarily hold them before payment.

### Flow

```text
Customer selects trip
→ Customer selects available seats
→ Customer creates booking
→ System locks selected seats
→ Booking status becomes pending_payment
→ Trip seats status becomes reserved
→ Customer has 10 minutes to pay
```

### Main Rules

* Customer must be logged in.
* Customer account must be active.
* Trip must be `scheduled`.
* Trip departure time must be greater than current time.
* Seat must be `available`.
* Seats are locked inside a database transaction.
* `lockForUpdate` is used to prevent two users from booking the same seat at the same time.
* Booking expires after 10 minutes if not paid.

### Status Changes

| Entity   | Before      | After             |
| -------- | ----------- | ----------------- |
| Booking  | none        | `pending_payment` |
| TripSeat | `available` | `reserved`        |

### Failure Cases

| Case                         | Result              |
| ---------------------------- | ------------------- |
| Trip already departed        | Booking is rejected |
| Trip cancelled/completed     | Booking is rejected |
| Seat already reserved/booked | Booking is rejected |
| User inactive                | API is blocked      |
| Invalid seat id              | Booking is rejected |

---

## 3. Booking Expiration Flow

### Goal

Release seats when customer does not pay within the allowed time.

### Flow

```text
Booking is pending_payment
→ 10 minutes pass
→ Expiration command runs
→ Booking becomes expired
→ Reserved seats become available again
```

### Main Rules

* Only `pending_payment` bookings can expire.
* Only `reserved` seats linked to the booking are released.
* Booking history is created.

### Status Changes

| Entity   | Before            | After       |
| -------- | ----------------- | ----------- |
| Booking  | `pending_payment` | `expired`   |
| TripSeat | `reserved`        | `available` |

### Related Command

```bash
php artisan bookings:expire-pending
```

### Scheduler

The command can be scheduled to run every minute.

---

## 4. Customer Cancel Booking Flow

### Goal

Allow customer to cancel a booking before payment.

### Flow

```text
Customer creates booking
→ Booking is still pending_payment
→ Customer cancels booking
→ Booking becomes cancelled
→ Reserved seats become available
```

### Main Rules

* Customer can only cancel their own booking.
* Only `pending_payment` booking can be cancelled.
* Confirmed booking cannot be cancelled directly.
* Refund workflow must be used for confirmed booking.

### Status Changes

| Entity   | Before            | After       |
| -------- | ----------------- | ----------- |
| Booking  | `pending_payment` | `cancelled` |
| TripSeat | `reserved`        | `available` |

---

## 5. Payment Flow

### Goal

Allow customer to pay for a booking through PayOS.

### Flow

```text
Customer creates booking
→ Customer requests PayOS payment link
→ System validates booking and trip
→ System creates PayOS payment link
→ Customer pays
→ PayOS return/webhook notifies system
→ System confirms payment
→ Booking becomes confirmed
→ Reserved seats become booked
→ Notification/email is created
```

### Main Rules

* Booking must belong to current customer.
* Booking must be `pending_payment`.
* Booking must not be expired.
* Trip must be `scheduled`.
* Trip departure time must be greater than current time.
* Payment amount must match booking total amount.
* Payment confirmation is idempotent to avoid duplicate processing.

### Status Changes

| Entity   | Before            | After       |
| -------- | ----------------- | ----------- |
| Payment  | `pending`         | `success`   |
| Booking  | `pending_payment` | `confirmed` |
| TripSeat | `reserved`        | `booked`    |

### PayOS Callback Endpoints

```text
GET  /api/payments/payos/return
GET  /api/payments/payos/cancel
POST /api/payments/payos/webhook
```

### Local Development

Admin can fake payment success:

```text
POST /api/admin/payments/{payment}/fake-success
```

---

## 6. Refund Request Flow

### Goal

Allow customer to request refund for a confirmed booking.

### Flow

```text
Customer has confirmed booking
→ Customer sends refund request
→ System validates refund condition
→ Booking becomes refund_requested
→ Admin reviews refund request
```

### Main Rules

* Customer can only request refund for their own booking.
* Booking must be `confirmed`.
* Trip must still be `scheduled`.
* Trip departure time must be greater than current time.
* Booking must have a successful payment.
* Ticket must not be checked in.
* Checked-in ticket cannot be refunded.

### Status Changes

| Entity  | Before      | After              |
| ------- | ----------- | ------------------ |
| Booking | `confirmed` | `refund_requested` |

### Failure Cases

| Case                      | Result                     |
| ------------------------- | -------------------------- |
| Booking is not confirmed  | Refund request is rejected |
| Trip already departed     | Refund request is rejected |
| Trip completed/cancelled  | Refund request is rejected |
| Ticket already checked in | Refund request is rejected |
| No successful payment     | Refund request is rejected |

---

## 7. Refund Approval Flow

### Goal

Allow admin to approve a refund request.

### Flow

```text
Booking is refund_requested
→ Admin approves refund
→ Payment becomes refunded
→ Booking becomes refunded
→ Booked seats become available again
→ Customer receives notification
```

### Main Rules

* Only admin can approve refund.
* Booking must be `refund_requested`.
* Payment must be `success`.
* Seats are released only when they are still linked to the booking and have status `booked`.
* Notification is created for customer.

### Status Changes

| Entity   | Before             | After       |
| -------- | ------------------ | ----------- |
| Booking  | `refund_requested` | `refunded`  |
| Payment  | `success`          | `refunded`  |
| TripSeat | `booked`           | `available` |

---

## 8. Refund Rejection Flow

### Goal

Allow admin to reject a refund request.

### Flow

```text
Booking is refund_requested
→ Admin rejects refund
→ Booking returns to confirmed
→ Customer receives notification
```

### Main Rules

* Only admin can reject refund.
* Booking must be `refund_requested`.
* Rejection reason is required.
* Notification is created for customer.

### Status Changes

| Entity  | Before             | After       |
| ------- | ------------------ | ----------- |
| Booking | `refund_requested` | `confirmed` |

---

## 9. Trip Creation Flow

### Goal

Allow admin to create a new trip and generate trip seats.

### Flow

```text
Admin selects route
→ Admin selects bus
→ Admin sets departure and arrival time
→ System checks bus availability
→ System creates trip
→ System generates trip seats from bus type seats
```

### Main Rules

* Bus cannot have overlapping active trips.
* Overlap check applies to trips with status `scheduled` or `departed`.
* Trip seats are generated based on the selected bus type.
* New trip status is `scheduled`.

### Status Changes

| Entity   | Before | After       |
| -------- | ------ | ----------- |
| Trip     | none   | `scheduled` |
| TripSeat | none   | `available` |

---

## 10. Trip Cancellation Flow

### Goal

Allow admin to cancel a scheduled trip.

### Flow

```text
Trip is scheduled
→ Admin cancels trip
→ Pending bookings are cancelled
→ Available seats are blocked
→ Trip becomes cancelled
```

### Main Rules

* Only `scheduled` trip can be cancelled.
* Trip with confirmed booking cannot be cancelled directly.
* Pending bookings are cancelled.
* Reserved seats from pending bookings are released.
* Available seats are blocked.

### Status Changes

| Entity   | Before            | After                                  |
| -------- | ----------------- | -------------------------------------- |
| Trip     | `scheduled`       | `cancelled`                            |
| Booking  | `pending_payment` | `cancelled`                            |
| TripSeat | `available`       | `blocked`                              |
| TripSeat | `reserved`        | `available` then blocked if applicable |

---

## 11. Trip Depart Flow

### Goal

Allow admin to start a trip.

### Flow

```text
Trip is scheduled
→ Admin starts trip
→ Pending bookings expire
→ Unsold seats become blocked
→ Booked seats remain booked
→ Trip becomes departed
```

### Main Rules

* Only `scheduled` trip can depart.
* Pending payment bookings become expired.
* Available and reserved seats become blocked.
* Booked seats remain booked.
* After departure, customer cannot book or request refund.

### Status Changes

| Entity   | Before            | After      |
| -------- | ----------------- | ---------- |
| Trip     | `scheduled`       | `departed` |
| Booking  | `pending_payment` | `expired`  |
| TripSeat | `available`       | `blocked`  |
| TripSeat | `reserved`        | `blocked`  |
| TripSeat | `booked`          | `booked`   |

---

## 12. Trip Complete Flow

### Goal

Allow admin to complete a departed trip.

### Flow

```text
Trip is departed
→ Admin completes trip
→ Trip becomes completed
```

### Main Rules

* Only `departed` trip can be completed.
* Completed trip can no longer be sold, refunded, or checked in.

### Status Changes

| Entity | Before     | After       |
| ------ | ---------- | ----------- |
| Trip   | `departed` | `completed` |

---

## 13. Passenger List Flow

### Goal

Allow admin to view passengers of a trip.

### Flow

```text
Admin opens trip passengers
→ System loads confirmed bookings
→ System returns customer info, seats, payments, and check-in status
```

### Main Rules

* Only confirmed bookings are considered passengers.
* Each seat shows check-in state.
* Passenger count is based on confirmed bookings.

### Returned Information

* Trip info
* Route info
* Bus info
* Seat summary
* Booking code
* Customer info
* Seat numbers
* Payment info
* Check-in info

---

## 14. Passenger Check-in Flow

### Goal

Allow admin to check in passengers before or during a trip.

### Flow

```text
Admin views passenger list
→ Admin checks in one seat or full booking
→ System stores checked_in_at and checked_in_by
→ Booking history is created
```

### Main Rules

* Booking must be `confirmed`.
* Trip must be `scheduled` or `departed`.
* Cancelled or completed trips cannot be checked in.
* A seat cannot be checked in twice.
* Admin can undo check-in if needed.
* Checked-in ticket cannot request refund.

### Check-in Types

| Type             | Description                      |
| ---------------- | -------------------------------- |
| Booking check-in | Check in all seats of a booking  |
| Item check-in    | Check in one seat of a booking   |
| Undo check-in    | Clear check-in data for one seat |

---

## 15. Ticket Verification Flow

### Goal

Allow admin or staff to verify ticket by booking code.

### Flow

```text
Admin enters or scans booking code
→ System finds booking
→ System checks booking validity
→ System returns ticket details
→ Admin can check in by booking code
```

### Main Rules

* Booking code must exist.
* Booking must be `confirmed`.
* Booking must have successful payment.
* Trip must allow check-in.
* Ticket must not be fully checked in.

### Verification Result

The system returns:

* Whether ticket is valid
* Whether ticket can be checked in
* Reason if it cannot be checked in
* Seat count
* Checked-in count
* Full check-in state

---

## 16. Notification Flow

### Goal

Notify customer about important booking events.

### Flow

```text
System event occurs
→ Notification is created
→ Customer views notifications
→ Customer marks notification as read
```

### Current Notification Events

| Event                             | Notification Type                |
| --------------------------------- | -------------------------------- |
| Booking confirmed                 | `booking_confirmed`              |
| Booking confirmation email failed | `booking_confirmed_email_failed` |
| Refund approved                   | `refund_approved`                |
| Refund rejected                   | `refund_rejected`                |

### Read/Unread Logic

| State  | Condition             |
| ------ | --------------------- |
| Unread | `read_at` is null     |
| Read   | `read_at` is not null |

---

## 17. Admin User Management Flow

### Goal

Allow admin to manage customer accounts.

### Flow

```text
Admin views users
→ Admin locks customer account
→ User tokens are deleted
→ Locked user cannot login or call authenticated APIs
→ Admin unlocks customer account
→ Customer can login again
```

### Main Rules

* Admin cannot lock their own account.
* Admin account cannot be locked through this API.
* Locked users cannot login.
* Locked users cannot use existing tokens.
* Active middleware protects authenticated APIs.

### Status Changes

| Entity | Before              | After               |
| ------ | ------------------- | ------------------- |
| User   | `is_active = true`  | `is_active = false` |
| User   | `is_active = false` | `is_active = true`  |

---

## 18. Public Sales Guard

### Goal

Prevent customers from buying tickets for invalid trips.

### Rules

Public trip APIs only expose trips that satisfy:

```text
status = scheduled
departure_time > now()
```

Booking and payment APIs also check:

* Booking is pending payment
* Booking is not expired
* Trip is scheduled
* Trip has not departed
* Trip is still open for sale

### Protected Cases

| Case              | Result                                  |
| ----------------- | --------------------------------------- |
| Trip departed     | Cannot view seats, book, pay, or refund |
| Trip completed    | Cannot view seats, book, pay, or refund |
| Trip cancelled    | Cannot view seats, book, pay, or refund |
| Booking expired   | Cannot pay                              |
| Ticket checked in | Cannot refund                           |

---

## 19. Core Status Summary

### Booking

```text
pending_payment
confirmed
cancelled
expired
refund_requested
refunded
```

### Trip

```text
scheduled
departed
completed
cancelled
```

### Trip Seat

```text
available
reserved
booked
blocked
```

### Payment

```text
pending
success
failed
refunded
```

---

## 20. Why These Flows Matter

This project is not only a CRUD system. It includes real business constraints such as:

* Preventing duplicate seat booking
* Holding seats temporarily
* Expiring unpaid bookings
* Confirming payment safely
* Preventing payment after trip departure
* Handling refund approval workflow
* Preventing refund after passenger check-in
* Managing trip operation lifecycle
* Verifying tickets by booking code
* Blocking inactive users from APIs

These workflows make the project closer to a real-world backend system.
