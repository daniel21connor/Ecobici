<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_type',
        'amount',
        'payment_method',
        'card_last_four',
        'transaction_id',
        'status',
        'start_date',
        'end_date',
        'features',
        'payment_details',
        'activated_at',
        'cancelled_at'
    ];

    protected $casts = [
        'features' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'activated_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '<', Carbon::now());
    }

    // Métodos
    public function isActive()
    {
        return $this->status === 'active' &&
            $this->start_date <= Carbon::now() &&
            $this->end_date >= Carbon::now();
    }

    public function isExpired()
    {
        return $this->status === 'active' && $this->end_date < Carbon::now();
    }

    public function getDaysRemaining()
    {
        if (!$this->isActive()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->end_date, false);
    }

    public function activate()
    {
        $this->update([
            'status' => 'active',
            'activated_at' => Carbon::now()
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now()
        ]);
    }

    // Configuraciones de planes
    public static function getPlanConfig($planType)
    {
        $plans = [
            'basic' => [
                'name' => 'Plan Básico',
                'price' => 9.99,
                'duration_months' => 1,
                'features' => [
                    'Acceso básico al sistema',
                    'Soporte por email',
                    '2 bicicletas registradas',
                    'Reportes básicos'
                ],
                'color' => '#28a745'
            ],
            'premium' => [
                'name' => 'Plan Premium',
                'price' => 19.99,
                'duration_months' => 1,
                'features' => [
                    'Acceso completo al sistema',
                    'Soporte prioritario',
                    '5 bicicletas registradas',
                    'Reportes avanzados',
                    'Notificaciones en tiempo real'
                ],
                'color' => '#007bff'
            ],
            'vip' => [
                'name' => 'Plan VIP',
                'price' => 39.99,
                'duration_months' => 1,
                'features' => [
                    'Acceso premium completo',
                    'Soporte 24/7',
                    'Bicicletas ilimitadas',
                    'Reportes personalizados',
                    'API access',
                    'Funciones exclusivas'
                ],
                'color' => '#ffc107'
            ]
        ];

        return $plans[$planType] ?? null;
    }

    public function getPlanInfo()
    {
        return self::getPlanConfig($this->plan_type);
    }
}
