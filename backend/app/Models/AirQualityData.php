<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirQualityData extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'location_lat',
        'location_lng',
        'no2_level',
        'o3_level',
        'pm25_level',
        'aqi_value',
        'data_source',
        'timestamp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location_lat' => 'decimal:8',
            'location_lng' => 'decimal:8',
            'no2_level' => 'decimal:2',
            'o3_level' => 'decimal:2',
            'pm25_level' => 'decimal:2',
            'aqi_value' => 'integer',
            'timestamp' => 'datetime',
        ];
    }

    /**
     * Scope to filter by location proximity.
     */
    public function scopeNearLocation($query, $latitude, $longitude, $radiusKm = 10)
    {
        return $query->whereRaw(
            "ST_DWithin(ST_Point(location_lng, location_lat), ST_Point(?, ?), ?)",
            [$longitude, $latitude, $radiusKm * 1000]
        );
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }
}
