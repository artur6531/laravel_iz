<?php

namespace Database\Seeders;

use App\Models\Parking;
use App\Models\ParkingLot;
use Illuminate\Database\Seeder;

class ParkingLotSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            "Belvárosi mélygarázs",
            "P+R Kossuth tér",
            "Városháza parkolóház",
            "Nyugati pályaudvar parkoló",
            "Széchenyi téri mélygarázs",
        ];

        
        $lots = collect($names)->shuffle()->take(6)->map(
            fn(string $name) => ParkingLot::factory()->create([
                "name" => $name,
            ]),
        );

        
        for ($i = 0; $i < 30; $i++) {
            $lot = $lots->random();

            $arrivedAt = now()
                ->subDays(fake()->numberBetween(0, 9))
                ->setTime(
                    fake()->numberBetween(8, 18),
                    fake()->numberBetween(0, 59),
                );

            $leftAt = $arrivedAt
                ->copy()
                ->addMinutes(fake()->numberBetween(20, 300));

            $hours = max(
                1,
                (int) ceil($arrivedAt->diffInMinutes($leftAt) / 60),
            );

            Parking::factory()->create([
                "parking_lot_id" => $lot->id,
                "arrived_at" => $arrivedAt,
                "left_at" => $leftAt,
                "fee" =>
                    $hours * fake()->randomElement([300, 400, 500, 600, 800]),
            ]);
        }

        
        for ($i = 0; $i < 8; $i++) {
            $lot = $lots->random();

            Parking::factory()->create([
                "parking_lot_id" => $lot->id,
                "arrived_at" => now()->subMinutes(
                    fake()->numberBetween(5, 180),
                ),
                "left_at" => null,
                "fee" => null,
            ]);

            // The car is inside, so one space is taken.
            $lot->decrement("free_spaces");
        }
    }
}
