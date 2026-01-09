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
use App\Http\Controllers\TableController;
use App\Http\Controllers\SettingController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// --- AUTH & SHIFT ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/shift/open', [ShiftController::class, 'openShift']);
Route::post('/shift/close', [ShiftController::class, 'closeShift']);

// --- TRANSACTIONS ---
// GET: Android mengambil data riwayat (JSON) -> pakai apiSync
Route::get('/transactions', [TransactionController::class, 'apiSync']); 

// POST: Android mengirim pesanan baru
Route::post('/transactions', [TransactionController::class, 'store']);

// POST: Android membatalkan pesanan (Untuk fix sinkronisasi meja)
Route::post('/transactions/{id}/cancel', [TransactionController::class, 'cancel']);
// [BARU - TAMBAHKAN INI] Route untuk Selesaikan Transaksi (Complete)
Route::post('/transactions/{id}/complete', [TransactionController::class, 'complete']);

// --- MENUS ---
Route::get('/menus', [MenuController::class, 'index']);
Route::post('/menus', [MenuController::class, 'store']);
Route::post('/menus/{id}/stock', [MenuController::class, 'updateStock']);

// --- KITCHEN ---
Route::get('/kitchen/orders', [KitchenController::class, 'index']);
Route::post('/kitchen/orders/{id}/done', [KitchenController::class, 'markAsDone']);

// --- TABLES ---
Route::get('/tables', [TableController::class, 'index']); // Ambil data meja

// --- EXPENSES ---
Route::get('/expenses', [ExpenseController::class, 'index']); 
Route::post('/expenses', [ExpenseController::class, 'store']);

// --- CASH FLOW & RESERVATION ---
Route::get('/cash-flows', [CashFlowController::class, 'index']); 
Route::post('/cash-flows', [CashFlowController::class, 'store']); 
Route::post('/reservations', [ReservationController::class, 'store']);

// --- SETTINGS TOKO ---
Route::get('/settings', [App\Http\Controllers\SettingController::class, 'index']);
Route::post('/settings', [App\Http\Controllers\SettingController::class, 'update']);