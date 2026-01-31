<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    // 1. AMBIL DAFTAR PESANAN DENGAN LOGIKA KUNCI ITEM LAMA
    public function index()
    {
        // Ambil transaksi yang statusnya BUKAN 'done'
        $transactions = DB::table('transactions')
            ->where('status', '!=', 'done') 
            ->orderBy('created_at', 'asc') 
            ->get();

        // Kita map (loop) setiap transaksi untuk ambil item & cek status waktunya
        $data = $transactions->map(function ($trx) {
            
            // 1. Ambil semua item milik transaksi ini
            $items = DB::table('transaction_items')
                ->where('transaction_id', $trx->id)
                ->get();

            // 2. CARI WAKTU TERTUA (TERBARU) DI BATCH INI
            // Kita cari 'created_at' yang paling besar (paling baru)
            $latestItemTime = $items->max('created_at');

            // 3. BANDINGKAN SETIAP ITEM DENGAN WAKTU TERBARU
            // Kita tambahkan field baru bernama 'view_mode' ke dalam item
            $processedItems = $items->map(function ($item) use ($latestItemTime) {
                
                // Jika waktu item LEBIH KECIL dari waktu terbaru = ITEM LAMA (Locked)
                if ($item->created_at < $latestItemTime) {
                    $item->view_mode = 'locked'; // Tandai sebagai view-only
                } else {
                    $item->view_mode = 'active'; // Tandai sebagai item baru (bisa diklik)
                }
                
                return $item;
            });
            
            // Tempelkan item yang sudah diberi tanda ke transaksi
            $trx->items = $processedItems; 
            return $trx;
        });

        return response()->json([
            'status' => 'success',
            'data' => $data
        ], 200);
    }
    // 2. TANDAI PESANAN SELESAI (Masakan Jadi)
    public function markAsDone($id)
    {
        // 1. Coba Update
        $affected = DB::table('transactions')
            ->where('id', $id)
            // Pastikan kita update data yang BELUM done saja
            ->where('status', '!=', 'done') 
            ->update([
                'status' => 'done', 
                'updated_at' => now()
            ]);

        // 2. Cek Hasilnya (Jujur-jujuran)
        if ($affected > 0) {
            return response()->json(['status' => 'success'], 200);
        } else {
            // Kalau tidak ada yang berubah, JANGAN BILANG SUKSES!
            // Kembalikan 404 agar Android tahu ID-nya salah.
            return response()->json(['message' => 'Gagal update/Data tidak ditemukan'], 404);
        }
    }
}