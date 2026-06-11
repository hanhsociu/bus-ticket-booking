# Project Structure

This document explains the main structure of the Bus Ticket Booking API project.

The project follows a Laravel API structure with separate controllers for public APIs, customer APIs, admin APIs, service classes for business logic, and models for core entities.

---

## 1. Main Application Layers

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── RouteController.php
│   │       ├── TripController.php
│   │       ├── BookingController.php
│   │       ├── PayOSPaymentController.php
│   │       ├── Admin/
│   │       └── Customer/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Services/
└── Mail/
```

---

## 2. Public API Controllers

### `AuthController`

Handles:

* Customer registration
* Login
* Logout
* Current authenticated user info
* Blocks inactive users from login

### `RouteController`

Handles:

* Public route listing

### `TripController`

Handles:

* Public trip listing
* Public trip detail
* Public trip seat listing

Important rules:

* Only `scheduled` trips are visible
* Trip departure time must be greater than current time
* Departed, completed, cancelled, or expired trips are hidden from public sales APIs

---

## 3. Customer API Controllers

### `BookingController`

Handles:

* Create booking
* Hold seats for 10 minutes
* View booking detail
* View my bookings
* Cancel pending booking
* Request refund

Important rules:

* Customer can only access their own bookings
* Booking requires active authenticated user
* Seats are locked inside a transaction
* `lockForUpdate` prevents duplicate seat booking
* Refund is blocked after check-in

### `CustomerDashboardController`

Handles:

* Customer overview dashboard
* Booking summary
* Upcoming bookings
* Pending payment bookings
* Recent notifications

### `CustomerNotificationController`

Handles:

* List customer notifications
* Count unread notifications
* Mark one notification as read
* Mark all notifications as read

Read/unread logic:

```text
Unread = read_at is null
Read   = read_at is not null
```

---

## 4. Admin API Controllers

### `AdminDashboardController`

Handles:

* Admin system overview
* Booking statistics
* Revenue statistics
* Trip statistics
* Seat statistics
* Upcoming trips
* Recent bookings

### `AdminRouteController`

Handles:

* Create route
* Update route
* Delete route
* View route list/detail

### `AdminBusTypeController`

Handles:

* Manage bus types
* Generate seats for bus type

### `AdminBusController`

Handles:

* Manage buses
* Link bus with bus type
* Lock/delete bus if allowed

### `AdminTripController`

Handles:

* Create trip
* View admin trip list
* View trip detail
* Cancel trip

Important rules:

* Bus cannot have overlapping `scheduled` or `departed` trips
* Trip seats are generated when a trip is created
* Trip with confirmed bookings cannot be cancelled directly

### `AdminBookingController`

Handles:

* View all bookings
* Filter bookings
* View booking detail
* Cancel pending payment booking

### `AdminRefundController`

Handles:

* View refund requests
* Approve refund
* Reject refund

Important rules:

* Only `refund_requested` booking can be approved/rejected
* Approved refund changes payment to `refunded`
* Approved refund releases booked seats when applicable
* Customer receives notification after approval/rejection

### `AdminTripOperationController`

Handles:

* Depart trip
* Complete trip
* View passenger list

Important rules:

* Only `scheduled` trip can depart
* Only `departed` trip can complete
* Pending bookings expire when trip departs
* Unsold seats are blocked when trip departs
* Booked seats remain booked

### `AdminPassengerCheckInController`

Handles:

* Check in full booking
* Check in one booking item/seat
* Undo check-in

Important rules:

* Booking must be `confirmed`
* Trip must be `scheduled` or `departed`
* Checked-in ticket cannot request refund

### `AdminTicketVerificationController`

Handles:

* Verify ticket by booking code
* Check in ticket by booking code

Useful for future QR code scanning flow.

### `AdminUserController`

Handles:

* List users
* View user detail
* Lock user
* Unlock user

Important rules:

* Admin cannot lock their own account
* Admin account cannot be locked through this API
* Locked user tokens are deleted
* Locked users cannot login or call authenticated APIs

---

## 5. Services

### `BookingPaymentService`

Responsible for payment business logic.

Handles:

* Confirm payment
* Mark payment as failed
* Update booking status after payment success
* Update trip seat status after payment success
* Create booking history
* Trigger notification/email after successful payment

Important rules:

* Payment confirmation is idempotent
* Booking must be `pending_payment`
* Paid amount must match payment amount
* Reserved seats become booked after successful payment

### `BookingNotificationService`

Responsible for booking-related notifications.

Handles:

* Send booking confirmation email
* Create booking confirmed notification
* Create refund approved notification
* Create refund rejected notification

### `TripSeatGenerationService`

Responsible for generating trip seats when admin creates a trip.

Handles:

* Read bus type seats
* Create trip seat records for a specific trip

---

## 6. Middleware

### `auth:sanctum`

Ensures the request has a valid authenticated token.

### `active`

Implemented by:

```text
app/Http/Middleware/EnsureUserIsActive.php
```

Ensures authenticated user is still active.

If user is locked:

* Current token is deleted when possible
* API returns 403

### `admin`

Implemented by:

```text
app/Http/Middleware/EnsureUserIsAdmin.php
```

Ensures authenticated user has admin role.

---

## 7. Models

### `User`

Represents admin and customer accounts.

Important fields:

* `name`
* `email`
* `phone`
* `password`
* `role`
* `is_active`

Relationships:

* has many bookings
* has many notifications

### `Route`

Represents bus route.

Example:

```text
Hà Nội → Hải Phòng
```

### `BusType`

Represents type of bus.

Example:

```text
Xe giường nằm 40 chỗ
```

### `Bus`

Represents actual bus.

Important fields:

* bus type
* name
* license plate
* active status

### `Seat`

Represents seat template of a bus type.

Example:

```text
A01, A02, B01, B02
```

### `Trip`

Represents a scheduled bus trip.

Important statuses:

```text
scheduled
departed
completed
cancelled
```

Relationships:

* belongs to route
* belongs to bus
* has many trip seats
* has many bookings

### `TripSeat`

Represents seat state in a specific trip.

Important statuses:

```text
available
reserved
booked
blocked
```

### `Booking`

Represents customer booking.

Important statuses:

```text
pending_payment
confirmed
cancelled
expired
refund_requested
refunded
```

Relationships:

* belongs to user
* belongs to trip
* has many booking items
* has many payments
* has many histories

### `BookingItem`

Represents each booked seat in a booking.

Important fields:

* `seat_number`
* `price`
* `checked_in_at`
* `checked_in_by`

### `Payment`

Represents payment record.

Important statuses:

```text
pending
success
failed
refunded
```

### `BookingHistory`

Stores booking status changes and important actions.

Example actions:

```text
booking_created
payment_success_fake
refund_requested
refund_approved
refund_rejected
booking_checked_in
ticket_checked_in_by_code
```

### `Notification`

Stores customer notifications.

Important fields:

* `type`
* `title`
* `message`
* `status`
* `sent_at`
* `read_at`
* `metadata`

---

## 8. Routes

Main API route file:

```text
routes/api.php
```

Route groups:

* Public auth
* Public trip browsing
* Authenticated customer APIs
* PayOS callbacks
* Admin APIs

Middleware groups:

```text
Customer: auth:sanctum + active
Admin:    auth:sanctum + active + admin
```

---

## 9. Commands and Scheduler

### Expire Pending Bookings

Command:

```bash
php artisan bookings:expire-pending
```

Purpose:

* Find expired `pending_payment` bookings
* Change booking status to `expired`
* Release reserved seats

Scheduler:

```text
Runs every minute when scheduler is active.
```

---

## 10. Documentation Files

```text
README.md
docs/API_OVERVIEW.md
docs/BUSINESS_FLOWS.md
docs/POSTMAN_TESTING_GUIDE.md
docs/PROJECT_STRUCTURE.md
```

### `README.md`

Main project introduction for GitHub.

### `API_OVERVIEW.md`

Detailed API grouping and endpoint summary.

### `BUSINESS_FLOWS.md`

Explains main business workflows and status transitions.

### `POSTMAN_TESTING_GUIDE.md`

Explains how to test the main flows using Postman.

### `PROJECT_STRUCTURE.md`

Explains code organization and responsibility of main files.
