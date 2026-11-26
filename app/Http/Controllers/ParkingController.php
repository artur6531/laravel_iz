<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexParkingRequest;
use App\Http\Requests\StoreParkingRequest;
use App\Http\Resources\ParkingResource;
use App\Models\Parking;
use App\Models\ParkingLot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParkingController extends Controller
{
    public function index(
        IndexParkingRequest $request,
    ): AnonymousResourceCollection {
        $sort = $request->input('sort', '-arrived_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $parkings = Parking::query()
            ->with('parkingLot')
            ->when(
                $request->filled('parking_lot_id'),
                fn ($query) => $query->where(
                    'parking_lot_id',
                    $request->integer('parking_lot_id'),
                ),
            )
            ->when(
                $request->filled('plate'),
                fn ($query) => $query->where(
                    'license_plate',
                    'like',
                    '%'.$request->input('plate').'%',
                ),
            )
            ->when($request->has('active'), function ($query) use ($request) {
                return $request->boolean('active')
                    ? $query->whereNull('left_at')
                    : $query->whereNotNull('left_at');
            })
            ->when(
                $request->filled('date'),
                fn ($query) => $query->whereDate(
                    'arrived_at',
                    $request->input('date'),
                ),
            )
            ->orderBy($column, $direction)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ParkingResource::collection($parkings);
    }

    public function store(StoreParkingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $parking = DB::transaction(function () use ($validated) {
            $parkingLot = ParkingLot::query()->findOrFail(
                $validated['parking_lot_id'],
            );

            if ($parkingLot->free_spaces <= 0) {
                throw ValidationException::withMessages([
                    'parking_lot_id' => 'The parking lot is full.',
                ]);
            }

            $alreadyParked = Parking::query()
                ->where('license_plate', $validated['license_plate'])
                ->whereNull('left_at')
                ->exists();

            if ($alreadyParked) {
                throw ValidationException::withMessages([
                    'license_plate' => 'This license plate is already parked.',
                ]);
            }

            $parking = $parkingLot->parkings()->create([
                'license_plate' => $validated['license_plate'],
                'arrived_at' => now(),
            ]);

            $parkingLot->decrement('free_spaces');

            return $parking;
        });

        $parking->load('parkingLot');

        return new ParkingResource($parking)->response()->setStatusCode(201);
    }

    public function show(Parking $parking): ParkingResource
    {
        $parking->load('parkingLot');

        return new ParkingResource($parking);
    }

    public function leave(Parking $parking): ParkingResource
    {
        if ($parking->left_at !== null) {
            throw ValidationException::withMessages([
                'parking' => 'This parking has already ended.',
            ]);
        }

        DB::transaction(function () use ($parking) {
            $parking->load('parkingLot');

            $minutes = (int) $parking->arrived_at->diffInMinutes(now());
            $hours = max(1, (int) ceil($minutes / 60));

            $parking->update([
                'left_at' => now(),
                'fee' => $hours * $parking->parkingLot->hourly_rate,
            ]);

            $parking->parkingLot->increment('free_spaces');
        });

        $parking->refresh()->load('parkingLot');

        return new ParkingResource($parking);
    }

    public function destroy(Parking $parking): Response
    {
        DB::transaction(function () use ($parking) {
            if ($parking->left_at === null) {
                $parking->parkingLot->increment('free_spaces');
            }

            $parking->delete();
        });

        return response()->noContent();
    }
}
