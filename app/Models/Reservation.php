<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',        // <--- WAJIB ADA
        'customer_name',
        'phone_number',
        'date',
        'time',
        'pax',
        'notes',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'pax' => 'integer',
    ];
}