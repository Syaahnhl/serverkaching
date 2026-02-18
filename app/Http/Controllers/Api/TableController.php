<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use App\Models\Table;

class TableController extends Controller
{
    // 1. AMBIL STATUS MEJA
    public function index(Request $request)
    {
        $userId = Auth::id();
        $query = Table::where('user_id', $userId);

        // [Penyempurnaan] Menggunakan filled() untuk cek key & value sekaligus
        if ($request->filled('area') && $request->area != 'Semua') {
            $query->where('area', $request->area);
        }

        if ($request->has('status') && $request->status == 'available') {
            $query->where('is_occupied', 0);
        }

        // CAST agar urutan: 1, 2, 3... 10, 11 (bukan urutan abjad)
        $tables = $query->orderByRaw('CAST(number AS UNSIGNED) ASC')->get();

        return response()->json([
            'status' => 'success',
            // [TAMBAHAN DEBUG] Kirim ID user ke Android
            // Cek Logcat Android nanti: apakah debug_user_id sama dengan user_id di phpMyAdmin?
            'debug_user_id' => $userId, 
            'total_tables' => $tables->count(),
            'data' => $tables
        ], 200);
    }

    // 2. TAMBAH MEJA BARU
    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|string|max:30',
            'area'   => 'nullable|string'
        ]);

        $userId = Auth::id();

        // Cek duplikasi nomor meja di toko yang sama
        $exists = Table::where('user_id', $userId)
                    ->where('number', $request->number)
                    ->exists();

        if ($exists) {
            return response()->json(['status' => 'error', 'message' => 'Nomor meja ini sudah digunakan.'], 400);
        }

        $table = Table::create([
            'user_id' => $userId,
            'number' => $request->number,
            'area' => $request->area ?? 'Main Area',
            'is_occupied' => false
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Meja berhasil ditambahkan',
            'data' => $table
        ], 201);
    }

    // 3. UPDATE MEJA (Nomor & Area)
    public function update(Request $request, $id)
    {
        $userId = Auth::id();
        $table = Table::where('id', $id)->where('user_id', $userId)->first();

        if (!$table) return response()->json(['status' => 'error', 'message' => 'Meja tidak ditemukan'], 404);

        $request->validate([
            'number' => 'required|string|max:30',
            'area' => 'required|string'
        ]);

        // Cek duplikasi dengan mengecualikan meja yang sedang diedit
        $duplicate = Table::where('user_id', $userId)
                        ->where('number', $request->number)
                        ->where('id', '!=', $id)
                        ->exists();

        if ($duplicate) {
            return response()->json(['status' => 'error', 'message' => 'Nomor meja sudah digunakan.'], 400);
        }

        $table->update([
            'number' => $request->number,
            'area' => $request->area
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Meja berhasil diperbarui',
            'data' => $table
        ]);
    }

    // 4. HAPUS MEJA
    public function destroy($id)
    {
        $table = Table::where('id', $id)->where('user_id', Auth::id())->first();
        
        if (!$table) return response()->json(['status' => 'error', 'message' => 'Meja tidak ditemukan'], 404);

        // [FIX] KITA KOMENTARI/HAPUS BAGIAN INI BIAR BISA PAKSA HAPUS
        // if ($table->is_occupied) {
        //    return response()->json(['status' => 'error', 'message' => 'Meja sedang digunakan...'], 400);
        // }

        $table->delete();
        return response()->json(['status' => 'success', 'message' => 'Meja berhasil dihapus']);
    }

    // 5. UPDATE STATUS MEJA (Manual/Internal)
    public function updateStatus(Request $request, $id)
    {
        // [FIX 1] Paksa casting ID dan Status ke Integer
        $tableId = (int)$id; 
        $userId = Auth::id();
        $isOccupied = (int)$request->is_occupied; // Pastikan jadi angka 0 atau 1

        // [FIX 2] LOGIKA ACTIVE TRX ID (Karena kolomnya SUDAH ADA sekarang)
        // Jika meja dikosongkan (0) -> Set active_trx_id jadi NULL (Biar bersih).
        // Jika meja diisi (1) -> Biarkan active_trx_id tetap seperti nilai lamanya (DB::raw).
        $activeTrxValue = ($isOccupied === 0) ? null : DB::raw('active_trx_id');

        // Gunakan DB::table agar lebih aman dan cepat
        $affected = DB::table('tables')
            ->where('id', $tableId)
            ->where('user_id', $userId)
            ->update([
                'is_occupied' => $isOccupied,
                'active_trx_id' => $activeTrxValue, // <--- BARIS INI DIAKTIFKAN KEMBALI
                'updated_at' => now()
            ]);

        if ($affected) {
            return response()->json([
                'status' => 'success',
                'message' => 'Status meja berhasil diupdate.'
            ]);
        }

        // Cek apakah data sebenarnya sudah sesuai? (Misal server sudah 0, dikirim 0 lagi)
        $exists = DB::table('tables')->where('id', $tableId)->where('user_id', $userId)->exists();
        
        if ($exists) {
             return response()->json(['status' => 'success', 'message' => 'Data sudah sesuai (No changes)']);
        }

        return response()->json([
            'status' => 'error', 
            'message' => "Gagal update. Meja ID $tableId tidak ditemukan."
        ], 404);
    }

    // [BARU] 6. HAPUS SATU AREA (Hapus semua meja di area ini)
    public function deleteArea(Request $request)
    {
        $request->validate(['area' => 'required|string']);
        $userId = Auth::id();

        // Hapus semua meja yang punya nama area ini
        $deleted = Table::where('user_id', $userId)
                        ->where('area', $request->area)
                        ->delete();

        if ($deleted) {
            return response()->json(['status' => 'success', 'message' => "Area '$request->area' dan $deleted meja didalamnya berhasil dihapus."]);
        }

        return response()->json(['status' => 'error', 'message' => 'Area tidak ditemukan'], 404);
    }

    public function renameArea(Request $request)
    {
        $request->validate([
            'old_name' => 'required|string',
            'new_name' => 'required|string|max:50'
        ]);

        $userId = Auth::id();

        // Update semua meja yang punya area lama menjadi area baru
        $affected = Table::where('user_id', $userId)
                         ->where('area', $request->old_name)
                         ->update(['area' => $request->new_name]);

        if ($affected > 0) {
            return response()->json([
                'status' => 'success', 
                'message' => "Berhasil mengubah area menjadi '$request->new_name'."
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Area tidak ditemukan'], 404);
    }
}