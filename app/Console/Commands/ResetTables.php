<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResetTables extends Command
{
    // Nama panggilan command
    protected $signature = 'tables:reset';

    // Deskripsi
    protected $description = 'Mereset status meja dan membatalkan transaksi gantung setiap jam 00:00';

    public function handle()
    {
        // 1. Reset Meja jadi Kosong
        DB::table('tables')->update(['is_occupied' => 0]);

        // 2. Transaksi 'Proses' dari hari KEMARIN dibatalkan otomatis
        DB::table('transactions')
            ->where('status', 'Proses')
            ->whereDate('created_at', '<', Carbon::today())
            ->update(['status' => 'Batal']);

        $this->info('Sukses: Meja direset & transaksi gantung dibersihkan.');
    }
}