<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Bike extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
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
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
        'last_maintenance' => 'date',
        'is_active' => 'boolean',
        'battery_level' => 'integer',
    ];

    // Relaciones
    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function usageHistory()
    {
        return $this->hasMany(BikeUsageHistory::class);
    }

    public function damageReports()
    {
        return $this->hasMany(DamageReport::class);
    }

    public function activeDamageReports()
    {
        return $this->hasMany(DamageReport::class)
            ->whereNotIn('status', ['resuelto']);
    }

    public function currentUsage()
    {
        return $this->hasOne(BikeUsageHistory::class)
            ->where('status', 'activo')
            ->latest();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'disponible');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInStation($query, $stationId)
    {
        return $query->where('station_id', $stationId);
    }

    // Accessors
    public function getIsAvailableAttribute()
    {
        return $this->status === 'disponible' && $this->is_active;
    }

    public function getIsElectricAttribute()
    {
        return $this->type === 'electrica';
    }

    public function getBatteryStatusAttribute()
    {
        if (!$this->is_electric) return null;

        if ($this->battery_level >= 80) return 'alta';
        if ($this->battery_level >= 50) return 'media';
        if ($this->battery_level >= 20) return 'baja';
        return 'critica';
    }

    public function getTotalUsageTimeAttribute()
    {
        return $this->usageHistory()
            ->where('status', 'completado')
            ->sum('duration_minutes');
    }

    // Métodos de utilidad
    public function canBeRented()
    {
        return $this->is_available &&
            ($this->type === 'tradicional' || $this->battery_level > 20);
    }

    public function startUsage($userId, $stationId)
    {
        if (!$this->canBeRented()) {
            throw new \Exception('Esta bicicleta no está disponible para alquiler');
        }

        // Cambiar estado a en uso
        $this->update(['status' => 'en_uso']);

        // Crear registro de uso
        return BikeUsageHistory::create([
            'user_id' => $userId,
            'bike_id' => $this->id,
            'start_station_id' => $stationId,
            'start_time' => now(),
            'status' => 'activo'
        ]);
    }

    public function endUsage($stationId, $notes = null)
    {
        $currentUsage = $this->currentUsage;

        if (!$currentUsage) {
            throw new \Exception('No hay un uso activo para esta bicicleta');
        }

        $endTime = now();
        $duration = $currentUsage->start_time->diffInMinutes($endTime);

        // Actualizar el registro de uso
        $currentUsage->update([
            'end_station_id' => $stationId,
            'end_time' => $endTime,
            'duration_minutes' => $duration,
            'status' => 'completado',
            'notes' => $notes
        ]);

        // Cambiar estado y estación de la bicicleta
        $this->update([
            'status' => 'disponible',
            'station_id' => $stationId
        ]);

        return $currentUsage->fresh();
    }

    public function reportDamage($userId, $description, $severity = 'leve', $photos = [])
    {
        // Crear reporte de daño
        $report = DamageReport::create([
            'user_id' => $userId,
            'bike_id' => $this->id,
            'description' => $description,
            'severity' => $severity,
            'photos' => $photos,
            'status' => 'pendiente'
        ]);

        // Cambiar estado a en reparación
        $this->update(['status' => 'en_reparacion']);

        return $report;
    }

    public static function getTypeOptions()
    {
        return [
            'tradicional' => 'Bicicleta Tradicional',
            'electrica' => 'Bicicleta Eléctrica'
        ];
    }

    public static function getStatusOptions()
    {
        return [
            'disponible' => 'Disponible',
            'en_uso' => 'En Uso',
            'en_reparacion' => 'En Reparación',
            'mantenimiento' => 'Mantenimiento'
        ];
    }
}
