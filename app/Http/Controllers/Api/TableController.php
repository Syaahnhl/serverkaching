<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth
use Carbon\Carbon;
use App\Models\Table; // [FIX] Gunakan Model Table

class TableController extends Controller // [FIX] Otomatis baca Controller di folder yang sama
{
    // 1. API: AMBIL STATUS MEJA (Untuk Halaman Depan Android)
    public function index()
    {
        $userId = Auth::id(); // [SaaS]

        // A. Ambil Daftar Meja Milik User Ini
        $tables = DB::table('tables')
                    ->where('user_id', $userId)
                    ->orderBy('number', 'asc') // Urutkan nomor meja 1, 2, 3...
                    ->get();

        // B. Ambil Transaksi Aktif Hari Ini (Milik User Ini)
        // Logic: Transaksi hari ini yang statusnya BELUM selesai/batal
        $activeTrx = DB::table('transactions')
            ->where('user_id', $userId) // [SaaS] Kunci Keamanan
            ->whereDate('created_at_device', Carbon::today()) // Gunakan waktu device
            ->whereNotIn('status', [
                'done', 
                'Batal', 
                'Cancelled', 
                'Selesai', 
                'Served' // Status ini dianggap meja sudah kosong
            ])
            ->whereNotNull('table_number')
            ->select('id', 'table_number') 
            ->get();

        // C. Gabungkan Data (Cek mana meja yang terisi)
        $tables = $tables->map(function($table) use ($activeTrx) {
            
            // Default: Kita anggap kosong dulu
            $table->is_occupied = 0;
            $table->active_trx_id = null; 

            foreach ($activeTrx as $trx) {
                // Logic regex: Ambil angka dari string (misal "Meja 1" -> "1")
                // Ini berguna kalau kasir input manual "Meja 01" padahal di database "1"
                preg_match('/^\d+/', $trx->table_number, $matches);
                $extractedNum = $matches[0] ?? ''; 

                // Jika Nomor Meja sama dengan Transaksi yang SEDANG AKTIF
                if ($extractedNum === (string)$table->number) {
                    $table->is_occupied = 1; // Paksa jadi Terisi
                    $table->active_trx_id = $trx->id; 
                    break; 
                }
            }

            return $table;
        });

        return response()->json([
            'status' => 'success',
            'data' => $tables
        ], 200);
    }

    // 2. API: TAMBAH MEJA BARU
    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|string', // Ubah jadi string biar fleksibel (misal "A1")
            'area'   => 'nullable|string'  // Tambah kolom Area (Opsional)
        ]);

        $userId = Auth::id();

        // Cek apakah nomor meja sudah ada di toko ini?
        $exists = Table::where('user_id', $userId)
                    ->where('number', $request->number)
                    ->exists();

        if ($exists) {
            return response()->json(['status' => 'error', 'message' => 'Nomor meja ini sudah ada.'], 400);
        }

        // Simpan Meja
        $table = Table::create([
            'user_id' => $userId,
            'number' => $request->number,
            'area' => $request->area ?? 'Main Area', // Default area
            'is_occupied' => false
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Meja berhasil ditambahkan',
            'data' => $table
        ]);
    }

    // 3. API: HAPUS MEJA
    public function destroy($id)
    {
        // Cari meja milik user ini
        $table = Table::where('id', $id)
                    ->where('user_id', Auth::id()) // [SaaS]
                    ->first();

        if ($table) {
            $table->delete();
            return response()->json(['status' => 'success', 'message' => 'Meja dihapus']);
        }

        return response()->json(['status' => 'error', 'message' => 'Meja tidak ditemukan'], 404);
    }

    // 4. (Opsional) API: SET STATUS MANUAL
    public function updateStatus(Request $request, $id)
    {
        DB::table('tables')
            ->where('id', $id)
            ->where('user_id', Auth::id()) // [SaaS]
            ->update(['is_occupied' => $request->is_occupied]);
            
        return response()->json(['status' => 'success']);
    }
}