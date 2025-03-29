<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Earthquake extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'origin_time',
        'magnitude',
        'latitude',
        'longitude',
        'depth',
        'region',
        'region_th',
        'external_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'origin_time' => 'datetime',
        'magnitude' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'depth' => 'float',
    ];

    /**
     * Scope a query to only include significant earthquakes (magnitude >= 4.0).
     */
    public function scopeSignificant($query)
    {
        return $query->where('magnitude', '>=', 4.0);
    }

    /**
     * Scope a query to only include recent earthquakes (last 24 hours).
     */
    public function scopeRecent($query)
    {
        return $query->where('origin_time', '>=', now()->subDay());
    }
}