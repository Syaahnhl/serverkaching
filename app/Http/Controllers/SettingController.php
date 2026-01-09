<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // Untuk upload logo

class SettingController extends Controller
{
    // API GET: Ambil Data Setting
    public function index()
    {
        // Selalu ambil baris pertama (ID 1)
        $setting = DB::table('settings')->first();
        
        return response()->json([
            'status' => 'success',
            'data' => $setting
        ]);
    }

    // API POST: Update Setting & Logo
    public function update(Request $request)
    {
        // 1. Siapkan Data Text
        $data = [
            'store_name' => $request->store_name,
            'store_address' => $request->store_address,
            'store_phone' => $request->store_phone,
            'receipt_footer' => $request->receipt_footer,
            'receipt_website' => $request->receipt_website,
            'tax_rate' => $request->tax_rate ?? '0',
            'service_charge' => $request->service_charge ?? '0',
            'updated_at' => now()
        ];

        // 2. Handle Upload Logo (Jika ada file 'logo' dikirim)
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            // Simpan di folder public/logos
            $path = $file->store('logos', 'public'); 
            
            // Buat URL lengkap agar bisa diakses Android
            // Contoh: http://192.168.1.5:8000/storage/logos/abc.jpg
            $logoUrl = asset('storage/' . $path);
            
            $data['logo_url'] = $logoUrl;
        }

        // 3. Update Database (ID 1)
        DB::table('settings')->where('id', 1)->update($data);

        // 4. Kembalikan Data Terbaru
        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan berhasil disimpan!',
            'data' => DB::table('settings')->first()
        ]);
    }
}