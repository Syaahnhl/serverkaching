<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Arus Kas - Kaching POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 p-6">

    <div class="max-w-6xl mx-auto">
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">💰 Arus Kas (Petty Cash)</h1>
                <p class="text-gray-500 text-sm mt-1">Catatan keluar-masuk uang laci/modal.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ url('/') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-bold text-gray-700">Dashboard</a>
            </div>
        </div>

        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-6 text-white shadow-lg mb-8 w-full md:w-1/3">
            <div class="text-emerald-100 text-sm font-bold uppercase">Sisa Uang di Laci (Estimasi)</div>
            <div class="text-4xl font-bold mt-2">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</div>
            <div class="text-xs text-emerald-100 mt-2 opacity-80">*Tidak termasuk hasil penjualan hari ini</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                    <tr>
                        <th class="p-4">Tanggal</th>
                        <th class="p-4">Tipe</th>
                        <th class="p-4">Keterangan</th>
                        <th class="p-4 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($flows as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-sm">
                            <div class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}</div>
                            <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }}</div>
                        </td>
                        <td class="p-4">
                            @if($item->type == 'IN')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">MASUK</span>
                            @else
                                <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded text-xs font-bold">KELUAR</span>
                            @endif
                        </td>
                        <td class="p-4 text-sm text-gray-600">
                            {{ $item->description }}
                        </td>
                        <td class="p-4 text-right font-bold {{ $item->type == 'IN' ? 'text-green-600' : 'text-orange-600' }}">
                            {{ $item->type == 'IN' ? '+' : '-' }} Rp {{ number_format($item->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-400 italic">
                            Belum ada catatan kas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>