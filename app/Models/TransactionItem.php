<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    protected $table = 'transaction_items';

    protected $fillable = [
        'transaction_id',
        'user_id',      
        'menu_name',
        'qty',
        'price',
        'note',
        'status'
    ];

    // [UBAH INI] Pastikan qty di-cast sebagai double
    protected $casts = [
        'qty' => 'double', // <--- FIX: Sekarang Laravel akan mempertahankan angka desimal (0.5)
        'price' => 'double',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}