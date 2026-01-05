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

        // [UPDATE] Ambil ID dan NOMOR MEJA dari transaksi aktif
        $activeTrx = DB::table('transactions')
            ->whereNotIn('status', ['done', 'Batal', 'Cancelled'])
            ->whereNotNull('table_number')
            ->select('id', 'table_number') // Ambil ID juga!
            ->get();

        $tables = $tables->map(function($table) use ($activeTrx) {
            
            $table->is_occupied = 0;
            $table->active_trx_id = null; // Siapkan kolom baru

            foreach ($activeTrx as $trx) {
                // Logic pencocokan nomor meja (PHP Murni)
                preg_match('/^\d+/', $trx->table_number, $matches);
                $extractedNum = $matches[0] ?? ''; 

                if ($extractedNum === (string)$table->number) {
                    $table->is_occupied = 1; 
                    $table->active_trx_id = $trx->id; // [PENTING] Simpan ID Transaksi
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