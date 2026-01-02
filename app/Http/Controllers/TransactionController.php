<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Transaction; // Gunakan Model yang baru dibuat

class TransactionController extends Controller
{
    /**
     * FUNGSI 1: Ambil semua data transaksi (API GET untuk Android Sync)
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
     * FUNGSI 2: Simpan data dari Android (API POST)
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
                
                // [UPDATE] Validasi untuk dua kolom nama berbeda
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
                    
                    // [UPDATE PENTING] Pisahkan Kasir dan Pelanggan
                    'customer_name' => $request->input('customer_name', 'Pelanggan'), // Nama pembeli (Ipung/Qwer)
                    'cashier_name' => $request->input('cashier_name', 'Kasir HP'),   // Nama penjaga toko
                    
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
                        
                        // [FIX] GUNAKAN 'menu_name' SESUAI SCREENSHOT KAMU
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

    /**
     * FUNGSI 3: [BARU] TAMPILAN WEB RIWAYAT TRANSAKSI (Dengan Detail Item)
     * Diakses lewat: localhost:8000/history
     */
    public function webIndex()
    {
        // Gunakan Model Transaction yang sudah punya relasi 'items'
        // orderBy 'created_at_device' agar urut dari terbaru
        $transactions = Transaction::with('items')
                        ->orderBy('created_at_device', 'desc')
                        ->paginate(10); // Tampilkan 10 per halaman

        return view('transactions.index', compact('transactions'));
    }

        public function cancel(Request $request, $id)
    {
        // Cari transaksi berdasarkan ID
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        // Ubah status jadi Batal
        $transaction->status = 'Batal'; // Sesuaikan string ini dengan enum di database Anda (misal: 'cancelled')
        
        // Simpan alasan (opsional, jika ada kolom cancel_reason di tabel)
        // $transaction->cancel_reason = $request->reason; 
        
        $transaction->save();

        return response()->json(['message' => 'Berhasil dibatalkan', 'data' => $transaction]);
    }

}