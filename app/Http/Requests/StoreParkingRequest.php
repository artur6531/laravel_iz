<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreParkingRequest extends FormRequest
{

    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            "parking_lot_id" => ["required", "integer", "exists:parking_lots,id"],
            "license_plate" => [
                "require",
                "string",
                "max:10",
                "regex:/^[A-Z]{3}-\d{3}$|^[A-Z]{2} [A-Z]{2}-\d{3}$/"
            ],
        ];
    }

    #[Override]
    public function messages() : array
    {
        return [
            "license_plate.regex" => "The license plate must be in ABC-123 or AA BC-123 format.",
        ];
    }
}
