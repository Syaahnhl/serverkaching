<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\KitchenController; 
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Api\ShiftController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- TRANSACTIONS ---
Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/transactions', [TransactionController::class, 'index']);
Route::post('/transactions/{id}/cancel', [TransactionController::class, 'cancel']);

// --- MENUS ---
Route::get('/menus', [MenuController::class, 'index']); // Sync (GET)
Route::post('/menus', [MenuController::class, 'store']); // <--- [WAJIB DITAMBAHKAN] Input Baru (POST)

// --- KITCHEN ---
Route::get('/kitchen/orders', [KitchenController::class, 'index']);
Route::post('/kitchen/orders/{id}/done', [KitchenController::class, 'markAsDone']);

// --- EXPENSES ---
Route::get('/expenses', [ExpenseController::class, 'index']); 
Route::post('/expenses', [ExpenseController::class, 'store']);

// --- CASH FLOW & RESERVATION ---
Route::get('/cash-flows', [CashFlowController::class, 'index']); 
Route::post('/cash-flows', [CashFlowController::class, 'store']); 
Route::post('/reservations', [ReservationController::class, 'store']);

// --- AUTH & SHIFT ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/shift/open', [ShiftController::class, 'openShift']);
Route::post('/shift/close', [ShiftController::class, 'closeShift']);