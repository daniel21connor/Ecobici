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
        'capacity' => 'integer'
    ];

    /**
     * Relación con bicicletas
     */
    public function bikes()
    {
        return $this->hasMany(Bike::class);
    }

    /**
     * Obtener bicicletas activas
     */
    public function activeBikes()
    {
        return $this->hasMany(Bike::class)->where('is_active', true);
    }

    /**
     * Obtener bicicletas disponibles
     */
    public function availableBikes()
    {
        return $this->hasMany(Bike::class)->where('status', 'disponible')->where('is_active', true);
    }

    /**
     * Obtener bicicletas en uso
     */
    public function bikesInUse()
    {
        return $this->hasMany(Bike::class)->where('status', 'en_uso')->where('is_active', true);
    }

    /**
     * Scope para estaciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Obtener porcentaje de ocupación
     */
    public function getOccupancyPercentageAttribute()
    {
        if ($this->capacity === 0) {
            return 0;
        }

        $totalBikes = $this->bikes()->where('is_active', true)->count();
        return round(($totalBikes / $this->capacity) * 100, 1);
    }

    /**
     * Verificar si la estación está llena
     */
    public function getIsFullAttribute()
    {
        return $this->bikes()->where('is_active', true)->count() >= $this->capacity;
    }

    /**
     * Obtener espacios disponibles
     */
    public function getAvailableSlotsAttribute()
    {
        $currentBikes = $this->bikes()->where('is_active', true)->count();
        return max(0, $this->capacity - $currentBikes);
    }

    /**
     * Obtener texto descriptivo del tipo
     */
    public function getTypeTextAttribute()
    {
        $types = [
            'carga' => 'Estación de Carga',
            'descanso' => 'Estación de Descanso',
            'seleccion' => 'Estación de Selección'
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * Obtener estadísticas de la estación
     */
    public function getStatsAttribute()
    {
        return [
            'total_bikes' => $this->bikes()->where('is_active', true)->count(),
            'available_bikes' => $this->availableBikes()->count(),
            'bikes_in_use' => $this->bikesInUse()->count(),
            'maintenance_bikes' => $this->bikes()->whereIn('status', ['en_reparacion', 'mantenimiento'])->where('is_active', true)->count(),
            'traditional_bikes' => $this->bikes()->where('type', 'tradicional')->where('is_active', true)->count(),
            'electric_bikes' => $this->bikes()->where('type', 'electrica')->where('is_active', true)->count(),
            'occupancy_percentage' => $this->occupancy_percentage,
            'available_slots' => $this->available_slots,
            'is_full' => $this->is_full
        ];
    }

    /**
     * Obtener coordenadas como array
     */
    public function getCoordinatesAttribute()
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude
        ];
    }

    /**
     * Validar si puede recibir una bicicleta más
     */
    public function canReceiveBike()
    {
        return $this->is_active && !$this->is_full;
    }

    /**
     * Obtener distancia desde coordenadas (en km)
     */
    public function getDistanceFrom($latitude, $longitude)
    {
        $earthRadius = 6371; // Radio de la Tierra en km

        $latFrom = deg2rad($latitude);
        $lonFrom = deg2rad($longitude);
        $latTo = deg2rad($this->latitude);
        $lonTo = deg2rad($this->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
