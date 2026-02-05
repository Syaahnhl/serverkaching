<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- GROUP 1: Controller di dalam folder "App/Http/Controllers/Api" ---
use App\Http\Controllers\Api\AuthController;       
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\AnalysisController;

// --- GROUP 2: Controller di dalam folder "App/Http/Controllers" (Luar) ---
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\KitchenController; 
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\SettingController;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// --- AUTH (REGISTER, LOGIN, OTP) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// --- SHIFT ---
Route::post('/shift/open', [ShiftController::class, 'openShift']);
Route::post('/shift/close', [ShiftController::class, 'closeShift']);
Route::post('/shift/upload-report', [ShiftController::class, 'uploadReport']);
Route::get('/shift/history', [ShiftController::class, 'getHistory']);

// --- TRANSACTIONS ---
Route::get('/transactions', [TransactionController::class, 'apiSync']); 
Route::post('/transactions', [TransactionController::class, 'store']);
Route::post('/transactions/{id}/cancel', [TransactionController::class, 'cancel']);
Route::post('/transactions/{id}/complete', [TransactionController::class, 'complete']);

// --- MENUS ---
Route::get('/menus', [MenuController::class, 'index']);
Route::post('/menus', [MenuController::class, 'store']);
Route::post('/menus/{id}/stock', [MenuController::class, 'updateStock']);

// --- KITCHEN ---
Route::get('/kitchen/orders', [KitchenController::class, 'index']);
Route::post('/kitchen/orders/{id}/done', [KitchenController::class, 'markAsDone']);

// --- TABLES ---
Route::get('/tables', [TableController::class, 'index']);

// --- EXPENSES ---
Route::get('/expenses', [ExpenseController::class, 'index']); 
Route::post('/expenses', [ExpenseController::class, 'store']);

// --- CASH FLOW & RESERVATION ---
Route::get('/cash-flows', [CashFlowController::class, 'index']); 
Route::post('/cash-flows', [CashFlowController::class, 'store']); 
Route::post('/reservations', [ReservationController::class, 'store']);

// --- SETTINGS TOKO ---
// [PERBAIKAN] Hapus "App\Http\Controllers\" karena sudah di-import di atas
Route::get('/settings', [SettingController::class, 'index']);
Route::post('/settings', [SettingController::class, 'update']);

// --- ANALYSIS ---
Route::get('/analysis/menu-performance', [AnalysisController::class, 'getMenuAnalysis']);


// --- DEBUGGING TOOL (Pembersih KDS) ---
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