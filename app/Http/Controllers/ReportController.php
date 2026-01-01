<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    // 1. TAMPILKAN HALAMAN LAPORAN (FILTER TANGGAL)
    public function index(Request $request)
    {
        // Default tanggal: Awal bulan ini sampai Hari ini
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // A. AMBIL DATA TRANSAKSI (PEMASUKAN)
        $transactions = DB::table('transactions')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at', 'desc')
            ->get();

        // B. [BARU] AMBIL DATA PENGELUARAN
        $expenses = DB::table('expenses')
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->orderBy('date', 'desc')
            ->get();

        // C. HITUNG-HITUNGAN LABA RUGI
        $totalOmset = $transactions->sum('total_amount');
        $totalPengeluaran = $expenses->sum('amount'); // Hitung total pengeluaran
        $labaBersih = $totalOmset - $totalPengeluaran; // Rumus Laba Bersih
        $totalTransaksi = $transactions->count();

        // Kirim semua data ke View
        return view('reports.index', [
            'transactions' => $transactions,
            'expenses' => $expenses,           // Data list pengeluaran
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalOmset' => $totalOmset,
            'totalPengeluaran' => $totalPengeluaran, // Total pengeluaran
            'labaBersih' => $labaBersih,       // Total laba bersih
            'totalTransaksi' => $totalTransaksi
        ]);
    }

    // 2. EXPORT KE EXCEL (FORMAT CSV) - Masih khusus Transaksi
    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $transactions = DB::table('transactions')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at', 'asc')
            ->get();

        $filename = "Laporan_Keuangan_$startDate-sd-$endDate.csv";

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
            fputcsv($file, ['No', 'Tanggal', 'Jam', 'ID Transaksi', 'Pelanggan', 'Meja', 'Metode Bayar', 'Total (Rp)']);

            $no = 1;
            foreach ($transactions as $row) {
                fputcsv($file, [
                    $no++,
                    Carbon::parse($row->created_at)->format('d-m-Y'),
                    Carbon::parse($row->created_at)->format('H:i'),
                    '#' . $row->id,
                    $row->cashier_name ?? 'Umum',
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