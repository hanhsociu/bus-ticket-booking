<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Xác nhận vé xe</title>
</head>

<body style="font-family: Arial, sans-serif; color: #222;">
    <h2>Vé xe của bạn đã được xác nhận</h2>

    <p>Xin chào <strong>{{ $booking->user->name }}</strong>,</p>

    <p>Cảm ơn bạn đã đặt vé. Dưới đây là thông tin vé của bạn:</p>

    <table cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse;">
        <tr>
            <td><strong>Mã booking</strong></td>
            <td>{{ $booking->booking_code }}</td>
        </tr>
        <tr>
            <td><strong>Tuyến</strong></td>
            <td>
                {{ $booking->trip->route->from_location }}
                →
                {{ $booking->trip->route->to_location }}
            </td>
        </tr>
        <tr>
            <td><strong>Thời gian khởi hành</strong></td>
            <td>{{ $booking->trip->departure_time->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Xe</strong></td>
            <td>{{ $booking->trip->bus->name }} - {{ $booking->trip->bus->license_plate }}</td>
        </tr>
        <tr>
            <td><strong>Ghế</strong></td>
            <td>
                {{ $booking->items->pluck('seat_number')->join(', ') }}
            </td>
        </tr>
        <tr>
            <td><strong>Tổng tiền</strong></td>
            <td>{{ number_format($booking->total_amount, 0, ',', '.') }}đ</td>
        </tr>
        <tr>
            <td><strong>Trạng thái</strong></td>
            <td>{{ $booking->status }}</td>
        </tr>
    </table>

    <p style="margin-top: 20px;">
        Vui lòng có mặt trước giờ khởi hành ít nhất 15 phút.
    </p>

    <p>Trân trọng,<br>Bus Ticket Booking</p>
</body>

</html>