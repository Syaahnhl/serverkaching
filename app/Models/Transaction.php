<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    // [UPDATE] Definisikan kolom yang boleh diisi, termasuk 'reservation_id'
    protected $fillable = [
        'app_uuid',
        'customer_name',
        'total_amount',
        'pay_amount',
        'payment_method',
        'status',
        'table_number',
        'created_at_device',
        'cashier_name',
        'reservation_id' // <--- WAJIB DITAMBAHKAN
    ];

    // Relasi: Satu Transaksi punya banyak Item Belanjaan
    public function items()
    {
        // 'transaction_id' adalah nama kolom penghubung di tabel anak
        return $this->hasMany(TransactionItem::class, 'transaction_id', 'id');
    }

    // --- AKSESOR PINTAR (LOGIC WARNA) ---

    // 1. Cek Tipe Pesanan (Dine In / Take Away / Delivery)
    // Logika: Mengambil teks di dalam kurung pada nama customer
    // Contoh: "Budi (Take Away)" -> diambil "Take Away"
    public function getOrderTypeAttribute()
    {
        // Cari teks di dalam kurung (...)
        if (preg_match('/\((.*?)\)/', $this->customer_name, $matches)) {
            $type = $matches[1]; // Isinya misal: "Dine In - Meja 1" atau "Take Away"
            
            // Bersihkan nomor meja jika ada, ambil kata depannya saja
            if (str_contains($type, ' - ')) {
                $parts = explode(' - ', $type);
                return $parts[0]; // Ambil "Dine In" nya saja
            }
            return $type;
        }
        return 'General'; // Default jika tidak ada kurung
    }

    // 2. Status Badge (Warna & Label untuk Status Utama)
    public function getStatusStyleAttribute()
    {
        // A. Jika Status BATAL
        if ($this->status === 'Batal' || $this->status === 'Cancelled') {
            return [
                'color' => 'bg-red-100 text-red-600 border-red-200',
                'label' => 'DIBATALKAN',
                'icon'  => '✕'
            ];
        }

        // B. Jika Status SELESAI / DONE / SERVED
        if (in_array($this->status, ['Selesai', 'Done', 'Served'])) {
            return [
                'color' => 'bg-green-100 text-green-700 border-green-200',
                'label' => 'SELESAI',
                'icon'  => '✓'
            ];
        }

        // C. Jika Status PROSES / BELUM LUNAS
        // Cek pembayaran
        $isLunas = $this->pay_amount >= $this->total_amount;

        if (!$isLunas) {
            return [
                'color' => 'bg-orange-100 text-orange-700 border-orange-200',
                'label' => 'BELUM LUNAS', // Proses tapi ngutang/bon
                'icon'  => '⏳'
            ];
        }

        // D. Default (Proses Lunas / Sedang Dimasak)
        return [
            'color' => 'bg-blue-100 text-blue-700 border-blue-200',
            'label' => 'DIPROSES',
            'icon'  => '👨‍🍳'
        ];
    }

    // 3. Badge Warna untuk Tipe Pesanan
    public function getTypeColorAttribute()
    {
        $type = strtolower($this->order_type);

        if (str_contains($type, 'take away')) return 'bg-purple-100 text-purple-700';
        if (str_contains($type, 'delivery')) return 'bg-yellow-100 text-yellow-700';
        if (str_contains($type, 'reservasi')) return 'bg-pink-100 text-pink-700';
        
        return 'bg-gray-100 text-gray-600'; // Default (Dine In)
    }
}