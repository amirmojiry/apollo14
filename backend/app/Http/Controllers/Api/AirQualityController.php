<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AirQualityService;
use App\Services\PythonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

class AirQualityController extends Controller
{
    protected $airQualityService;
    protected $pythonService;

    public function __construct(AirQualityService $airQualityService, PythonService $pythonService)
    {
        $this->airQualityService = $airQualityService;
        $this->pythonService = $pythonService;
    }

    /**
     * @OA\Get(
     *     path="/air-quality/current",
     *     tags={"Air Quality"},
     *     summary="Get current air quality for a location",
     *     description="Retrieves current air quality data including AQI, NO2, O3, and PM2.5 levels for the specified coordinates",
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         required=true,
     *         description="Latitude coordinate",
     *         @OA\Schema(type="number", format="float", minimum=-90, maximum=90, example=40.7128)
     *     ),
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         required=true,
     *         description="Longitude coordinate",
     *         @OA\Schema(type="number", format="float", minimum=-180, maximum=180, example=-74.0060)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="aqi_value", type="integer", example=3),
     *                 @OA\Property(property="no2_level", type="number", format="float", example=25.5),
     *                 @OA\Property(property="o3_level", type="number", format="float", example=45.2),
     *                 @OA\Property(property="pm25_level", type="number", format="float", example=12.8),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
     *                 @OA\Property(property="data_sources", type="array", @OA\Items(type="string"), example={"EPA", "OpenAQ"}),
     *                 @OA\Property(
     *                     property="location",
     *                     type="object",
     *                     @OA\Property(property="latitude", type="number", example=40.7128),
     *                     @OA\Property(property="longitude", type="number", example=-74.0060)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid location coordinates"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch air quality data"),
     *             @OA\Property(property="error", type="string", example="Service unavailable")
     *         )
     *     )
     * )
     */
    public function getCurrent(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid location coordinates',
                'errors' => $validator->errors()
            ], 422);
        }

        $cacheKey = "air_quality_current_{$request->lat}_{$request->lng}";
        
        return Cache::remember($cacheKey, 300, function () use ($request) {
            try {
                $airQualityData = $this->pythonService->getAirQualityData([
                    'latitude' => $request->lat,
                    'longitude' => $request->lng,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'aqi_value' => $airQualityData['current_aqi'] ?? null,
                        'no2_level' => $airQualityData['no2_level'] ?? null,
                        'o3_level' => $airQualityData['o3_level'] ?? null,
                        'pm25_level' => $airQualityData['pm25_level'] ?? null,
                        'timestamp' => $airQualityData['timestamp'] ?? now()->toISOString(),
                        'data_sources' => $airQualityData['data_sources'] ?? [],
                        'location' => [
                            'latitude' => $request->lat,
                            'longitude' => $request->lng,
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch air quality data',
                    'error' => $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * @OA\Get(
     *     path="/air-quality/forecast",
     *     tags={"Air Quality"},
     *     summary="Get 7-day air quality forecast",
     *     description="Retrieves 7-day air quality forecast data for the specified coordinates",
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         required=true,
     *         description="Latitude coordinate",
     *         @OA\Schema(type="number", format="float", minimum=-90, maximum=90, example=40.7128)
     *     ),
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         required=true,
     *         description="Longitude coordinate",
     *         @OA\Schema(type="number", format="float", minimum=-180, maximum=180, example=-74.0060)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *                     @OA\Property(property="aqi_value", type="integer", example=3),
     *                     @OA\Property(property="no2_level", type="number", format="float", example=25.5),
     *                     @OA\Property(property="o3_level", type="number", format="float", example=45.2),
     *                     @OA\Property(property="pm25_level", type="number", format="float", example=12.8)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid location coordinates"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch forecast data"),
     *             @OA\Property(property="error", type="string", example="Service unavailable")
     *         )
     *     )
     * )
     */
    public function getForecast(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid location coordinates',
                'errors' => $validator->errors()
            ], 422);
        }

        $cacheKey = "air_quality_forecast_{$request->lat}_{$request->lng}";
        
        return Cache::remember($cacheKey, 1800, function () use ($request) {
            try {
                $forecastData = $this->pythonService->getAirQualityForecast([
                    'latitude' => $request->lat,
                    'longitude' => $request->lng,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $forecastData['forecast'] ?? []
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch forecast data',
                    'error' => $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * @OA\Get(
     *     path="/air-quality/history",
     *     tags={"Air Quality"},
     *     summary="Get historical air quality data",
     *     description="Retrieves historical air quality data for the specified coordinates and time period",
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         required=true,
     *         description="Latitude coordinate",
     *         @OA\Schema(type="number", format="float", minimum=-90, maximum=90, example=40.7128)
     *     ),
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         required=true,
     *         description="Longitude coordinate",
     *         @OA\Schema(type="number", format="float", minimum=-180, maximum=180, example=-74.0060)
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         required=false,
     *         description="Number of days to retrieve (1-30, default: 7)",
     *         @OA\Schema(type="integer", minimum=1, maximum=30, example=7)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *                     @OA\Property(property="aqi_value", type="integer", example=3),
     *                     @OA\Property(property="no2_level", type="number", format="float", example=25.5),
     *                     @OA\Property(property="o3_level", type="number", format="float", example=45.2),
     *                     @OA\Property(property="pm25_level", type="number", format="float", example=12.8)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid parameters"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch historical data"),
     *             @OA\Property(property="error", type="string", example="Service unavailable")
     *         )
     *     )
     * )
     */
    public function getHistory(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'days' => 'integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        $days = $request->get('days', 7);
        $cacheKey = "air_quality_history_{$request->lat}_{$request->lng}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($request, $days) {
            try {
                $historyData = $this->pythonService->getAirQualityHistory([
                    'latitude' => $request->lat,
                    'longitude' => $request->lng,
                    'days' => $days,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $historyData['history'] ?? []
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch historical data',
                    'error' => $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * @OA\Get(
     *     path="/air-quality/alerts",
     *     tags={"Air Quality"},
     *     summary="Get air quality alerts for a location",
     *     description="Retrieves air quality alerts and warnings for the specified coordinates",
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         required=true,
     *         description="Latitude coordinate",
     *         @OA\Schema(type="number", format="float", minimum=-90, maximum=90, example=40.7128)
     *     ),
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         required=true,
     *         description="Longitude coordinate",
     *         @OA\Schema(type="number", format="float", minimum=-180, maximum=180, example=-74.0060)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="alert_type", type="string", example="high_pollution"),
     *                     @OA\Property(property="severity", type="string", example="moderate"),
     *                     @OA\Property(property="message", type="string", example="Air quality is unhealthy for sensitive groups"),
     *                     @OA\Property(property="valid_until", type="string", format="date-time", example="2024-01-15T18:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid location coordinates"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch alerts"),
     *             @OA\Property(property="error", type="string", example="Service unavailable")
     *         )
     *     )
     * )
     */
    public function getAlerts(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid location coordinates',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $alerts = $this->airQualityService->getAlerts([
                'latitude' => $request->lat,
                'longitude' => $request->lng,
            ]);

            return response()->json([
                'success' => true,
                'data' => $alerts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
