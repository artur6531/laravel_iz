<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParkingLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'hourly_rate' => ['sometimes', 'required', 'integer', 'min:0', 'max:5000'],
            'free_spaces' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
