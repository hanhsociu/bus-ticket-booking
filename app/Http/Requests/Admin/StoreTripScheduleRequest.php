<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', Rule::exists('routes', 'id')],
            'bus_id' => ['required', 'integer', Rule::exists('buses', 'id')],
            'name' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', 'string', Rule::in(['daily', 'weekly'])],
            'days_of_week' => ['required_if:frequency,weekly', 'nullable', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'departure_time' => ['required', 'date_format:H:i'],
            'arrival_time' => ['required', 'date_format:H:i'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'generate_days_ahead' => ['nullable', 'integer', 'min:1', 'max:60'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'route_id.required' => 'Vui lòng chọn tuyến xe.',
            'bus_id.required' => 'Vui lòng chọn xe.',
            'frequency.required' => 'Vui lòng chọn tần suất (daily/weekly).',
            'days_of_week.required_if' => 'Lịch weekly cần chọn ít nhất một ngày trong tuần (1=Thứ 2 ... 7=Chủ nhật).',
            'departure_time.date_format' => 'Giờ đi phải đúng định dạng HH:MM.',
            'arrival_time.date_format' => 'Giờ đến phải đúng định dạng HH:MM.',
            'start_date.required' => 'Vui lòng nhập ngày bắt đầu áp dụng lịch.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ];
    }
}
