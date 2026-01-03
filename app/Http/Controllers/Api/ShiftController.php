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
}