<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\BikeController;
use App\Http\Controllers\BikeUsageHistoryController;
use App\Http\Controllers\DamageReportController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::get('/', [UserController::class, 'showLogin'])->name('login');
Route::get('/login', [UserController::class, 'showLogin'])->name('login');
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/logout', [UserController::class, 'logout']);

// Rutas autenticadas básicas
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/user', [UserController::class, 'getUser']);
    Route::get('/users-catalog', [UserController::class, 'getUsersCatalog']);
    Route::post('/create-admin', [UserController::class, 'createAdmin']);
});

// Rutas de membresías
Route::middleware(['auth'])->group(function () {
    Route::get('/memberships', [MembershipController::class, 'index'])->name('memberships.index');
    Route::get('/memberships/plans', [MembershipController::class, 'getPlans'])->name('memberships.plans');
    Route::get('/memberships/current', [MembershipController::class, 'getCurrentMembership'])->name('memberships.current');
    Route::get('/memberships/history', [MembershipController::class, 'getMembershipHistory'])->name('memberships.history');
    Route::post('/memberships/payment', [MembershipController::class, 'processPayment'])->name('memberships.payment');
    Route::delete('/memberships/cancel', [MembershipController::class, 'cancelMembership'])->name('memberships.cancel');
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('api')->group(function () {

    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    });

    // Rutas de membresías
    Route::prefix('memberships')->group(function () {
        Route::get('/current', [MembershipController::class, 'getCurrentMembership']);
        Route::get('/user', [MembershipController::class, 'getUserMemberships']);
        Route::post('/check-access', [MembershipController::class, 'checkAccess']);
    });

    // Estaciones
    Route::prefix('stations')->group(function () {
        Route::get('/', [StationController::class, 'index']);
        Route::get('/types', [StationController::class, 'getTypeOptions']);
        Route::get('/statistics', [StationController::class, 'getStatistics']);
        Route::get('/{station}', [StationController::class, 'show']);
        Route::get('/{station}/available-bikes', [StationController::class, 'getAvailableBikes']);

        // Solo administradores
        Route::middleware('admin')->group(function () {
            Route::post('/', [StationController::class, 'store']);
            Route::put('/{station}', [StationController::class, 'update']);
            Route::delete('/{station}', [StationController::class, 'destroy']);
        });
    });

    // Bicicletas
    Route::prefix('bikes')->group(function () {
        Route::get('/statistics', [BikeController::class, 'getStatistics']); // ESTA LÍNEA DEBE IR ANTES
        Route::get('/types', [BikeController::class, 'getTypeOptions']);
        Route::get('/statuses', [BikeController::class, 'getStatusOptions']);
        Route::get('/', [BikeController::class, 'index']);
        Route::get('/{bike}', [BikeController::class, 'show']);
        Route::get('/bikes', [BikeController::class, 'index']);
        Route::get('/bikes/statistics', [BikeController::class, 'getStatistics']);
        // Acciones de usuario
        Route::post('/{bike}/rent', [BikeController::class, 'rent']);
        Route::post('/{bike}/return', [BikeController::class, 'returnBike']);
        Route::post('/{bike}/report-damage', [BikeController::class, 'reportDamage']);

        // Solo administradores
        Route::middleware('admin')->group(function () {
            Route::post('/', [BikeController::class, 'store']);
            Route::put('/{bike}', [BikeController::class, 'update']);
            Route::delete('/{bike}', [BikeController::class, 'destroy']);
        });
    });

    // Historial de uso
    Route::prefix('usage-history')->group(function () {
        Route::get('/', [BikeUsageHistoryController::class, 'index']);
        Route::get('/current', [BikeUsageHistoryController::class, 'getCurrentUsage']);
        Route::get('/stats', [BikeUsageHistoryController::class, 'getUserStats']);
        Route::get('/statuses', [BikeUsageHistoryController::class, 'getStatusOptions']);
        Route::get('/{usage}', [BikeUsageHistoryController::class, 'show']);

        Route::post('/{usage}/complete', [BikeUsageHistoryController::class, 'complete']);
        Route::post('/{usage}/cancel', [BikeUsageHistoryController::class, 'cancel']);

        Route::middleware('admin')->group(function () {
            Route::get('/general-stats', [BikeUsageHistoryController::class, 'getGeneralStats']);
        });
    });

    // Reportes de daño
    Route::prefix('damage-reports')->group(function () {
        Route::get('/', [DamageReportController::class, 'index']);
        Route::get('/severities', [DamageReportController::class, 'getSeverityOptions']);
        Route::get('/statuses', [DamageReportController::class, 'getStatusOptions']);
        Route::get('/{damageReport}', [DamageReportController::class, 'show']);
        Route::post('/', [DamageReportController::class, 'store']);

        Route::post('/{damageReport}/photos', [DamageReportController::class, 'addPhoto']);
        Route::delete('/{damageReport}/photos', [DamageReportController::class, 'removePhoto']);

        Route::middleware('admin')->group(function () {
            Route::put('/{damageReport}', [DamageReportController::class, 'update']);
            Route::post('/{damageReport}/review', [DamageReportController::class, 'markAsInReview']);
            Route::post('/{damageReport}/repair', [DamageReportController::class, 'markAsInRepair']);
            Route::post('/{damageReport}/resolve', [DamageReportController::class, 'resolve']);
            Route::get('/statistics', [DamageReportController::class, 'getStatistics']);
        });
    });

    // Estaciones (rutas adicionales)
    Route::get('/estaciones/data', [StationController::class, 'index'])->name('estaciones.data');
    Route::get('/estaciones/{id}', [StationController::class, 'show'])->name('estaciones.show');
    Route::get('/estaciones/mapas', [StationController::class, 'paraMapas'])->name('estaciones.mapas');

    Route::middleware('admin')->group(function () {
        Route::post('/estaciones', [StationController::class, 'store'])->name('estaciones.store');
        Route::put('/estaciones/{id}', [StationController::class, 'update'])->name('estaciones.update');
        Route::delete('/estaciones/{id}', [StationController::class, 'destroy'])->name('estaciones.destroy');
        Route::get('/estaciones/estadisticas', [StationController::class, 'estadisticas'])->name('estaciones.estadisticas');
    });
});

// Rutas públicas
Route::prefix('public')->group(function () {
    Route::get('stations', function () {
        $stations = \App\Models\Estacion::active()
            ->select('id', 'nombre as name', 'id as code', 'latitud as latitude', 'longitud as longitude', 'tipo_estacion as type')
            ->withCount(['availableBikes'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stations
        ]);
    });
});
