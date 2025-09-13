<?php

use App\Http\Controllers\RankingController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRankingController;
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
Route::post('/create-user', [UserController::class, 'createUser'])->name('create.user');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/user', [UserController::class, 'getUser']);
    Route::get('/users-catalog', [UserController::class, 'getUsersCatalog']);
    Route::post('/create-admin', [UserController::class, 'createAdmin']);
    // Ruta para crear usuario regular




// Ruta para listar usuarios (si no la tienes ya)
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
Route::middleware(['web', 'auth'])->prefix('api')->group(function () {

    // Rutas de membresías
    Route::prefix('memberships')->group(function () {
        Route::get('/current', [MembershipController::class, 'getCurrentMembership']);
        Route::get('/user', [MembershipController::class, 'getUserMemberships']);
        Route::post('/check-access', [MembershipController::class, 'checkAccess']);
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

Route::middleware([ 'auth'])->prefix('api')->group(function () {

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

    // ===============================
    // ESTACIONES Y BICICLETAS
    // ===============================

    // Estaciones - RF-04
    // Rutas de Estaciones


// Rutas de Bicicletas

    // ===============================
    // USO DE BICICLETAS
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

        // Solo para administradores
        Route::middleware('admin')->group(function () {
            Route::get('/general-stats', [BikeUsageHistoryController::class, 'getGeneralStats']);
        });
    });

    // Reportes de daño - RF-08

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
Route::middleware(['auth'])->group(function () {
    // Rutas del módulo de rutas - IMPORTANTE: Las rutas más específicas van primero
    Route::get('/routes/badges', [RouteController::class, 'getBadges'])->name('routes.badges');
    Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
    Route::post('/routes', [RouteController::class, 'store'])->name('routes.store');
    Route::patch('/routes/{route}/complete', [RouteController::class, 'complete'])->name('routes.complete');
    Route::delete('/routes/{route}', [RouteController::class, 'destroy'])->name('routes.destroy');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/stations', [StationController::class, 'index'])->name('stations.index');
    Route::get('/stations/data', [StationController::class, 'getData'])->name('stations.data');
    Route::get('/stations/{station}', [StationController::class, 'show'])->name('stations.show');

    // Solo admins pueden crear, editar y eliminar
    Route::middleware(['auth'])->group(function () {
        Route::post('/stations', [StationController::class, 'store'])->name('stations.store');
        Route::put('/stations/{station}', [StationController::class, 'update'])->name('stations.update');
        Route::delete('/stations/{station}', [StationController::class, 'destroy'])->name('stations.destroy');
        Route::patch('/stations/{station}/toggle-status', [StationController::class, 'toggleStatus'])->name('stations.toggle-status');
    });



});
//Rutas de Bicicletas
Route::middleware(['auth'])->group(function () {
    // Rutas que todos los usuarios autenticados pueden acceder
    Route::get('/bikes', [BikeController::class, 'index'])->name('bikes.index');
    Route::get('/bikes/data', [BikeController::class, 'getData'])->name('bikes.data');

    // Rutas específicas van antes que las rutas con parámetros
    Route::get('/bikes/create', [BikeController::class, 'create'])->name('bikes.create');

    // Primero las rutas fijas
    Route::get('damage-reports/bikes', [DamageReportController::class, 'getAvailableBikes'])->name('damage-reports.bikes');
    Route::get('damage-reports/statistics', [DamageReportController::class, 'statistics'])->name('damage-reports.statistics');

    // Luego las rutas generales
    Route::get('damage-reports', [DamageReportController::class, 'index'])->name('damage-reports.index');
    Route::post('damage-reports', [DamageReportController::class, 'store'])->name('damage-reports.store');
    Route::get('damage-reports/{id}', [DamageReportController::class, 'show'])->name('damage-reports.show');
    Route::delete('damage-reports/{id}', [DamageReportController::class, 'destroy'])->name('damage-reports.destroy');
    Route::put('damage-reports/{id}/status', [DamageReportController::class, 'updateStatus'])->name('damage-reports.update-status');
});


Route::middleware(['auth'])->group(function () {

    // Rutas del módulo de rutas - IMPORTANTE: Las rutas más específicas van primero
    Route::get('/routes/badges', [RouteController::class, 'getBadges'])->name('routes.badges');
    Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
    Route::post('/routes', [RouteController::class, 'store'])->name('routes.store'); // Esta es la que usa RouteMapper
    Route::patch('/routes/{route}/complete', [RouteController::class, 'complete'])->name('routes.complete');
    Route::delete('/routes/{route}', [RouteController::class, 'destroy'])->name('routes.destroy');

    // Rutas para Rankings de Usuarios (sin prefijo /api)
    // Coinciden exactamente con las URLs que usa tu componente Vue

    // Obtener ranking general
    // GET /rankings
    Route::get('/rankings', [UserRankingController::class, 'index']);

    // Obtener ranking del usuario actual con su posición
    // GET /rankings/user
    Route::get('/rankings/user', [UserRankingController::class, 'getUserRanking']);

    // Forzar actualización de rankings
    // POST /rankings/update
    Route::post('/rankings/update', [UserRankingController::class, 'updateRankings']);

    // Obtener estadísticas detalladas del usuario actual
    // GET /rankings/stats
    Route::get('/rankings/stats', [UserRankingController::class, 'getUserStats']);

    // Obtener estadísticas de un usuario específico (solo admin)
    // GET /rankings/stats/{userId}
    Route::get('/rankings/stats/{userId}', [RankingController::class, 'getUserStats'])
        ->middleware('admin'); // Solo admins pueden ver stats de otros usuarios

    // Obtener ranking por período
    // GET /rankings/period?period=month&limit=20
    Route::get('/rankings/period', [RankingController::class, 'getRankingByPeriod']);

});

