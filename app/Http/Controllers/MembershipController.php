<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MembershipController extends Controller
{
    // ===== RUTAS WEB (con autenticación de sesión) =====

    public function index()
    {
        $user = Auth::user();
        $currentMembership = $user->memberships()->active()->first();
        $membershipHistory = $user->memberships()->orderBy('created_at', 'desc')->get();

        $plans = [
            'basic' => Membership::getPlanConfig('basic'),
            'premium' => Membership::getPlanConfig('premium'),
            'vip' => Membership::getPlanConfig('vip')
        ];

        return view('memberships.index', compact('currentMembership', 'membershipHistory', 'plans'));
    }

    // ===== RUTAS AJAX (para usar desde JavaScript con autenticación de sesión) =====

    public function getPlans(Request $request)
    {
        $plans = [
            'basic' => Membership::getPlanConfig('basic'),
            'premium' => Membership::getPlanConfig('premium'),
            'vip' => Membership::getPlanConfig('vip')
        ];

        return response()->json([
            'success' => true,
            'plans' => $plans
        ]);
    }

    public function getCurrentMembership(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Usar el método del modelo User para obtener la membresía activa
            $activeMembership = $user->activeMembership;

            if (!$activeMembership) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay membresía activa',
                    'membership' => null
                ]);
            }

            // Obtener información del plan
            $planConfig = Membership::getPlanConfig($activeMembership->plan_type);

            // Calcular días restantes
            $endDate = \Carbon\Carbon::parse($activeMembership->end_date);
            $daysRemaining = max(0, $endDate->diffInDays(now()));

            // Preparar la respuesta con toda la información necesaria
            $membershipData = [
                'id' => $activeMembership->id,
                'plan_type' => $activeMembership->plan_type,
                'status' => $activeMembership->status,
                'start_date' => $activeMembership->start_date,
                'end_date' => $activeMembership->end_date,
                'days_remaining' => $daysRemaining,
                'is_active' => $activeMembership->status === 'active' && $endDate->isFuture(),
                'plan_info' => $planConfig,
                'features' => $this->getPlanFeatures($activeMembership->plan_type),
                'permissions' => $this->getUserPermissions($user)
            ];

            return response()->json([
                'success' => true,
                'membership' => $membershipData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error obteniendo membresía actual: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener todas las membresías del usuario
     */
    public function getUserMemberships(Request $request)
    {
        try {
            $user = Auth::user();

            $memberships = $user->memberships()
                ->with(['user'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($membership) {
                    $planConfig = Membership::getPlanConfig($membership->plan_type);
                    return [
                        'id' => $membership->id,
                        'plan_type' => $membership->plan_type,
                        'status' => $membership->status,
                        'start_date' => $membership->start_date,
                        'end_date' => $membership->end_date,
                        'amount' => $membership->amount,
                        'created_at' => $membership->created_at,
                        'plan_info' => $planConfig
                    ];
                });

            return response()->json([
                'success' => true,
                'memberships' => $memberships
            ]);

        } catch (\Exception $e) {
            \Log::error('Error obteniendo membresías del usuario: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Verificar acceso a una funcionalidad específica
     */
    public function checkAccess(Request $request)
    {
        $request->validate([
            'feature' => 'required|string'
        ]);

        $user = Auth::user();
        $feature = $request->input('feature');

        $canAccess = $user->canAccess($feature);
        $currentPlan = $user->getCurrentPlan();

        return response()->json([
            'success' => true,
            'can_access' => $canAccess,
            'current_plan' => $currentPlan,
            'has_active_membership' => $user->hasActiveMembership()
        ]);
    }
    public function processPayment(Request $request)
    {
        try {
            // Debug: Ver qué datos llegan
            \Log::info('Payment request data:', $request->all());

            // Validaciones básicas primero
            $request->validate([
                'plan_type' => 'required|in:basic,premium,vip',
                'payment_method' => 'required|in:credit_card,debit_card,paypal,bank_transfer',
            ]);

            // Validaciones específicas según método de pago
            switch ($request->payment_method) {
                case 'credit_card':
                case 'debit_card':
                    $request->validate([
                        'card_number' => 'required|string|digits:16',
                        'card_name' => 'required|string|min:2|max:100',
                        'card_expiry' => 'required|string|regex:/^\d{2}\/\d{2}$/',
                        'card_cvv' => 'required|string|digits_between:3,4'
                    ], [
                        'card_number.digits' => 'El número de tarjeta debe tener exactamente 16 dígitos',
                        'card_expiry.regex' => 'El formato de fecha debe ser MM/AA',
                        'card_cvv.digits_between' => 'El CVV debe tener 3 o 4 dígitos'
                    ]);
                    break;

                case 'paypal':
                    $request->validate([
                        'paypal_email' => 'required|email|max:100'
                    ]);
                    break;

                case 'bank_transfer':
                    $request->validate([
                        'bank_account' => 'required|string|min:10|max:20'
                    ]);
                    break;
            }

            $user = Auth::user();
            $planConfig = Membership::getPlanConfig($request->plan_type);

            if (!$planConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan no válido'
                ], 400);
            }

            // Verificar si el usuario ya tiene este plan activo
            $activeMembership = $user->memberships()->active()->first();
            if ($activeMembership && $activeMembership->plan_type === $request->plan_type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes este plan activo'
                ], 400);
            }

            // Simular proceso de pago
            DB::beginTransaction();

            try {
                // Simular validación de pago
                $paymentResult = $this->simulatePayment($request->all(), $planConfig['price']);

                if (!$paymentResult['success']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => $paymentResult['message']
                    ], 400);
                }

                // Cancelar membresía activa si existe
                if ($activeMembership) {
                    $activeMembership->update(['status' => 'cancelled']);
                }

                // Crear nueva membresía
                $startDate = Carbon::now();
                $endDate = $startDate->copy()->addMonths($planConfig['duration_months'] ?? 1);

                $membership = Membership::create([
                    'user_id' => $user->id,
                    'plan_type' => $request->plan_type,
                    'amount' => $planConfig['price'],
                    'payment_method' => $request->payment_method,
                    'card_last_four' => $this->getCardLastFour($request->card_number ?? null),
                    'transaction_id' => $paymentResult['transaction_id'],
                    'status' => 'active',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'features' => json_encode($planConfig['features'] ?? []),
                    'payment_details' => json_encode($paymentResult['details']),
                    'activated_at' => Carbon::now()
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pago procesado exitosamente',
                    'membership' => [
                        'id' => $membership->id,
                        'plan_type' => $membership->plan_type,
                        'plan_info' => $planConfig,
                        'transaction_id' => $membership->transaction_id,
                        'status' => $membership->status,
                        'start_date' => $membership->start_date->format('Y-m-d'),
                        'end_date' => $membership->end_date->format('Y-m-d'),
                        'amount' => $membership->amount
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Payment processing error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno del servidor'
                ], 500);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Unexpected error in processPayment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado'
            ], 500);
        }
    }

    /**
     * Obtener las características de un plan específico
     */
    private function getPlanFeatures($planType)
    {
        $features = [
            'basic' => [
                'Acceso básico al sistema',
                'Registro de hasta 2 bicicletas',
                'Soporte por email',
                'Reportes básicos'
            ],
            'premium' => [
                'Acceso completo al sistema',
                'Registro de hasta 5 bicicletas',
                'Soporte prioritario',
                'Reportes avanzados',
                'Notificaciones en tiempo real'
            ],
            'vip' => [
                'Acceso completo al sistema',
                'Bicicletas ilimitadas',
                'Soporte 24/7',
                'Reportes personalizados',
                'Acceso a API',
                'Características exclusivas'
            ]
        ];

        return $features[$planType] ?? [];
    }

    /**
     * Obtener permisos del usuario basados en su membresía
     */
    private function getUserPermissions($user)
    {
        $permissions = [
            'can_access_reports' => $user->canAccess('advanced_reports') || $user->canAccess('basic_reports'),
            'can_register_bikes' => $user->canRegisterMoreBikes(),
            'bike_limit' => $user->getBikeLimit(),
            'has_priority_support' => $user->canAccess('priority_support'),
            'has_24_7_support' => $user->canAccess('support_24_7'),
            'can_access_api' => $user->canAccess('api_access')
        ];

        return $permissions;
    }

    public function cancelMembership(Request $request)
    {
        $user = Auth::user();
        $membership = $user->memberships()->active()->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'No hay membresía activa para cancelar'
            ], 404);
        }

        $membership->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Membresía cancelada exitosamente'
        ]);
    }

    public function getMembershipHistory(Request $request)
    {
        $user = Auth::user();
        $memberships = $user->memberships()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($membership) {
                return [
                    'id' => $membership->id,
                    'plan_type' => $membership->plan_type,
                    'plan_info' => Membership::getPlanConfig($membership->plan_type),
                    'amount' => $membership->amount,
                    'status' => $membership->status,
                    'start_date' => $membership->start_date->format('Y-m-d'),
                    'end_date' => $membership->end_date->format('Y-m-d'),
                    'created_at' => $membership->created_at->format('Y-m-d H:i:s'),
                    'transaction_id' => $membership->transaction_id
                ];
            });

        return response()->json([
            'success' => true,
            'memberships' => $memberships
        ]);
    }

    // Métodos privados
    private function simulatePayment($paymentData, $amount)
    {
        // 1. VALIDACIONES REALES PRIMERO
        if (isset($paymentData['card_number'])) {
            // Validar formato de tarjeta con algoritmo Luhn
            if (!$this->validateCardNumber($paymentData['card_number'])) {
                return [
                    'success' => false,
                    'message' => 'Número de tarjeta inválido'
                ];
            }

            // Validar fecha de expiración
            if (!$this->validateExpiryDate($paymentData['card_expiry'])) {
                return [
                    'success' => false,
                    'message' => 'Fecha de expiración inválida o vencida'
                ];
            }

            // Validar CVV
            if (!$this->validateCVV($paymentData['card_cvv'])) {
                return [
                    'success' => false,
                    'message' => 'CVV inválido'
                ];
            }
        }

        // 2. TARJETAS DE PRUEBA ESPECÍFICAS (para testing)
        if (isset($paymentData['card_number'])) {
            $testCards = [
                '4000000000000002' => 'card_declined',
                '4000000000000127' => 'insufficient_funds',
                '4000000000000119' => 'processing_error',
                '4111111111111111' => 'success', // Visa de prueba válida
                '5555555555554444' => 'success', // Mastercard de prueba válida
            ];

            if (array_key_exists($paymentData['card_number'], $testCards)) {
                $scenario = $testCards[$paymentData['card_number']];

                if ($scenario !== 'success') {
                    $messages = [
                        'card_declined' => 'Tarjeta declinada por el banco',
                        'insufficient_funds' => 'Fondos insuficientes',
                        'processing_error' => 'Error de procesamiento'
                    ];

                    return [
                        'success' => false,
                        'message' => $messages[$scenario]
                    ];
                }
            }
        }

        // 3. SIMULACIÓN REALISTA (95% éxito para tarjetas válidas en desarrollo)
        $random = rand(1, 100);

        if ($random > 95) { // Cambiado de 85 a 95 para más éxito en desarrollo
            $errors = [
                'Fondos insuficientes en la tarjeta',
                'Error temporal del banco, intente en unos minutos',
                'Tarjeta bloqueada temporalmente',
                'Error de red, intente nuevamente'
            ];

            return [
                'success' => false,
                'message' => $errors[array_rand($errors)]
            ];
        }

        // 4. ÉXITO - Generar respuesta realista
        return [
            'success' => true,
            'transaction_id' => 'TXN_' . strtoupper(Str::random(12)),
            'details' => [
                'gateway' => 'SimulatedGateway',
                'reference' => 'REF_' . time(),
                'amount' => $amount,
                'currency' => 'GTQ',
                'card_type' => $this->getCardType($paymentData['card_number'] ?? ''),
                'processed_at' => Carbon::now()->toISOString()
            ]
        ];
    }

    // Validación de tarjeta usando algoritmo Luhn (validación real)
    private function validateCardNumber($cardNumber)
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }

        $sum = 0;
        $alternate = false;

        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $n = intval($cardNumber[$i]);

            if ($alternate) {
                $n *= 2;
                if ($n > 9) {
                    $n = ($n % 10) + 1;
                }
            }

            $sum += $n;
            $alternate = !$alternate;
        }

        return ($sum % 10) === 0;
    }

    // Validar fecha de expiración
    private function validateExpiryDate($expiry)
    {
        if (!$expiry || !preg_match('/^(\d{2})\/(\d{2})$/', $expiry, $matches)) {
            return false;
        }

        $month = intval($matches[1]);
        $year = intval('20' . $matches[2]);

        if ($month < 1 || $month > 12) {
            return false;
        }

        $now = Carbon::now();
        $expiryDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        return $expiryDate->isAfter($now);
    }

    // Validar CVV
    private function validateCVV($cvv)
    {
        return $cvv && preg_match('/^\d{3,4}$/', $cvv);
    }

    // Obtener tipo de tarjeta
    private function getCardType($cardNumber)
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        if (preg_match('/^4/', $cardNumber)) {
            return 'Visa';
        } elseif (preg_match('/^5[1-5]/', $cardNumber)) {
            return 'Mastercard';
        } elseif (preg_match('/^3[47]/', $cardNumber)) {
            return 'American Express';
        } else {
            return 'Desconocida';
        }
    }

    private function getCardLastFour($cardNumber)
    {
        if (!$cardNumber) return null;
        return substr(preg_replace('/\D/', '', $cardNumber), -4);
    }
}
