<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MembershipController;
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

    // ===== RUTAS AJAX PARA MEMBRESÍAS (cambiar de /api a rutas web) =====

    // Obtener planes disponibles
    Route::get('/memberships/plans', [MembershipController::class, 'getPlans'])->name('memberships.plans');

    // Obtener membresía actual del usuario
    Route::get('/memberships/current', [MembershipController::class, 'getCurrentMembership'])->name('memberships.current');

    // Procesar pago de membresía
    Route::post('/memberships/payment', [MembershipController::class, 'processPayment'])->name('memberships.payment');

    // Cancelar membresía activa
    Route::post('/memberships/cancel', [MembershipController::class, 'cancelMembership'])->name('memberships.cancel');

    // Obtener historial de membresías
    Route::get('/memberships/history', [MembershipController::class, 'getMembershipHistory'])->name('memberships.history');
});
