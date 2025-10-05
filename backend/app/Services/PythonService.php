<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PythonService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.python_service.url', 'http://localhost:5000');
    }

    /**
     * Get air quality data for a location
     */
    public function getAirQualityData(array $location): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/air-quality", [
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Python service error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->getMockData($location);

        } catch (\Exception $e) {
            Log::error('Python service exception', [
                'message' => $e->getMessage(),
                'location' => $location,
            ]);

            return $this->getMockData($location);
        }
    }

    /**
     * Get air quality forecast for a location
     */
    public function getAirQualityForecast(array $location): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/forecast", [
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return $this->getMockForecast($location);

        } catch (\Exception $e) {
            Log::error('Python service forecast exception', [
                'message' => $e->getMessage(),
                'location' => $location,
            ]);

            return $this->getMockForecast($location);
        }
    }

    /**
     * Get historical air quality data
     */
    public function getAirQualityHistory(array $params): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/history", $params);

            if ($response->successful()) {
                return $response->json();
            }

            return $this->getMockHistory($params);

        } catch (\Exception $e) {
            Log::error('Python service history exception', [
                'message' => $e->getMessage(),
                'params' => $params,
            ]);

            return $this->getMockHistory($params);
        }
    }

    /**
     * Get mock data when Python service is unavailable
     */
    private function getMockData(array $location): array
    {
        // Generate realistic mock data based on location
        $baseValue = $this->getLocationBasedAQI($location);
        $variation = rand(-1, 1);
        $aqi = max(1, min(5, $baseValue + $variation));

        return [
            'current_aqi' => $aqi,
            'no2_level' => $this->generatePollutantLevel($aqi, 'no2'),
            'o3_level' => $this->generatePollutantLevel($aqi, 'o3'),
            'pm25_level' => $this->generatePollutantLevel($aqi, 'pm25'),
            'timestamp' => now()->toISOString(),
            'data_sources' => ['mock_data'],
            'forecast' => $this->getMockForecast($location)['forecast'],
        ];
    }

    /**
     * Get mock forecast data
     */
    private function getMockForecast(array $location): array
    {
        $baseValue = $this->getLocationBasedAQI($location);
        $forecast = [];

        for ($i = 1; $i <= 7; $i++) {
            $variation = rand(-1, 1);
            $aqi = max(1, min(5, $baseValue + $variation));
            
            $forecast[] = [
                'date' => now()->addDays($i)->toDateString(),
                'aqi_value' => $aqi,
                'no2_level' => $this->generatePollutantLevel($aqi, 'no2'),
                'o3_level' => $this->generatePollutantLevel($aqi, 'o3'),
                'pm25_level' => $this->generatePollutantLevel($aqi, 'pm25'),
            ];
        }

        return ['forecast' => $forecast];
    }

    /**
     * Get mock historical data
     */
    private function getMockHistory(array $params): array
    {
        $days = $params['days'] ?? 7;
        $baseValue = $this->getLocationBasedAQI($params);
        $history = [];

        for ($i = $days; $i >= 0; $i--) {
            $variation = rand(-1, 1);
            $aqi = max(1, min(5, $baseValue + $variation));
            
            $history[] = [
                'date' => now()->subDays($i)->toDateString(),
                'aqi_value' => $aqi,
                'no2_level' => $this->generatePollutantLevel($aqi, 'no2'),
                'o3_level' => $this->generatePollutantLevel($aqi, 'o3'),
                'pm25_level' => $this->generatePollutantLevel($aqi, 'pm25'),
            ];
        }

        return ['history' => $history];
    }

    /**
     * Generate location-based AQI (simplified)
     */
    private function getLocationBasedAQI(array $location): int
    {
        // Simple algorithm based on latitude/longitude
        $lat = $location['latitude'];
        $lng = $location['longitude'];
        
        // Urban areas tend to have higher pollution
        $urbanFactor = abs($lat) + abs($lng) / 100;
        
        // Base AQI between 2-4
        $baseAQI = 2 + ($urbanFactor % 3);
        
        return (int) $baseAQI;
    }

    /**
     * Generate pollutant level based on AQI
     */
    private function generatePollutantLevel(int $aqi, string $pollutant): float
    {
        $baseLevels = [
            'no2' => [10, 25, 50, 100, 200],
            'o3' => [50, 100, 150, 200, 300],
            'pm25' => [15, 35, 55, 85, 150],
        ];

        $levels = $baseLevels[$pollutant] ?? [10, 20, 30, 40, 50];
        $index = $aqi - 1;
        
        return $levels[$index] + rand(-5, 5);
    }
}
