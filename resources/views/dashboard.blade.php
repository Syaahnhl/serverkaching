<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaching POS - Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Transisi halus untuk sidebar */
        .sidebar-transition { transition: all 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <div class="flex h-screen overflow-hidden">

        <aside id="sidebar" class="bg-white w-64 flex-shrink-0 border-r border-gray-200 flex flex-col sidebar-transition hidden md:flex z-50 fixed md:relative h-full">
            
            <div class="h-16 flex items-center px-6 border-b border-gray-100">
                <div class="text-2xl font-bold text-blue-600 flex items-center gap-2">
                    ⚡ <span>Kaching.</span>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 bg-blue-50 text-blue-700 rounded-xl font-semibold transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Dashboard</span>
                </a>

                <div class="px-4 mt-6 mb-2 text-xs font-bold text-gray-400 uppercase tracking-wider">Operasional</div>

                <a href="{{ route('menus.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-xl font-medium transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <span>Kelola Menu</span>
                </a>

                <a href="{{ route('reservations.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-xl font-medium transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Reservasi</span>
                </a>

                <div class="px-4 mt-6 mb-2 text-xs font-bold text-gray-400 uppercase tracking-wider">Keuangan</div>

                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-xl font-medium transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>Laba Rugi</span>
                </a>

                <a href="{{ route('cash_flows.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-xl font-medium transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Arus Kas</span>
                </a>

            </nav>

            <div class="p-4 border-t border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                        B
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-900">Boss Owner</div>
                        <div class="text-xs text-gray-500">Administrator</div>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden relative">
            
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 z-10">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    </button>
                    <h1 class="text-xl font-bold text-gray-800 hidden sm:block">Dashboard Overview</h1>
                </div>

                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500 font-medium">{{ \Carbon\Carbon::now()->format('d M Y') }}</span>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                
                <div class="max-w-7xl mx-auto space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Omset Hari Ini</p>
                                    <h3 class="text-2xl font-bold text-gray-900 mt-2">Rp {{ number_format($todayOmset, 0, ',', '.') }}</h3>
                                </div>
                                <div class="p-3 bg-green-50 text-green-600 rounded-xl">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Total Transaksi</p>
                                    <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ $todayCount }} <span class="text-sm font-normal text-gray-400">struk</span></h3>
                                </div>
                                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Stok Menipis</p>
                                    <h3 class="text-2xl font-bold text-red-600 mt-2">{{ $lowStockMenus->count() }} <span class="text-sm font-normal text-gray-400">item</span></h3>
                                </div>
                                <div class="p-3 bg-red-50 text-red-600 rounded-xl">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        
                        <div class="xl:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-bold text-gray-800 text-lg">Tren Penjualan (7 Hari)</h3>
                            </div>
                            <div class="relative h-64 w-full">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>

                        <div class="space-y-6">
                            
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    ⚠️ Perlu Restock
                                </h3>
                                @if($lowStockMenus->isEmpty())
                                    <div class="flex flex-col items-center justify-center py-6 text-center">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 mb-2">✅</div>
                                        <span class="text-sm text-gray-500">Stok aman terkendali</span>
                                    </div>
                                @else
                                    <div class="space-y-3 max-h-48 overflow-y-auto pr-1">
                                        @foreach($lowStockMenus as $item)
                                        <div class="flex justify-between items-center p-3 bg-red-50 rounded-xl border border-red-100">
                                            <span class="text-sm font-medium text-gray-700 truncate">{{ $item->name }}</span>
                                            <span class="text-xs font-bold bg-white text-red-600 px-2 py-1 rounded shadow-sm">Sisa: {{ $item->stock }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                                <h3 class="font-bold text-gray-800 mb-4">Transaksi Terkini</h3>
                                <div class="space-y-4">
                                    @foreach($recentTransactions as $trx)
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold text-xs">
                                                {{ $loop->iteration }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-800">{{ $trx->payment_method }}</div>
                                                <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }}</div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-bold text-green-600">+ {{ number_format($trx->total_amount, 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        // 1. Logic Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            // Toggle class 'hidden' di layar kecil, dan 'hidden' di layar besar
            sidebar.classList.toggle('hidden');
        }

        // 2. Logic Chart
        const chartLabels = @json($chartLabels);
        const chartData = @json($chartData);

        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Omset',
                    data: chartData,
                    borderColor: '#2563EB',
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, "rgba(37, 99, 235, 0.2)");
                        gradient.addColorStop(1, "rgba(37, 99, 235, 0)");
                        return gradient;
                    },
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2563EB',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [5, 5], color: '#f3f4f6' },
                        ticks: { font: { size: 11 } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    </script>
</body>
</html>