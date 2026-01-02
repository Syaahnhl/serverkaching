<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\KitchenController; 
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\ReservationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/transactions', [TransactionController::class, 'index']);
Route::get('/menus', [MenuController::class, 'index']);
Route::get('/kitchen/orders', [KitchenController::class, 'index']); // Ambil pesanan
Route::post('/kitchen/orders/{id}/done', [KitchenController::class, 'markAsDone']); // Tandai selesai
// Route untuk Pengeluaran
Route::get('/expenses', [ExpenseController::class, 'index']); 
Route::post('/expenses', [ExpenseController::class, 'store']);
// Route Kas Kecil
Route::get('/cash-flows', [CashFlowController::class, 'index']); 
Route::post('/cash-flows', [CashFlowController::class, 'store']); 
Route::post('/reservations', [ReservationController::class, 'store']);

Route::post('/transactions/{id}/cancel', [TransactionController::class, 'cancel']);