<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Transaction; // Pastikan Model ini ada

class TransactionController extends Controller
{
    /**
     * FUNGSI 1: TAMPILAN WEB (Browser)
     * Diakses lewat: localhost:8000/transactions
     * Ini yang bikin UI cantik muncul.
     */
    public function index()
    {
        // Ambil data transaksi beserta item-nya, urutkan dari yang terbaru
        $transactions = Transaction::with('items')
                            ->orderBy('created_at_device', 'desc')
                            ->paginate(10);

        // Return ke file View (resources/views/transactions/index.blade.php)
        return view('transactions.index', compact('transactions'));
    }

    /**
     * FUNGSI 2: API SYNC (Untuk Android)
     * Diakses lewat: localhost:8000/api/transactions
     * Ini untuk sinkronisasi data ke HP.
     */
    public function apiSync()
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
     * FUNGSI 3: Simpan data dari Android (API POST)
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
                'cashier_name' => 'nullable|string', 
                'customer_name' => 'nullable|string', 
                'table_number' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi Gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Gunakan DB Transaction agar data aman
            $id = DB::transaction(function () use ($request) {
                
                // 2. SIMPAN HEADER TRANSAKSI
                $trxId = DB::table('transactions')->insertGetId([
                    'app_uuid' => $request->app_uuid,
                    'total_amount' => $request->total_amount,
                    'payment_method' => $request->payment_method,
                    'customer_name' => $request->input('customer_name', 'Pelanggan'),
                    'cashier_name' => $request->input('cashier_name', 'Kasir HP'),
                    'table_number' => $request->input('table_number'),
                    'status' => 'pending', 
                    'created_at_device' => Carbon::parse($request->created_at_device),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 3. SIMPAN ITEM & POTONG STOK
                foreach ($request->items as $item) {
                    
                    DB::table('transaction_items')->insert([
                        'transaction_id' => $trxId,
                        'menu_name' => $item['name'], 
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'note' => $item['note'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Update Stok Otomatis
                    $menu = DB::table('menus')->where('name', $item['name'])->first();

                    if ($menu) {
                        if ($menu->stock != -1) {
                            $qtyBeli = $item['qty'];
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
    
    // Fungsi Cancel (Opsional)
    public function cancel(Request $request, $id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }
        $transaction->status = 'Batal'; 
        $transaction->save();

        return response()->json(['message' => 'Berhasil dibatalkan', 'data' => $transaction]);
    }
}