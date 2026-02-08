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
    public function index(Request $request)
    {
        $userId = Auth::id(); // [SaaS]

        // A. Mulai Query Dasar
        $query = DB::table('tables')->where('user_id', $userId);

        // --- [LOGIC BARU: DUAL MODE] ---
        
        // 1. Filter Area (Khusus Mode Classic / Tab View)
        // Contoh Request: GET /api/tables?area=Indoor Utama
        if ($request->has('area') && $request->area != 'Semua') {
            $query->where('area', $request->area);
        }

        // 2. Filter Status (Khusus Mode Fast Casual / Popup)
        // Contoh Request: GET /api/tables?status=available
        if ($request->has('status') && $request->status == 'available') {
            $query->where('is_occupied', 0);
        }

        // -------------------------------

        // B. Eksekusi Query & Sorting
        // Kita pakai orderByRaw agar angka '10' tidak dianggap lebih kecil dari '2' (String sorting issue)
        $tables = $query->orderByRaw('CAST(number AS UNSIGNED) ASC')->get();

        // C. (OPSIONAL) Logic Cek Transaksi Aktif 
        // Jika sistemmu update kolom 'is_occupied' secara real-time saat checkout, 
        // maka bagian "Ambil Transaksi Aktif" yang lama sebenarnya BISA DIHAPUS agar performa lebih cepat.
        // Tapi jika mau double-check, bisa ditaruh di sini (saya skip agar query ringan untuk popup).

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