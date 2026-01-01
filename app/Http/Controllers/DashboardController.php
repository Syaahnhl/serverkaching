<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. HITUNG OMSET & TRANSAKSI HARI INI
        $today = Carbon::today();
        
        $todayOmset = DB::table('transactions')
                        ->whereDate('created_at', $today)
                        ->sum('total_amount') ?? 0;
                        
        $todayCount = DB::table('transactions')
                        ->whereDate('created_at', $today)
                        ->count();

        // 2. CEK STOK MENIPIS
        $lowStockMenus = DB::table('menus')
            ->where('stock', '<', 10)
            ->where('stock', '!=', -1)
            ->get();

        // 3. DATA GRAFIK 7 HARI TERAKHIR
        $chartLabels = [];
        $chartData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d M');
            
            $sum = DB::table('transactions')
                ->whereDate('created_at', $date)
                ->sum('total_amount') ?? 0;
                
            $chartData[] = $sum;
        }

        // 4. DAFTAR TRANSAKSI TERBARU
        $recentTransactions = DB::table('transactions')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'todayOmset' => $todayOmset,
            'todayCount' => $todayCount,
            'lowStockMenus' => $lowStockMenus,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'recentTransactions' => $recentTransactions
        ]);
    }
}