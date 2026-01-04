<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Kaching POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 p-6">

    <div class="max-w-7xl mx-auto">
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">🧾 Riwayat Transaksi</h1>
                <p class="text-gray-500 text-sm mt-1">Daftar struk penjualan lengkap.</p>
            </div>
            <a href="{{ url('/') }}" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-bold text-gray-700 shadow-sm transition">
                Kembali ke Dashboard
            </a>
        </div>

        <div class="space-y-6">
            @forelse($transactions as $trx)
            
            @php
                $rawName = $trx->customer_name ?? $trx->cashier_name ?? 'Pelanggan Umum';
                $displayName = $rawName;
                $displayInfo = '';

                // Cek format "Nama (Info)"
                if (Str::contains($rawName, '(') && Str::endsWith($rawName, ')')) {
                    $displayName = Str::before($rawName, ' (');
                    $infoPart = Str::between($rawName, '(', ')');

                    // Cek Duplikat "Take Away - Take Away"
                    if (Str::contains($infoPart, ' - ')) {
                        $parts = explode(' - ', $infoPart);
                        // Jika kiri == kanan (case insensitive), ambil satu aja
                        if (count($parts) == 2 && strtolower(trim($parts[0])) == strtolower(trim($parts[1]))) {
                            $displayInfo = trim($parts[0]);
                        } else {
                            $displayInfo = $infoPart; // Normal: Dine In - Meja 5
                        }
                    } else {
                        $displayInfo = $infoPart; // Normal: Take Away (tanpa meja)
                    }
                }
            @endphp

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="{{ $trx->status == 'Batal' ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-200' }} px-6 py-4 border-b flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full {{ $trx->status == 'Batal' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600' }} flex items-center justify-center font-bold text-sm">
                            #{{ $trx->id }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900 text-lg">{{ $displayName }}</span>
                                @if($displayInfo)
                                    <span class="bg-white border border-gray-300 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                                        {{ $displayInfo }}
                                    </span>
                                @endif
                            </div>

                            <div class="text-xs text-gray-500 flex items-center gap-2 mt-1">
                                <span>📅 {{ \Carbon\Carbon::parse($trx->created_at_device)->format('d M Y, H:i') }}</span>
                                <span>•</span>
                                <span class="bg-gray-200 px-2 py-0.5 rounded text-gray-700 font-semibold text-[10px] uppercase">
                                    {{ $trx->payment_method }}
                                </span>
                                <span class="text-gray-400 italic">(Kasir: {{ $trx->cashier_name ?? '-' }})</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        @if($trx->status == 'Batal')
                            <div class="inline-block px-2 py-1 bg-red-100 text-red-600 rounded text-[10px] font-bold uppercase tracking-wider mb-1">
                                DIBATALKAN
                            </div>
                            <div class="text-xl font-bold text-gray-400 line-through">
                                Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                            </div>
                            @if(isset($trx->cancel_reason))
                                <div class="text-xs text-red-500 mt-1 italic">
                                    "{{ $trx->cancel_reason }}"
                                </div>
                            @endif
                        @else
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Total Bayar</div>
                            <div class="text-xl font-bold text-green-600">
                                Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-4 bg-white">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 font-medium border-b border-gray-100">
                            <tr>
                                <th class="pb-2 w-1/2">Menu</th>
                                <th class="pb-2 text-center">Qty</th>
                                <th class="pb-2 text-right">Harga</th>
                                <th class="pb-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($trx->items as $item)
                            <tr>
                                <td class="py-3">
                                    <div class="font-bold text-gray-700 text-base">
                                        {{ $item->menu_name ?? $item->name ?? 'Item Tanpa Nama' }}
                                    </div>
                                    @if($item->note)
                                        <div class="text-xs text-orange-500 italic mt-0.5">Catatan: "{{ $item->note }}"</div>
                                    @endif
                                </td>
                                <td class="py-3 text-center font-bold text-gray-600">x{{ $item->qty }}</td>
                                <td class="py-3 text-right text-gray-500">{{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="py-3 text-right font-bold {{ $trx->status == 'Batal' ? 'text-gray-400 line-through' : 'text-gray-800' }}">
                                    {{ number_format($item->price * $item->qty, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @empty
            <div class="text-center py-20 bg-white rounded-xl border border-dashed border-gray-300">
                <div class="text-6xl mb-4">📭</div>
                <h3 class="text-xl font-bold text-gray-400">Belum ada data transaksi</h3>
                <p class="text-gray-400">Silakan lakukan penjualan di aplikasi HP.</p>
            </div>
            @endforelse

            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        </div>

    </div>
</body>
</html>