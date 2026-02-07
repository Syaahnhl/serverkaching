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
        'area',        // <--- WAJIB DITAMBAHKAN DI SINI
        'status',      // Tambahkan juga ini (tadi ada di controller 'status' => 'Kosong')
        'is_occupied'
    ];
}