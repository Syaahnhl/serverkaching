<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CashFlowController extends Controller
{
    // 1. SIMPAN DATA DARI ANDROID
    public function store(Request $request)
    {
        try {
            // Validasi data (Harus sesuai dengan kiriman HP)
            $validator = Validator::make($request->all(), [
                'type' => 'required|string',       // "IN" / "OUT"
                'amount' => 'required|numeric',    // Angka
                'description' => 'required|string',
                'date' => 'required|date',         // "2024-01-01"
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }

            // Masukkan ke Database
            $id = DB::table('cash_flows')->insertGetId([
                'type' => $request->type,
                'amount' => $request->amount,
                'description' => $request->description,
                'operator' => 'Kasir', // Default dulu
                'date' => $request->date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success', 
                'message' => 'Data Kas berhasil disimpan!', 
                'server_id' => $id
            ], 201);

        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 2. LIHAT DATA (Untuk Web Admin nanti)
    public function index()
    {
        $data = DB::table('cash_flows')->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $data], 200);
    }

    // [BARU] TAMPILKAN HALAMAN WEB
    public function webIndex()
    {
        $flows = DB::table('cash_flows')->orderBy('date', 'desc')->get();
        
        // Hitung Saldo Akhir (Masuk - Keluar)
        $totalMasuk = $flows->where('type', 'IN')->sum('amount');
        $totalKeluar = $flows->where('type', 'OUT')->sum('amount');
        $saldoAkhir = $totalMasuk - $totalKeluar;

        return view('cash_flows.index', [
            'flows' => $flows,
            'saldoAkhir' => $saldoAkhir
        ]);
    }
}