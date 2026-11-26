<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parkings', function (Blueprint $table) {
            $table->id();
            $table->foreignId("parking_lot_id")->constrained()->cascadeOnDelete();
            $table->string("license_plate", 10);
            $table->dateTime("arrived_at");
            $table->dateTime("left_at")->nullable();
            $table->unsignedInteger("fee")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parkings');
    }
};
