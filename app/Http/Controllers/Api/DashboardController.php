<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 0. Ambil ID User yang Login
        $userId = Auth::id();

        // 1. HITUNG OMSET & TRANSAKSI HARI INI (Milik User Ini)
        $today = Carbon::today();
        
        $todayOmset = DB::table('transactions')
                        ->where('user_id', $userId) // [SaaS]
                        ->whereDate('created_at_device', $today) // Gunakan waktu device biar akurat
                        ->where('status', '!=', 'Batal') 
                        ->sum('total_amount') ?? 0;
                        
        $todayCount = DB::table('transactions')
                        ->where('user_id', $userId) // [SaaS]
                        ->whereDate('created_at_device', $today)
                        ->where('status', '!=', 'Batal')
                        ->count();

        // 2. CEK STOK MENIPIS (Milik User Ini)
        $lowStockMenus = DB::table('menus')
            ->where('user_id', $userId) // [SaaS]
            ->where('stock', '<', 10)
            ->where('stock', '!=', -1) // -1 biasanya untuk stok unlimited
            ->limit(5) // Ambil 5 saja biar ringan
            ->get();

        // 3. DATA GRAFIK 7 HARI TERAKHIR (Milik User Ini)
        $chartLabels = [];
        $chartData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d M');
            
            $sum = DB::table('transactions')
                ->where('user_id', $userId) // [SaaS]
                ->whereDate('created_at_device', $date)
                ->where('status', '!=', 'Batal')
                ->sum('total_amount') ?? 0;
                
            $chartData[] = $sum;
        }

        // 4. DAFTAR TRANSAKSI TERBARU (Milik User Ini)
        $recentTransactions = DB::table('transactions')
            ->where('user_id', $userId) // [SaaS]
            ->orderBy('created_at_device', 'desc')
            ->limit(5)
            ->get();

        // [FIX] Return JSON (Karena ini API untuk Android)
        return response()->json([
            'status' => 'success',
            'data' => [
                'today_omset' => $todayOmset,
                'today_count' => $todayCount,
                'low_stock_menus' => $lowStockMenus,
                'chart' => [
                    'labels' => $chartLabels,
                    'data' => $chartData
                ],
                'recent_transactions' => $recentTransactions
            ]
        ], 200);
    }
}