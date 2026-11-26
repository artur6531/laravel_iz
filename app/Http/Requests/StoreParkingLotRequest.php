<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParkingLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'hourly_rate' => ['required', 'integer', 'min:0', 'max:5000'],
            'free_spaces' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
