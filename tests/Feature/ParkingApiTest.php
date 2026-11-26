<?php

use App\Models\Parking;
use App\Models\ParkingLot;

it(
    "decreases free spaces when a car arrives and ignores arrived_at and fee",
    function () {
        $lot = ParkingLot::factory()->create(["free_spaces" => 10]);

        $this->postJson("/api/parkings", [
            "parking_lot_id" => $lot->id,
            "license_plate" => "ABC-123",
            "arrived_at" => "2000-01-01 00:00:00",
            "fee" => 999999,
        ])
            ->assertCreated()
            ->assertJsonPath("data.active", true)
            ->assertJsonPath("data.fee", null)
            ->assertJsonPath("data.parking_lot_name", $lot->name);

        expect($lot->fresh()->free_spaces)->toBe(9);
    },
);

it("rejects an arrival when the parking lot is full", function () {
    $lot = ParkingLot::factory()->create(["free_spaces" => 0]);

    $this->postJson("/api/parkings", [
        "parking_lot_id" => $lot->id,
        "license_plate" => "ABC-123",
    ])
        ->assertStatus(422)
        ->assertJsonPath("errors.parking_lot_id.0", "The parking lot is full.");
});

it("rejects a second active parking with the same plate", function () {
    $lot = ParkingLot::factory()->create(["free_spaces" => 10]);
    Parking::factory()->create([
        "license_plate" => "ABC-123",
        "left_at" => null,
    ]);

    $this->postJson("/api/parkings", [
        "parking_lot_id" => $lot->id,
        "license_plate" => "ABC-123",
    ])
        ->assertStatus(422)
        ->assertJsonPath(
            "errors.license_plate.0",
            "This license plate is already parked.",
        );
});

it("calculates the fee and frees a space when the car leaves", function () {
    $lot = ParkingLot::factory()->create([
        "hourly_rate" => 500,
        "free_spaces" => 5,
    ]);

    $parking = Parking::factory()->create([
        "parking_lot_id" => $lot->id,
        "arrived_at" => now()->subMinutes(61),
        "left_at" => null,
    ]);

    $this->patchJson("/api/parkings/{$parking->id}/leave")
        ->assertOk()
        ->assertJsonPath("data.fee", 1000)
        ->assertJsonPath("data.active", false);

    expect($lot->fresh()->free_spaces)->toBe(6);
});

it("rejects leaving twice", function () {
    $parking = Parking::factory()->create([
        "left_at" => now(),
        "fee" => 500,
    ]);

    $this->patchJson("/api/parkings/{$parking->id}/leave")->assertStatus(422);
});

it("frees a space when an active parking is deleted", function () {
    $lot = ParkingLot::factory()->create(["free_spaces" => 3]);
    $parking = Parking::factory()->create([
        "parking_lot_id" => $lot->id,
        "left_at" => null,
    ]);

    $this->deleteJson("/api/parkings/{$parking->id}")->assertNoContent();

    expect($lot->fresh()->free_spaces)->toBe(4);
});
