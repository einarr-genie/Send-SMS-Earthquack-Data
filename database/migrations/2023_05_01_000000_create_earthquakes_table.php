<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('earthquakes', function (Blueprint $table) {
            $table->id();
            $table->dateTime('origin_time');
            $table->float('magnitude', 3, 1);
            $table->float('latitude', 10, 6);
            $table->float('longitude', 10, 6);
            $table->float('depth', 8, 2);
            $table->string('region');
            $table->string('region_th')->nullable();
            $table->string('external_id')->nullable()->unique();
            $table->timestamps();
            
            // Index for faster queries
            $table->index('origin_time');
            $table->index('magnitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earthquakes');
    }
};