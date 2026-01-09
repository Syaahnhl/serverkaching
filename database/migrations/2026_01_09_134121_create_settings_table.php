<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            // Identitas Toko
            $table->string('store_name')->default('Toko Saya');
            $table->string('store_address')->nullable();
            $table->string('store_phone')->nullable();
            
            // Pengaturan Struk
            $table->string('receipt_footer')->nullable();
            $table->string('receipt_website')->nullable();
            
            // Pengaturan Keuangan
            $table->string('tax_rate')->default('0'); // Simpan sebagai string biar aman
            $table->string('service_charge')->default('0');
            
            // Logo (Path URL)
            $table->string('logo_url')->nullable();
            
            $table->timestamps();
        });

        // [PENTING] Langsung isi data default agar ID 1 selalu ada
        DB::table('settings')->insert([
            'store_name' => 'Toko Baru',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};