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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->json('location_preference')->nullable();
            $table->json('notification_settings')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

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

        Schema::create('notification_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('endpoint');
            $table->string('p256dh_key');
            $table->string('auth_key');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'endpoint']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('notification_subscriptions');
        Schema::dropIfExists('air_quality_data');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('users');
    }
};
