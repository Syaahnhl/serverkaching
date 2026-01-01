<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            
            // Link ke tabel transaksi utama
            // (Cascade artinya: kalau transaksi induk dihapus, item ini ikut kehapus)
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');

            // Data Menu
            $table->string('menu_name'); // Nama menu (disimpan teks biar aman kalau nama menu asli berubah)
            $table->integer('qty');
            $table->decimal('price', 15, 2); // Harga satuan saat transaksi terjadi
            $table->string('note')->nullable(); // Catatan (Pedas, Tanpa Es, dll)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};