<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth
use Carbon\Carbon;

class ReportController extends Controller
{
    // 1. LAPORAN RINGKASAN (Untuk Tampil di HP Android)
    public function index(Request $request)
    {
        $userId = Auth::id(); // [SaaS] Ambil ID User

        // Default tanggal: Awal bulan ini s/d Hari ini
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // A. Total Penjualan Kotor (Gross Sales)
        $grossSales = DB::table('transactions')
            ->where('user_id', $userId) // [SaaS]
            ->whereBetween('created_at_device', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', '!=', 'Batal')
            ->sum('total_amount');

        // B. Total Transaksi (Jumlah Struk)
        $trxCount = DB::table('transactions')
            ->where('user_id', $userId) // [SaaS]
            ->whereBetween('created_at_device', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', '!=', 'Batal')
            ->count();

        // C. Total Pengeluaran (Expense)
        $totalExpense = DB::table('expenses')
            ->where('user_id', $userId) // [SaaS]
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // D. Hitung HPP (Modal Barang Terjual)
        // Agar laba bersih akurat: (Jual - Modal) - Pengeluaran
        $totalCOGS = DB::table('transaction_items as ti')
            ->join('transactions as t', 'ti.transaction_id', '=', 't.id')
            ->join('menus as m', 'ti.menu_name', '=', 'm.name')
            ->where('t.user_id', $userId) // [SaaS] Filter User
            ->where('m.user_id', $userId) // [SaaS] Pastikan menu punya user ini
            ->whereBetween('t.created_at_device', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('t.status', '!=', 'Batal')
            ->sum(DB::raw('ti.qty * m.cost_price'));

        // E. Laba Bersih
        $netProfit = $grossSales - $totalCOGS - $totalExpense;

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => "$startDate s/d $endDate",
                'gross_sales' => (double)$grossSales,
                'trx_count' => (int)$trxCount,
                'total_expense' => (double)$totalExpense,
                'total_cogs' => (double)$totalCOGS, // Total Modal
                'net_profit' => (double)$netProfit
            ]
        ], 200);
    }

    // 2. EXPORT KE EXCEL/CSV (Aman SaaS)
    public function export(Request $request)
    {
        $userId = Auth::id(); // [SaaS]
        
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Ambil Data Transaksi Milik User Ini
        $transactions = DB::table('transactions')
            ->where('user_id', $userId) // [SaaS] Filter User Wajib
            ->whereDate('created_at_device', '>=', $startDate)
            ->whereDate('created_at_device', '<=', $endDate)
            ->where('status', '!=', 'Batal')
            ->orderBy('created_at_device', 'asc')
            ->get();

        $filename = "Laporan_$startDate-sd-$endDate.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Header Kolom Excel
            fputcsv($file, ['No', 'Tanggal', 'Jam', 'ID Struk', 'Kasir', 'Meja', 'Metode Bayar', 'Total (Rp)']);

            $no = 1;
            foreach ($transactions as $row) {
                fputcsv($file, [
                    $no++,
                    Carbon::parse($row->created_at_device)->format('d-m-Y'),
                    Carbon::parse($row->created_at_device)->format('H:i'),
                    '#' . $row->id,
                    $row->cashier_name ?? 'Owner',
                    $row->table_number ?? 'Take Away',
                    $row->payment_method,
                    $row->total_amount
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}