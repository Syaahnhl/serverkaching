<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CashFlowController; // <-- Jangan lupa import
use App\Http\Controllers\ReservationController;

// Saat buka halaman utama (localhost:8000), langsung panggil fungsi dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::resource('menus', MenuController::class);
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
Route::get('/cash-flows', [CashFlowController::class, 'webIndex'])->name('cash_flows.index');
Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');