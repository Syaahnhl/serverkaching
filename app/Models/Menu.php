<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menus';

    // Kolom yang boleh diisi (termasuk user_id)
    protected $fillable = [
        'user_id', 
        'name',
        'category',
        'price',
        'cost_price',
        'stock',
        'has_variant',
        'description',
        'image_url',
        'is_available'
    ];

    protected $casts = [
        'price' => 'double',
        'cost_price' => 'double',
        'stock' => 'integer',
        'has_variant' => 'boolean',
        'is_available' => 'boolean',
    ];
}