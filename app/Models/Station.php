<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'latitude',
        'longitude',
        'address',
        'type',
        'capacity',
        'is_active'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function bikes()
    {
        return $this->hasMany(Bike::class);
    }

    public function availableBikes()
    {
        return $this->hasMany(Bike::class)->where('status', 'disponible');
    }

    public function startedUsages()
    {
        return $this->hasMany(BikeUsageHistory::class, 'start_station_id');
    }

    public function endedUsages()
    {
        return $this->hasMany(BikeUsageHistory::class, 'end_station_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getAvailableBikesCountAttribute()
    {
        return $this->availableBikes()->count();
    }

    public function getTotalBikesAttribute()
    {
        return $this->bikes()->where('is_active', true)->count();
    }

    public function getOccupancyPercentageAttribute()
    {
        if ($this->capacity === 0) return 0;
        return round(($this->total_bikes / $this->capacity) * 100, 2);
    }

    // Métodos de utilidad
    public function canAcceptBike()
    {
        return $this->total_bikes < $this->capacity;
    }

    public function getAvailableBikesByType($type = null)
    {
        $query = $this->availableBikes();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    public static function getTypeOptions()
    {
        return [
            'carga' => 'Estación de Carga',
            'descanso' => 'Estación de Descanso',
            'seleccion' => 'Estación de Selección'
        ];
    }
}
