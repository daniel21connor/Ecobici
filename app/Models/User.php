<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'apellidos',
        'email',
        'dpi',
        'fecha_nacimiento',
        'telefono',
        'foto',
        'password',
        // 'role' removido para evitar mass assignment malicioso
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    // ===== AGREGAR ESTAS LÍNEAS PARA LAS MEMBRESÍAS =====

    // Relación con membresías
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    // Obtener membresía activa
    public function activeMembership()
    {
        return $this->hasOne(Membership::class)->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    // Verificar si tiene membresía activa
    public function hasActiveMembership()
    {
        return $this->activeMembership()->exists();
    }

    // Obtener el tipo de plan actual
    public function getCurrentPlan()
    {
        $membership = $this->activeMembership;
        return $membership ? $membership->plan_type : null;
    }

    // Verificar si puede acceder a una funcionalidad específica según su plan
    public function canAccess($feature)
    {
        $membership = $this->activeMembership;

        if (!$membership) {
            return false; // Sin membresía, sin acceso
        }

        $planConfig = Membership::getPlanConfig($membership->plan_type);

        if (!$planConfig) {
            return false;
        }

        // Definir características por plan
        $planFeatures = [
            'basic' => [
                'basic_system_access',
                'email_support',
                'max_bikes_2',
                'basic_reports'
            ],
            'premium' => [
                'basic_system_access',
                'full_system_access',
                'priority_support',
                'max_bikes_5',
                'advanced_reports',
                'real_time_notifications'
            ],
            'vip' => [
                'basic_system_access',
                'full_system_access',
                'priority_support',
                'support_24_7',
                'unlimited_bikes',
                'custom_reports',
                'api_access',
                'exclusive_features'
            ]
        ];

        $allowedFeatures = $planFeatures[$membership->plan_type] ?? [];

        return in_array($feature, $allowedFeatures);
    }

    // Obtener límite de bicicletas según el plan
    public function getBikeLimit()
    {
        $membership = $this->activeMembership;

        if (!$membership) {
            return 0; // Sin membresía, sin bicicletas
        }

        switch ($membership->plan_type) {
            case 'basic':
                return 2;
            case 'premium':
                return 5;
            case 'vip':
                return -1; // Ilimitado
            default:
                return 0;
        }
    }

    // Verificar si puede registrar más bicicletas
    public function canRegisterMoreBikes()
    {
        $limit = $this->getBikeLimit();

        if ($limit === -1) {
            return true; // Ilimitado
        }

        if ($limit === 0) {
            return false; // Sin membresía
        }

        // Cuando implementes el modelo Bike, descomenta estas líneas:
        // $currentBikes = $this->bikes()->count();
        // return $currentBikes < $limit;

        return true; // Por ahora retorna true
    }
    public function routes()
    {
        return $this->hasMany(\App\Models\Route::class);
    }
}
