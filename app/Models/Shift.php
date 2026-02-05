<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',          // <--- WAJIB ADA
        'app_uuid',         // ID Unik dari HP (untuk sinkronisasi)
        'cashier_name',
        'start_time',
        'end_time',
        'start_cash',
        'end_cash',
        'total_cash_sales',
        'total_expense',
        'cash_drop',
        'expected_cash',
        'difference',
        'status',
        'payment_details'   // Menyimpan rincian pembayaran (JSON)
    ];

    protected $casts = [
        'start_cash' => 'double',
        'end_cash' => 'double',
        'total_cash_sales' => 'double',
        'expected_cash' => 'double',
        'difference' => 'double',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}