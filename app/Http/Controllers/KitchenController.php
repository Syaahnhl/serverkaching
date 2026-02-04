<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KitchenController extends Controller
{
    // 1. AMBIL DAFTAR PESANAN (VERSI SMART FILTER)
    public function index()
    {
        // 1. Ambil Transaksi Hari Ini yang Belum Beres
        $transactions = DB::table('transactions')
            ->whereDate('created_at', Carbon::today()) 
            ->whereNotIn('status', ['done', 'Selesai', 'Served', 'Batal', 'Cancelled']) 
            ->orderBy('created_at', 'asc') 
            ->get();

        $data = $transactions->map(function ($trx) {
            $items = DB::table('transaction_items')
                ->where('transaction_id', $trx->id)
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

        return response()->json(['status' => 'success', 'data' => $filteredData], 200);
    }

    // 2. TANDAI PESANAN SELESAI (KDS Button)
    public function markAsDone($id)
    {
        return DB::transaction(function () use ($id) {
            // Update Header jadi 'Served'
            $affected = DB::table('transactions')
                ->where('id', $id)
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

            return response()->json(['message' => 'Gagal update'], 404);
        });
    }
}