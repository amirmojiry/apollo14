<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'photo_path',
        'user_guess',
        'actual_level',
        'accuracy_score',
        'location_lat',
        'location_lng',
        'air_quality_data',
        'submitted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_guess' => 'integer',
        'actual_level' => 'integer',
        'accuracy_score' => 'integer',
        'location_lat' => 'decimal:8',
        'location_lng' => 'decimal:8',
        'air_quality_data' => 'array',
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the submission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the air quality level label.
     */
    public function getAirQualityLabelAttribute(): string
    {
        $labels = [
            1 => 'Excellent',
            2 => 'Good',
            3 => 'Moderate',
            4 => 'Poor',
            5 => 'Hazardous'
        ];

        return $labels[$this->actual_level] ?? 'Unknown';
    }

    /**
     * Get the user guess label.
     */
    public function getUserGuessLabelAttribute(): string
    {
        $labels = [
            1 => 'Excellent',
            2 => 'Good',
            3 => 'Moderate',
            4 => 'Poor',
            5 => 'Hazardous'
        ];

        return $labels[$this->user_guess] ?? 'Unknown';
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('submitted_at', [$startDate, $endDate]);
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
}
