<?php

use App\Models\Parking;
use App\Models\ParkingLot;

it("lists parking lots by name with a parking count", function () {
    ParkingLot::factory()->create(["name" => "Zebra parkoló"]);
    ParkingLot::factory()->create(["name" => "Alfa parkoló"]);

    $this->getJson("/api/parking-lots")
        ->assertOk()
        ->assertJsonPath("data.0.name", "Alfa parkoló")
        ->assertJsonPath("data.1.name", "Zebra parkoló");
});

it("creates a parking lot and defaults free_spaces to zero", function () {
    $this->postJson("/api/parking-lots", [
        "name" => "Új parkoló",
        "address" => "Budapest, Fő utca 1.",
        "hourly_rate" => 500,
    ])
        ->assertCreated()
        ->assertJsonPath("data.free_spaces", 0)
        ->assertJsonPath("data.full", true);
});

it("does not delete a parking lot with an active parking", function () {
    $lot = ParkingLot::factory()->create();
    Parking::factory()->create([
        "parking_lot_id" => $lot->id,
        "left_at" => null,
    ]);

    $this->deleteJson("/api/parking-lots/{$lot->id}")->assertStatus(409);
});

it("returns a json 404 for an unknown parking lot", function () {
    $this->getJson("/api/parking-lots/9999")
        ->assertNotFound()
        ->assertJsonPath("message", "The requested resource was not found.");
});
