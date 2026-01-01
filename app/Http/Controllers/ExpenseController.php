<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    // FUNGSI UNTUK MENYIMPAN PENGELUARAN DARI ANDROID
    public function store(Request $request)
    {
        try {
            // 1. Cek kelengkapan data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'amount' => 'required|numeric',
                'category' => 'required|string',
                'date' => 'required|date', // Server minta format "2024-01-01"
                'note' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak lengkap',
                    'errors' => $validator->errors()
                ], 422);
            }

            // 2. Simpan ke Database
            $id = DB::table('expenses')->insertGetId([
                'name' => $request->name,
                'amount' => $request->amount,
                'category' => $request->category,
                'date' => $request->date,
                'note' => $request->note,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil dicatat!',
                'server_id' => $id
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // FUNGSI UNTUK MELIHAT SEMUA PENGELUARAN (Opsional/Bonus)
    public function index()
    {
        $expenses = DB::table('expenses')->orderBy('date', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $expenses], 200);
    }
}