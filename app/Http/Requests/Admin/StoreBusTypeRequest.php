<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_seats' => ['required', 'integer', 'min:1', 'max:100'],
            'seat_layout' => ['nullable', 'array'],
            'seat_layout.floors' => ['nullable', 'integer', 'min:1', 'max:3'],
            'seat_layout.rows' => ['nullable', 'integer', 'min:1', 'max:30'],
            'seat_layout.columns' => ['nullable', 'integer', 'min:1', 'max:6'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
