<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class Parking extends Model
{
    /** @use HasFactory<\Database\Factories\ParkingFactory> */
    use HasFactory;
    protected $fillable = [
        'parking_lot_id',
        'license_plate',
        'arrived_at',
        'left_at',
        'fee',
    ];

    #[Override]
    protected function casts()
    {
        return [
            "arrived_at" => "datetime",
            "left_at" => "datetime",
            "fee" => "integer",
        ];
    }
    
    public function parkingLot() : BelongsTo {
        return $this->belongsTo(ParkingLot::class);
    }
}
