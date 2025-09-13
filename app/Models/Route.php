<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Route extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'start_point',
        'end_point',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'route_points',
        'distance',
        'estimated_time',
        'route_description',
        'co2_saved',
        'green_points',
        'completed'
    ];

    protected $casts = [
        'distance' => 'decimal:2',
        'co2_saved' => 'decimal:2',
        'completed' => 'boolean',
        'start_latitude' => 'decimal:8',
        'start_longitude' => 'decimal:8',
        'end_latitude' => 'decimal:8',
        'end_longitude' => 'decimal:8',
        'route_points' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcular distancia basada en coordenadas usando fórmula de Haversine
     */
    public function calculateDistanceFromCoordinates()
    {
        if (!$this->start_latitude || !$this->start_longitude ||
            !$this->end_latitude || !$this->end_longitude) {
            return null;
        }

        $earthRadius = 6371; // Radio de la Tierra en km

        $dLat = deg2rad($this->end_latitude - $this->start_latitude);
        $dLon = deg2rad($this->end_longitude - $this->start_longitude);

        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($this->start_latitude)) * cos(deg2rad($this->end_latitude)) *
            sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        $this->distance = round($earthRadius * $c, 2);
        return $this->distance;
    }

    /**
     * Calcular tiempo estimado basado en distancia
     * Velocidad promedio en bicicleta: 15 km/h
     */
    public function calculateEstimatedTime()
    {
        if ($this->distance) {
            $avgSpeed = 15; // km/h
            $this->estimated_time = round(($this->distance / $avgSpeed) * 60); // en minutos
            return $this->estimated_time;
        }
        return null;
    }

    /**
     * Calcular CO2 reducido basado en la distancia
     * Fórmula simple: 0.21 kg de CO2 por km (diferencia auto vs bicicleta)
     */
    public function calculateCO2Saved()
    {
        $this->co2_saved = round($this->distance * 0.21, 2);
        $this->save();
        return $this->co2_saved;
    }

    /**
     * Calcular puntos verdes
     * 10 puntos por km recorrido
     */
    public function calculateGreenPoints()
    {
        $this->green_points = round($this->distance * 10);
        $this->save();
        return $this->green_points;
    }

    /**
     * Marcar ruta como completada y calcular recompensas
     */
    public function completeRoute()
    {
        $this->completed = true;
        $this->completed_at = now();
        $this->calculateCO2Saved();
        $this->calculateGreenPoints();
        $this->save();
    }

    /**
     * Obtener descripción formateada de la ruta
     */
    public function getFormattedDescriptionAttribute()
    {
        return $this->route_description ?: 'Ruta desde ' . $this->start_point . ' hasta ' . $this->end_point;
    }

    /**
     * Obtener tiempo estimado en formato legible
     */
    public function getFormattedTimeAttribute()
    {
        if (!$this->estimated_time) return null;

        $hours = floor($this->estimated_time / 60);
        $minutes = $this->estimated_time % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'min';
        }
        return $minutes . ' min';
    }
}
