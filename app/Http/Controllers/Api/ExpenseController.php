<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace sudah benar

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense; // [FIX] Gunakan Model Expense
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth untuk SaaS
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    // 1. SIMPAN PENGELUARAN (Milik User Login)
    public function store(Request $request)
    {
        try {
            // Validasi Data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'amount' => 'required|numeric',
                'category' => 'required|string',
                'date' => 'required|date', // Format: "2024-01-01"
                'note' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak lengkap',
                    'errors' => $validator->errors()
                ], 422);
            }

            // [SaaS] Simpan Data dengan User ID
            // Pastikan 'user_id' sudah ada di $fillable Model Expense
            $expense = Expense::create([
                'user_id' => Auth::id(), // <--- KUNCI SAAS
                'name' => $request->name,
                'amount' => $request->amount,
                'category' => $request->category,
                'date' => $request->date,
                'note' => $request->note ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil dicatat!',
                'data' => $expense
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // 2. LIHAT SEMUA PENGELUARAN (Hanya Milik Toko Sendiri)
    public function index()
    {
        // [SaaS] Filter where('user_id', Auth::id())
        $expenses = Expense::where('user_id', Auth::id())
                        ->orderBy('date', 'desc')
                        ->orderBy('created_at', 'desc') // Urutkan yang terbaru di input
                        ->limit(100) // Limit biar ringan
                        ->get();

        return response()->json([
            'status' => 'success', 
            'data' => $expenses
        ], 200);
    }
}