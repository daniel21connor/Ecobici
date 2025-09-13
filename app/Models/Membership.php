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
                'price' => 15.00,
                'yearly_price' => 150.00,
                'duration_days' => 30,
                'description' => 'Perfecto para usuarios ocasionales que necesitan funciones básicas',
                'color' => 'emerald',
                'color_hex' => '#10B981',
                'bg_class' => 'bg-emerald-50',
                'border_class' => 'border-emerald-200',
                'text_class' => 'text-emerald-600',
                'button_class' => 'bg-emerald-500 hover:bg-emerald-600',
                'icon' => '🌱',
                'popular' => false,
                'features' => [
                    'Acceso básico al sistema',
                    'Registro de hasta 2 bicicletas',
                    'Soporte por email',
                    'Reportes básicos',
                    'Panel de usuario estándar'
                ],
                'limits' => [
                    'bikes' => 2,
                    'reports' => 'basic',
                    'support' => 'email'
                ]
            ],
            'premium' => [
                'name' => 'Plan Premium',
                'price' => 35.00,
                'yearly_price' => 350.00,
                'duration_days' => 30,
                'description' => 'La opción más popular para usuarios regulares',
                'color' => 'blue',
                'color_hex' => '#3B82F6',
                'bg_class' => 'bg-blue-50',
                'border_class' => 'border-blue-200',
                'text_class' => 'text-blue-600',
                'button_class' => 'bg-blue-500 hover:bg-blue-600',
                'icon' => '⭐',
                'popular' => true,
                'features' => [
                    'Acceso completo al sistema',
                    'Registro de hasta 5 bicicletas',
                    'Soporte prioritario',
                    'Reportes avanzados',
                    'Notificaciones en tiempo real',
                    'Panel de control avanzado',
                    'Exportación de datos'
                ],
                'limits' => [
                    'bikes' => 5,
                    'reports' => 'advanced',
                    'support' => 'priority'
                ]
            ],
            'vip' => [
                'name' => 'Plan VIP',
                'price' => 65.00,
                'yearly_price' => 650.00,
                'duration_days' => 30,
                'description' => 'Para usuarios profesionales que necesitan acceso completo',
                'color' => 'purple',
                'color_hex' => '#8B5CF6',
                'bg_class' => 'bg-purple-50',
                'border_class' => 'border-purple-200',
                'text_class' => 'text-purple-600',
                'button_class' => 'bg-purple-500 hover:bg-purple-600',
                'icon' => '👑',
                'popular' => false,
                'features' => [
                    'Acceso completo al sistema',
                    'Bicicletas ilimitadas',
                    'Soporte 24/7',
                    'Reportes personalizados',
                    'Acceso a API',
                    'Características exclusivas',
                    'Asesor personal dedicado',
                    'Actualizaciones prioritarias'
                ],
                'limits' => [
                    'bikes' => -1, // Ilimitado
                    'reports' => 'custom',
                    'support' => '24_7'
                ]
            ]
        ];

        return $plans[$planType] ?? null;
    }


    public static function getAvailablePlans()
    {
        return [
            'basic' => self::getPlanConfig('basic'),
            'premium' => self::getPlanConfig('premium'),
            'vip' => self::getPlanConfig('vip')
        ];
    }
    public function getPlanInfo()
    {
        return self::getPlanConfig($this->plan_type);
    }
}
