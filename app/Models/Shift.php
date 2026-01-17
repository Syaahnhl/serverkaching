<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $table = 'shifts';

    // Izinkan kolom ini diisi dari Controller
    protected $fillable = [
        'app_uuid',
        'cashier_name',
        'start_time',
        'end_time',
        'start_cash',
        'end_cash',
        'total_cash_sales',
        'total_expense', // Baru
        'cash_drop',     // Baru
        'expected_cash',
        'difference',
        'status',
        'payment_details' // Baru
    ];

    // Otomatis ubah JSON jadi Array saat data diambil
    protected $casts = [
        'payment_details' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}