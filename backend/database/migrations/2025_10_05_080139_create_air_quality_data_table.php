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
        Schema::create('air_quality_data', function (Blueprint $table) {
            $table->id();
            $table->decimal('location_lat', 10, 8);
            $table->decimal('location_lng', 11, 8);
            $table->decimal('no2_level', 8, 2)->nullable();
            $table->decimal('o3_level', 8, 2)->nullable();
            $table->decimal('pm25_level', 8, 2)->nullable();
            $table->integer('aqi_value');
            $table->string('data_source');
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->index(['location_lat', 'location_lng', 'timestamp']);
            $table->index(['timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('air_quality_data');
    }
};
