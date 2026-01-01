<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // Tambahan buat hapus gambar

class MenuController extends Controller
{
    // 1. FUNGSI UTAMA: Menampilkan Data (Hybrid + Gambar)
    public function index()
    {
        // A. API (HP Android)
        if (request()->wantsJson() || request()->is('api/*')) {
            $menus = DB::table('menus')->where('is_available', 1)->get();
            
            // [PENTING] Ubah path gambar jadi URL Lengkap (http://192.168.../storage/...)
            // Supaya Android bisa download gambarnya
            $menus->transform(function ($menu) {
                if ($menu->image_url) {
                    // Cek apakah url sudah lengkap atau belum
                    if (!str_starts_with($menu->image_url, 'http')) {
                        $menu->image_url = asset('storage/' . $menu->image_url);
                    }
                }
                return $menu;
            });

            return response()->json(['status' => 'success', 'data' => $menus], 200);
        }

        // B. WEB ADMIN
        $menus = DB::table('menus')->orderBy('created_at', 'desc')->get();
        return view('menus.index', ['menus' => $menus]);
    }

    // 2. WEB: Simpan Menu + Gambar
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'category' => 'required',
            'price' => 'required|numeric',
            'cost_price' => 'required|numeric',
            'stock' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' // Validasi Gambar
        ]);

        // LOGIKA UPLOAD GAMBAR
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Simpan ke folder 'public/menus'
            // Hasilnya misal: menus/unik123.jpg
            $imagePath = $request->file('image')->store('menus', 'public');
        }

        DB::table('menus')->insert([
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'cost_price' => $request->cost_price,
            'stock' => $request->stock,
            'has_variant' => $request->has('has_variant'),
            'description' => $request->description,
            'image_url' => $imagePath, // Simpan path gambar
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Menu berhasil ditambahkan!');
    }

    // 3. WEB: Hapus Menu + Hapus Gambar
    public function destroy($id)
    {
        $menu = DB::table('menus')->where('id', $id)->first();

        // Hapus gambar dari penyimpanan biar gak nyampah
        if ($menu->image_url) {
            Storage::disk('public')->delete($menu->image_url);
        }

        DB::table('menus')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Menu dihapus!');
    }
}