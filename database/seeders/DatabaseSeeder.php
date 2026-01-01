<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Masukkan data menu contoh
        DB::table('menus')->insert([
            [
                'name' => 'Nasi Goreng Spesial',
                'category' => 'Makanan',
                'price' => 25000,
                'cost_price' => 15000, // Modal
                'stock' => 50,
                'image_url' => null,
                'has_variant' => true, // Ada pedas/sedang
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
                'has_variant' => true, // Ada less sugar/normal
                'description' => 'Segar',
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}