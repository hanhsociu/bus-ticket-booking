<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $busId = $this->route('bus')?->id;

        return [
            'bus_type_id' => [
                'required',
                'integer',
                Rule::exists('bus_types', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'license_plate' => [
                'required',
                'string',
                'max:30',
                Rule::unique('buses', 'license_plate')->ignore($busId),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
