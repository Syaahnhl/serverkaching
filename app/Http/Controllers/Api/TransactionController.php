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
        $userId = Auth::id();

        // 1. Cek dulu, apakah Kasir ini punya Shift yang sedang OPEN?
        $activeShift = \App\Models\Shift::where('user_id', $userId)
                        ->where('status', 'open')
                        ->first();

        // 2. Jika TIDAK ADA shift buka (Toko Tutup / Belum Buka)
        // Maka server jangan kasih data transaksi apapun.
        // Ini bikin HP tetap bersih setelah tutup toko.
        if (!$activeShift) {
            return response()->json([
                'status' => 'success',
                'message' => 'Toko sedang tutup, tidak ada data sinkronisasi.',
                'total' => 0,
                'data' => [] // Array kosong penting agar Android menghapus data lokal
            ], 200);
        }

        // 3. Jika ADA shift buka, ambil transaksi HANYA sejak jam buka shift
        $data = Transaction::with('items') 
                    ->where('user_id', $userId) // [SaaS]
                    ->where('created_at_device', '>=', $activeShift->start_time) // [FILTER WAKTU]
                    ->orderBy('created_at_device', 'desc')
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
                        'reservation_id' => $request->input('reservation_id', 0),
                        'updated_at' => now()
                    ]);

                    // [FIX SINKRONISASI HAPUS] 1.5. Hapus item yang dibuang oleh Android
                    if ($request->has('items')) {
                        // Kumpulkan semua nama menu dari HP Android saat ini
                        $incomingMenuNames = collect($request->items)->pluck('name')->toArray();

                        // Cari item di Server yang TIDAK ADA di HP Android
                        $itemsToDelete = DB::table('transaction_items')
                            ->where('transaction_id', $existing->id)
                            ->whereNotIn('menu_name', $incomingMenuNames)
                            ->get();

                        foreach ($itemsToDelete as $delItem) {
                            // Kembalikan stok sebelum dihapus
                            $menu = DB::table('menus')->where('name', $delItem->menu_name)->where('user_id', $userId)->first();
                            if ($menu && $menu->stock != -1) {
                                DB::table('menus')->where('id', $menu->id)->increment('stock', $delItem->qty);
                            }
                        }

                        // Hapus item zombie dari database server!
                        DB::table('transaction_items')
                            ->where('transaction_id', $existing->id)
                            ->whereNotIn('menu_name', $incomingMenuNames)
                            ->delete();
                    }

                    // 2. Masukkan Item Tambahan (Sisa kode ke bawah biarkan sama persis)
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
                                // [FIX LOGIKA KUNCI] 
                                // Cek apakah item lain di transaksi ini sudah ada yang statusnya 'Proses' / 'Cooking'?
                                // Jika ya, berarti koki sedang memasak, jadi item susulan ini biarkan 'Proses'.
                                // Jika semuanya masih 'PreOrder', berarti item baru ini ikut dikunci 'PreOrder'.
                                $anyActive = DB::table('transaction_items')
                                    ->where('transaction_id', $existing->id)
                                    ->whereIn('status', ['Proses', 'Cooking', 'Done', 'Served'])
                                    ->exists();
                                
                                // Deteksi apakah ini reservasi yang belum dibayar?
                                $isUnpaidRes = ($request->input('reservation_id', 0) > 0) && ($request->pay_amount == 0);
                                
                                // Jika Reservasi Belum Lunas DAN belum ada item yang dimasak -> Kunci (PreOrder)
                                // Selain itu -> Bebaskan (Proses)
                                $itemStatus = ($isUnpaidRes && !$anyActive) ? 'PreOrder' : 'Proses';

                                // Insert Item Baru dengan USER ID
                                DB::table('transaction_items')->insert([
                                    'transaction_id' => $existing->id,
                                    'user_id' => $userId, 
                                    'menu_name' => $item['name'], 
                                    'qty' => $item['qty'],
                                    'price' => $item['price'],
                                    'note' => $item['note'] ?? null,
                                    'created_at' => now(), 
                                    'updated_at' => now(),
                                    'status' => $itemStatus // [FIX] Gunakan variabel dinamis
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

                $isPrePrepReservation = ($request->input('reservation_id', 0) > 0) && ($request->pay_amount == 0);
                $initialItemStatus = $isPrePrepReservation ? 'PreOrder' : 'Proses';

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
                        'status' => $initialItemStatus
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
                    ->where('user_id', Auth::id())
                    ->whereNotIn('status', ['Selesai', 'Batal', 'Served']) 
                    ->orderBy('created_at', 'asc') 
                    ->get();

        $data = $orders->map(function ($order) {
            $order->items = DB::table('transaction_items')
                            ->where('transaction_id', $order->id)
                            ->get();

            // [SOLUSI FINAL] Pastikan handling null aman
            // Ambil ID reservasi, jika null anggap 0
            $resId = isset($order->reservation_id) ? (int)$order->reservation_id : 0;

            if ($resId > 0) {
                $order->order_type = 'Reservasi';
            } else {
                $order->order_type = 'Dine In'; 
            }

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

    public function updateItemStatus(Request $request, $itemId)
    {
        $userId = Auth::id(); // [SaaS]

        // Validasi input status
        $request->validate([
            'status' => 'required|string|in:Proses,Cooking,Done,Served'
        ]);

        // Cari Item di database yang punya User ID ini
        $item = DB::table('transaction_items')
                    ->where('id', $itemId)
                    ->where('user_id', $userId)
                    ->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found or access denied'], 404);
        }

        // Update Status
        DB::table('transaction_items')
            ->where('id', $itemId)
            ->update([
                'status' => $request->status,
                'updated_at' => now()
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Item status updated to ' . $request->status
        ]);
    }

    // 8. PARTIAL REFUND (REFUND PER ITEM)
    public function refundItem(Request $request, $id)
    {
        $userId = Auth::id(); // [SaaS] Keamanan User

        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'item_id'   => 'required|integer',
            'qty'       => 'required|numeric',
            'menu_name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        try {
            DB::transaction(function () use ($request, $userId, $id) {
                
                // [BARU] 1. Cari Item & Pastikan belum pernah direfund (Mencegah Kasir nakal nge-klik berkali-kali)
                $item = DB::table('transaction_items')
                    ->where('id', $request->item_id)
                    ->where('transaction_id', $id) // ID struk dari URL
                    ->where('user_id', $userId)
                    ->where('status', '!=', 'Refund') 
                    ->first();

                if (!$item) {
                    throw new \Exception("Item tidak ditemukan atau sudah pernah direfund.");
                }

                // 2. RESTORE STOCK (Kembalikan Stok)
                $menu = DB::table('menus')
                    ->where('name', $request->menu_name)
                    ->where('user_id', $userId)
                    ->first();

                if ($menu && $menu->stock != -1) {
                    DB::table('menus')
                        ->where('id', $menu->id)
                        ->increment('stock', $request->qty);
                }

                // [BARU] 3. HITUNG NOMINAL YANG DIKEMBALIKAN
                $refundAmount = $item->price * $request->qty;

                // [BARU] 4. UPDATE HEADER TRANSAKSI (Kurangi uang di struk utama agar laporan akurat)
                DB::table('transactions')
                    ->where('id', $id)
                    ->where('user_id', $userId)
                    ->decrement('total_amount', $refundAmount);
                
                DB::table('transactions')
                    ->where('id', $id)
                    ->where('user_id', $userId)
                    ->decrement('pay_amount', $refundAmount);

                // 5. UPDATE STATUS ITEM JADI 'REFUND'
                DB::table('transaction_items')
                    ->where('id', $request->item_id)
                    ->update([
                        'status' => 'Refund',
                        'note'   => $request->input('reason', 'Refund via App'),
                        'updated_at' => now()
                    ]);
                
                // [BARU] 6. CEK JIKA SEMUA ITEM DI STRUK SUDAH DIREFUND
                $remainingItems = DB::table('transaction_items')
                    ->where('transaction_id', $id)
                    ->where('status', '!=', 'Refund')
                    ->count();
                
                // Jika sudah habis semua barangnya, otomatis batalkan struknya
                if ($remainingItems == 0) {
                    DB::table('transactions')->where('id', $id)->update(['status' => 'Batal']);
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Item berhasil direfund, stok & saldo laporan telah disesuaikan.'
            ]);

        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function incrementItemProgress(Request $request, $id)
    {
        $userId = Auth::id(); // [SaaS] Pastikan data milik user ini

        // 1. Cari Item
        $item = DB::table('transaction_items')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        // 2. Cek apakah masih bisa ditambah?
        if ($item->qty_done < $item->qty) {
            
            $newQtyDone = $item->qty_done + 1;
            
            // Logic Status: Jika sudah penuh, otomatis DONE. Jika belum, COOKING.
            $newStatus = ($newQtyDone >= $item->qty) ? 'Done' : 'Cooking';

            DB::table('transaction_items')
                ->where('id', $id)
                ->update([
                    'qty_done' => $newQtyDone,
                    'status' => $newStatus,
                    'updated_at' => now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Progres diperbarui',
                'qty_done' => $newQtyDone,
                'item_status' => $newStatus
            ]);
        }

        return response()->json(['message' => 'Item sudah selesai semua'], 400);
    }

    public function toggleKdsViewMode(Request $request, $id)
    {
        $userId = Auth::id();
        $mode = $request->input('view_mode');

        // 1. Cari transaksi (Cek ID Server atau UUID Android)
        $transaction = DB::table('transactions')
            ->where('user_id', $userId)
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                      ->orWhere('app_uuid', $id);
            })
            ->first();

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        }

        // 2. Ambil ID Server yang asli (Penting!)
        $serverId = $transaction->id;
        $itemStatus = ($mode === 'locked') ? 'PreOrder' : 'Proses';

        // 3. Update Item menggunakan ID Server yang asli
        if ($mode === 'active') {
            DB::table('transaction_items')
                ->where('transaction_id', $serverId)
                ->where('status', 'PreOrder') // Targetkan gemboknya saja
                ->update([
                    'status' => 'Proses', // Buka gembok jadi antrian aktif
                    'updated_at' => now()
                ]);
        } else {
            // Logika untuk mengunci ulang (jarang dipakai, tapi jaga-jaga)
            DB::table('transaction_items')
                ->where('transaction_id', $serverId)
                ->whereIn('status', ['Proses', 'Queued']) // Hanya kunci yang masih antri
                ->update([
                    'status' => 'PreOrder',
                    'updated_at' => now()
                ]);
        }

        return response()->json([
            'status' => 'success', 
            'message' => "Pesanan KDS berhasil di-set menjadi $mode"
        ], 200);
    }
}