<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Engineering Analysis (DSS)</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f4f6f9; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .icon-box { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 1.5rem; }
        
        /* Warna Kategori */
        .bg-star { background-color: #e8f5e9; color: #2e7d32; }
        .bg-cow { background-color: #fffde7; color: #f9a825; }
        .bg-puzzle { background-color: #e3f2fd; color: #1565c0; }
        .bg-dog { background-color: #ffebee; color: #c62828; }

        .badge-star { background-color: #2e7d32; }
        .badge-cow { background-color: #f9a825; color: #000; }
        .badge-puzzle { background-color: #1565c0; }
        .badge-dog { background-color: #c62828; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark"><i class="fas fa-chart-pie me-2 text-primary"></i>Menu Intelligence</h2>
            <p class="text-muted mb-0">Analisis performa menu metode SAW + BCG Matrix</p>
        </div>
        <div class="d-flex gap-2">
            <input type="date" id="startDate" class="form-control" value="{{ date('Y-m-01') }}">
            <input type="date" id="endDate" class="form-control" value="{{ date('Y-m-d') }}">
            <button onclick="fetchAnalysis()" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-star me-3"><i class="fas fa-star"></i></div>
                    <div>
                        <p class="text-muted mb-0 small fw-bold">STAR</p>
                        <h3 class="fw-bold mb-0" id="count-star">0</h3>
                        <small class="text-success">Laris & Untung Tinggi</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-cow me-3"><i class="fas fa-sack-dollar"></i></div>
                    <div>
                        <p class="text-muted mb-0 small fw-bold">CASH COW</p>
                        <h3 class="fw-bold mb-0" id="count-cow">0</h3>
                        <small class="text-warning text-dark">Laris, Margin Kecil</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-puzzle me-3"><i class="fas fa-question"></i></div>
                    <div>
                        <p class="text-muted mb-0 small fw-bold">PUZZLE</p>
                        <h3 class="fw-bold mb-0" id="count-puzzle">0</h3>
                        <small class="text-primary">Gak Laku, Margin Besar</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-dog me-3"><i class="fas fa-ban"></i></div>
                    <div>
                        <p class="text-muted mb-0 small fw-bold">DOG</p>
                        <h3 class="fw-bold mb-0" id="count-dog">0</h3>
                        <small class="text-danger">Kurang Diminati</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold">Matriks Posisi Menu</h5>
                </div>
                <div class="card-body">
                    <canvas id="menuChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold">Rekomendasi Tindakan (Ranking SAW)</h5>
                    <button class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i> Export</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" width="5%">Rank</th>
                                <th width="20%">Menu</th>
                                <th width="15%">Kategori</th>
                                <th width="25%">Statistik</th>
                                <th width="25%">Rekomendasi AI</th>
                                <th class="text-center" width="10%">Skor</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="6" class="text-center py-4">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    let menuChart = null;

    document.addEventListener('DOMContentLoaded', function() {
        fetchAnalysis();
    });

    function fetchAnalysis() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const tableBody = document.getElementById('table-body');
        
        // Reset UI
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Menganalisis Data...</p></td></tr>';

        // Panggil API Laravel
        axios.get(`/api/analysis/menu-performance?start_date=${startDate}&end_date=${endDate}`)
            .then(response => {
                const data = response.data.data;
                renderDashboard(data);
            })
            .catch(error => {
                console.error(error);
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Gagal memuat data. Pastikan API berjalan.</td></tr>';
            });
    }

    function renderDashboard(data) {
        // 1. Update Kartu Hitungan
        let counts = { STAR: 0, CASH_COW: 0, PUZZLE: 0, DOG: 0 };
        
        // 2. Render Tabel
        const tableBody = document.getElementById('table-body');
        let html = '';
        
        // Data untuk Chart
        let chartData = [];

        if(data.length === 0) {
            html = '<tr><td colspan="6" class="text-center py-4">Belum ada transaksi di periode ini.</td></tr>';
        } else {
            data.forEach((item, index) => {
                // Hitung Kategori
                if(counts[item.category] !== undefined) counts[item.category]++;

                // Badge Color
                let badgeClass = 'bg-secondary';
                if(item.category === 'STAR') badgeClass = 'badge-star';
                if(item.category === 'CASH_COW') badgeClass = 'badge-cow';
                if(item.category === 'PUZZLE') badgeClass = 'badge-puzzle';
                if(item.category === 'DOG') badgeClass = 'badge-dog';

                // Tambahkan Row Tabel
                html += `
                    <tr>
                        <td class="text-center fw-bold text-muted">#${index + 1}</td>
                        <td class="fw-bold">${item.name}</td>
                        <td><span class="badge ${badgeClass} px-3 py-2 rounded-pill">${item.category.replace('_', ' ')}</span></td>
                        <td class="small text-muted">${item.stats}</td>
                        <td class="small fw-semibold text-dark"><i class="fas fa-lightbulb text-warning me-1"></i> ${item.action}</td>
                        <td class="text-center fw-bold text-primary">${item.score}</td>
                    </tr>
                `;

                // Data Chart (Mapping sederhana untuk visualisasi)
                // Kita gunakan skor SAW sebagai Y (Kualitas) dan Index sebagai sebaran (agar tidak numpuk)
                // Di real case, bisa pakai Profit (X) vs Qty (Y)
                chartData.push({
                    x: item.name, 
                    y: item.score,
                    category: item.category
                });
            });
        }

        tableBody.innerHTML = html;
        
        // Update Angka di Kartu
        document.getElementById('count-star').innerText = counts.STAR;
        document.getElementById('count-cow').innerText = counts.CASH_COW;
        document.getElementById('count-puzzle').innerText = counts.PUZZLE;
        document.getElementById('count-dog').innerText = counts.DOG;

        // 3. Render Chart
        updateChart(chartData);
    }

    function updateChart(items) {
        const ctx = document.getElementById('menuChart').getContext('2d');
        
        if (menuChart) {
            menuChart.destroy();
        }

        const labels = items.map(i => i.x);
        const scores = items.map(i => i.y);
        const colors = items.map(i => {
            if(i.category === 'STAR') return '#2e7d32';
            if(i.category === 'CASH_COW') return '#f9a825';
            if(i.category === 'PUZZLE') return '#1565c0';
            return '#c62828';
        });

        menuChart = new Chart(ctx, {
            type: 'bar', // Menggunakan Bar Chart untuk ranking skor
            data: {
                labels: labels,
                datasets: [{
                    label: 'Skor SAW (Kualitas Menu)',
                    data: scores,
                    backgroundColor: colors,
                    borderRadius: 5,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Skor: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 1.0,
                        title: { display: true, text: 'Skor SAW (0 - 1.0)' }
                    }
                }
            }
        });
    }
</script>

</body>
</html>