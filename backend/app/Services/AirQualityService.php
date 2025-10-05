<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirQualityService
{
    protected $tempoApiKey;
    protected $weatherApiKey;
    protected $openAQApiKey;

    public function __construct()
    {
        $this->tempoApiKey = config('services.nasa_tempo.api_key');
        $this->weatherApiKey = config('services.weather.api_key');
        $this->openAQApiKey = config('services.openaq.api_key');
    }

    /**
     * Get air quality alerts for a location
     */
    public function getAlerts(array $location): array
    {
        $alerts = [];

        try {
            // Check current air quality
            $currentAQI = $this->getCurrentAQI($location);
            
            if ($currentAQI >= 4) {
                $alerts[] = [
                    'type' => 'air_quality_warning',
                    'level' => $currentAQI >= 5 ? 'hazardous' : 'poor',
                    'message' => $this->getAlertMessage($currentAQI),
                    'timestamp' => now()->toISOString(),
                ];
            }

            // Check forecast for upcoming alerts
            $forecast = $this->getForecastAlerts($location);
            $alerts = array_merge($alerts, $forecast);

        } catch (\Exception $e) {
            Log::error('Air quality alerts error', [
                'message' => $e->getMessage(),
                'location' => $location,
            ]);
        }

        return $alerts;
    }

    /**
     * Get current AQI for location
     */
    private function getCurrentAQI(array $location): int
    {
        try {
            // Try OpenAQ first
            $openAQData = $this->getOpenAQData($location);
            if ($openAQData) {
                return $this->calculateAQI($openAQData);
            }

            // Fallback to mock data
            return $this->getMockAQI($location);

        } catch (\Exception $e) {
            Log::error('Current AQI error', [
                'message' => $e->getMessage(),
                'location' => $location,
            ]);

            return $this->getMockAQI($location);
        }
    }

    /**
     * Get OpenAQ data
     */
    private function getOpenAQData(array $location): ?array
    {
        try {
            $response = Http::timeout(10)->get('https://api.openaq.org/v2/latest', [
                'coordinates' => "{$location['latitude']},{$location['longitude']}",
                'radius' => 10000, // 10km radius
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['results'])) {
                    return $data['results'][0]['measurements'] ?? null;
                }
            }

        } catch (\Exception $e) {
            Log::error('OpenAQ API error', ['message' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Calculate AQI from pollutant measurements
     */
    private function calculateAQI(array $measurements): int
    {
        $maxAQI = 1;

        foreach ($measurements as $measurement) {
            $parameter = $measurement['parameter'];
            $value = $measurement['value'];

            $aqi = $this->parameterToAQI($parameter, $value);
            $maxAQI = max($maxAQI, $aqi);
        }

        return min(5, $maxAQI); // Cap at 5 for our scale
    }

    /**
     * Convert parameter value to AQI
     */
    private function parameterToAQI(string $parameter, float $value): int
    {
        $thresholds = [
            'pm25' => [15, 35, 55, 85, 150],
            'pm10' => [50, 100, 150, 200, 300],
            'no2' => [25, 50, 100, 200, 400],
            'o3' => [50, 100, 150, 200, 300],
        ];

        $paramThresholds = $thresholds[$parameter] ?? [10, 20, 30, 40, 50];

        for ($i = 0; $i < count($paramThresholds); $i++) {
            if ($value <= $paramThresholds[$i]) {
                return $i + 1;
            }
        }

        return 5; // Above highest threshold
    }

    /**
     * Get forecast alerts
     */
    private function getForecastAlerts(array $location): array
    {
        $alerts = [];

        try {
            // Get weather forecast
            $weatherData = $this->getWeatherForecast($location);
            
            if ($weatherData) {
                // Check for conditions that might worsen air quality
                foreach ($weatherData['forecast'] as $day) {
                    if ($this->isPoorAirQualityDay($day)) {
                        $alerts[] = [
                            'type' => 'forecast_warning',
                            'level' => 'moderate',
                            'message' => "Poor air quality expected on {$day['date']}",
                            'date' => $day['date'],
                        ];
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Forecast alerts error', ['message' => $e->getMessage()]);
        }

        return $alerts;
    }

    /**
     * Get weather forecast
     */
    private function getWeatherForecast(array $location): ?array
    {
        if (!$this->weatherApiKey) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/forecast', [
                'lat' => $location['latitude'],
                'lon' => $location['longitude'],
                'appid' => $this->weatherApiKey,
                'units' => 'metric',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

        } catch (\Exception $e) {
            Log::error('Weather API error', ['message' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if weather conditions suggest poor air quality
     */
    private function isPoorAirQualityDay(array $day): bool
    {
        // High temperature + low wind + high humidity = poor air quality
        $temp = $day['main']['temp'] ?? 20;
        $windSpeed = $day['wind']['speed'] ?? 5;
        $humidity = $day['main']['humidity'] ?? 50;

        return $temp > 30 && $windSpeed < 3 && $humidity > 70;
    }

    /**
     * Get alert message based on AQI level
     */
    private function getAlertMessage(int $aqi): string
    {
        $messages = [
            1 => 'Air quality is excellent. Enjoy outdoor activities!',
            2 => 'Air quality is good. Most people can enjoy outdoor activities.',
            3 => 'Air quality is moderate. Sensitive individuals should limit outdoor activities.',
            4 => 'Air quality is poor. Everyone should limit outdoor activities.',
            5 => 'Air quality is hazardous. Avoid outdoor activities and stay indoors.',
        ];

        return $messages[$aqi] ?? 'Air quality information unavailable.';
    }

    /**
     * Get mock AQI for testing
     */
    private function getMockAQI(array $location): int
    {
        // Simple algorithm based on location
        $lat = $location['latitude'];
        $lng = $location['longitude'];
        
        $urbanFactor = abs($lat) + abs($lng) / 100;
        $baseAQI = 2 + ($urbanFactor % 3);
        
        return (int) $baseAQI;
    }
}
