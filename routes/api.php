<?php

use App\Http\Controllers\ParkingController;
use App\Http\Controllers\ParkingLotController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['message' => 'pong']));

Route::apiResource("parking-lots", ParkingLotController::class);

Route::get("parking/{parking_lot}/parkings", [ParkingLotController::class, "parkings"]);

Route::apiResource("parkings", ParkingController::class)->except(["update"]);

Route::patch("parkings/{parking}/leave", [ParkingController::class, "leave"]);
