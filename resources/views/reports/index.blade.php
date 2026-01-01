<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba Rugi - Kaching POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 p-6">

    <div class="max-w-7xl mx-auto">
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📊 Laporan Laba Rugi</h1>
                <p class="text-gray-500 text-sm mt-1">Rekap keuangan lengkap.</p>
            </div>
            <a href="{{ url('/') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-bold text-gray-700">Kembali ke Dashboard</a>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
            <form action="{{ route('reports.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full border p-2 rounded-lg bg-gray-50">
                </div>
                <div class="flex-1 w-full">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full border p-2 rounded-lg bg-gray-50">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold shadow transition">
                    🔍 Filter Data
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
                <div class="text-blue-600 font-bold text-sm uppercase">Total Pemasukan</div>
                <div class="text-3xl font-bold text-gray-900 mt-1">Rp {{ number_format($totalOmset, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-2">{{ $totalTransaksi }} Transaksi</div>
            </div>

            <div class="bg-red-50 p-6 rounded-xl border border-red-100">
                <div class="text-red-600 font-bold text-sm uppercase">Total Pengeluaran</div>
                <div class="text-3xl font-bold text-gray-900 mt-1">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-2">{{ count($expenses) }} Catatan Pengeluaran</div>
            </div>

            <div class="bg-green-50 p-6 rounded-xl border border-green-100">
                <div class="text-green-600 font-bold text-sm uppercase">Keuntungan Bersih</div>
                <div class="text-3xl font-bold text-green-700 mt-1">Rp {{ number_format($labaBersih, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-2">Pemasukan - Pengeluaran</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-fit">
                <div class="bg-gray-50 p-4 border-b font-bold text-gray-700">Rincian Pemasukan</div>
                <table class="w-full text-left">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($transactions as $trx)
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 text-sm">
                                <div class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($trx->created_at)->format('d M') }}</div>
                                <div class="text-xs text-gray-400">#{{ $trx->id }} - {{ $trx->payment_method }}</div>
                            </td>
                            <td class="p-3 text-right font-bold text-blue-600 text-sm">
                                + Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="p-4 text-center text-xs text-gray-400">Kosong</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-fit">
                <div class="bg-red-50 p-4 border-b font-bold text-red-700">Rincian Pengeluaran</div>
                <table class="w-full text-left">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($expenses as $exp)
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 text-sm">
                                <div class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($exp->date)->format('d M') }}</div>
                                <div class="font-medium">{{ $exp->name }}</div>
                                <div class="text-xs text-gray-400">{{ $exp->category }}</div>
                            </td>
                            <td class="p-3 text-right font-bold text-red-600 text-sm">
                                - Rp {{ number_format($exp->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="p-4 text-center text-xs text-gray-400">Tidak ada pengeluaran</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>
</html>