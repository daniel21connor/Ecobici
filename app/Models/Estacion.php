<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'estaciones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion',
        'latitud',
        'longitud',
        'tipo_estacion',
        'capacidad_total',
        'capacidad_disponible',
        'estado',
        'direccion',
        'telefono',
        'observaciones'
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'capacidad_total' => 'integer',
        'capacidad_disponible' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Constantes para tipos de estación
    const TIPO_CARGA = 'carga';
    const TIPO_DESCANSO = 'descanso';
    const TIPO_SELECCION = 'seleccion';

    // Constantes para estados
    const ESTADO_ACTIVA = 'activa';
    const ESTADO_INACTIVA = 'inactiva';
    const ESTADO_MANTENIMIENTO = 'mantenimiento';

    /**
     * Obtener los tipos de estación disponibles
     */
    public static function getTiposEstacion()
    {
        return [
            self::TIPO_CARGA => 'Estación de Carga',
            self::TIPO_DESCANSO => 'Estación de Descanso',
            self::TIPO_SELECCION => 'Estación de Selección'
        ];
    }

    /**
     * Obtener los estados disponibles
     */
    public static function getEstados()
    {
        return [
            self::ESTADO_ACTIVA => 'Activa',
            self::ESTADO_INACTIVA => 'Inactiva',
            self::ESTADO_MANTENIMIENTO => 'En Mantenimiento'
        ];
    }

    /**
     * Scope para filtrar por tipo de estación
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_estacion', $tipo);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para estaciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }

    /**
     * Accessor para el nombre del tipo de estación
     */
    public function getTipoEstacionNombreAttribute()
    {
        $tipos = self::getTiposEstacion();
        return $tipos[$this->tipo_estacion] ?? 'Tipo no definido';
    }

    /**
     * Accessor para el nombre del estado
     */
    public function getEstadoNombreAttribute()
    {
        $estados = self::getEstados();
        return $estados[$this->estado] ?? 'Estado no definido';
    }

    /**
     * Calcular porcentaje de ocupación
     */
    public function getPorcentajeOcupacionAttribute()
    {
        if ($this->capacidad_total <= 0) {
            return 0;
        }

        $ocupadas = $this->capacidad_total - $this->capacidad_disponible;
        return round(($ocupadas / $this->capacidad_total) * 100, 2);
    }

    /**
     * Verificar si la estación tiene capacidad disponible
     */
    public function tieneCapacidadDisponible()
    {
        return $this->capacidad_disponible > 0;
    }

    /**
     * Verificar si la estación está activa
     */
    public function estaActiva()
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    /**
     * Relación con bicicletas (para futura implementación)
     */
    public function bicicletas()
    {
        return $this->hasMany(Bicicleta::class);
    }

    /**
     * Obtener coordenadas como array
     */
    public function getCoordenadas()
    {
        return [
            'lat' => $this->latitud,
            'lng' => $this->longitud
        ];
    }
}
