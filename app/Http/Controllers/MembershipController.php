<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
        $user = Auth::user();
        $membership = $user->memberships()->active()->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'No hay membresía activa'
            ]);
        }

        return response()->json([
            'success' => true,
            'membership' => [
                'id' => $membership->id,
                'plan_type' => $membership->plan_type,
                'plan_info' => $membership->getPlanInfo(),
                'status' => $membership->status,
                'start_date' => $membership->start_date->format('Y-m-d'),
                'end_date' => $membership->end_date->format('Y-m-d'),
                'days_remaining' => $membership->getDaysRemaining(),
                'features' => $membership->features
            ]
        ]);
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'plan_type' => 'required|in:basic,premium,vip',
            'payment_method' => 'required|in:credit_card,debit_card,paypal,bank_transfer',
            'card_number' => 'required_if:payment_method,credit_card,debit_card|string|min:16|max:16',
            'card_name' => 'required_if:payment_method,credit_card,debit_card|string|max:100',
            'card_expiry' => 'required_if:payment_method,credit_card,debit_card|string',
            'card_cvv' => 'required_if:payment_method,credit_card,debit_card|string|min:3|max:4',
            'paypal_email' => 'required_if:payment_method,paypal|email',
            'bank_account' => 'required_if:payment_method,bank_transfer|string'
        ]);

        $user = Auth::user();
        $planConfig = Membership::getPlanConfig($request->plan_type);

        if (!$planConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Plan no válido'
            ], 400);
        }

        // Simular proceso de pago
        DB::beginTransaction();

        try {
            // Cancelar membresía activa si existe
            $activeMembership = $user->memberships()->active()->first();
            if ($activeMembership) {
                $activeMembership->cancel();
            }

            // Simular validación de pago
            $paymentResult = $this->simulatePayment($request->all(), $planConfig['price']);

            if (!$paymentResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $paymentResult['message']
                ], 400);
            }

            // Crear nueva membresía
            $startDate = Carbon::now();
            $endDate = $startDate->copy()->addMonths($planConfig['duration_months']);

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
                'features' => $planConfig['features'],
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
                    'plan_info' => $membership->getPlanInfo(),
                    'transaction_id' => $membership->transaction_id,
                    'status' => $membership->status,
                    'start_date' => $membership->start_date->format('Y-m-d'),
                    'end_date' => $membership->end_date->format('Y-m-d'),
                    'amount' => $membership->amount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago: ' . $e->getMessage()
            ], 500);
        }
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

        $membership->cancel();

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
                    'plan_info' => $membership->getPlanInfo(),
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

        // 3. SIMULACIÓN REALISTA (85% éxito para tarjetas válidas)
        $random = rand(1, 100);

        if ($random > 85) {
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
        return substr($cardNumber, -4);
    }
}
