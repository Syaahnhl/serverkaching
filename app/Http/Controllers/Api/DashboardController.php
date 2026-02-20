<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // [FIX UTAMA] Cek Shift Aktif
        $activeShift = DB::table('shifts')
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->first();

        // JIKA TIDAK ADA SHIFT BUKA (TUTUP TOKO) -> KIRIM 0 SEMUA
        if (!$activeShift) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'today_omset' => 0,
                    'today_count' => 0,
                    'low_stock_menus' => [], // Boleh tetap kirim stok atau kosong
                    'chart' => ['labels' => [], 'data' => []],
                    'recent_transactions' => []
                ]
            ], 200);
        }

        // JIKA SHIFT BUKA -> Hitung Data SEJAK JAM BUKA SHIFT (Bukan sejak 00:00)
        $startTime = $activeShift->start_time;

        $todayOmset = (int) DB::table('transactions')
            ->where('user_id', $userId)
            ->where('created_at_device', '>=', $startTime) // [FIX] Filter Waktu Shift
            ->where('status', '!=', 'Batal')
            ->sum('pay_amount');

        $todayCount = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('created_at_device', '>=', $startTime) // [FIX] Filter Waktu Shift
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