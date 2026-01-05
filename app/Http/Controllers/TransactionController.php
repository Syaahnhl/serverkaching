<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Transaction; // Pastikan Model ini ada
use Illuminate\Support\Facades\Log;

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

                // Ambil data nomor meja dari request Android
                $tableNumber = $request->input('table_number'); 
                
                // Cek jika ada nomor mejanya (bukan Take Away/Delivery)
                if ($tableNumber) {
                    // Bersihkan string jika formatnya "Meja 1" -> ambil "1" saja
                    // (Opsional, tergantung Android kirimnya apa. Kalau Android kirim angka murni "1", ini aman)
                    $cleanNumber = preg_replace('/[^0-9]/', '', $tableNumber);

                    // Update tabel 'tables' set is_occupied = true (1)
                    DB::table('tables')
                        ->where('number', $cleanNumber) // Pastikan kolom 'number' di DB cocok
                        ->update(['is_occupied' => true]);
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
        Log::info("=== MULAI PROSES CANCEL REQUEST: $id ===");

        // 1. CARI TRANSAKSI (Logic Lebih Pintar)
        // Coba cari pakai ID biasa (Primary Key)
        $transaction = Transaction::find($id);

        // Kalau gak ketemu, coba cari pakai app_uuid (Siapa tau Android kirim UUID atau ID Lokal)
        if (!$transaction) {
            Log::info("Cari by ID gagal. Mencoba cari by app_uuid...");
            $transaction = Transaction::where('app_uuid', $id)->first();
        }

        // Kalau masih gak ketemu juga, baru nyerah
        if (!$transaction) {
            Log::error("FATAL: Transaksi dengan ID atau UUID '$id' benar-benar tidak ada di Database Server.");
            return response()->json(['message' => 'Transaksi tidak ditemukan. Pastikan data sudah tersinkron.'], 404);
        }

        Log::info("Transaksi Ditemukan! ID Server: " . $transaction->id);

        // 2. UBAH STATUS JADI BATAL
        $transaction->status = 'Batal'; 
        if ($request->has('reason')) {
            $transaction->cancel_reason = $request->reason;
        }
        $transaction->save();
        
        // 3. UPDATE MEJA (Logic Regex yang sudah benar)
        $tableInfo = $transaction->table_number; 
            
            if (!empty($tableInfo) && preg_match('/(\d+)/', $tableInfo, $matches)) {
                
                // [PERBAIKAN] Tambahkan (int) untuk memaksa jadi Angka Murni
                $cleanNumber = (int)$matches[0]; 

                // Debugging biar kelihatan di Log Laravel
                Log::info("Mencoba update meja. Angka (Int): " . $cleanNumber);

                // Update Tabel Meja
                $affected = DB::table('tables')
                    ->where('number', $cleanNumber) // Sekarang ini angka murni 1, bukan "1"
                    ->update(['is_occupied' => 0]); 
                
                if ($affected) {
                    Log::info("SUKSES: Meja $cleanNumber berhasil di-nol-kan.");
                } else {
                    // Kalau ini muncul di log, berarti nomor mejanya emang gak ada di tabel tables
                    Log::error("GAGAL: Tidak ada baris yang berubah. Cek apakah meja nomor $cleanNumber ada di tabel 'tables'?");
                }

            }

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi dibatalkan & Meja dibuka.',
            'data' => $transaction
        ]);
    }

    public function getKitchenOrders()
    {
        // BIANG KEROK 1: Query harus membuang status 'Batal'
        $orders = DB::table('transactions')
                    ->whereNotIn('status', ['Selesai', 'Batal', 'Served']) // <--- INI KUNCINYA
                    ->orderBy('created_at', 'asc') // FIFO (First In First Out)
                    ->get();

        // Ambil detail item
        $data = $orders->map(function ($order) {
            $order->items = DB::table('transaction_items')
                              ->where('transaction_id', $order->id)
                              ->get();
            return $order;
        });

        return response()->json([
            'status' => 'success', 
            'data' => $data
        ], 200);
    }

    // FUNGSI KHUSUS TOMBOL SELESAI DI KDS
    public function markAsServed(Request $request, $id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) return response()->json(['message' => 'Not found'], 404);

        // HANYA Ubah Status jadi 'Served' agar hilang dari layar KDS
        // JANGAN ubah status meja (is_occupied) di sini!
        $transaction->status = 'Served'; 
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Pesanan siap disajikan. Meja masih terkunci.'
        ]);
    }
}