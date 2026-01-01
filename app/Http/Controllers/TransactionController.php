<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * FUNGSI 1: Ambil semua data transaksi (GET)
     */
    public function index()
    {
        $data = DB::table('transactions')
                    ->orderBy('created_at_device', 'desc')
                    ->get();

        return response()->json([
            'status' => 'success',
            'total' => $data->count(),
            'data' => $data
        ], 200);
    }

    /**
     * FUNGSI 2: Simpan data dari Android (POST) - FIXED
     */
    public function store(Request $request)
    {
        try {
            // 1. Validasi Input
            $validator = Validator::make($request->all(), [
                'app_uuid' => 'required|string',
                'total_amount' => 'required|numeric',
                'payment_method' => 'required|string',
                'created_at_device' => 'required|date',
                'items' => 'required|array', 
                'items.*.name' => 'required|string',
                'items.*.qty' => 'required|integer',
                'items.*.price' => 'required|numeric',
                // [FIX] Pastikan server menerima field ini (nullable gpp)
                'cashier_name' => 'nullable|string', 
                'table_number' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi Gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $id = DB::transaction(function () use ($request) {
                
                // 2. SIMPAN HEADER TRANSAKSI (BAGIAN PENTING!)
                $trxId = DB::table('transactions')->insertGetId([
                    'app_uuid' => $request->app_uuid,
                    'total_amount' => $request->total_amount,
                    'payment_method' => $request->payment_method,
                    
                    // [FIX] SIMPAN DATA MEJA & NAMA PELANGGAN DISINI
                    // Ambil dari request, default ke 'Admin' atau NULL jika tidak ada
                    'cashier_name' => $request->input('cashier_name', 'Pelanggan'), 
                    'table_number' => $request->input('table_number'), // Ini yang bikin KDS muncul meja
                    
                    'status' => 'pending', // Wajib pending biar masuk KDS
                    'created_at_device' => Carbon::parse($request->created_at_device),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 3. SIMPAN ITEM & POTONG STOK
                foreach ($request->items as $item) {
                    // Simpan history belanja
                    DB::table('transaction_items')->insert([
                        'transaction_id' => $trxId,
                        'menu_name' => $item['name'],
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'note' => $item['note'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Update Stok
                    // Kita cari menu yang namanya SAMA dengan yang dibeli
                    $menu = DB::table('menus')->where('name', $item['name'])->first();

                    if ($menu) {
                        // Jika stok tidak unlimited (-1), kurangi stoknya
                        if ($menu->stock != -1) {
                            $qtyBeli = $item['qty'];
                            // Query langsung biar cepat (Decrement)
                            DB::table('menus')
                                ->where('id', $menu->id)
                                ->decrement('stock', $qtyBeli);
                        }
                    }
                }

                return $trxId;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi Berhasil & Stok Berkurang!',
                'server_id' => $id
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'fatal_error',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    /**
     * FUNGSI 3: Dashboard Web
     */
    public function dashboard()
    {
        // 1. Ambil Transaksi Utama
        $transactions = DB::table('transactions')
                        ->orderBy('created_at_device', 'desc')
                        ->get();

        // 2. Ambil Semua Item
        $items = DB::table('transaction_items')->get();

        // 3. Gabungkan (Mapping manual)
        $transactions->map(function ($trx) use ($items) {
            $trx->detail_items = $items->where('transaction_id', $trx->id);
            return $trx;
        });

        // 4. Hitung Total Omset
        $totalOmset = $transactions->sum('total_amount');

        return view('dashboard', [
            'transactions' => $transactions,
            'totalOmset' => $totalOmset
        ]);
    }
}