# Postman Testing Guide

This document describes how to test the main API flows of the Bus Ticket Booking system using Postman.

## 1. Base URL

```text
http://127.0.0.1:8000/api
```

## 2. Default Accounts

### Admin

```text
Email: admin@bus.local
Password: 12345678
```

### Customer

```text
Email: customer@bus.local
Password: 12345678
```

## 3. Authentication

### Login Admin

```http
POST /auth/login
```

Body:

```json
{
  "email": "admin@bus.local",
  "password": "12345678"
}
```

Save the returned token as:

```text
admin_token
```

### Login Customer

```http
POST /auth/login
```

Body:

```json
{
  "email": "customer@bus.local",
  "password": "12345678"
}
```

Save the returned token as:

```text
customer_token
```

For authenticated APIs, use:

```text
Authorization: Bearer <token>
```

---

## 4. Public Trip Flow

### Get Routes

```http
GET /routes
```

Expected:

```text
Return active routes.
```

### Get Open Trips

```http
GET /trips
```

Expected:

```text
Only scheduled trips with departure_time > now are returned.
```

### Get Trip Detail

```http
GET /trips/{trip_id}
```

Expected:

```text
Return trip detail only if the trip is still open for sale.
```

### Get Trip Seats

```http
GET /trips/{trip_id}/seats
```

Expected:

```text
Return seats only if the trip is still open for sale.
```

---

## 5. Booking Flow

Use customer token.

### Create Booking

```http
POST /bookings
```

Body:

```json
{
  "trip_id": 1,
  "trip_seat_ids": [1, 2]
}
```

Expected:

```text
Booking status: pending_payment
Selected seats status: reserved
Booking expired_at: now + 10 minutes
```

### View My Bookings

```http
GET /my/bookings
```

Expected:

```text
Return current customer's bookings only.
```

### Cancel Pending Booking

```http
POST /bookings/{booking_id}/cancel
```

Expected:

```text
Only pending_payment booking can be cancelled.
Reserved seats become available again.
```

---

## 6. Payment Flow

Use customer token.

### Create PayOS Payment Link

```http
POST /payments/payos/create
```

Body:

```json
{
  "booking_id": 1
}
```

Expected:

```text
Return checkout_url and qr_code if booking is valid.
```

Rules:

```text
Booking must be pending_payment.
Booking must not be expired.
Trip must be scheduled.
Trip departure_time must be greater than now.
```

### Fake Payment Success

Use admin token.

```http
POST /admin/payments/{payment_id}/fake-success
```

Expected:

```text
Payment status: success
Booking status: confirmed
Trip seats status: booked
Notification/email record is created
```

---

## 7. Refund Flow

Use customer token.

### Request Refund

```http
POST /bookings/{booking_id}/request-refund
```

Body:

```json
{
  "reason": "Tôi có việc bận nên muốn hoàn vé."
}
```

Expected:

```text
Booking status: refund_requested
```

Rules:

```text
Booking must be confirmed.
Trip must still be scheduled.
Trip must not have departed.
Ticket must not be checked in.
```

### Admin Approves Refund

Use admin token.

```http
POST /admin/bookings/{booking_id}/approve-refund
```

Body:

```json
{
  "note": "Đã duyệt hoàn tiền thủ công cho khách."
}
```

Expected:

```text
Booking status: refunded
Payment status: refunded
Booked seats become available again
Customer receives refund_approved notification
```

### Admin Rejects Refund

Use admin token.

```http
POST /admin/bookings/{booking_id}/reject-refund
```

Body:

```json
{
  "reason": "Vé không đủ điều kiện hoàn theo chính sách."
}
```

Expected:

```text
Booking status returns to confirmed
Customer receives refund_rejected notification
```

---

## 8. Trip Operation Flow

Use admin token.

### View Passengers

```http
GET /admin/trips/{trip_id}/passengers
```

Expected:

```text
Return confirmed bookings as passengers.
Return seat summary and check-in status.
```

### Depart Trip

```http
POST /admin/trips/{trip_id}/depart
```

Body:

```json
{
  "note": "Xe bắt đầu xuất bến."
}
```

Expected:

```text
Trip status: departed
Pending bookings become expired
Available/reserved seats become blocked
Booked seats remain booked
```

### Complete Trip

```http
POST /admin/trips/{trip_id}/complete
```

Body:

```json
{
  "note": "Chuyến xe đã hoàn thành."
}
```

Expected:

```text
Trip status: completed
```

---

## 9. Passenger Check-in Flow

Use admin token.

### Check-in Full Booking

```http
POST /admin/bookings/{booking_id}/check-in
```

Body:

```json
{
  "note": "Khách đã lên xe."
}
```

Expected:

```text
All booking items have checked_in_at and checked_in_by.
```

### Check-in One Seat

```http
POST /admin/booking-items/{booking_item_id}/check-in
```

Body:

```json
{
  "note": "Khách ghế A09 đã lên xe."
}
```

Expected:

```text
Only selected booking item is checked in.
```

### Undo Check-in

```http
POST /admin/booking-items/{booking_item_id}/undo-check-in
```

Body:

```json
{
  "reason": "Admin bấm nhầm check-in."
}
```

Expected:

```text
checked_in_at and checked_in_by are cleared.
```

---

## 10. Ticket Verification Flow

Use admin token.

### Verify Ticket by Booking Code

```http
GET /admin/tickets/verify?code=BK-YYYYMMDD-XXXXXX
```

Expected:

```text
Return ticket detail, customer info, seats, payment and verification result.
```

### Check-in by Booking Code

```http
POST /admin/tickets/check-in
```

Body:

```json
{
  "code": "BK-YYYYMMDD-XXXXXX",
  "note": "Khách đã lên xe bằng mã vé."
}
```

Expected:

```text
All seats in the booking are checked in.
If already fully checked in, can_check_in is false.
```

---

## 11. Customer Notification Flow

Use customer token.

### List Notifications

```http
GET /customer/notifications
```

Expected:

```text
Return current customer's notifications.
```

### Get Unread Count

```http
GET /customer/notifications/unread-count
```

Expected:

```text
Return unread_count.
```

### Mark One As Read

```http
POST /customer/notifications/{notification_id}/mark-as-read
```

Expected:

```text
read_at is filled.
```

### Mark All As Read

```http
POST /customer/notifications/mark-all-as-read
```

Expected:

```text
All unread notifications become read.
```

---

## 12. Admin User Management Flow

Use admin token.

### List Users

```http
GET /admin/users
```

### Show User Detail

```http
GET /admin/users/{user_id}
```

### Lock User

```http
POST /admin/users/{user_id}/lock
```

Body:

```json
{
  "reason": "Tài khoản có hoạt động bất thường."
}
```

Expected:

```text
User is_active becomes false.
User tokens are deleted.
User cannot login or call authenticated APIs.
```

### Unlock User

```http
POST /admin/users/{user_id}/unlock
```

Body:

```json
{
  "note": "Đã xác minh tài khoản hợp lệ."
}
```

Expected:

```text
User is_active becomes true.
User can login again.
```

---

## 13. Important Negative Test Cases

### Booking with unavailable seat

Expected:

```text
API returns error.
Seat cannot be booked twice.
```

### Create payment for expired booking

Expected:

```text
API rejects payment creation.
```

### Request refund after check-in

Expected:

```text
API returns: Vé đã được check-in, không thể yêu cầu hoàn vé.
```

### View seats of departed trip

Expected:

```text
API rejects public seat viewing.
```

### Inactive user calls API

Expected:

```text
API returns 403 or unauthenticated if token was deleted.
```

---

## 14. Useful Commands During Testing

### Clear Cache

```bash
php artisan optimize:clear
```

### Run Scheduler

```bash
php artisan schedule:work
```

### Expire Pending Bookings Manually

```bash
php artisan bookings:expire-pending
```

### List API Routes

```bash
php artisan route:list --path=api
```
