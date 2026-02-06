<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;       // [FIX] Gunakan Model
use App\Models\Transaction; // [FIX] Gunakan Model
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ShiftController extends Controller // [FIX] Otomatis baca Controller di folder yang sama
{
    // 1. BUKA SHIFT (OPEN)
    public function openShift(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cashier_name' => 'required',
            'start_cash' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $userId = Auth::id();

        // [SaaS] Cek Double Shift (Satu User cuma boleh 1 shift open di waktu yang sama)
        $activeShift = Shift::where('user_id', $userId)
                        ->where('status', 'open')
                        ->first();

        if ($activeShift) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda masih memiliki shift yang belum ditutup!',
                'data' => $activeShift
            ], 400);
        }

        // Buat Shift Baru
        $shift = Shift::create([
            'user_id' => $userId, // [SaaS] Kunci User
            'cashier_name' => $request->cashier_name,
            'start_cash' => $request->start_cash,
            'start_time' => $request->start_time ?? now(),
            'status' => 'open',
            'end_cash' => 0
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Shift berhasil dibuka',
            'data' => $shift
        ]);
    }

    // 2. TUTUP SHIFT (CLOSE) - Versi Server Side Calculation
    public function closeShift(Request $request)
    {
        $request->validate([
            'shift_id' => 'required',
            'end_cash' => 'required|numeric',
            'end_time' => 'required'
        ]);

        $userId = Auth::id();

        // Cari Shift Milik User Ini
        $shift = Shift::where('id', $request->shift_id)
                    ->where('user_id', $userId) // [SaaS]
                    ->first();

        if (!$shift || $shift->status == 'closed') {
            return response()->json(['message' => 'Shift tidak ditemukan atau sudah ditutup'], 404);
        }

        // HITUNG OMSET TUNAI (Hanya transaksi milik user ini)
        // Kita hitung ulang di server biar aman
        $totalCashSales = Transaction::where('user_id', $userId) // [SaaS]
                            ->where('cashier_name', $shift->cashier_name)
                            ->whereBetween('created_at_device', [$shift->start_time, $request->end_time])
                            ->where('payment_method', 'Tunai')
                            ->where('status', '!=', 'Batal')
                            ->sum('total_amount');

        $expectedCash = $shift->start_cash + $totalCashSales;
        $actualCash = $request->end_cash;
        $difference = $actualCash - $expectedCash;

        $shift->update([
            'end_time' => $request->end_time,
            'end_cash' => $actualCash,
            'total_cash_sales' => $totalCashSales,
            'expected_cash' => $expectedCash,
            'difference' => $difference,
            'status' => 'closed'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Shift berhasil ditutup',
            'data' => $shift
        ]);
    }
    
    // 3. CEK STATUS SHIFT (Untuk Sinkronisasi Awal)
    public function checkShift(Request $request)
    {
        $cashierName = $request->query('cashier_name');
        
        $shift = Shift::where('user_id', Auth::id()) // [SaaS]
                      ->where('cashier_name', $cashierName)
                      ->where('status', 'open')
                      ->latest()
                      ->first();
                      
        if ($shift) {
            return response()->json(['status' => 'open', 'data' => $shift]);
        } else {
            return response()->json(['status' => 'closed', 'data' => null]);
        }
    }

    // 4. UPLOAD REPORT (Sinkronisasi Laporan Lengkap dari Android)
    public function uploadReport(Request $request)
    {
        // 1. Validasi
        $validator = Validator::make($request->all(), [
            'app_uuid' => 'required',
            'cashier_name' => 'required',
            'actual_cash' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        try {
            $userId = Auth::id();

            // 2. Cek Duplikat (User ID + UUID)
            // Mencegah laporan ganda kalau tombol ditekan 2x
            $existingShift = Shift::where('app_uuid', $request->app_uuid)
                            ->where('user_id', $userId) // [SaaS]
                            ->first();

            if ($existingShift) {
                return response()->json(['message' => 'Data sudah ada (Synced)'], 200);
            }

            // 3. Simpan Laporan Baru
            $shift = Shift::create([
                'user_id' => $userId, // [SaaS] Wajib ada
                'app_uuid' => $request->app_uuid,
                'cashier_name' => $request->cashier_name,
                
                'end_time' => $request->date, 
                'start_time' => $request->date, // (Opsional, disamakan dulu kalau data start hilang)
                
                'start_cash' => $request->capital_in,
                'end_cash' => $request->actual_cash,
                
                'total_cash_sales' => $request->cash_sales,
                'total_expense' => $request->total_expense ?? 0,
                'cash_drop' => $request->cash_drop ?? 0,
                
                'expected_cash' => $request->expected_cash,
                'difference' => ($request->actual_cash - $request->expected_cash),
                
                'status' => 'closed', 
                'payment_details' => $request->payment_breakdown // JSON String dari Android
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan tersimpan di server',
                'data' => $shift
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // 5. HISTORY SHIFT
    public function getHistory()
    {
        // [SaaS] Hanya ambil data milik user ini
        $shifts = Shift::where('user_id', Auth::id())
                    ->where('status', 'closed')
                    ->orderBy('created_at', 'desc')
                    ->limit(30)
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $shifts
        ]);
    }
}