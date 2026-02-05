<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // [WAJIB] Tambah ini

class AnalysisController extends Controller
{
    public function getMenuAnalysis(Request $request)
    {
        // 1. Ambil Input Tanggal (Default: Hari Ini)
        $startDate = $request->input('start_date', date('Y-m-d'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        
        // [SaaS] Ambil User ID
        $userId = Auth::id();

        // 2. Query Data Mentah (Update SaaS)
        // Kita perlu JOIN ke tabel MENUS untuk mengambil cost_price (Modal)
        $rawStats = DB::table('transaction_items as ti')
            ->join('transactions as t', 'ti.transaction_id', '=', 't.id')
            // [FIX] Join Menu spesifik punya User ini
            // (Mencegah salah ambil modal dari "Nasi Goreng" milik toko sebelah)
            ->join('menus as m', function($join) use ($userId) {
                $join->on('ti.menu_name', '=', 'm.name')
                     ->where('m.user_id', '=', $userId);
            }) 
            ->select(
                'm.name',
                'm.price as current_selling_price',
                'm.cost_price', // Kita butuh ini untuk hitung margin
                DB::raw('SUM(ti.qty) as total_qty'),
                DB::raw('COUNT(ti.id) as popularity') // Frekuensi muncul di struk
            )
            // [FIX] Filter hanya transaksi milik toko ini
            ->where('t.user_id', $userId)
            ->whereBetween('t.created_at_device', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('t.status', '!=', 'Batal') // Abaikan transaksi batal
            ->groupBy('m.name', 'm.price', 'm.cost_price')
            ->get();

        if ($rawStats->isEmpty()) {
            return response()->json([
                'status' => 'success', // Tetap success tapi data kosong
                'data' => []
            ]);
        }

        // 3. Persiapan Variabel Hitungan
        $totalMenus = $rawStats->count();
        $sumQty = 0;
        $sumProfit = 0;
        
        $matrix = [];
        $maxQty = 0;
        $maxProfit = 0;
        $maxPopularity = 0;

        // Loop 1: Hitung Total & Cari Nilai Max (Untuk Normalisasi SAW)
        foreach ($rawStats as $item) {
            // Hitung Margin Real (Harga Jual - Modal)
            $marginPerUnit = $item->current_selling_price - $item->cost_price;
            // Total Profit = Margin x Qty Terjual
            $totalProfit = $marginPerUnit * $item->total_qty;

            // Akumulasi untuk Rata-rata
            $sumQty += $item->total_qty;
            $sumProfit += $totalProfit;

            // Cari Max Value (Agar rumus SAW tidak error bagi 0)
            if ($item->total_qty > $maxQty) $maxQty = $item->total_qty;
            if ($totalProfit > $maxProfit) $maxProfit = $totalProfit;
            if ($item->popularity > $maxPopularity) $maxPopularity = $item->popularity;

            $matrix[] = [
                'name' => $item->name,
                'qty' => $item->total_qty,
                'profit' => $totalProfit,
                'popularity' => $item->popularity
            ];
        }

        // Hitung Rata-rata (Threshold untuk Kuadran)
        $avgQty = ($totalMenus > 0) ? $sumQty / $totalMenus : 0;
        $avgProfit = ($totalMenus > 0) ? $sumProfit / $totalMenus : 0;

        // 4. PROSES SAW & LABELING
        // Bobot: Qty (40%), Profit (40%), Popularitas (20%)
        $w1 = 0.4; 
        $w2 = 0.4; 
        $w3 = 0.2;

        $results = [];

        foreach ($matrix as $row) {
            // A. Labeling Kuadran (Menu Engineering)
            $isHighQty = $row['qty'] >= $avgQty;
            $isHighProfit = $row['profit'] >= $avgProfit;

            if ($isHighQty && $isHighProfit) {
                $category = "STAR";
                $action = "🔥 Menu Juara! Pertahankan stok & kualitas.";
            } elseif ($isHighQty && !$isHighProfit) {
                $category = "CASH_COW";
                $action = "💡 Laris tapi margin tipis. Coba naikkan harga/bundling.";
            } elseif (!$isHighQty && $isHighProfit) {
                $category = "PUZZLE";
                $action = "📢 Untung besar! Genjot promosi di Medsos.";
            } else {
                $category = "DOG";
                $action = "⚠️ Beban toko. Pertimbangkan hapus/ganti resep.";
            }

            // B. Hitung Skor SAW
            // Normalisasi: Nilai / NilaiMax
            $normQty = ($maxQty > 0) ? $row['qty'] / $maxQty : 0;
            $normProfit = ($maxProfit > 0) ? $row['profit'] / $maxProfit : 0;
            $normPop = ($maxPopularity > 0) ? $row['popularity'] / $maxPopularity : 0;

            // Rumus SAW Final
            $finalScore = ($normQty * $w1) + ($normProfit * $w2) + ($normPop * $w3);

            // Format Angka Duit
            $profitFormatted = number_format($row['profit'], 0, ',', '.');

            $results[] = [
                'name' => $row['name'],
                'category' => $category,
                'score' => round($finalScore, 2), // Pembulatan 2 desimal
                'stats' => "{$row['qty']} Terjual • Profit Rp {$profitFormatted}",
                'action' => $action
            ];
        }

        // 5. Urutkan Ranking (Score Tertinggi di atas)
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return response()->json([
            'status' => 'success',
            'data' => $results
        ]);
    }
}