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
        
        // [UPDATE] Tambahkan where status != Batal agar omset bersih
        $todayOmset = DB::table('transactions')
                        ->whereDate('created_at', $today)
                        ->where('status', '!=', 'Batal') 
                        ->sum('total_amount') ?? 0;
                        
        // [UPDATE] Tambahkan where status != Batal agar jumlah struk valid
        $todayCount = DB::table('transactions')
                        ->whereDate('created_at', $today)
                        ->where('status', '!=', 'Batal')
                        ->count();

        // 2. CEK STOK MENIPIS (Tidak ada perubahan)
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
            
            // [UPDATE] Filter grafik juga agar tidak lonjakan palsu
            $sum = DB::table('transactions')
                ->whereDate('created_at', $date)
                ->where('status', '!=', 'Batal')
                ->sum('total_amount') ?? 0;
                
            $chartData[] = $sum;
        }

        // 4. DAFTAR TRANSAKSI TERBARU
        // (Sengaja tidak difilter 'Batal', agar Admin tetap tahu ada aktivitas pembatalan di tabel history)
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