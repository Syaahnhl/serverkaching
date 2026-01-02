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
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                            #{{ $trx->id }}
                        </div>
                        <div>
                            <div class="font-bold text-gray-900 text-lg">
                                {{ $trx->customer_name ?? $trx->cashier_name ?? 'Pelanggan Umum' }}
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
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Total Bayar</div>
                        <div class="text-xl font-bold text-green-600">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</div>
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
                                <td class="py-3 text-right font-bold text-gray-800">
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