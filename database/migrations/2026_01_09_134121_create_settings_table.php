<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // [BARU] Kolom Penanda Pemilik Toko (Wajib untuk Multi-User)
            $table->unsignedBigInteger('user_id'); 

            // Identitas Toko
            $table->string('store_name')->nullable();
            $table->text('store_address')->nullable();
            $table->string('store_phone')->nullable();
            
            // Pengaturan Struk
            $table->string('receipt_footer')->nullable();
            $table->string('receipt_website')->nullable();
            
            // Pengaturan Keuangan
            $table->decimal('tax_rate', 5, 2)->default(0);      // Ubah ke decimal biar lebih presisi untuk angka
            $table->decimal('service_charge', 5, 2)->default(0); 
            
            // Logo (Path URL)
            $table->string('logo_url')->nullable();
            
            $table->timestamps();

            // [OPSIONAL] Foreign Key Constraint
            // Artinya: Jika User dihapus, maka data Tokonya otomatis ikut terhapus (Biar database bersih)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // [DIBUANG] Kita HAPUS kode insert default 'Toko Baru'.
        // Tujuannya agar saat user baru daftar, datanya kosong, 
        // sehingga Android bisa mendeteksi dan memunculkan halaman 'Setup Outlet'.
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};