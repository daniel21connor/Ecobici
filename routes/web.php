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

// Usuarios autenticados
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');



    Route::get('/user', [UserController::class, 'getUser']);
    Route::get('/users-catalog', [UserController::class, 'getUsersCatalog']);
    Route::post('/create-admin', [UserController::class, 'createAdmin']);

    // Membresías
    Route::prefix('memberships')->group(function () {
        Route::get('/', [MembershipController::class, 'index'])->name('memberships.index');
        Route::get('/plans', [MembershipController::class, 'getPlans'])->name('memberships.plans');
        Route::get('/current', [MembershipController::class, 'getCurrentMembership'])->name('memberships.current');
        Route::post('/payment', [MembershipController::class, 'processPayment'])->name('memberships.payment');
        Route::post('/cancel', [MembershipController::class, 'cancelMembership'])->name('memberships.cancel');
        Route::get('/history', [MembershipController::class, 'getMembershipHistory'])->name('memberships.history');
    });
});
