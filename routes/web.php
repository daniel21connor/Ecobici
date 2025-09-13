<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\BikeController;
use App\Http\Controllers\BikeUsageHistoryController;
use App\Http\Controllers\DamageReportController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', [UserController::class, 'showLogin'])->name('login');
Route::get('/login', [UserController::class, 'showLogin'])->name('login');
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/logout', [UserController::class, 'logout']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/user', [UserController::class, 'getUser']);
    Route::get('/users-catalog', [UserController::class, 'getUsersCatalog']);
    Route::post('/create-admin', [UserController::class, 'createAdmin']);
});


Route::middleware(['auth'])->group(function () {
    // Vista principal de membresías
    Route::get('/memberships', [MembershipController::class, 'index'])->name('memberships.index');

    // Rutas AJAX para el componente Vue
    Route::get('/memberships/plans', [MembershipController::class, 'getPlans'])->name('memberships.plans');
    Route::get('/memberships/current', [MembershipController::class, 'getCurrentMembership'])->name('memberships.current');
    Route::get('/memberships/history', [MembershipController::class, 'getMembershipHistory'])->name('memberships.history');
    Route::post('/memberships/payment', [MembershipController::class, 'processPayment'])->name('memberships.payment');
    Route::delete('/memberships/cancel', [MembershipController::class, 'cancelMembership'])->name('memberships.cancel');
});

// Para APIs que se llaman desde el frontend web (con sesiones)
Route::middleware(['auth'])->prefix('api')->group(function () {

    // Rutas de membresías
    Route::prefix('memberships')->group(function () {
        Route::get('/current', [MembershipController::class, 'getCurrentMembership']);
        Route::get('/user', [MembershipController::class, 'getUserMemberships']);
        Route::post('/check-access', [MembershipController::class, 'checkAccess']);
    });
    // Agregar rutas de bicicletas para web
    Route::prefix('bikes')->group(function () {
        Route::get('/', [BikeController::class, 'index']);
        Route::get('/types', [BikeController::class, 'getTypeOptions']);
        Route::get('/statuses', [BikeController::class, 'getStatusOptions']);
        Route::get('/statistics', [BikeController::class, 'getStatistics']);
        Route::get('/{bike}', [BikeController::class, 'show']);


    });
    // Rutas de estaciones para web
    Route::prefix('stations')->group(function () {
        Route::get('/', [StationController::class, 'index']);
    });
    // Ruta para obtener el usuario autenticado
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| API Routes - Módulos 3 y 4
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // ===============================
    // MÓDULO 3 - ESTACIONES Y BICICLETAS
    // ===============================

    // Estaciones - RF-04
    Route::prefix('stations')->group(function () {
        Route::get('/', [StationController::class, 'index']);
        Route::get('/types', [StationController::class, 'getTypeOptions']);
        Route::get('/statistics', [StationController::class, 'getStatistics']);
        Route::get('/{station}', [StationController::class, 'show']);
        Route::get('/{station}/bikes', [StationController::class, 'getAvailableBikes']);

        // Rutas solo para administradores
        Route::middleware('admin')->group(function () {
            Route::post('/', [StationController::class, 'store']);
            Route::put('/{station}', [StationController::class, 'update']);
            Route::delete('/{station}', [StationController::class, 'destroy']);
        });
    });

    // Bicicletas - RF-05
    Route::prefix('bikes')->group(function () {
        Route::get('/', [BikeController::class, 'index']);
        Route::get('/types', [BikeController::class, 'getTypeOptions']);
        Route::get('/statuses', [BikeController::class, 'getStatusOptions']);
        Route::get('/statistics', [BikeController::class, 'getStatistics']);
        Route::get('/{bike}', [BikeController::class, 'show']);

        // Rutas solo para administradores (agregar esta sección)
        Route::middleware('admin')->group(function () {
            Route::post('/', [BikeController::class, 'store']);
            Route::put('/{bike}', [BikeController::class, 'update']);
            Route::delete('/{bike}', [BikeController::class, 'destroy']);
        });
        // Acciones de usuario
        Route::post('/{bike}/rent', [BikeController::class, 'rent']); // RF-06
        Route::post('/{bike}/return', [BikeController::class, 'returnBike']);
        Route::post('/{bike}/report-damage', [BikeController::class, 'reportDamage']); // RF-08

        // Rutas solo para administradores
        Route::middleware('admin')->group(function () {
            Route::post('/', [BikeController::class, 'store']);
            Route::put('/{bike}', [BikeController::class, 'update']);
            Route::delete('/{bike}', [BikeController::class, 'destroy']);
        });
    });

    // ===============================
    // MÓDULO 4 - USO DE BICICLETAS
    // ===============================

    // Historial de uso - RF-07
    Route::prefix('usage-history')->group(function () {
        Route::get('/', [BikeUsageHistoryController::class, 'index']);
        Route::get('/current', [BikeUsageHistoryController::class, 'getCurrentUsage']);
        Route::get('/stats', [BikeUsageHistoryController::class, 'getUserStats']);
        Route::get('/statuses', [BikeUsageHistoryController::class, 'getStatusOptions']);
        Route::get('/{usage}', [BikeUsageHistoryController::class, 'show']);

        Route::post('/{usage}/complete', [BikeUsageHistoryController::class, 'complete']);
        Route::post('/{usage}/cancel', [BikeUsageHistoryController::class, 'cancel']);


    });

    // Reportes de daño - RF-08
    Route::prefix('damage-reports')->group(function () {
        Route::get('/', [DamageReportController::class, 'index']);
        Route::get('/severities', [DamageReportController::class, 'getSeverityOptions']);
        Route::get('/statuses', [DamageReportController::class, 'getStatusOptions']);
        Route::get('/{damageReport}', [DamageReportController::class, 'show']);
        Route::post('/', [DamageReportController::class, 'store']);

        Route::post('/{damageReport}/photos', [DamageReportController::class, 'addPhoto']);
        Route::delete('/{damageReport}/photos', [DamageReportController::class, 'removePhoto']);

        // Solo para administradores
        Route::middleware('admin')->group(function () {
            Route::put('/{damageReport}', [DamageReportController::class, 'update']);
            Route::post('/{damageReport}/review', [DamageReportController::class, 'markAsInReview']);
            Route::post('/{damageReport}/repair', [DamageReportController::class, 'markAsInRepair']);
            Route::post('/{damageReport}/resolve', [DamageReportController::class, 'resolve']);
            Route::get('/statistics', [DamageReportController::class, 'getStatistics']);
        });
    });
});

// Rutas públicas (sin autenticación)
Route::prefix('public')->group(function () {
    // Información básica de estaciones para mapas públicos
    Route::get('stations', function () {
        $stations = \App\Models\Station::active()
            ->select('id', 'name', 'code', 'latitude', 'longitude', 'type')
            ->withCount(['availableBikes'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stations
        ]);
    });
});
