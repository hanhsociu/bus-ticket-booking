<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => [
                'required',
                'integer',
                Rule::exists('routes', 'id')->where('is_active', true),
            ],

            'bus_id' => [
                'required',
                'integer',
                Rule::exists('buses', 'id')->where('is_active', true),
            ],

            'departure_time' => [
                'required',
                'date',
                'after:now',
            ],

            'arrival_time' => [
                'required',
                'date',
                'after:departure_time',
            ],

            'base_price' => [
                'required',
                'numeric',
                'min:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'route_id.required' => 'Vui lòng chọn tuyến xe.',
            'route_id.exists' => 'Tuyến xe không tồn tại hoặc đã bị khóa.',

            'bus_id.required' => 'Vui lòng chọn xe.',
            'bus_id.exists' => 'Xe không tồn tại hoặc đã bị khóa.',

            'departure_time.required' => 'Vui lòng nhập thời gian khởi hành.',
            'departure_time.after' => 'Thời gian khởi hành phải lớn hơn thời gian hiện tại.',

            'arrival_time.required' => 'Vui lòng nhập thời gian đến.',
            'arrival_time.after' => 'Thời gian đến phải sau thời gian khởi hành.',

            'base_price.required' => 'Vui lòng nhập giá vé.',
            'base_price.min' => 'Giá vé phải lớn hơn hoặc bằng 1.000đ.',
        ];
    }
}
