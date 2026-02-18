<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'number',
        'area',
        'status',
        'is_occupied',
        'active_trx_id'
    ];

    // [TAMBAHAN WAJIB] Casting tipe data
    // Ini memaksa PHP mengubah 0/1 menjadi false/true di JSON
    protected $casts = [
        'is_occupied' => 'boolean',
        'number' => 'string' // Biar aman kalau ada nomor meja "01"
    ];
}