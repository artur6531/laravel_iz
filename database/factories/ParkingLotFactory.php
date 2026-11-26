<?php

namespace Database\Factories;

use App\Models\ParkingLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParkingLot>
 */
class ParkingLotFactory extends Factory
{
    public function definition(): array
    {
        return [
            "name" => fake()->randomElement([
                "Belvárosi mélygarázs",
                "P+R Kossuth tér",
                "Városháza parkolóház",
                "Nyugati pályaudvar parkoló",
                "Széchenyi téri mélygarázs",
            ]),
            "address" => fake()->streetAddress(),
            "hourly_rate" => fake()->randomElement([300, 400, 500, 600, 800]),
            "free_spaces" => fake()->numberBetween(5, 150),
        ];
    }
}
