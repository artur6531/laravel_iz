<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreParkingLotRequest;
use App\Http\Requests\UpdateParkingLotRequest;
use App\Http\Resources\ParkingLotResource;
use App\Http\Resources\ParkingResource;
use App\Models\ParkingLot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ParkingLotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $parkingLots = ParkingLot::query()
            ->withCount("parkings")
            ->orderBy("name")
            ->get();
        return ParkingLotResource::collection($parkingLots);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreParkingLotRequest $request): JsonResponse
    {
        $parkingLot = ParkingLot::create($request->validated());
        $parkingLot->loadCount("parkings");

        return new ParkingLotResource($parkingLot)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ParkingLot $parkingLot): ParkingLotResource
    {
        $parkingLot
            ->load(["parkings" => fn($query) => $query->latest("arrived_at")])
            ->loadCount("parkings");

        return new ParkingLotResource($parkingLot);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateParkingLotRequest $request,
        ParkingLot $parkingLot,
    ): ParkingLotResource {
        $parkingLot->update($request->validated());
        $parkingLot->loadCount("parkings");
        return new ParkingLotResource("parkings");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ParkingLot $parkingLot): JsonResponse|Response
    {
        if ($parkingLot->parkings()->whereNull("left_at")->exists()) {
            return response()->json(
                [
                    "message" =>
                        "'The parking lot cannot be deleted because it has active parkings.",
                ],
                409,
            );
        }

        $parkingLot->delete();
        return response()->noContent();
    }

    public function parkings(
        ParkingLot $parkingLot,
    ): AnonymousResourceCollection {
        $parkings = $parkingLot
            ->parkings()
            ->with("parkingLot")
            ->latest("arrived_at")
            ->paginate(15)
            ->withQueryString();

        return ParkingResource::collection($parkings);
    }
}
