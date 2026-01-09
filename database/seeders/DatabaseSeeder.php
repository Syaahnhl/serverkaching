<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Panggil Seeder Meja (Agar Meja dibuat otomatis)
        $this->call([
            TableSeeder::class,
        ]);

        // 2. Masukkan data menu contoh (Kode lama Anda)
        // Tips: Jika data menu makin banyak, sebaiknya pindahkan ke MenuSeeder.php terpisah
        DB::table('menus')->insert([
            [
                'name' => 'Nasi Goreng Spesial',
                'category' => 'Makanan',
                'price' => 25000,
                'cost_price' => 15000,
                'stock' => 50,
                'image_url' => null,
                'has_variant' => true,
                'description' => 'Nasi goreng mantap',
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Es Teh Manis',
                'category' => 'Minuman',
                'price' => 5000,
                'cost_price' => 2000,
                'stock' => 100,
                'image_url' => null,
                'has_variant' => true,
                'description' => 'Segar',
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}