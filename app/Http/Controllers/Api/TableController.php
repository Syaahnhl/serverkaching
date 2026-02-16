<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $table = Table::where('id', $id)->where('user_id', Auth::id())->first();

        if ($table) {
            $table->update(['is_occupied' => $request->is_occupied]);
            return response()->json(['status' => 'success']);
        }
        
        return response()->json(['status' => 'error'], 404);
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