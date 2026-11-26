<?php

namespace Database\Factories;

use App\Models\Parking;
use App\Models\ParkingLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parking>
 */
class ParkingFactory extends Factory
{
    public function definition(): array
    {
        return [
            "parking_lot_id" => ParkingLot::factory(),
            "license_plate" => strtoupper(fake()->bothify("???-###")),
            "arrived_at" => now()
                ->subDays(fake()->numberBetween(0, 10))
                ->setTime(fake()->numberBetween(8, 18), fake()->numberBetween(0, 59)),
            "left_at" => null,
            "fee" => null,
        ];
    }
}
