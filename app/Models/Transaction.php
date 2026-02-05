<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    // [PENTING] Tambahkan 'user_id' agar sistem SaaS berjalan
    protected $fillable = [
        'user_id',          // <--- WAJIB ADA (Agar data tersimpan milik user tertentu)
        'app_uuid',
        'customer_name',
        'total_amount',
        'pay_amount',
        'payment_method',
        'status',
        'table_number',
        'created_at_device',
        'cashier_name',
        'reservation_id',
        'cancel_reason'     // Tambahan jika ada pembatalan
    ];

    // [BARU] Pastikan format data benar saat keluar/masuk
    protected $casts = [
        'total_amount' => 'double',
        'pay_amount' => 'double',
        'created_at_device' => 'datetime', // Biar bisa langsung di-format ->format('d M Y')
        'reservation_id' => 'integer'
    ];

    // Relasi: Satu Transaksi punya banyak Item
    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id', 'id');
    }

    // --- AKSESOR (Kodingan Mas sudah bagus, saya pertahankan) ---

    public function getOrderTypeAttribute()
    {
        if (preg_match('/\((.*?)\)/', $this->customer_name, $matches)) {
            $type = $matches[1]; 
            if (str_contains($type, ' - ')) {
                $parts = explode(' - ', $type);
                return $parts[0]; 
            }
            return $type;
        }
        return 'Dine In'; // Default saya ubah jadi Dine In biar lebih umum
    }

    public function getStatusStyleAttribute()
    {
        // A. BATAL
        if ($this->status === 'Batal' || $this->status === 'Cancelled') {
            return ['color' => 'bg-red-100 text-red-600 border-red-200', 'label' => 'DIBATALKAN', 'icon' => '✕'];
        }

        // B. SELESAI
        if (in_array($this->status, ['Selesai', 'Done', 'Served'])) {
            return ['color' => 'bg-green-100 text-green-700 border-green-200', 'label' => 'SELESAI', 'icon' => '✓'];
        }

        // C. BELUM LUNAS (Kasbon)
        if ($this->pay_amount < $this->total_amount) {
            return ['color' => 'bg-orange-100 text-orange-700 border-orange-200', 'label' => 'BELUM LUNAS', 'icon' => '⏳'];
        }

        // D. PROSES
        return ['color' => 'bg-blue-100 text-blue-700 border-blue-200', 'label' => 'DIPROSES', 'icon' => '👨‍🍳'];
    }
}