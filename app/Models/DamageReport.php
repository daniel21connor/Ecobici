<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bike_id',
        'description',
        'severity',
        'status',
        'photos',
        'resolved_by',
        'resolved_at',
        'resolution_notes'
    ];

    protected $casts = [
        'photos' => 'array',
        'resolved_at' => 'datetime',
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

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['en_revision', 'en_reparacion']);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resuelto');
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeForBike($query, $bikeId)
    {
        return $query->where('bike_id', $bikeId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getIsPendingAttribute()
    {
        return $this->status === 'pendiente';
    }

    public function getIsInProgressAttribute()
    {
        return in_array($this->status, ['en_revision', 'en_reparacion']);
    }

    public function getIsResolvedAttribute()
    {
        return $this->status === 'resuelto';
    }

    public function getSeverityColorAttribute()
    {
        return match($this->severity) {
            'leve' => 'text-green-600 bg-green-100',
            'moderado' => 'text-yellow-600 bg-yellow-100',
            'grave' => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pendiente' => 'text-yellow-600 bg-yellow-100',
            'en_revision' => 'text-blue-600 bg-blue-100',
            'en_reparacion' => 'text-orange-600 bg-orange-100',
            'resuelto' => 'text-green-600 bg-green-100',
            default => 'text-gray-600 bg-gray-100'
        };
    }

    public function getResolutionTimeAttribute()
    {
        if (!$this->resolved_at) return null;

        return $this->created_at->diffForHumans($this->resolved_at, true);
    }

    public function getPhotosCountAttribute()
    {
        return is_array($this->photos) ? count($this->photos) : 0;
    }

    // Métodos de utilidad
    public function markAsInReview($adminId = null)
    {
        $this->update([
            'status' => 'en_revision',
            'resolved_by' => $adminId
        ]);
    }

    public function markAsInRepair($adminId = null)
    {
        $this->update([
            'status' => 'en_reparacion',
            'resolved_by' => $adminId
        ]);
    }

    public function resolve($adminId, $notes = null)
    {
        $this->update([
            'status' => 'resuelto',
            'resolved_by' => $adminId,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);

        // Cambiar estado de la bicicleta a disponible
        $this->bike->update(['status' => 'disponible']);
    }

    public function addPhoto($photoPath)
    {
        $photos = $this->photos ?: [];
        $photos[] = $photoPath;

        $this->update(['photos' => $photos]);
    }

    public function removePhoto($photoPath)
    {
        if (!is_array($this->photos)) return;

        $photos = array_filter($this->photos, fn($photo) => $photo !== $photoPath);

        $this->update(['photos' => array_values($photos)]);
    }

    public static function getSeverityOptions()
    {
        return [
            'leve' => 'Leve',
            'moderado' => 'Moderado',
            'grave' => 'Grave'
        ];
    }

    public static function getStatusOptions()
    {
        return [
            'pendiente' => 'Pendiente',
            'en_revision' => 'En Revisión',
            'en_reparacion' => 'En Reparación',
            'resuelto' => 'Resuelto'
        ];
    }

    // Métodos estáticos de estadísticas
    public static function getStatistics($period = 'month')
    {
        $query = static::query();

        switch ($period) {
            case 'week':
                $query->where('created_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->subMonth());
                break;
            case 'year':
                $query->where('created_at', '>=', now()->subYear());
                break;
        }

        return [
            'total' => $query->count(),
            'pending' => $query->clone()->pending()->count(),
            'in_progress' => $query->clone()->inProgress()->count(),
            'resolved' => $query->clone()->resolved()->count(),
            'by_severity' => [
                'leve' => $query->clone()->bySeverity('leve')->count(),
                'moderado' => $query->clone()->bySeverity('moderado')->count(),
                'grave' => $query->clone()->bySeverity('grave')->count(),
            ],
            'average_resolution_time' => $query->clone()
                ->resolved()
                ->whereNotNull('resolved_at')
                ->get()
                ->avg(fn($report) => $report->created_at->diffInHours($report->resolved_at))
        ];
    }
}
