<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BikeUsageHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bike_id',
        'start_station_id',
        'end_station_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'distance_km',
        'status',
        'notes'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'distance_km' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bike()
    {
        return $this->belongsTo(Bike::class);
    }

    public function startStation()
    {
        return $this->belongsTo(Station::class, 'start_station_id');
    }

    public function endStation()
    {
        return $this->belongsTo(Station::class, 'end_station_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'activo');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completado');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForBike($query, $bikeId)
    {
        return $query->where('bike_id', $bikeId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    // Accessors
    public function getIsActiveAttribute()
    {
        return $this->status === 'activo';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completado';
    }

    public function getDurationFormattedAttribute()
    {
        if (!$this->duration_minutes) return 'En curso';

        $hours = intval($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    public function getRouteAttribute()
    {
        if (!$this->startStation || !$this->endStation) return null;

        return [
            'start' => [
                'station' => $this->startStation->name,
                'code' => $this->startStation->code,
                'coordinates' => [
                    'lat' => $this->startStation->latitude,
                    'lng' => $this->startStation->longitude
                ]
            ],
            'end' => [
                'station' => $this->endStation->name,
                'code' => $this->endStation->code,
                'coordinates' => [
                    'lat' => $this->endStation->latitude,
                    'lng' => $this->endStation->longitude
                ]
            ]
        ];
    }

    // Mutators
    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = $value ?: now();
    }

    // Métodos de utilidad
    public function calculateDuration()
    {
        if (!$this->end_time) return null;

        return $this->start_time->diffInMinutes($this->end_time);
    }

    public function complete($endStationId, $notes = null)
    {
        $this->update([
            'end_station_id' => $endStationId,
            'end_time' => now(),
            'duration_minutes' => $this->calculateDuration(),
            'status' => 'completado',
            'notes' => $notes
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelado',
            'notes' => $reason
        ]);
    }

    public static function getStatusOptions()
    {
        return [
            'activo' => 'En Uso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado'
        ];
    }

    // Métodos estáticos de estadísticas
    public static function getUserStats($userId, $period = 'month')
    {
        $query = static::forUser($userId)->completed();

        switch ($period) {
            case 'week':
                $query->where('start_time', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('start_time', '>=', now()->subMonth());
                break;
            case 'year':
                $query->where('start_time', '>=', now()->subYear());
                break;
        }

        return [
            'total_trips' => $query->count(),
            'total_duration' => $query->sum('duration_minutes'),
            'total_distance' => $query->sum('distance_km'),
            'average_duration' => $query->avg('duration_minutes'),
            'favorite_start_station' => $query->with('startStation')
                ->get()
                ->groupBy('start_station_id')
                ->map(fn($trips) => $trips->count())
                ->sortDesc()
                ->keys()
                ->first()
        ];
    }
}
