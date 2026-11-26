<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParkingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return[
            "id" => $this->id,
            "parking_lot_id" => $this->parking_lot_id,
            "parking_lot_name" => $this->whenLoaded(
                "parkingLot",
                fn () => $this->parkingLot->name,
            ),
            'license_plate' => $this->license_plate,
            'arrived_at' => $this->arrived_at?->format('Y-m-d H:i:s'),
            'left_at' => $this->left_at?->format('Y-m-d H:i:s'),
            'fee' => $this->fee,
            'active' => $this->left_at === null,
        ];
    }
}
