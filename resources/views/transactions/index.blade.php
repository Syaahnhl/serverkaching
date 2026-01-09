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
                <p class="text-gray-500 text-sm mt-1">Pantau status pembayaran, pesanan dapur, dan tipe order.</p>
            </div>
            <a href="{{ url('/') }}" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-bold text-gray-700 shadow-sm transition">
                Kembali ke Dashboard
            </a>
        </div>

        <div class="space-y-6">
            @forelse($transactions as $trx)
            
            @php $style = $trx->status_style; @endphp

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition duration-200">
                
                <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap justify-between items-start gap-4 {{ $trx->status == 'Batal' ? 'bg-gray-50' : 'bg-white' }}">
                    
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl {{ $style['color'] }} flex items-center justify-center text-xl shadow-sm">
                            {{ $style['icon'] }}
                        </div>

                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-bold text-gray-900 text-lg">
                                    {{ preg_replace('/\s*\(.*?\)\s*/', '', $trx->customer_name) ?: 'Pelanggan Umum' }}
                                </h3>
                                
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide {{ $trx->type_color }}">
                                    {{ $trx->order_type }}
                                </span>
                            </div>

                            <div class="text-xs text-gray-500 flex flex-wrap items-center gap-3">
                                <span class="flex items-center gap-1">
                                    🕒 {{ \Carbon\Carbon::parse($trx->created_at_device)->format('d M Y, H:i') }}
                                </span>
                                
                                @if($trx->table_number && $trx->table_number != '-')
                                <span class="flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded text-gray-600 font-semibold">
                                    🪑 {{ $trx->table_number }}
                                </span>
                                @endif

                                <span class="text-gray-400">| Kasir: {{ $trx->cashier_name ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border mb-2 {{ $style['color'] }}">
                            {{ $style['label'] }}
                        </div>

                        <div class="text-xl font-bold {{ $trx->status == 'Batal' ? 'text-gray-400 line-through' : 'text-gray-900' }}">
                            Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                        </div>

                        @if($trx->status != 'Batal')
                            <div class="text-xs mt-1 font-medium {{ $trx->pay_amount >= $trx->total_amount ? 'text-green-600' : 'text-orange-500' }}">
                                @if($trx->pay_amount >= $trx->total_amount)
                                    <span class="flex items-center justify-end gap-1">LUNAS via {{ $trx->payment_method }}</span>
                                @else
                                    <span class="flex items-center justify-end gap-1">KURANG: Rp {{ number_format($trx->total_amount - $trx->pay_amount, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        @else
                             <div class="text-xs text-red-500 mt-1 italic">Alasan: "{{ $trx->cancel_reason ?? '-' }}"</div>
                        @endif
                    </div>
                </div>

                @if($trx->status != 'Batal')
                <div class="px-6 py-4 bg-gray-50/50">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 text-xs uppercase font-semibold border-b border-gray-200">
                            <tr>
                                <th class="pb-2 pl-2">Menu Pesanan</th>
                                <th class="pb-2 text-center">Qty</th>
                                <th class="pb-2 text-right pr-2">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($trx->items as $item)
                            <tr>
                                <td class="py-2 pl-2">
                                    <div class="font-medium text-gray-700">
                                        {{ $item->menu_name ?? $item->name }}
                                    </div>
                                    @if($item->note)
                                        <div class="text-[10px] text-orange-500 italic">"{{ $item->note }}"</div>
                                    @endif
                                </td>
                                <td class="py-2 text-center text-gray-500">x{{ $item->qty }}</td>
                                <td class="py-2 text-right pr-2 font-medium text-gray-700">
                                    {{ number_format($item->price * $item->qty, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @empty
            <div class="text-center py-20 bg-white rounded-xl border-2 border-dashed border-gray-200">
                <div class="text-6xl mb-4 grayscale opacity-50">🧾</div>
                <h3 class="text-lg font-bold text-gray-500">Belum ada transaksi</h3>
                <p class="text-gray-400 text-sm">Data penjualan dari Android akan muncul di sini.</p>
            </div>
            @endforelse

            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        </div>

    </div>
</body>
</html>