<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses'; // Nama tabel di database

    // Daftar kolom yang boleh diisi (White-list)
    protected $fillable = [
        'user_id',    // <--- WAJIB ADA (Agar data tidak bocor ke user lain)
        'name',       // Nama pengeluaran
        'amount',     // Jumlah uang
        'category',   // Kategori (Bahan Baku, Operasional, dll)
        'date',       // Tanggal
        'note'        // Catatan tambahan
    ];

    // Mengubah tipe data saat dikirim ke Android (JSON)
    protected $casts = [
        'amount' => 'double', // Biar jadi angka (15000), bukan string ("15000")
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}