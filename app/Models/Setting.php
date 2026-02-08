<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings'; // (Opsional) Memastikan nama tabelnya benar

    protected $fillable = [
        'user_id',          // <--- Wajib ada untuk Multi-User
        'store_name',
        'store_address',
        'store_phone',
        'receipt_footer',
        'receipt_website',
        'tax_rate',
        'service_charge',
        'logo_url',
        'service_mode',
    ];

    // [TAMBAHAN] Casting Tipe Data
    // Supaya di Android tidak perlu convert string ke number lagi
    protected $casts = [
        'tax_rate' => 'double',       // Contoh output: 10.5 (bukan "10.5")
        'service_charge' => 'double', // Contoh output: 5.0 (bukan "5.0")
        'user_id' => 'integer',
    ];
}