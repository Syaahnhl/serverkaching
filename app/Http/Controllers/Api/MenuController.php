<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    // 1. TAMPILKAN SEMUA MENU
    public function index()
    {
        try {
            $userId = Auth::id();
            
            // Pastikan model Menu sudah di-import: use App\Models\Menu;
            $menus = Menu::where('user_id', $userId)
                        ->where('is_available', 1)
                        ->orderBy('name', 'asc')
                        ->get();
            
            // Transform URL Gambar
            $menus->transform(function ($menu) {
                if ($menu->image_url && !str_starts_with($menu->image_url, 'http')) {
                    $menu->image_url = asset('storage/' . $menu->image_url);
                }
                
                // [OPSIONAL] Pastikan tipe data benar sebelum dikirim
                $menu->stock = (float) $menu->stock; 
                $menu->is_kds = (boolean) $menu->is_kds;
                
                return $menu;
            });

            return response()->json(['status' => 'success', 'data' => $menus], 200);

        } catch (\Exception $e) {
            // Ini akan membantu kita melihat error di response API jika server crash
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 2. SIMPAN MENU BARU
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'category' => 'required|string',
            'unit' => 'required|string',
            'price' => 'required|numeric',
            'cost_price' => 'required|numeric',
            'stock' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menus', 'public');
        }

        $menu = Menu::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'cost_price' => $request->cost_price,
            'stock' => $request->stock,
            'has_variant' => filter_var($request->has_variant, FILTER_VALIDATE_BOOLEAN),
            'unit' => $request->unit,
            'is_kds' => filter_var($request->input('is_kds', true), FILTER_VALIDATE_BOOLEAN),
            'description' => $request->description,
            'image_url' => $imagePath,
            'is_available' => true,
        ]);

        if ($menu->image_url) {
            $menu->image_url = asset('storage/' . $menu->image_url);
        }

        return response()->json(['status' => 'success', 'message' => 'Menu berhasil ditambahkan', 'data' => $menu], 201);
    }

    // 3. UPDATE DETAIL MENU (TERMASUK KDS & GAMBAR)
    public function update(Request $request, $id)
    {
        // 1. Cari Menu
        $menu = Menu::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$menu) return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan'], 404);

        // 2. Validasi (Gunakan 'nullable' untuk image agar tidak wajib upload ulang)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'category' => 'required|string',
            'price' => 'required',
            'cost_price' => 'required',
            'stock' => 'required',
            // Hapus validasi strict boolean disini karena Multipart mengirimnya sbg string
        ]);

        if ($validator->fails()) return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);

        // 3. Handle Gambar
        $currentPath = $menu->getRawOriginal('image_url'); 

        if ($request->hasFile('image')) {
            // Hapus file lama fisik jika ada
            if ($currentPath && Storage::disk('public')->exists($currentPath)) {
                Storage::disk('public')->delete($currentPath);
            }
            $currentPath = $request->file('image')->store('menus', 'public');
        }

        // 4. Konversi Data String Multipart ke Boolean/Angka yang benar
        $isKds = filter_var($request->input('is_kds'), FILTER_VALIDATE_BOOLEAN);
        $hasVariant = filter_var($request->input('has_variant'), FILTER_VALIDATE_BOOLEAN);

        // 5. Update Database
        $menu->update([
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'cost_price' => $request->cost_price,
            'stock' => $request->stock,
            'unit' => $request->unit,
            'has_variant' => $hasVariant,
            'is_kds' => $isKds, // Pastikan masuk sebagai boolean 1/0
            'description' => $request->description,
            'image_url' => $currentPath,
        ]);

        // Format URL untuk response
        if ($menu->image_url && !str_starts_with($menu->image_url, 'http')) {
            $menu->image_url = asset('storage/' . $menu->image_url);
        }

        return response()->json([
            'status' => 'success', 
            'message' => 'Menu diperbarui', 
            'data' => $menu
        ], 200);
    }

    // 4. HAPUS MENU TOTAL
    public function destroy($id)
    {
        $menu = Menu::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$menu) return response()->json(['status' => 'error'], 404);

        // Ambil path asli untuk dihapus dari storage
        $path = $menu->getRawOriginal('image_url');
        if ($path) {
            Storage::disk('public')->delete($path);
        }
        
        $menu->delete();

        return response()->json(['status' => 'success', 'message' => 'Menu berhasil dihapus'], 200);
    }

    // 5. QUICK UPDATE STOK SAJA
    public function updateStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'stock' => 'required|numeric'
        ]);

        if ($validator->fails()) return response()->json(['status' => 'error'], 422);

        $menu = Menu::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$menu) return response()->json(['status' => 'error'], 404);

        $menu->update(['stock' => $request->stock]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stok berhasil diupdate',
            'data' => ['id' => $menu->id, 'new_stock' => (double)$menu->stock]
        ], 200);
    }
}