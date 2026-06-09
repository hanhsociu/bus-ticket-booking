<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'trip_id' => [
                'required',
                'integer',
                'exists:trips,id',
            ],

            'trip_seat_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'trip_seat_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:trip_seats,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Vui lòng chọn người dùng.',
            'user_id.exists' => 'Người dùng không tồn tại.',

            'trip_id.required' => 'Vui lòng chọn chuyến xe.',
            'trip_id.exists' => 'Chuyến xe không tồn tại.',

            'trip_seat_ids.required' => 'Vui lòng chọn ít nhất một ghế.',
            'trip_seat_ids.array' => 'Danh sách ghế không hợp lệ.',
            'trip_seat_ids.min' => 'Vui lòng chọn ít nhất một ghế.',
            'trip_seat_ids.*.distinct' => 'Danh sách ghế không được trùng nhau.',
            'trip_seat_ids.*.exists' => 'Ghế không tồn tại.',
        ];
    }
}
