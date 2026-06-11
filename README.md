# Bus Ticket Booking API

Backend API for a bus ticket booking system built with Laravel.
The project focuses on real-world booking workflows such as seat locking, payment confirmation, refund handling, passenger check-in, ticket verification, admin operations, and role-based access control.

## Overview

This project simulates a practical bus ticket booking backend system with two main roles:

* **Customer**: search trips, select seats, create bookings, pay with PayOS, request refunds, view notifications.
* **Admin**: manage routes, buses, trips, bookings, refunds, passengers, users, and trip operations.

The system is designed to demonstrate backend business logic, transactional consistency, and API-based system design rather than simple CRUD only.

## Tech Stack

* **Backend**: Laravel
* **Database**: MySQL
* **Authentication**: Laravel Sanctum
* **Payment Gateway**: PayOS
* **Queue/Cache Ready**: Redis-ready configuration
* **Mail**: Laravel Mail / Mailpit / SMTP
* **API Testing**: Postman
* **Local Environment**: Laragon / Docker-ready direction

## Main Features

### Authentication & Authorization

* Customer registration and login
* Sanctum token authentication
* Admin/customer role separation
* Active user middleware
* Locked users cannot login or use authenticated APIs

### Public Trip Searching

* View active routes
* View trips that are still open for sale
* View available seats of an open trip
* Hide departed, completed, cancelled, or expired trips from public sales APIs

### Booking Workflow

* Customer creates booking by selecting seats
* Seats are locked using database transaction and `lockForUpdate`
* Booking is held for 10 minutes
* Expired bookings release reserved seats
* Customer can cancel pending payment bookings
* Customer can view booking history and current booking status

### Payment Workflow

* PayOS payment link creation
* Prevent payment for invalid, expired, cancelled, or departed trips
* PayOS return URL handling
* PayOS webhook handling
* Fake payment success API for local development
* Idempotent payment confirmation
* Booking becomes confirmed after successful payment
* Reserved seats become booked after successful payment

### Refund Workflow

* Customer can request refund for confirmed bookings
* Refund is blocked if the trip has departed or the ticket has been checked in
* Admin can approve or reject refund requests
* Approved refund changes payment status to refunded
* Refunded seats become available again when applicable
* Customer receives notification after refund approval or rejection

### Trip Operation

* Admin can start a trip
* Admin can complete a trip
* Pending bookings expire when the trip departs
* Remaining unsold seats are blocked when the trip departs
* Admin can view passenger list of a trip

### Passenger Check-in

* Admin can check in a full booking
* Admin can check in a single seat
* Admin can undo check-in if needed
* Check-in stores `checked_in_at` and `checked_in_by`
* Checked-in tickets cannot request refund

### Ticket Verification

* Admin can verify ticket by booking code
* Admin can check in ticket by booking code
* Suitable for future QR code scanning flow

### Notification System

* Customer can view notifications
* Customer can see unread count
* Customer can mark one notification as read
* Customer can mark all notifications as read
* Notifications are created for booking confirmation and refund processing

### Admin Management

* Admin dashboard overview
* Manage routes
* Manage bus types
* Generate seats for bus types
* Manage buses
* Manage trips
* Manage bookings
* Manage refunds
* Manage users
* Lock/unlock customer accounts

## Business Flows

### Booking Flow

```text
Customer selects trip
→ Customer selects seats
→ System locks seats for 10 minutes
→ Booking status: pending_payment
→ Customer pays
→ Booking status: confirmed
→ Trip seats status: booked
```

### Payment Flow

```text
Create booking
→ Create PayOS payment link
→ PayOS return/webhook
→ Confirm payment
→ Confirm booking
→ Send notification/email
```

### Refund Flow

```text
Customer requests refund
→ Booking status: refund_requested
→ Admin approves or rejects
→ If approved: booking refunded, payment refunded, seats released
→ If rejected: booking returns to confirmed
→ Customer receives notification
```

### Trip Operation Flow

```text
Trip scheduled
→ Admin starts trip
→ Trip departed
→ Pending bookings expired
→ Unsold seats blocked
→ Admin completes trip
→ Trip completed
```

### Check-in Flow

```text
Admin views passenger list
→ Admin checks ticket
→ Admin checks in seat or booking
→ checked_in_at and checked_in_by are stored
→ Checked-in ticket cannot be refunded
```

## Important Statuses

### Booking Status

| Status             | Description                        |
| ------------------ | ---------------------------------- |
| `pending_payment`  | Booking is waiting for payment     |
| `confirmed`        | Booking has been paid successfully |
| `cancelled`        | Booking has been cancelled         |
| `expired`          | Booking payment time has expired   |
| `refund_requested` | Customer requested a refund        |
| `refunded`         | Booking has been refunded          |

### Trip Status

| Status      | Description                       |
| ----------- | --------------------------------- |
| `scheduled` | Trip is scheduled and can be sold |
| `departed`  | Trip has departed                 |
| `completed` | Trip has completed                |
| `cancelled` | Trip has been cancelled           |

### Trip Seat Status

| Status      | Description                        |
| ----------- | ---------------------------------- |
| `available` | Seat is available                  |
| `reserved`  | Seat is temporarily locked         |
| `booked`    | Seat has been booked               |
| `blocked`   | Seat is blocked and cannot be sold |

### Payment Status

| Status     | Description          |
| ---------- | -------------------- |
| `pending`  | Payment is waiting   |
| `success`  | Payment succeeded    |
| `failed`   | Payment failed       |
| `refunded` | Payment was refunded |

## API Documentation

Detailed API overview is available here:

```text
docs/API_OVERVIEW.md
```

Main API groups:

* Public Auth APIs
* Public Route & Trip APIs
* Customer Booking APIs
* Customer Notification APIs
* PayOS Callback APIs
* Admin Master Data APIs
* Admin Booking APIs
* Admin Refund APIs
* Admin Trip Operation APIs
* Admin Passenger Check-in APIs
* Admin Ticket Verification APIs
* Admin User Management APIs

## Local Setup

### 1. Clone project

```bash
git clone <repository-url>
cd bus-ticket-booking
```

### 2. Install dependencies

```bash
composer install
```

### 3. Create environment file

```bash
cp .env.example .env
```

Update database and service configuration in `.env`.

Example:

```env
APP_NAME="Bus Ticket Booking"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=bus_booking
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```

### 4. Generate app key

```bash
php artisan key:generate
```

### 5. Run migrations and seeders

```bash
php artisan migrate --seed
```

### 6. Start local server

```bash
php artisan serve
```

API base URL:

```text
http://127.0.0.1:8000/api
```

## Default Accounts

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

## Useful Commands

### Clear cache

```bash
php artisan optimize:clear
```

### Run scheduler locally

```bash
php artisan schedule:work
```

### Expire pending bookings manually

```bash
php artisan bookings:expire-pending
```

### List API routes

```bash
php artisan route:list --path=api
```

## Development Notes

This project includes several backend practices that are important in real booking systems:

* Database transaction for booking and payment processing
* `lockForUpdate` to prevent duplicate seat booking
* Booking expiration logic
* Payment idempotency
* Admin approval workflow for refund
* Middleware-based access control
* Check-in state per booking item
* Customer notification read/unread state
* Public API guards for trips that are no longer open for sale

## Future Improvements

Potential improvements for the next phase:

* API Resource classes for cleaner response formatting
* Queue email and notification jobs
* Feature tests for booking, payment, refund, and check-in flows
* Docker full environment with app, MySQL, Redis, Mailpit, queue worker, and scheduler
* Postman collection export
* QR code generation for ticket verification
* Staff/operator role separate from admin
* CI pipeline with GitHub Actions
* Better audit logs for admin actions

## Project Goal

The goal of this project is to build a realistic backend portfolio project that goes beyond basic CRUD by handling actual business workflows such as seat locking, payment confirmation, refund approval, passenger check-in, ticket verification, and admin operation.
