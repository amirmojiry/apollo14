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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('photo_path');
            $table->integer('user_guess'); // 1-5 scale
            $table->integer('actual_level')->nullable(); // 1-5 scale
            $table->integer('accuracy_score')->nullable(); // 1-5 scale
            $table->decimal('location_lat', 10, 8);
            $table->decimal('location_lng', 11, 8);
            $table->json('air_quality_data')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['user_id', 'submitted_at']);
            $table->index(['location_lat', 'location_lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
