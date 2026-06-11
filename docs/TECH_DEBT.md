# Technical Debt & Refactor Proposals

Tài liệu ghi các đề xuất cải thiện **lớn hơn** — không nên làm trong một PR cleanup nhỏ vì có thể ảnh hưởng business logic hoặc cần migration DB.

---

## 1. Notification schema vs application code (ưu tiên cao)

**Vấn đề:** Migration `create_notifications_table` định nghĩa:

- `type` ENUM: `email`, `sms`, `system`
- `status` ENUM: `pending`, `sent`, `failed`

Code ứng dụng dùng:

- `type`: `booking_confirmed`, `refund_approved`, `refund_rejected`, `booking_confirmed_email_failed`, ...
- `status`: `read` (khi mark-as-read)

Trên MySQL strict, insert/update có thể fail. SQLite dev thường không enforce ENUM nên Postman có thể vẫn pass.

**Đề xuất:**

1. Migration đổi `type` và `status` sang `string` (hoặc mở rộng ENUM đầy đủ).
2. Chuẩn hóa: `read_at` cho trạng thái đọc; `status` chỉ giữ `pending` / `sent` / `failed`.
3. Cập nhật `CustomerNotificationController` nếu bỏ `status = 'read'`.

**Rủi ro:** Cần test lại toàn bộ notification flow sau migration.

---

## 2. BookingSeatService — gom logic nhả ghế / hủy booking

**Vấn đề:** Cùng một block “release reserved/booked seats + update booking + ghi history” lặp ở:

- `BookingController::cancel`
- `AdminBookingController::cancel`
- `ExpirePendingBookingsCommand`
- `AdminTripController::cancel`
- `AdminTripOperationController::depart`
- `AdminRefundController::approve`

**Đề xuất:** `BookingSeatService` với các method rõ ràng:

- `releaseReservedSeats(Booking $booking): void`
- `expirePendingBooking(Booking $booking): void`
- `cancelBooking(Booking $booking, string $action, string $note, ...): void`

**Lợi ích:** Junior đọc một chỗ; giảm lỗi khi sửa một nhánh mà quên nhánh khác.

---

## 3. Unify check-in validation

**Vấn đề:** `AdminPassengerCheckInController::ensureBookingCanCheckIn` và `AdminTicketVerificationController` có logic overlap (confirmed, trip status, payment).

**Đề xuất:** `TicketCheckInService` hoặc trait dùng chung.

---

## 4. Laravel Policies thay ownership check thủ công

**Vấn đề:** Nhiều controller tự check `user_id`, role admin.

**Đề xuất:** `BookingPolicy`, `NotificationPolicy` + `$this->authorize()`.

---

## 5. PHP Enums cho status strings

**Vấn đề:** Magic strings: `pending_payment`, `confirmed`, `reserved`, `booked`, `scheduled`, `departed`, ...

**Đề xuất:** Backed enums (`BookingStatus`, `TripStatus`, `TripSeatStatus`, `PaymentStatus`) + dần thay thế trong code.

---

## 6. API Resources & response envelope

**Vấn đề:** `{ success, message, data }` và eager-load arrays lặp ~15 lần (booking detail shape).

**Đề xuất:** `BookingResource`, `TripResource`, trait `RespondsWithJson` (optional).

---

## 7. Centralized exception → HTTP status mapping

**Vấn đề:** Nhiều controller `catch (\Throwable)` → 422; `BookingController` có `getExceptionStatusCode`, các controller khác không.

**Đề xuất:** Handler trong `bootstrap/app.php` hoặc custom exceptions.

---

## 8. PayOSPaymentController tách gateway

**Vấn đề:** Controller ~380 dòng, trộn PayOS I/O, validation, response.

**Đề xuất:** `PayOSGateway` class; controller mỏng.

---

## 9. Admin dashboard query optimization

**Vấn đề:** `AdminDashboardController` chạy 15+ aggregate queries mỗi request.

**Đề xuất:** Single query với conditional aggregates, cache TTL ngắn, hoặc read model.

---

## 10. API rate limiting

**Vấn đề:** Không throttle login, booking, payment.

**Đề xuất:** `Route::middleware('throttle:...')` trên auth và payment routes.

---

## 11. Test coverage

**Vấn đề:** Chỉ có `ExampleTest` stub.

**Ưu tiên test:**

- `BookingController::store` (seat locking)
- `BookingPaymentService` (idempotency)
- `ExpirePendingBookingsCommand`
- Refund approve/reject
- Notification mark-all-as-read route
- Check-in flows

---

## 12. Model factories

**Vấn đề:** Chỉ có `UserFactory`.

**Đề xuất:** Factories cho `Trip`, `Booking`, `TripSeat`, `Payment` để hỗ trợ feature tests.

---

## 13. ExpirePendingBookingsCommand robustness

**Vấn đề:** Load tất cả expired bookings một lần, không chunk; race nhỏ với booking mới (đã có `lockForUpdate` trong loop).

**Đề xuất:** `chunkById()` + optional index trên `(status, expired_at)`.

---

## 14. `fake-success` endpoint

**Hiện trạng:** Đã thêm guard `local` / `testing` trong cleanup PR.

**Đề xuất dài hạn:** Xóa route khỏi production build hoặc feature flag rõ ràng trong config.
