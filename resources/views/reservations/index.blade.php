<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Reservasi - Kaching POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 p-6">

    <div class="max-w-7xl mx-auto">
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📅 Jadwal Reservasi</h1>
                <p class="text-gray-500 text-sm mt-1">Daftar booking meja yang akan datang.</p>
            </div>
            <a href="{{ url('/') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-bold text-gray-700">Kembali ke Dashboard</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-indigo-50 text-indigo-900 uppercase text-xs font-bold">
                    <tr>
                        <th class="p-4 border-b border-indigo-100">Tanggal & Jam</th>
                        <th class="p-4 border-b border-indigo-100">Nama Pelanggan</th>
                        <th class="p-4 border-b border-indigo-100 text-center">Pax (Orang)</th>
                        <th class="p-4 border-b border-indigo-100">Catatan</th>
                        <th class="p-4 border-b border-indigo-100">Kontak</th>
                        <th class="p-4 border-b border-indigo-100 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($reservations as $res)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-800 text-lg">{{ \Carbon\Carbon::parse($res->date)->format('d M Y') }}</span>
                                <span class="text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded w-fit text-sm mt-1">
                                    🕒 {{ $res->time }}
                                </span>
                            </div>
                        </td>

                        <td class="p-4">
                            <div class="font-bold text-gray-900">{{ $res->customer_name }}</div>
                            <div class="text-xs text-gray-400 italic mt-1">Booking via App</div>
                        </td>

                        <td class="p-4 text-center">
                            <span class="bg-gray-100 text-gray-700 font-bold px-3 py-1 rounded-full text-sm">
                                👥 {{ $res->pax }}
                            </span>
                        </td>

                        <td class="p-4">
                            @if($res->notes)
                                <div class="bg-yellow-50 text-yellow-800 p-2 rounded text-sm border border-yellow-100">
                                    {{ $res->notes }}
                                </div>
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>

                        <td class="p-4 text-sm text-gray-600">
                            {{ $res->phone_number ?? '-' }}
                        </td>

                        <td class="p-4 text-center">
                            @if($res->status == 'Pending')
                                <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold border border-yellow-200">
                                    ⏳ Pending
                                </span>
                            @elseif($res->status == 'Confirmed')
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">
                                    ✅ Confirmed
                                </span>
                            @else
                                <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold">
                                    {{ $res->status }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-10 text-center text-gray-400 italic">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Belum ada reservasi mendatang.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>