<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    // API: Ambil Semua Meja (Untuk Android)
    public function index()
    {
        $tables = DB::table('tables')->get();

        // [UPDATE PERBAIKAN DI SINI] 
        // Tambahkan 'Selesai' dan 'Served' agar status ini dianggap meja KOSONG.
        $activeTrx = DB::table('transactions')
            ->whereNotIn('status', [
                'done', 
                'Batal', 
                'Cancelled', 
                'Selesai',  // <--- INI KUNCINYA!
                'Served'    // Tambahkan juga ini jika KDS sudah menyajikan
            ])
            ->whereNotNull('table_number')
            ->select('id', 'table_number') 
            ->get();

        $tables = $tables->map(function($table) use ($activeTrx) {
            
            // Default: Kita anggap kosong dulu
            $table->is_occupied = 0;
            $table->active_trx_id = null; 

            foreach ($activeTrx as $trx) {
                // Logic pencocokan nomor meja
                preg_match('/^\d+/', $trx->table_number, $matches);
                $extractedNum = $matches[0] ?? ''; 

                // Jika Nomor Meja sama dengan Transaksi yang SEDANG AKTIF
                if ($extractedNum === (string)$table->number) {
                    $table->is_occupied = 1; // Paksa jadi Terisi
                    $table->active_trx_id = $trx->id; 
                    break; 
                }
            }

            return $table;
        });

        return response()->json([
            'status' => 'success',
            'data' => $tables
        ], 200);
    }

    // (Opsional) API: Set Status Meja
    public function updateStatus(Request $request, $id)
    {
        DB::table('tables')->where('id', $id)->update(['is_occupied' => $request->is_occupied]);
        return response()->json(['status' => 'success']);
    }
}