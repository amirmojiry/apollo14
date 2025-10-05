<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NASA TEMPO Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for NASA TEMPO satellite data API
    |
    */

    'nasa_tempo' => [
        'api_key' => env('NASA_TEMPO_API_KEY'),
        'base_url' => env('NASA_TEMPO_BASE_URL', 'https://tempo.si.edu/api'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Weather API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for weather data APIs
    |
    */

    'weather' => [
        'api_key' => env('WEATHER_API_KEY'),
        'base_url' => env('WEATHER_API_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAQ Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenAQ air quality data API
    |
    */

    'openaq' => [
        'api_key' => env('OPENAQ_API_KEY'),
        'base_url' => env('OPENAQ_API_BASE_URL', 'https://api.openaq.org/v2'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Python Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Python data processing service
    |
    */

    'python_service' => [
        'url' => env('PYTHON_SERVICE_URL', 'http://localhost:5000'),
        'timeout' => env('PYTHON_SERVICE_TIMEOUT', 30),
    ],
];