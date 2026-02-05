<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    protected $table = 'transaction_items';

    // [PENTING] Masukkan user_id disini juga
    protected $fillable = [
        'transaction_id',
        'user_id',      // <--- WAJIB ADA (Untuk filter di Dapur/Laporan per item)
        'menu_name',
        'qty',
        'price',
        'note',
        'status'
    ];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'double',
    ];

    // Relasi Balik ke Transaksi
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}