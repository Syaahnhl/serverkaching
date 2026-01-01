<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    // 1. AMBIL DAFTAR PESANAN YANG BELUM SELESAI (Pending)
    public function index()
    {
        // Ambil transaksi yang statusnya BUKAN 'done' (bisa pending atau cooking)
        $transactions = DB::table('transactions')
            ->where('status', '!=', 'done') 
            ->orderBy('created_at', 'asc') // Yang pesan duluan, muncul di atas
            ->get();

        // Kita perlu melampirkan detail item (menu apa aja) ke setiap transaksi
        $data = $transactions->map(function ($trx) {
            $items = DB::table('transaction_items')
                ->where('transaction_id', $trx->id)
                ->get();
            
            $trx->items = $items; // Tempelkan item ke transaksi
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
        DB::table('transactions')
            ->where('id', $id)
            ->update(['status' => 'done', 'updated_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesanan selesai!'
        ], 200);
    }
}