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
        // Ambil ID User
        $userId = Auth::id();

        // [SaaS] Ambil menu milik user ini saja
        $menus = Menu::where('user_id', $userId)
                     ->where('is_available', 1)
                     ->orderBy('name', 'asc')
                     ->get();
        
        // Transform URL Gambar (Biar muncul di Android)
        $menus->transform(function ($menu) {
            if ($menu->image_url) {
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
            'name' => 'required',
            'category' => 'required',
            'price' => 'required|numeric',
            'cost_price' => 'required|numeric',
            'stock' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Data tidak lengkap',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Upload Gambar
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menus', 'public');
        }

        // 3. Simpan ke Database (SaaS)
        $menu = Menu::create([
            'user_id' => Auth::id(), // <--- KUNCI SAAS (Milik User Login)
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'cost_price' => $request->cost_price,
            'stock' => $request->stock,
            'has_variant' => filter_var($request->has_variant, FILTER_VALIDATE_BOOLEAN),
            'description' => $request->description,
            'image_url' => $imagePath,
            'is_available' => true,
        ]);

        // Fix URL Gambar untuk respon
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
        // [SaaS] Cek kepemilikan
        $menu = Menu::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->first();

        if (!$menu) {
            return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan'], 404);
        }

        $menu->update([
            'stock' => $request->stock
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stok berhasil diupdate',
            'data' => ['id' => $menu->id, 'new_stock' => $menu->stock]
        ], 200);
    }
}