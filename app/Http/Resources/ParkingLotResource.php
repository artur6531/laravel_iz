<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParkingLotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "address" => $this->address,
            "hourly_rate" => $this->hourly_rate,
            "free_spaces" => $this->free_spaces,
            "full" => $this->free_spaces <= 0,
            "parkings_count" => $this->whenCounted("parkings"),
            "parkings" => $this->when(
                $this->relationLoaded("parkings"),
                fn() => ParkingResource::collection($this->parkings),
            ),
        ];
    }
}
