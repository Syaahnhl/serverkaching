<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace sudah benar

use Illuminate\Http\Request;
use App\Models\CashFlow; // [FIX] Gunakan Model CashFlow
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth untuk SaaS
use Illuminate\Support\Facades\Validator;

class CashFlowController extends Controller
{
    // 1. SIMPAN DATA DARI ANDROID
    public function store(Request $request)
    {
        try {
            // Validasi data
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:IN,OUT',    // Hanya boleh IN atau OUT
                'amount' => 'required|numeric',    
                'description' => 'required|string',
                'date' => 'required|date',        
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // [SaaS] Simpan Data dengan User ID
            // Pastikan 'user_id' sudah ada di $fillable Model CashFlow
            $cashFlow = CashFlow::create([
                'user_id' => Auth::id(), // <--- KUNCI SAAS (Milik User Login)
                'type' => $request->type,
                'amount' => $request->amount,
                'description' => $request->description,
                'operator' => $request->input('operator', 'Kasir'), 
                'date' => $request->date,
            ]);

            return response()->json([
                'status' => 'success', 
                'message' => 'Data Kas berhasil disimpan!', 
                'data' => $cashFlow
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // 2. LIHAT DATA (Hanya Milik Toko Sendiri)
    public function index()
    {
        // [SaaS] Filter where('user_id', Auth::id())
        $data = CashFlow::where('user_id', Auth::id())
                    ->orderBy('date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->limit(100) // Batasi 100 transaksi terakhir biar ringan
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ], 200);
    }
}