<?php

use App\Http\Controllers\RouteController;
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
    Route::middleware(['auth'])->group(function () {
        Route::get('/bikes', [BikeController::class, 'index'])->name('bikes.index');
        Route::get('/bikes/data', [BikeController::class, 'getData'])->name('bikes.data');

        // Solo admins pueden crear, editar y eliminar
        Route::middleware(['admin'])->group(function () {
            Route::get('/bikes/create', [BikeController::class, 'create'])->name('bikes.create');
            Route::post('/bikes', [BikeController::class, 'store'])->name('bikes.store');
            Route::get('/bikes/{bike}/edit', [BikeController::class, 'edit'])->name('bikes.edit');
            Route::put('/bikes/{bike}', [BikeController::class, 'update'])->name('bikes.update');
            Route::delete('/bikes/{bike}', [BikeController::class, 'destroy'])->name('bikes.destroy');
            Route::patch('/bikes/{bike}/toggle-status', [BikeController::class, 'toggleStatus'])->name('bikes.toggle-status');
            Route::patch('/bikes/{bike}/move-to-station', [BikeController::class, 'moveToStation'])->name('bikes.move-to-station');
            Route::patch('/bikes/{bike}/update-battery', [BikeController::class, 'updateBattery'])->name('bikes.update-battery');
        });

        Route::get('/bikes/{bike}', [BikeController::class, 'show'])->name('bikes.show');

    });

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
