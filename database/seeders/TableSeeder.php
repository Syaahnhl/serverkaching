<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TableSeeder extends Seeder
{
    public function run()
    {
        // Buat Meja 1-10 (Indoor)
        for ($i = 1; $i <= 10; $i++) {
            DB::table('tables')->insert([
                'number' => (string)$i,
                'area' => 'Indoor Utama',
                'is_occupied' => false,
                'created_at' => now(), 'updated_at' => now()
            ]);
        }
        // Buat Meja 11-15 (Outdoor)
        for ($i = 11; $i <= 15; $i++) {
            DB::table('tables')->insert([
                'number' => (string)$i,
                'area' => 'Outdoor',
                'is_occupied' => false,
                'created_at' => now(), 'updated_at' => now()
            ]);
        }
    }
}