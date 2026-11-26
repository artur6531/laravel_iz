<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexParkingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "parking_lot_id" => ["sometimes", "integer", "exists:parking_lots,id"],
            "plate" => ["sometimes", "string", "max:10"],
            "active" => ["sometimes", "boolean"],
            "date" => ["sometimes", "date_format:Y-m-d"],
            "sort" => ["sometimes", "in:id,arrived_at,fee,-id,-arrived_at,-fee"],
            "per_page" => ["sometimes", "integer", "min:1", "max:100"]
        ];
    }
}
