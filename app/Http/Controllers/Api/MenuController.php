<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace benar

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu; // [FIX] Gunakan Model Menu
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // Tambahan biar rapi pakai Auth::id()

class MenuController extends Controller // [FIX] Otomatis baca Controller di folder yang sama
{
    // 1. FUNGSI UTAMA: Menampilkan Data (Hanya Punya User Login)
    public function index()
    {
        $userId = Auth::id();

        $menus = Menu::where('user_id', $userId)
                     ->where('is_available', 1)
                     ->orderBy('name', 'asc')
                     ->get();
        
        // [FIX] Transformasi URL Gambar agar Android bisa load
        $menus->transform(function ($menu) {
            if ($menu->image_url) {
                // Cek apakah sudah ada http/https (biar ga double)
                if (!str_starts_with($menu->image_url, 'http')) {
                    $menu->image_url = asset('storage/' . $menu->image_url);
                }
            }
            return $menu;
        });

        return response()->json([
            'status' => 'success', 
            'data' => $menus
        ], 200);
    }

    // 2. SIMPAN MENU BARU
    public function store(Request $request)
    {
        // 1. Validasi
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'category' => 'required|string',
            'unit' => 'required|string', // [PENTING] Validasi Unit
            'price' => 'required|numeric',
            'cost_price' => 'required|numeric',
            'stock' => 'required|numeric', // Bisa desimal (0.5)
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Upload Gambar
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Simpan path relatif: "menus/namafile.jpg"
            $imagePath = $request->file('image')->store('menus', 'public');
        }

        // 3. Simpan ke Database
        $menu = Menu::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'cost_price' => $request->cost_price,
            'stock' => $request->stock,
            'has_variant' => filter_var($request->has_variant, FILTER_VALIDATE_BOOLEAN),
            'unit' => $request->unit, // [FIX] Simpan Unit
            'is_kds' => filter_var($request->input('is_kds', true), FILTER_VALIDATE_BOOLEAN),
            'description' => $request->description,
            'image_url' => $imagePath, // Simpan path relatif di DB
            'is_available' => true,
        ]);

        // [FIX] Ubah path menjadi Full URL khusus untuk Response JSON ini
        // Agar Android langsung bisa menampilkan gambar tanpa refresh
        if ($menu->image_url) {
            $menu->image_url = asset('storage/' . $menu->image_url);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Menu berhasil ditambahkan',
            'data' => $menu
        ], 201);
    }

    // 3. HAPUS MENU
    public function destroy($id)
    {
        // [SaaS] Cari menu berdasarkan ID DAN User ID
        $menu = Menu::where('id', $id)
                    ->where('user_id', Auth::id()) // <--- Cegah hapus punya orang
                    ->first();

        if (!$menu) {
            return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan'], 404);
        }

        // Hapus gambar fisik
        if ($menu->image_url) {
            Storage::disk('public')->delete($menu->image_url);
        }
        
        $menu->delete();

        return response()->json(['status' => 'success', 'message' => 'Menu berhasil dihapus'], 200);
    }

    // 4. UPDATE STOK
    public function updateStock(Request $request, $id)
    {
        // [FIX] Tambahkan Validasi
        $validator = Validator::make($request->all(), [
            'stock' => 'required|numeric' // Wajib angka/desimal
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Stok harus berupa angka'], 422);
        }

        // Cari menu milik user
        $menu = Menu::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->first();

        if (!$menu) {
            return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan'], 404);
        }

        // Update
        $menu->update([
            'stock' => $request->stock
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stok berhasil diupdate',
            'data' => [
                'id' => $menu->id, 
                'new_stock' => (double)$menu->stock // Cast ke double biar aman
            ]
        ], 200);
    }
}