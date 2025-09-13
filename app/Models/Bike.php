<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bike extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'brand',
        'model',
        'type',
        'status',
        'station_id',
        'battery_level',
        'description',
        'purchase_price',
        'purchase_date',
        'last_maintenance',
        'is_active'
    ];

    protected $casts = [
        'battery_level' => 'integer',
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
        'last_maintenance' => 'date',
        'is_active' => 'boolean'
    ];

    /**
     * Relación con estación
     */
// App/Models/Bike.php
    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }

    /**
     * Scope para bicicletas activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para bicicletas disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'disponible')->where('is_active', true);
    }

    /**
     * Scope para bicicletas en uso
     */
    public function scopeInUse($query)
    {
        return $query->where('status', 'en_uso')->where('is_active', true);
    }

    /**
     * Scope para bicicletas eléctricas
     */
    public function scopeElectric($query)
    {
        return $query->where('type', 'electrica');
    }

    /**
     * Scope para bicicletas tradicionales
     */
    public function scopeTraditional($query)
    {
        return $query->where('type', 'tradicional');
    }

    /**
     * Scope para filtrar por estación
     */
    public function scopeAtStation($query, $stationId)
    {
        return $query->where('station_id', $stationId);
    }

    /**
     * Obtener texto descriptivo del tipo
     */
    public function getTypeTextAttribute()
    {
        $types = [
            'tradicional' => 'Bicicleta Tradicional',
            'electrica' => 'Bicicleta Eléctrica'
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * Obtener texto descriptivo del estado
     */
    public function getStatusTextAttribute()
    {
        $statuses = [
            'disponible' => 'Disponible',
            'en_uso' => 'En Uso',
            'en_reparacion' => 'En Reparación',
            'mantenimiento' => 'En Mantenimiento'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Obtener clase CSS del estado para badges
     */
    public function getStatusClassAttribute()
    {
        $classes = [
            'disponible' => 'badge-success',
            'en_uso' => 'badge-warning',
            'en_reparacion' => 'badge-danger',
            'mantenimiento' => 'badge-info'
        ];

        return $classes[$this->status] ?? 'badge-secondary';
    }

    /**
     * Verificar si es bicicleta eléctrica
     */
    public function getIsElectricAttribute()
    {
        return $this->type === 'electrica';
    }

    /**
     * Verificar si está disponible
     */
    public function getIsAvailableAttribute()
    {
        return $this->status === 'disponible' && $this->is_active;
    }

    /**
     * Verificar si está en uso
     */
    public function getIsInUseAttribute()
    {
        return $this->status === 'en_uso';
    }

    /**
     * Verificar si necesita mantenimiento
     */
    public function getNeedsMaintenanceAttribute()
    {
        if (!$this->last_maintenance) {
            return true;
        }

        // Si hace más de 3 meses desde el último mantenimiento
        return $this->last_maintenance->diffInMonths(now()) >= 3;
    }

    /**
     * Obtener nivel de batería con texto
     */
    public function getBatteryLevelTextAttribute()
    {
        if (!$this->is_electric || $this->battery_level === null) {
            return 'N/A';
        }

        return $this->battery_level . '%';
    }

    /**
     * Obtener clase CSS para el nivel de batería
     */
    public function getBatteryClassAttribute()
    {
        if (!$this->is_electric || $this->battery_level === null) {
            return '';
        }

        if ($this->battery_level >= 80) {
            return 'battery-high';
        } elseif ($this->battery_level >= 50) {
            return 'battery-medium';
        } elseif ($this->battery_level >= 20) {
            return 'battery-low';
        } else {
            return 'battery-critical';
        }
    }

    /**
     * Verificar si la batería está baja (solo para eléctricas)
     */
    public function getHasLowBatteryAttribute()
    {
        return $this->is_electric && $this->battery_level !== null && $this->battery_level < 20;
    }

    /**
     * Calcular días desde el último mantenimiento
     */
    public function getDaysSinceMaintenanceAttribute()
    {
        if (!$this->last_maintenance) {
            return null;
        }

        return $this->last_maintenance->diffInDays(now());
    }

    /**
     * Obtener antigüedad de la bicicleta
     */
    public function getAgeInDaysAttribute()
    {
        if (!$this->purchase_date) {
            return null;
        }

        return $this->purchase_date->diffInDays(now());
    }

    /**
     * Verificar si puede ser asignada a una estación
     */
    public function canBeAssignedToStation()
    {
        return $this->is_active && in_array($this->status, ['disponible', 'mantenimiento']);
    }

    /**
     * Verificar si puede ser puesta en uso
     */
    public function canBeUsed()
    {
        if (!$this->is_active || $this->status !== 'disponible') {
            return false;
        }

        // Para bicicletas eléctricas, verificar nivel de batería
        if ($this->is_electric && $this->battery_level !== null && $this->battery_level < 10) {
            return false;
        }

        return true;
    }

    /**
     * Cambiar estado a disponible
     */
    public function makeAvailable()
    {
        $this->update(['status' => 'disponible']);
    }

    /**
     * Cambiar estado a en uso
     */
    public function putInUse()
    {
        if ($this->canBeUsed()) {
            $this->update(['status' => 'en_uso']);
            return true;
        }
        return false;
    }

    /**
     * Cambiar estado a mantenimiento
     */
    public function putInMaintenance()
    {
        $this->update([
            'status' => 'mantenimiento',
            'last_maintenance' => now()
        ]);
    }

    /**
     * Cambiar estado a reparación
     */
    public function putInRepair()
    {
        $this->update(['status' => 'en_reparacion']);
    }

    /**
     * Actualizar nivel de batería (solo para eléctricas)
     */
    public function updateBattery($level)
    {
        if ($this->is_electric && $level >= 0 && $level <= 100) {
            $this->update(['battery_level' => $level]);
            return true;
        }
        return false;
    }

    /**
     * Asignar a estación
     */
    public function assignToStation($stationId)
    {
        $station = Station::find($stationId);

        if (!$station || !$station->canReceiveBike() || !$this->canBeAssignedToStation()) {
            return false;
        }

        $this->update(['station_id' => $stationId]);
        return true;
    }

    /**
     * Remover de estación
     */
    public function removeFromStation()
    {
        $this->update(['station_id' => null]);
    }

    /**
     * Obtener resumen de información
     */
    public function getSummaryAttribute()
    {
        $summary = [
            'code' => $this->code,
            'type' => $this->type_text,
            'status' => $this->status_text,
            'station' => $this->station ? $this->station->name : 'Sin asignar',
            'is_active' => $this->is_active
        ];

        if ($this->is_electric) {
            $summary['battery'] = $this->battery_level_text;
        }

        if ($this->needs_maintenance) {
            $summary['maintenance_warning'] = 'Requiere mantenimiento';
        }

        return $summary;
    }
}
