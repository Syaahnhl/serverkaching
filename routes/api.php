<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// ==============================================================================
//  IMPORT CONTROLLER
// ==============================================================================
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\CashFlowController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES (Bisa diakses tanpa Login)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// Debugging Tool (Reset Status Harian KDS)
Route::get('/fix-kds', function() {
    DB::table('transaction_items')
        ->whereDate('created_at', Carbon::today())
        ->update(['status' => 'Served']);
        
    DB::table('transactions')
        ->whereDate('created_at', Carbon::today())
        ->where('status', 'Proses')
        ->update(['status' => 'Served']);
    return "KDS Berhasil Dibersihkan! Silakan mulai order baru.";
});

/*
|--------------------------------------------------------------------------
| 2. PRIVATE ROUTES (Harus Login / Punya Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Cek User Login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // --- DASHBOARD ---
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // --- SETTINGS TOKO ---
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);

    // --- SHIFT ---
    Route::post('/shift/open', [ShiftController::class, 'openShift']);
    Route::post('/shift/close', [ShiftController::class, 'closeShift']);
    Route::post('/shift/upload-report', [ShiftController::class, 'uploadReport']);
    Route::get('/shift/history', [ShiftController::class, 'getHistory']);

    // --- TRANSACTIONS ---
    Route::get('/transactions/sync', [TransactionController::class, 'apiSync']); // Sync data antar HP
    Route::get('/transactions', [TransactionController::class, 'index']); // Lihat history
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/{id}/cancel', [TransactionController::class, 'cancel']);
    Route::post('/transactions/{id}/complete', [TransactionController::class, 'complete']);
    Route::post('/transactions/{id}/refund-item', [TransactionController::class, 'refundItem']);
    Route::post('/transactions/{id}/view-mode', [TransactionController::class, 'toggleKdsViewMode']);

    // --- MENUS ---
    Route::get('/menus', [MenuController::class, 'index']);
    Route::post('/menus', [MenuController::class, 'store']);
    
    // [KOREKSI KRITIS] Gunakan POST untuk Update agar upload gambar (Multipart) berjalan lancar
    // Android tetap mengirim field _method: PUT, tapi route harus POST
    Route::put('/menus/{id}', [MenuController::class, 'update']);
    
    Route::post('/menus/{id}/stock', [MenuController::class, 'updateStock']);
    Route::delete('/menus/{id}', [MenuController::class, 'destroy']);

    // --- KITCHEN (KDS) ---
    Route::get('/kitchen/orders', [TransactionController::class, 'getKitchenOrders']); 
    Route::post('/kitchen/orders/{id}/done', [TransactionController::class, 'markAsServed']);
    Route::post('kitchen/items/{id}/status', [KitchenController::class, 'updateItemStatus']);
    Route::post('kitchen/items/{id}/increment', [KitchenController::class, 'incrementItemQty']);

    // --- TABLES ---
    Route::get('/tables', [TableController::class, 'index']);
    Route::post('/tables', [TableController::class, 'store']);
    Route::delete('/tables/{id}', [TableController::class, 'destroy']); // DELETE method untuk hapus
    // Route khusus untuk update/rename area (pake POST aman)
    Route::post('tables/delete-area', [TableController::class, 'deleteArea']);
    Route::post('tables/rename-area', [TableController::class, 'renameArea']);
    
    // [BARU] Route Update Status (Kosongkan Meja)
    Route::post('/tables/{id}/status', [TableController::class, 'updateStatus']); // <--- TAMBAHKAN INI

    // Jika ada update meja spesifik
    Route::put('/tables/{id}', [TableController::class, 'update']); // Update meja (number/area)

    // --- EXPENSES ---
    Route::get('/expenses', [ExpenseController::class, 'index']); // [CHECK] Sudah ada GET
    Route::post('/expenses', [ExpenseController::class, 'store']);

    // --- CASH FLOW & RESERVATION ---
    Route::get('/cash-flows', [CashFlowController::class, 'index']); // [CHECK] Sudah ada GET
    Route::post('/cash-flows', [CashFlowController::class, 'store']); 
    
    Route::get('/reservations', [ReservationController::class, 'index']); // [CHECK] Sudah ada GET
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::post('/reservations/{id}/status', [ReservationController::class, 'updateStatus']);
    Route::post('/reservations/{id}/update-notes', [ReservationController::class, 'updateNotes']);
    Route::delete('/reservations/{id}', [ReservationController::class, 'destroy']);

    // --- ANALYSIS ---
    Route::get('/analysis/menu-performance', [AnalysisController::class, 'getMenuAnalysis']);
    
    // --- LOGOUT ---
    Route::post('/logout', [AuthController::class, 'logout']);

});