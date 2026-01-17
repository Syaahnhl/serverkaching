<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Transaction;
use Carbon\Carbon;

class ShiftController extends Controller
{
    // 1. BUKA SHIFT (OPEN)
    public function openShift(Request $request)
    {
        $request->validate([
            'cashier_name' => 'required',
            'start_cash' => 'required|numeric',
            'start_time' => 'required'
        ]);

        // Buat Shift Baru
        $shift = new Shift();
        $shift->cashier_name = $request->cashier_name;
        $shift->start_cash = $request->start_cash;
        $shift->start_time = $request->start_time;
        $shift->status = 'open';
        $shift->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Shift berhasil dibuka',
            'data' => $shift
        ]);
    }

    // 2. TUTUP SHIFT (CLOSE)
    public function closeShift(Request $request)
    {
        $request->validate([
            'shift_id' => 'required',
            'end_cash' => 'required|numeric',
            'end_time' => 'required'
        ]);

        $shift = Shift::find($request->shift_id);

        if (!$shift || $shift->status == 'closed') {
            return response()->json(['message' => 'Shift tidak ditemukan atau sudah ditutup'], 404);
        }

        // HITUNG OMSET
        $totalCashSales = Transaction::where('cashier_name', $shift->cashier_name)
                            ->whereBetween('created_at_device', [$shift->start_time, $request->end_time])
                            ->where('payment_method', 'Tunai')
                            ->where('status', '!=', 'Batal')
                            ->sum('total_amount');

        $expectedCash = $shift->start_cash + $totalCashSales;
        $actualCash = $request->end_cash;
        $difference = $actualCash - $expectedCash;

        $shift->end_time = $request->end_time;
        $shift->end_cash = $actualCash;
        $shift->total_cash_sales = $totalCashSales;
        $shift->expected_cash = $expectedCash;
        $shift->difference = $difference;
        $shift->status = 'closed';
        $shift->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Shift berhasil ditutup',
            'data' => $shift
        ]);
    }
    
    // 3. CEK SHIFT
    public function checkShift(Request $request)
    {
        $cashierName = $request->query('cashier_name');
        
        $shift = Shift::where('cashier_name', $cashierName)
                      ->where('status', 'open')
                      ->latest()
                      ->first();
                      
        if ($shift) {
            return response()->json(['status' => 'open', 'data' => $shift]);
        } else {
            return response()->json(['status' => 'closed', 'data' => null]);
        }
    }

    // [BARU] Method Khusus untuk Sinkronisasi Laporan dari Android
    public function uploadReport(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'app_uuid' => 'required',
            'cashier_name' => 'required',
            'actual_cash' => 'required|numeric',
        ]);

        try {
            // 2. Cek apakah data dengan UUID ini sudah ada? (Biar gak dobel)
            $existingShift = Shift::where('app_uuid', $request->app_uuid)->first();

            if ($existingShift) {
                return response()->json(['message' => 'Data sudah ada'], 200);
            }

            // 3. Simpan Laporan Baru
            $shift = Shift::create([
                'app_uuid' => $request->app_uuid,
                'cashier_name' => $request->cashier_name,
                
                // Android kirim 'date', kita simpan sebagai waktu tutup
                'end_time' => $request->date, 
                'start_time' => $request->date, // (Opsional) Disamakan dulu jika tidak ada data start
                
                'start_cash' => $request->capital_in,
                'end_cash' => $request->actual_cash,
                
                'total_cash_sales' => $request->cash_sales,
                'total_expense' => $request->total_expense,
                'cash_drop' => $request->cash_drop,
                
                'expected_cash' => $request->expected_cash,
                'difference' => ($request->actual_cash - $request->expected_cash),
                
                'status' => 'closed', // Langsung closed karena ini laporan
                'payment_details' => $request->payment_breakdown // JSON Rincian
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

    public function getHistory()
    {
        // Ambil 30 shift terakhir yang sudah closed, urutkan dari yang terbaru
        $shifts = Shift::where('status', 'closed')
                    ->orderBy('created_at', 'desc')
                    ->limit(30)
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $shifts
        ]);
    }
}