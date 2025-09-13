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
        'distance',
        'co2_saved',
        'green_points',
        'completed'
    ];

    protected $casts = [
        'distance' => 'decimal:2',
        'co2_saved' => 'decimal:2',
        'completed' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcular CO2 reducido basado en la distancia
     * Fórmula simple: 0.2 kg de CO2 por km
     */
    public function calculateCO2Saved()
    {
        $this->co2_saved = $this->distance * 0.2;
        $this->save();
        return $this->co2_saved;
    }

    /**
     * Calcular puntos verdes
     * 10 puntos por km recorrido
     */
    public function calculateGreenPoints()
    {
        $this->green_points = $this->distance * 10;
        $this->save();
        return $this->green_points;
    }

    /**
     * Marcar ruta como completada y calcular recompensas
     */
    public function completeRoute()
    {
        $this->completed = true;
        $this->calculateCO2Saved();
        $this->calculateGreenPoints();
        $this->save();
    }
}
