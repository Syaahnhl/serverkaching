<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    // Supaya bisa diisi semua kolomnya
    protected $guarded = [];

    // Relasi: Satu Transaksi punya banyak Item Belanjaan
    public function items()
    {
        // 'transaction_id' adalah nama kolom penghubung di tabel anak
        return $this->hasMany(TransactionItem::class, 'transaction_id', 'id');
    }
}