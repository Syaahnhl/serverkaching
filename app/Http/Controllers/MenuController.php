<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    // 1. FUNGSI UTAMA: Menampilkan Data (Hybrid + Gambar)
    public function index()
    {
        // A. API (HP Android)
        if (request()->wantsJson() || request()->is('api/*')) {
            $menus = DB::table('menus')->where('is_available', 1)->get();
            
            // [PENTING] Ubah path gambar jadi URL Lengkap
            $menus->transform(function ($menu) {
                if ($menu->image_url) {
                    if (!str_starts_with($menu->image_url, 'http')) {
                        $menu->image_url = asset('storage/' . $menu->image_url);
                    }
                }
                return $menu;
            });

            // Format sesuai MenuRepository Android
            return response()->json([
                'status' => 'success', 
                'data' => $menus
            ], 200);
        }

        // B. WEB ADMIN
        $menus = DB::table('menus')->orderBy('created_at', 'desc')->get();
        return view('menus.index', ['menus' => $menus]);
    }

    // 2. SIMPAN MENU (Hybrid: Web & Android)
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'category' => 'required',
            'price' => 'required|numeric',
            'cost_price' => 'required|numeric',
            'stock' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Jika Validasi Gagal & Request dari Android -> Return JSON Error
        if ($validator->fails()) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // 2. LOGIKA UPLOAD GAMBAR
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menus', 'public');
        }

        // 3. Simpan ke Database & AMBIL ID-NYA (insertGetId)
        // Kita butuh ID baru ini untuk dikirim balik ke Android
        $newId = DB::table('menus')->insertGetId([
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'cost_price' => $request->cost_price,
            'stock' => $request->stock,
            // Handle boolean dari Android (kadang dikirim string "1" atau "true")
            'has_variant' => filter_var($request->has_variant, FILTER_VALIDATE_BOOLEAN),
            'description' => $request->description,
            'image_url' => $imagePath,
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // --- SKENARIO 1: REQUEST DARI ANDROID (API) ---
        if ($request->wantsJson() || $request->is('api/*')) {
            // Ambil data menu yang barusan dibuat
            $newMenu = DB::table('menus')->where('id', $newId)->first();
            
            // Format URL Gambar agar Android bisa baca
            if ($newMenu->image_url) {
                $newMenu->image_url = asset('storage/' . $newMenu->image_url);
            }

            // Return JSON Sukses (Android akan simpan data ini ke Room)
            return response()->json([
                'status' => 'success',
                'message' => 'Menu berhasil ditambahkan',
                'data' => $newMenu
            ], 200);
        }

        // --- SKENARIO 2: REQUEST DARI WEB ADMIN ---
        return redirect()->back()->with('success', 'Menu berhasil ditambahkan!');
    }

    // 3. HAPUS MENU
    public function destroy($id)
    {
        $menu = DB::table('menus')->where('id', $id)->first();

        if ($menu) {
            if ($menu->image_url) {
                Storage::disk('public')->delete($menu->image_url);
            }
            DB::table('menus')->where('id', $id)->delete();
        }

        // Support API Delete juga jika nanti dibutuhkan
        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json(['status' => 'success', 'message' => 'Menu dihapus'], 200);
        }

        return redirect()->back()->with('success', 'Menu dihapus!');
    }

    // [BARU] FUNGSI UPDATE STOK DARI ANDROID
    public function updateStock(Request $request, $id)
    {
        // 1. Cari Menu berdasarkan ID
        $menu = DB::table('menus')->where('id', $id)->first();

        if (!$menu) {
            return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan'], 404);
        }

        // 2. Update Stok
        DB::table('menus')->where('id', $id)->update([
            'stock' => $request->stock,
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stok berhasil diupdate',
            'data' => ['id' => $id, 'new_stock' => $request->stock]
        ], 200);
    }
}