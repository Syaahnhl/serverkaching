<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting; // [PENTING] Gunakan Model Setting yang baru dibuat
use Illuminate\Support\Facades\Storage; 

class SettingController extends Controller
{
    // API GET: Ambil Data Setting Milik User Login
    public function index()
    {
        // 1. Ambil ID User yang sedang login
        $userId = auth()->id(); 

        // 2. Cari setting berdasarkan user_id tersebut
        $setting = Setting::where('user_id', $userId)->first();
        
        return response()->json([
            'status' => 'success',
            'data' => $setting
        ]);
    }

    // API POST: Update Setting & Logo (Multi-User Ready)
    public function update(Request $request)
    {
        // 1. Ambil ID User yang sedang login
        $userId = auth()->id();

        // [SAFETY CHECK] Pastikan user login (Token valid)
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // 2. Siapkan Data Text
        $data = [
            'store_name' => $request->store_name,
            'store_address' => $request->store_address,
            'store_phone' => $request->store_phone,
            'receipt_footer' => $request->receipt_footer,
            'receipt_website' => $request->receipt_website,
            'tax_rate' => $request->tax_rate ?? '0',
            'service_charge' => $request->service_charge ?? '0',
            // 'updated_at' otomatis diurus oleh Eloquent
        ];

        // 3. Handle Upload Logo
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('logos', 'public'); 
            $data['logo_url'] = asset('storage/' . $path);
        }

        // 4. [LOGIC BARU] Update atau Buat Baru (UpdateOrCreate)
        // Laravel akan mencari setting punya user ini ($userId).
        // Kalau ketemu -> DIUPDATE.
        // Kalau tidak ketemu (user baru) -> DIBUAT BARU.
        $setting = Setting::updateOrCreate(
            ['user_id' => $userId], // Kunci pencarian
            $data                   // Data yang disimpan
        );

        // 5. Kembalikan Data Terbaru
        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan berhasil disimpan!',
            'data' => $setting
        ]);
    }
}