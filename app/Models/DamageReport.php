<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'resolved_at' => 'datetime'
    ];

    protected $attributes = [
        'severity' => 'leve',
        'status' => 'pendiente'
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bike::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    // Accessors
    public function getSeverityTextAttribute()
    {
        $severities = [
            'leve' => 'Leve',
            'moderado' => 'Moderado',
            'grave' => 'Grave'
        ];

        return $severities[$this->severity] ?? 'Desconocido';
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            'pendiente' => 'Pendiente',
            'en_revision' => 'En Revisión',
            'en_reparacion' => 'En Reparación',
            'resuelto' => 'Resuelto'
        ];

        return $statuses[$this->status] ?? 'Desconocido';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pendiente' => 'yellow',
            'en_revision' => 'blue',
            'en_reparacion' => 'orange',
            'resuelto' => 'green'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getSeverityColorAttribute()
    {
        $colors = [
            'leve' => 'green',
            'moderado' => 'yellow',
            'grave' => 'red'
        ];

        return $colors[$this->severity] ?? 'gray';
    }

    // Métodos de utilidad
    public function isResolved(): bool
    {
        return $this->status === 'resuelto';
    }

    public function canBeResolvedBy(User $user): bool
    {
        return $user->role === 'admin' && !$this->isResolved();
    }

    public function markAsResolved(User $resolver, string $notes = null): bool
    {
        if (!$this->canBeResolvedBy($resolver)) {
            return false;
        }

        $this->update([
            'status' => 'resuelto',
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);

        return true;
    }

    public function updateStatus(string $status): bool
    {
        $validStatuses = ['pendiente', 'en_revision', 'en_reparacion', 'resuelto'];

        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $this->update(['status' => $status]);
        return true;
    }
}
