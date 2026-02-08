<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting; // [FIX] Gunakan Model Setting
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth

class SettingController extends Controller // [FIX] Otomatis baca Controller di folder yang sama
{
    // API GET: Ambil Data Setting Milik User Login
    public function index()
    {
        // 1. Ambil setting milik user ini
        $setting = Setting::where('user_id', Auth::id())->first();
        
        // 2. Jika setting belum ada, return null
        if (!$setting) {
            return response()->json([
                'status' => 'success',
                'data' => null 
            ]);
        }

        // 3. Format URL Logo biar bisa tampil di Android
        if ($setting->logo_url && !str_contains($setting->logo_url, 'http')) {
            $setting->logo_url = asset('storage/' . $setting->logo_url);
        }

        return response()->json([
            'status' => 'success',
            'data' => $setting
        ]);
    }

    // API POST: Update Setting & Logo (Multi-User Ready)
    public function update(Request $request)
    {
        // 1. Validasi Input [UPDATED]
        // store_name kita buat nullable agar bisa update parsial (misal cuma update mode)
        $validator = Validator::make($request->all(), [
            'store_name' => 'nullable|string|max:100', 
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'service_mode' => 'nullable|in:classic,fast_casual' // [BARU] Validasi Mode
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $userId = Auth::id();

        // 2. Siapkan Data Text [UPDATED]
        // Kita pakai logika: "Update hanya yang dikirim saja"
        $data = [];

        // Cek satu per satu, kalau ada di request, baru masukkan ke array data
        if ($request->has('store_name')) $data['store_name'] = $request->store_name;
        if ($request->has('store_address')) $data['store_address'] = $request->store_address;
        if ($request->has('store_phone')) $data['store_phone'] = $request->store_phone;
        if ($request->has('receipt_footer')) $data['receipt_footer'] = $request->receipt_footer;
        if ($request->has('receipt_website')) $data['receipt_website'] = $request->receipt_website;
        if ($request->has('tax_rate')) $data['tax_rate'] = $request->tax_rate;
        if ($request->has('service_charge')) $data['service_charge'] = $request->service_charge;
        
        // [BARU] Masukkan service_mode ke data update
        if ($request->has('service_mode')) $data['service_mode'] = $request->service_mode;


        // 3. Handle Upload Logo (Masih sama seperti kodemu, aman)
        if ($request->hasFile('logo')) {
            
            // A. Cek setting lama
            $oldSetting = Setting::where('user_id', $userId)->first();
            
            // B. Hapus logo lama
            if ($oldSetting && $oldSetting->logo_url) {
                $oldPath = str_replace(asset('storage/'), '', $oldSetting->logo_url);
                if (str_contains($oldSetting->logo_url, 'http')) {
                      $parsed = parse_url($oldSetting->logo_url);
                      $oldPath = ltrim($parsed['path'] ?? '', '/storage/'); // Safety null check
                } else {
                      $oldPath = $oldSetting->logo_url;
                }
                
                Storage::disk('public')->delete($oldPath);
            }

            // C. Upload logo baru
            $file = $request->file('logo');
            $path = $file->store('logos', 'public'); 
            
            // D. Simpan path relatif
            $data['logo_url'] = $path; 
        }

        // 4. Update atau Buat Baru (SaaS Logic)
        $setting = Setting::updateOrCreate(
            ['user_id' => $userId], // Kunci pencarian
            $data                   // Data update
        );

        // 5. Format URL Logo untuk respon
        if ($setting->logo_url && !str_contains($setting->logo_url, 'http')) {
            $setting->logo_url = asset('storage/' . $setting->logo_url);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan berhasil diperbarui!',
            'data' => $setting
        ]);
    }
}