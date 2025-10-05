<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\AirQualityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use OpenApi\Annotations as OA;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Air quality routes (public for demo purposes)
Route::prefix('air-quality')->group(function () {
    Route::get('current', [AirQualityController::class, 'getCurrent']);
    Route::get('forecast', [AirQualityController::class, 'getForecast']);
    Route::get('history', [AirQualityController::class, 'getHistory']);
    Route::get('alerts', [AirQualityController::class, 'getAlerts']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('auth/profile', [AuthController::class, 'profile']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    
    // Submissions
    Route::prefix('submissions')->group(function () {
        Route::post('/', [SubmissionController::class, 'store']);
        Route::get('/', [SubmissionController::class, 'index']);
        Route::get('{id}', [SubmissionController::class, 'show']);
    });
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::post('subscribe', [NotificationController::class, 'subscribe']);
        Route::post('unsubscribe', [NotificationController::class, 'unsubscribe']);
        Route::get('settings', [NotificationController::class, 'getSettings']);
        Route::put('settings', [NotificationController::class, 'updateSettings']);
    });
});

// Admin routes (for future expansion)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('submissions', [SubmissionController::class, 'adminIndex']);
    Route::get('users', [AuthController::class, 'adminUsers']);
    Route::get('analytics', [AirQualityController::class, 'getAnalytics']);
});

/**
 * @OA\Get(
 *     path="/health",
 *     tags={"Health"},
 *     summary="Health check",
 *     description="Check if the API is running and healthy",
 *     @OA\Response(
 *         response=200,
 *         description="API is healthy",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="ok"),
 *             @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *             @OA\Property(property="version", type="string", example="1.0.0")
 *         )
 *     )
 * )
 */
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
    ]);
});
