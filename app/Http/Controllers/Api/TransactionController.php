<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Transaction; // [FIX] Gunakan Model
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth

class TransactionController extends Controller // [FIX] Otomatis baca Controller di folder yang sama
{
    // 1. TAMPILKAN DATA (HANYA MILIK SENDIRI)
    public function index()
    {
        $transactions = Transaction::with('items')
                            ->where('user_id', Auth::id()) // [SaaS]
                            ->orderBy('created_at_device', 'desc')
                            ->paginate(10);

        // [FIX] Return JSON (Bukan View)
        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }

    // 2. API SYNC (HANYA MILIK SENDIRI)
    public function apiSync()
    {
        $data = DB::table('transactions')
                    ->where('user_id', Auth::id()) // [SaaS]
                    ->orderBy('created_at_device', 'desc')
                    ->limit(500) // Limit biar gak overload
                    ->get();

        return response()->json([
            'status' => 'success',
            'total' => $data->count(),
            'data' => $data
        ], 200);
    }

    // 3. SIMPAN DATA (INJECT USER ID OTOMATIS)
    public function store(Request $request)
    {
        try {
            $userId = Auth::id(); // [SaaS]

            // Cek apakah UUID ini sudah ada DI TOKO INI?
            $existing = Transaction::where('app_uuid', $request->app_uuid)
                        ->where('user_id', $userId) // <--- Kunci Utama
                        ->first();

            if ($existing) {
                // ====================================================
                // SKENARIO A: UPDATE PESANAN / TAMBAH ITEM (ADD ON)
                // ====================================================

                DB::transaction(function () use ($existing, $request, $userId) {
                    
                    // 1. Update Header Transaksi
                    $existing->update([
                        'total_amount' => $request->total_amount,
                        'pay_amount' => $request->pay_amount,
                        'status' => $request->input('status', $existing->status), 
                        'payment_method' => $request->payment_method, 
                        'updated_at' => now()
                    ]);

                    // 2. Masukkan Item Tambahan
                    if ($request->has('items')) {
                        foreach ($request->items as $item) {
                            
                            // [FIX ANTI-DUPLIKAT + SaaS Filter]
                            // Cek apakah item ini baru saja ditambahkan?
                            $isDuplicate = DB::table('transaction_items')
                                ->where('transaction_id', $existing->id)
                                ->where('user_id', $userId) // [SaaS]
                                ->where('menu_name', $item['name'])
                                ->where('qty', $item['qty']) 
                                ->where('note', $item['note'] ?? null) 
                                ->where('created_at', '>=', Carbon::now()->subSeconds(5)) // Toleransi 5 detik
                                ->exists();

                            if (!$isDuplicate) {
                                // Insert Item Baru dengan USER ID
                                DB::table('transaction_items')->insert([
                                    'transaction_id' => $existing->id,
                                    'user_id' => $userId, // [SaaS] Item ditandai punya user ini
                                    'menu_name' => $item['name'], 
                                    'qty' => $item['qty'],
                                    'price' => $item['price'],
                                    'note' => $item['note'] ?? null,
                                    'created_at' => now(), 
                                    'updated_at' => now(),
                                    'status' => 'Proses' 
                                ]);

                                // Update Stok (Hanya stok di toko ini)
                                $menu = DB::table('menus')
                                            ->where('name', $item['name'])
                                            ->where('user_id', $userId) // [SaaS]
                                            ->first();

                                if ($menu && $menu->stock != -1) {
                                    DB::table('menus')
                                        ->where('id', $menu->id)
                                        ->decrement('stock', $item['qty']);
                                }
                            }
                        }
                    }
                });

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data berhasil diupdate (SaaS Mode)',
                    'data' => $existing
                ], 200);
            }

            // =========================================================================
            // SKENARIO B: TRANSAKSI BARU (NORMAL)
            // =========================================================================

            $validator = Validator::make($request->all(), [
                'app_uuid' => 'required|string',
                'total_amount' => 'required|numeric',
                'pay_amount' => 'required|numeric',
                'payment_method' => 'required|string',
                'created_at_device' => 'required|date',
                'items' => 'required|array|min:1', 
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
            }

            $newTrx = DB::transaction(function () use ($request, $userId) {
                
                $trxStatus = ($request->payment_method === 'Bayar Nanti') ? 'Belum Lunas' : 'Proses';

                // 2. SIMPAN HEADER TRANSAKSI (Dengan User ID)
                $trx = Transaction::create([
                    'user_id' => $userId, // <--- [SaaS] Kunci Utama
                    'app_uuid' => $request->app_uuid,
                    'total_amount' => $request->total_amount,
                    'pay_amount' => $request->pay_amount,
                    'payment_method' => $request->payment_method,
                    'customer_name' => $request->input('customer_name', 'Pelanggan'),
                    'cashier_name' => $request->input('cashier_name', 'Kasir HP'),
                    'table_number' => $request->input('table_number'),
                    'status' => $trxStatus,
                    'created_at_device' => Carbon::parse($request->created_at_device),
                    'reservation_id' => $request->input('reservation_id', 0) 
                ]);

                // Update Status Reservasi (Cek Kepemilikan)
                if ($request->has('reservation_id') && $request->reservation_id != 0) {
                    DB::table('reservations')
                        ->where('id', $request->reservation_id)
                        ->where('user_id', $userId) // [SaaS]
                        ->update(['status' => 'Selesai']);
                }

                // 3. SIMPAN ITEM & POTONG STOK
                foreach ($request->items as $item) {
                    DB::table('transaction_items')->insert([
                        'transaction_id' => $trx->id,
                        'user_id' => $userId, // [SaaS] Item juga ditandai
                        'menu_name' => $item['name'], 
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'note' => $item['note'] ?? null,
                        'created_at' => now(), 
                        'updated_at' => now(),
                        'status' => 'Proses'
                    ]);

                    // Update Stok (Cek Kepemilikan: Jangan potong stok toko sebelah!)
                    $menu = DB::table('menus')
                                ->where('name', $item['name'])
                                ->where('user_id', $userId) // [SaaS]
                                ->first();

                    if ($menu && $menu->stock != -1) {
                        DB::table('menus')
                            ->where('id', $menu->id)
                            ->decrement('stock', $item['qty']);
                    }
                }

                // 4. Update Meja (Cek Kepemilikan)
                $tableNumber = $request->input('table_number'); 
                if ($tableNumber) {
                    $cleanNumber = preg_replace('/[^0-9]/', '', $tableNumber);
                    if ($cleanNumber) {
                        DB::table('tables')
                            ->where('number', $cleanNumber)
                            ->where('user_id', $userId) // [SaaS] Hanya update meja sendiri
                            ->update(['is_occupied' => true, 'updated_at' => now()]);    
                    }
                }

                return $trx;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi Baru Berhasil!',
                'data' => $newTrx
            ], 201);

        } catch (\Throwable $e) {
            return response()->json(['status' => 'fatal_error', 'message' => $e->getMessage()], 500);
        }
    }
    
    // 4. CANCEL TRANSAKSI (AMANKAN DARI USER LAIN)
    public function cancel(Request $request, $id)
    {
        Log::info("=== CANCEL REQUEST: $id ===");

        $userId = Auth::id();

        // 1. CARI TRANSAKSI + CEK USER ID
        $transaction = Transaction::where('id', $id)
                        ->where('user_id', $userId)
                        ->first();

        // Jika tidak ketemu pakai ID, coba pakai UUID + User ID
        if (!$transaction) {
            $transaction = Transaction::where('app_uuid', $id)
                            ->where('user_id', $userId)
                            ->first();
        }

        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan atau akses ditolak.'], 404);
        }

        // 2. UBAH STATUS
        $transaction->status = 'Batal'; 
        if ($request->has('reason')) {
            $transaction->cancel_reason = $request->reason;
        }
        $transaction->save();
        
        // 3. KOSONGKAN MEJA (Hanya milik user ini)
        $tableInfo = $transaction->table_number; 
        if (!empty($tableInfo) && preg_match('/(\d+)/', $tableInfo, $matches)) {
            $cleanNumber = (int)$matches[0]; 
            
            DB::table('tables')
                ->where('number', $cleanNumber)
                ->where('user_id', $userId) // [SaaS]
                ->update(['is_occupied' => 0]); 
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi dibatalkan & Meja dibuka.',
            'data' => $transaction
        ]);
    }

    // 5. DAPUR / KITCHEN (HANYA PESANAN SENDIRI)
    public function getKitchenOrders()
    {
        $orders = DB::table('transactions')
                    ->where('user_id', Auth::id()) // [SaaS] Filter User
                    ->whereNotIn('status', ['Selesai', 'Batal', 'Served']) 
                    ->orderBy('created_at', 'asc') 
                    ->get();

        $data = $orders->map(function ($order) {
            $order->items = DB::table('transaction_items')
                              ->where('transaction_id', $order->id)
                              // Item otomatis aman karena transaksi induknya sudah difilter
                              ->get();
            return $order;
        });

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    // 6. MARK AS SERVED (KDS)
    public function markAsServed(Request $request, $id)
    {
        // Cari + Filter User
        $transaction = Transaction::where('id', $id)
                        ->where('user_id', Auth::id()) // [SaaS]
                        ->first();

        if (!$transaction) return response()->json(['message' => 'Not found'], 404);

        // Update item status
        DB::table('transaction_items')
            ->where('transaction_id', $id)
            ->update(['status' => 'Served', 'updated_at' => now()]);

        $transaction->status = 'Served';
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status Updated to Served',
            'new_status' => $transaction->status 
        ]);
    }

    // 7. COMPLETE & CLEAR TABLE
    public function complete(Request $request, $id)
    {
        $userId = Auth::id();

        // Cari + Filter User
        $transaction = Transaction::where('id', $id)
                        ->where('user_id', $userId) // [SaaS]
                        ->first();
        
        if (!$transaction) {
            $transaction = Transaction::where('app_uuid', $id)
                            ->where('user_id', $userId)
                            ->first();
        }

        if (!$transaction) return response()->json(['message' => 'Not found'], 404);

        // Update Status
        $transaction->status = 'Selesai';
        $transaction->save();

        // Kosongkan Meja (Hanya milik user ini)
        $tableInfo = $transaction->table_number;
        if (!empty($tableInfo) && preg_match('/(\d+)/', $tableInfo, $matches)) {
            $cleanNumber = (int)$matches[0]; 
            
            DB::table('tables')
                ->where('number', $cleanNumber)
                ->where('user_id', $userId) // [SaaS]
                ->update(['is_occupied' => 0]); 
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi Selesai & Meja Kosong.',
            'data' => $transaction
        ]);
    }
}