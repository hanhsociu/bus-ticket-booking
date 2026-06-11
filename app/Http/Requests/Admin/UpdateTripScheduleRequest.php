<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTripScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['sometimes', 'integer', Rule::exists('routes', 'id')],
            'bus_id' => ['sometimes', 'integer', Rule::exists('buses', 'id')],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'frequency' => ['sometimes', 'string', Rule::in(['daily', 'weekly'])],
            'days_of_week' => ['sometimes', 'nullable', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'departure_time' => ['sometimes', 'date_format:H:i'],
            'arrival_time' => ['sometimes', 'date_format:H:i'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'generate_days_ahead' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
