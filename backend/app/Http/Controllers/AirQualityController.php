<?php

namespace App\Http\Controllers;

use App\Services\AirQualityService;
use App\Services\PythonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

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
     * Get current air quality for a location
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
     * Get 7-day air quality forecast
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
     * Get historical air quality data
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
     * Get air quality alerts for a location
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
