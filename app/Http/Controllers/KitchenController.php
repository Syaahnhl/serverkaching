<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    // 1. AMBIL DAFTAR PESANAN DENGAN LOGIKA KUNCI ITEM LAMA
    public function index()
    {
        $transactions = DB::table('transactions')
            ->where('status', '!=', 'done') 
            ->orderBy('created_at', 'asc') 
            ->get();

        $data = $transactions->map(function ($trx) {
            $items = DB::table('transaction_items')
                ->where('transaction_id', $trx->id)
                ->get();

            // [SOLUSI] Jangan pakai perbandingan waktu (max created_at).
            // Biarkan item tetap 'active' selama statusnya bukan 'Served' atau 'Selesai'.
            $processedItems = $items->map(function ($item) {
                // Item hanya dikunci jika status di DB memang sudah selesai
                if ($item->status == 'Served' || $item->status == 'Selesai') {
                    $item->view_mode = 'locked';
                } else {
                    $item->view_mode = 'active';
                }
                return $item;
            });
            
            $trx->items = $processedItems; 
            return $trx;
        });

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }
    // 2. TANDAI PESANAN SELESAI (Masakan Jadi)
    public function markAsDone($id)
    {
        // Gunakan Transaction agar jika salah satu gagal, semua dibatalkan
        return DB::transaction(function () use ($id) {
            // 1. Update Status Header (Transaksi)
            $affected = DB::table('transactions')
                ->where('id', $id)
                ->update([
                    'status' => 'done', 
                    'updated_at' => now()
                ]);

            if ($affected > 0) {
                // 2. [SOLUSI] Update SEMUA item di transaksi ini menjadi 'Served'
                // Ini yang akan memicu warna ABU-ABU di Android
                DB::table('transaction_items')
                    ->where('transaction_id', $id)
                    ->update(['status' => 'Served']);

                return response()->json(['status' => 'success'], 200);
            }

            return response()->json(['message' => 'Gagal update'], 404);
        });
    }
}