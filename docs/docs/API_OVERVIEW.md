# Bus Ticket Booking API Overview

## 1. Tổng quan

Đây là backend API cho hệ thống đặt vé xe khách, xây dựng bằng Laravel. Hệ thống hỗ trợ khách hàng tìm chuyến, chọn ghế, giữ ghế, thanh toán, nhận vé, yêu cầu hoàn vé và check-in lên xe. Admin có thể quản lý tuyến xe, loại xe, xe, chuyến xe, booking, refund, người dùng, dashboard và vận hành chuyến.

## 2. Nhóm quyền

### Public

Không cần đăng nhập.

* Xem tuyến xe
* Xem chuyến đang mở bán
* Xem ghế của chuyến đang mở bán
* Đăng ký
* Đăng nhập

### Customer

Cần đăng nhập và tài khoản đang hoạt động.

* Xem thông tin tài khoản
* Xem dashboard cá nhân
* Đặt vé
* Xem booking của mình
* Hủy booking chờ thanh toán
* Yêu cầu hoàn vé
* Tạo thanh toán PayOS
* Xem thông báo
* Đánh dấu thông báo đã đọc

### Admin

Cần đăng nhập, tài khoản đang hoạt động và có role admin.

* Quản lý tuyến xe
* Quản lý loại xe
* Quản lý xe
* Quản lý chuyến
* Hủy chuyến
* Xem booking
* Hủy booking pending payment
* Duyệt/từ chối hoàn vé
* Xem dashboard hệ thống
* Cho chuyến khởi hành
* Hoàn thành chuyến
* Xem danh sách hành khách
* Check-in hành khách
* Soát vé bằng mã booking
* Quản lý người dùng
* Khóa/mở khóa tài khoản
* Fake payment success cho môi trường dev

---

## 3. Public API

### Auth

| Method | Endpoint             | Mô tả                      |
| ------ | -------------------- | -------------------------- |
| POST   | `/api/auth/register` | Đăng ký tài khoản customer |
| POST   | `/api/auth/login`    | Đăng nhập và nhận token    |

### Routes & Trips

| Method | Endpoint                  | Mô tả                                    |
| ------ | ------------------------- | ---------------------------------------- |
| GET    | `/api/routes`             | Lấy danh sách tuyến xe                   |
| GET    | `/api/trips`              | Lấy danh sách chuyến đang mở bán         |
| GET    | `/api/trips/{trip}`       | Xem chi tiết chuyến đang mở bán          |
| GET    | `/api/trips/{trip}/seats` | Xem danh sách ghế của chuyến đang mở bán |

---

## 4. Customer API

### Account

| Method | Endpoint           | Mô tả                   |
| ------ | ------------------ | ----------------------- |
| GET    | `/api/auth/me`     | Lấy thông tin tài khoản |
| POST   | `/api/auth/logout` | Đăng xuất               |

### Dashboard

| Method | Endpoint                           | Mô tả                        |
| ------ | ---------------------------------- | ---------------------------- |
| GET    | `/api/customer/dashboard/overview` | Tổng quan dashboard customer |

### Booking

| Method | Endpoint                                 | Mô tả                           |
| ------ | ---------------------------------------- | ------------------------------- |
| POST   | `/api/bookings`                          | Tạo booking và giữ ghế 10 phút  |
| GET    | `/api/bookings/{booking}`                | Xem chi tiết booking của mình   |
| GET    | `/api/my/bookings`                       | Xem danh sách booking của mình  |
| POST   | `/api/bookings/{booking}/cancel`         | Hủy booking đang chờ thanh toán |
| POST   | `/api/bookings/{booking}/request-refund` | Gửi yêu cầu hoàn vé             |

### Payment

| Method | Endpoint                     | Mô tả                     |
| ------ | ---------------------------- | ------------------------- |
| POST   | `/api/payments/payos/create` | Tạo link thanh toán PayOS |

### Notification

| Method | Endpoint                                                  | Mô tả                         |
| ------ | --------------------------------------------------------- | ----------------------------- |
| GET    | `/api/customer/notifications`                             | Xem danh sách thông báo       |
| GET    | `/api/customer/notifications/unread-count`                | Đếm thông báo chưa đọc        |
| POST   | `/api/customer/notifications/mark-all-as-read`            | Đánh dấu tất cả đã đọc        |
| POST   | `/api/customer/notifications/{notification}/mark-as-read` | Đánh dấu một thông báo đã đọc |

---

## 5. PayOS Callback API

Các endpoint này public vì PayOS redirect/webhook về hệ thống.

| Method | Endpoint                      | Mô tả                                   |
| ------ | ----------------------------- | --------------------------------------- |
| GET    | `/api/payments/payos/return`  | PayOS redirect sau thanh toán           |
| GET    | `/api/payments/payos/cancel`  | PayOS redirect khi khách hủy thanh toán |
| POST   | `/api/payments/payos/webhook` | Webhook thanh toán từ PayOS             |

---

## 6. Admin API

### Dashboard

| Method | Endpoint                        | Mô tả                     |
| ------ | ------------------------------- | ------------------------- |
| GET    | `/api/admin/dashboard/overview` | Tổng quan dashboard admin |

### Route Management

| Method    | Endpoint                    | Mô tả                |
| --------- | --------------------------- | -------------------- |
| GET       | `/api/admin/routes`         | Danh sách tuyến      |
| POST      | `/api/admin/routes`         | Tạo tuyến            |
| GET       | `/api/admin/routes/{route}` | Chi tiết tuyến       |
| PUT/PATCH | `/api/admin/routes/{route}` | Cập nhật tuyến       |
| DELETE    | `/api/admin/routes/{route}` | Xóa tuyến nếu hợp lệ |

### Bus Type Management

| Method    | Endpoint                                        | Mô tả                  |
| --------- | ----------------------------------------------- | ---------------------- |
| GET       | `/api/admin/bus-types`                          | Danh sách loại xe      |
| POST      | `/api/admin/bus-types`                          | Tạo loại xe            |
| GET       | `/api/admin/bus-types/{busType}`                | Chi tiết loại xe       |
| PUT/PATCH | `/api/admin/bus-types/{busType}`                | Cập nhật loại xe       |
| DELETE    | `/api/admin/bus-types/{busType}`                | Xóa loại xe nếu hợp lệ |
| POST      | `/api/admin/bus-types/{busType}/generate-seats` | Sinh ghế cho loại xe   |

### Bus Management

| Method    | Endpoint                 | Mô tả             |
| --------- | ------------------------ | ----------------- |
| GET       | `/api/admin/buses`       | Danh sách xe      |
| POST      | `/api/admin/buses`       | Tạo xe            |
| GET       | `/api/admin/buses/{bus}` | Chi tiết xe       |
| PUT/PATCH | `/api/admin/buses/{bus}` | Cập nhật xe       |
| DELETE    | `/api/admin/buses/{bus}` | Xóa xe nếu hợp lệ |

### Trip Management

| Method | Endpoint                         | Mô tả            |
| ------ | -------------------------------- | ---------------- |
| GET    | `/api/admin/trips`               | Danh sách chuyến |
| POST   | `/api/admin/trips`               | Tạo chuyến       |
| GET    | `/api/admin/trips/{trip}`        | Chi tiết chuyến  |
| POST   | `/api/admin/trips/{trip}/cancel` | Hủy chuyến       |

### Trip Operation

| Method | Endpoint                             | Mô tả                    |
| ------ | ------------------------------------ | ------------------------ |
| POST   | `/api/admin/trips/{trip}/depart`     | Cho chuyến khởi hành     |
| POST   | `/api/admin/trips/{trip}/complete`   | Hoàn thành chuyến        |
| GET    | `/api/admin/trips/{trip}/passengers` | Xem danh sách hành khách |

### Booking Management

| Method | Endpoint                               | Mô tả                       |
| ------ | -------------------------------------- | --------------------------- |
| GET    | `/api/admin/bookings`                  | Danh sách booking           |
| GET    | `/api/admin/bookings/{booking}`        | Chi tiết booking            |
| POST   | `/api/admin/bookings/{booking}/cancel` | Hủy booking pending payment |

### Refund Management

| Method | Endpoint                                       | Mô tả                     |
| ------ | ---------------------------------------------- | ------------------------- |
| GET    | `/api/admin/refunds`                           | Danh sách yêu cầu hoàn vé |
| POST   | `/api/admin/bookings/{booking}/approve-refund` | Duyệt hoàn vé             |
| POST   | `/api/admin/bookings/{booking}/reject-refund`  | Từ chối hoàn vé           |

### Passenger Check-in

| Method | Endpoint                                               | Mô tả                    |
| ------ | ------------------------------------------------------ | ------------------------ |
| POST   | `/api/admin/bookings/{booking}/check-in`               | Check-in toàn bộ booking |
| POST   | `/api/admin/booking-items/{bookingItem}/check-in`      | Check-in từng ghế        |
| POST   | `/api/admin/booking-items/{bookingItem}/undo-check-in` | Hoàn tác check-in ghế    |

### Ticket Verification

| Method | Endpoint                      | Mô tả                         |
| ------ | ----------------------------- | ----------------------------- |
| GET    | `/api/admin/tickets/verify`   | Kiểm tra vé bằng booking code |
| POST   | `/api/admin/tickets/check-in` | Check-in vé bằng booking code |

### User Management

| Method | Endpoint                         | Mô tả                |
| ------ | -------------------------------- | -------------------- |
| GET    | `/api/admin/users`               | Danh sách người dùng |
| GET    | `/api/admin/users/{user}`        | Chi tiết người dùng  |
| POST   | `/api/admin/users/{user}/lock`   | Khóa tài khoản       |
| POST   | `/api/admin/users/{user}/unlock` | Mở khóa tài khoản    |

### Dev/Test Only

| Method | Endpoint                                     | Mô tả                                                |
| ------ | -------------------------------------------- | ---------------------------------------------------- |
| POST   | `/api/admin/payments/{payment}/fake-success` | Giả lập thanh toán thành công trong môi trường local |

---

## 7. Business Flow

### 7.1 Booking Flow

1. Customer xem danh sách chuyến đang mở bán.
2. Customer xem ghế của chuyến.
3. Customer chọn ghế và tạo booking.
4. Hệ thống giữ ghế trong 10 phút.
5. Booking ở trạng thái `pending_payment`.
6. Nếu quá hạn, booking chuyển `expired`, ghế được nhả lại.
7. Nếu customer hủy trước thanh toán, booking chuyển `cancelled`, ghế được nhả lại.

### 7.2 Payment Flow

1. Customer tạo booking.
2. Customer tạo link thanh toán PayOS.
3. Hệ thống chỉ tạo payment nếu booking còn `pending_payment`, chưa hết hạn và chuyến vẫn đang mở bán.
4. PayOS return/webhook về hệ thống.
5. Nếu thanh toán thành công:

   * Payment chuyển `success`
   * Booking chuyển `confirmed`
   * Ghế chuyển `booked`
   * Hệ thống gửi email/thông báo xác nhận vé

### 7.3 Refund Flow

1. Customer gửi yêu cầu hoàn vé cho booking `confirmed`.
2. Hệ thống chặn hoàn vé nếu:

   * Chuyến đã khởi hành
   * Booking không có payment success
   * Vé đã check-in
3. Booking chuyển `refund_requested`.
4. Admin duyệt hoặc từ chối.
5. Nếu duyệt:

   * Booking chuyển `refunded`
   * Payment chuyển `refunded`
   * Ghế được mở lại nếu chuyến chưa chạy
   * Customer nhận notification
6. Nếu từ chối:

   * Booking quay lại `confirmed`
   * Customer nhận notification

### 7.4 Trip Operation Flow

1. Admin tạo chuyến.
2. Chuyến ở trạng thái `scheduled`.
3. Khi xe xuất bến, admin gọi depart:

   * Trip chuyển `departed`
   * Booking pending payment bị expired
   * Ghế available/reserved bị blocked
   * Ghế booked giữ nguyên
4. Khi chuyến kết thúc, admin gọi complete:

   * Trip chuyển `completed`

### 7.5 Passenger Check-in Flow

1. Admin xem danh sách hành khách của chuyến.
2. Admin check-in từng ghế hoặc toàn bộ booking.
3. Hệ thống lưu `checked_in_at` và `checked_in_by`.
4. Admin có thể hoàn tác check-in nếu bấm nhầm.
5. Vé đã check-in thì không thể yêu cầu hoàn vé.

### 7.6 Ticket Verification Flow

1. Admin nhập hoặc quét mã booking.
2. Hệ thống kiểm tra vé.
3. Nếu vé hợp lệ và chưa check-in, admin có thể check-in bằng mã booking.
4. Nếu vé đã check-in toàn bộ, hệ thống báo không thể check-in tiếp.

---

## 8. Status chính trong hệ thống

### Booking Status

| Status             | Ý nghĩa                     |
| ------------------ | --------------------------- |
| `pending_payment`  | Booking đang chờ thanh toán |
| `confirmed`        | Đã thanh toán thành công    |
| `cancelled`        | Đã hủy                      |
| `expired`          | Hết hạn thanh toán          |
| `refund_requested` | Đã yêu cầu hoàn vé          |
| `refunded`         | Đã hoàn vé                  |

### Trip Status

| Status      | Ý nghĩa                             |
| ----------- | ----------------------------------- |
| `scheduled` | Chuyến đã lên lịch và có thể mở bán |
| `departed`  | Chuyến đã khởi hành                 |
| `completed` | Chuyến đã hoàn thành                |
| `cancelled` | Chuyến đã hủy                       |

### Trip Seat Status

| Status      | Ý nghĩa                    |
| ----------- | -------------------------- |
| `available` | Ghế còn trống              |
| `reserved`  | Ghế đang được giữ          |
| `booked`    | Ghế đã được đặt thành công |
| `blocked`   | Ghế bị khóa, không bán     |

### Payment Status

| Status     | Ý nghĩa               |
| ---------- | --------------------- |
| `pending`  | Đang chờ thanh toán   |
| `success`  | Thanh toán thành công |
| `failed`   | Thanh toán thất bại   |
| `refunded` | Đã hoàn tiền          |

---

## 9. Middleware chính

| Middleware     | Ý nghĩa                      |
| -------------- | ---------------------------- |
| `auth:sanctum` | Yêu cầu token đăng nhập      |
| `active`       | Chặn tài khoản bị khóa       |
| `admin`        | Chỉ cho phép tài khoản admin |

---

## 10. Ghi chú kỹ thuật nổi bật

* Dùng transaction và `lockForUpdate` để chống đặt trùng ghế.
* Booking có thời hạn thanh toán 10 phút.
* Có command/scheduler để expire booking quá hạn.
* PayOS được tích hợp qua return URL và webhook.
* Payment confirmation có idempotency để tránh xử lý trùng.
* Check-in lưu theo từng booking item.
* Refund có workflow admin duyệt/từ chối.
* Notification hỗ trợ unread/read qua `read_at`.
* Admin có thể khóa user và token cũ bị xóa.
