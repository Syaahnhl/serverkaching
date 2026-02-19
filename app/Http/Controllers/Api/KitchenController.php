<?php

namespace App\Http\Controllers\Api; // [FIX] Namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // [FIX] Tambah Auth
use Carbon\Carbon;

class KitchenController extends Controller
{
    // 1. AMBIL DAFTAR PESANAN (VERSI SMART FILTER)
    public function index()
    {
        $userId = Auth::id(); // [SaaS] Ambil ID User

        // 1. Ambil Transaksi Hari Ini yang Belum Beres (Milik Toko Ini)
        $transactions = DB::table('transactions')
            ->where('user_id', $userId) // [SaaS] Filter User
            ->whereDate('created_at_device', Carbon::today()) // Gunakan waktu device
            ->whereNotIn('status', ['done', 'Selesai', 'Served', 'Batal', 'Cancelled']) 
            ->orderBy('created_at', 'asc') 
            ->get();

        $data = $transactions->map(function ($trx) {
            $items = DB::table('transaction_items')
                ->join('menus', 'transaction_items.menu_name', '=', 'menus.name') // Hubungkan lewat nama menu
                ->where('transaction_items.transaction_id', $trx->id)
                ->select('transaction_items.*', 'menus.category') // <--- INI KUNCINYA
                ->get();

            $hasActiveItems = false;

            $processedItems = $items->map(function ($item) use (&$hasActiveItems) {
                // Item dianggap AKTIF jika belum Served/Selesai
                if ($item->status == 'Served' || $item->status == 'Selesai') {
                    $item->view_mode = 'locked';
                } else {
                    $item->view_mode = 'active';
                    $hasActiveItems = true;
                }
                return $item;
            });
            
            $trx->items = $processedItems; 
            
            // Flagging: Apakah tiket ini masih punya item aktif?
            $trx->has_active_items = $hasActiveItems; 
            
            return $trx;
        });

        // 2. [FILTER FINAL] Buang tiket yang isinya kosong / sudah selesai semua
        $filteredData = $data->filter(function ($trx) {
            return $trx->has_active_items; // Hanya loloskan yang masih punya item aktif
        })->values();

        return response()->json([
            'status' => 'success', 
            'data' => $filteredData
        ], 200);
    }

    // 2. TANDAI PESANAN SELESAI (KDS Button)
    public function markAsDone($id)
    {
        // [SaaS] Pastikan update transaksi milik sendiri
        return DB::transaction(function () use ($id) {
            $userId = Auth::id();

            // Update Header jadi 'Served' HANYA jika milik user ini
            $affected = DB::table('transactions')
                ->where('id', $id)
                ->where('user_id', $userId) // [SaaS] Kunci Keamanan
                ->update([
                    'status' => 'Served',
                    'updated_at' => now()
                ]);

            if ($affected > 0) {
                // Update SEMUA Item jadi 'Served'
                DB::table('transaction_items')
                    ->where('transaction_id', $id)
                    ->update(['status' => 'Served']);

                return response()->json(['status' => 'success'], 200);
            }

            return response()->json(['message' => 'Pesanan tidak ditemukan atau bukan milik Anda'], 404);
        });
    }

    // 3. [BARU] UPDATE STATUS PER ITEM (Dan Reset Jika Undo)
    public function updateItemStatus(Request $request, $id)
    {
        $userId = Auth::id();
        $newStatus = $request->input('status');

        // Pastikan item ini ada dan merupakan milik user ini (SaaS Security)
        $item = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transaction_items.id', $id)
            ->where('transactions.user_id', $userId)
            ->select('transaction_items.id', 'transaction_items.qty')
            ->first();

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak ditemukan'], 404);
        }

        $updateData = ['status' => $newStatus];

        // [KUNCI FIX UNDO] Jika status kembali ke 'Proses' atau 'Queued', kembalikan progress ke 0
        if (strtolower($newStatus) == 'proses' || strtolower($newStatus) == 'queued') {
            $updateData['qty_done'] = 0;
        } 
        // Opsional: Jika diklik 'Done', pastikan qty_done full sesuai qty asli
        else if (strtolower($newStatus) == 'done') {
            $updateData['qty_done'] = $item->qty;
        }

        DB::table('transaction_items')
            ->where('id', $id)
            ->update($updateData);

        return response()->json(['status' => 'success'], 200);
    }

    // 4. [BARU] TAMBAH PROGRESS CICILAN MASAK (Increment Qty Done)
    public function incrementItemQty($id)
    {
        $userId = Auth::id();

        // Cari item & pastikan milik toko ini
        $item = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transaction_items.id', $id)
            ->where('transactions.user_id', $userId)
            ->select('transaction_items.id', 'transaction_items.qty', 'transaction_items.qty_done')
            ->first();

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak ditemukan'], 404);
        }

        $currentDone = $item->qty_done ?? 0;
        $maxQty = $item->qty;

        // Cegah agar qty_done tidak kebablasan melebihi pesanan
        if ($currentDone < $maxQty) {
            DB::table('transaction_items')
                ->where('id', $id)
                ->update(['qty_done' => $currentDone + 1]);
        }

        return response()->json(['status' => 'success'], 200);
    }
}