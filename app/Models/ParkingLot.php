<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'hourly_rate',
        'free_spaces',
    ];

    /**
     * Default value when "free_spaces" is not sent in the request.
     * Without this, a missing value would be inserted as NULL.
     */
    protected $attributes = [
        'free_spaces' => 0,
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'integer',
            'free_spaces' => 'integer',
        ];
    }

    public function parkings(): HasMany
    {
        return $this->hasMany(Parking::class);
    }
}
