@extends('layouts.admin')

@section('title', 'KANTI - Proyeksi Inflasi')
@section('page_title', 'Proyeksi & Early Warning System')

@push('scripts')
    <!-- Gunakan versi UMD yang lebih stabil untuk penggunaan langsung di browser -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>

    <script>
        function forecastingData() {
            // Variabel lokal untuk menyimpan instance Chart agar tidak diganggu oleh Alpine Reactivity
            let chart = null;

            return {
                selectedRegion: 'Kota Jambi', // Default
                selectedCommodity: 'Cabai Merah',
                isLoading: false,

                // Mock Data
                forecastResult: {
                    currentPrice: 0,
                    predictedPrice: 0,
                    trend: 'up',
                    mape: 0,
                    modelParams: 'SARIMA(1,1,1)(0,1,1,4)',
                    confidence: '95%'
                },

                // Sync with index.blade.php data
                inflasiMap: {
                    'Kota Jambi': 2.68,
                    'Kab. Kerinci': 6.70,
                    'Kab. Merangin': 2.10,
                    'Muaro Jambi': 3.05,
                    'Kab. Batanghari': 3.80,
                    'Kab. Sarolangun': 3.15,
                    'Kab. Bungo': 2.54,
                    'Kab. Tebo': 4.68,
                    'Tanjung Jabung Barat': 3.90,
                    'Tanjung Jabung Timur': 3.20,
                    'Kota Sungai Penuh': 4.10
                },
                regions: ['Kota Jambi', 'Kab. Kerinci', 'Kab. Merangin', 'Muaro Jambi', 'Kab. Batanghari',
                    'Kab. Sarolangun', 'Kab. Bungo', 'Kab. Tebo', 'Tanjung Jabung Barat', 'Tanjung Jabung Timur',
                    'Kota Sungai Penuh'
                ],
                commodities: ['Cabai Merah', 'Bawang Merah', 'Bawang Putih', 'Beras', 'Daging Ayam Ras', 'Telur Ayam Ras',
                    'Cabai Rawit', 'Daging Sapi', 'Gula Pasir', 'Minyak Goreng'
                ],

                init() {
                    // Cek URL Parameter 'region'
                    const urlParams = new URLSearchParams(window.location.search);
                    const regionParam = urlParams.get('region');

                    if (regionParam && this.regions.includes(regionParam)) {
                        this.selectedRegion = regionParam;
                    }

                    // Tunggu sebentar untuk memastikan DOM & Script Chart.js siap
                    setTimeout(() => {
                        this.initChart();
                        this.generateForecast();
                    }, 500);
                },

                async generateForecast() {
                    this.isLoading = true;

                    try {
                        // Simulasi delay (Gimmick)
                        await new Promise(r => setTimeout(r, 1000));

                        // --- GENERATE DATA DUMMY ---
                        const historyLabels = [];
                        const historyData = [];
                        let basePrice = this.selectedCommodity === 'Cabai Merah' ? 45000 :
                            (this.selectedCommodity === 'Bawang Merah' ? 32000 : 28000);

                        // Modifikasi sedikit data berdasarkan region agar terlihat dinamis
                        if (this.selectedRegion === 'Kab. Kerinci') basePrice -= 5000; // Lebih murah di produsen
                        if (this.selectedRegion === 'Kota Jambi') basePrice += 5000; // Lebih mahal di konsumen

                        for (let i = 12; i > 0; i--) {
                            historyLabels.push(`W-${i}`);
                            const noise = Math.floor(Math.random() * 4000) - 2000;
                            const season = Math.sin(i) * 2500;
                            historyData.push(basePrice + season + noise);
                        }
                        const lastRealPrice = historyData[historyData.length - 1];

                        // Forecast Data
                        const forecastLabels = ['Minggu Ini', 'H+1', 'H+2', 'H+3'];
                        const forecastData = [];
                        const upperBond = [];
                        const lowerBond = [];

                        // Reset list tabel
                        this.forecastList = [];

                        let futurePrice = lastRealPrice;
                        const trendFactor = Math.random() > 0.5 ? 1 : -1;

                        for (let i = 0; i < 4; i++) {
                            const step = (Math.random() * 1500 + 500) * trendFactor;
                            futurePrice += step;
                            forecastData.push(futurePrice);

                            const uncertainty = 2000 + (i * 1000);
                            const upper = futurePrice + uncertainty;
                            const lower = futurePrice - uncertainty;

                            upperBond.push(upper);
                            lowerBond.push(lower);

                            // Tambahkan data ke list untuk tabel
                            this.forecastList.push({
                                period: i === 0 ? 'Minggu Depan' : `Minggu ke-${i+1} (H+${(i+1)*7})`,
                                mean: futurePrice,
                                lower: lower,
                                upper: upper
                            });
                        }

                        // Update UI State
                        this.forecastResult = {
                            currentPrice: lastRealPrice,
                            predictedPrice: forecastData[0],
                            trend: trendFactor > 0 ? 'up' : 'down',
                            mape: (Math.random() * (8.5 - 2.1) + 2.1).toFixed(2),
                            modelParams: `SARIMA(${Math.floor(Math.random()*2)},1,1)(0,1,1,4)`,
                            confidence: '95%'
                        };

                        // Update Chart (Menggunakan variabel lokal 'chart')
                        if (chart) {
                            this.updateChartData(historyLabels, historyData, forecastLabels, forecastData, upperBond,
                                lowerBond);
                        } else {
                            console.warn("Chart instance belum siap, mencoba inisialisasi ulang...");
                            this.initChart(); // Coba init lagi jika null
                            if (chart) this.updateChartData(historyLabels, historyData, forecastLabels, forecastData,
                                upperBond, lowerBond);
                        }

                    } catch (error) {
                        console.error("Error Detail:", error);
                        // Tampilkan pesan error yang lebih spesifik
                        alert("Gagal memuat grafik: " + error.message);
                    } finally {
                        this.isLoading = false;
                    }
                },

                initChart() {
                    const canvas = document.getElementById('sarimaChart');

                    // Cek apakah library Chart sudah terload
                    if (typeof Chart === 'undefined') {
                        console.error("Library Chart.js belum dimuat.");
                        return;
                    }

                    if (!canvas) {
                        console.error("Canvas element #sarimaChart tidak ditemukan.");
                        return;
                    }

                    const ctx = canvas.getContext('2d');

                    // Hancurkan chart lama jika ada (mencegah duplikasi/glitch)
                    if (chart) {
                        chart.destroy();
                    }

                    // Buat Chart Baru (Simpan ke variabel lokal 'chart')
                    chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: [],
                            datasets: [{
                                    label: 'Data Historis (Aktual)',
                                    data: [],
                                    borderColor: '#2563eb',
                                    backgroundColor: '#2563eb',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    pointRadius: 3,
                                    fill: false
                                },
                                {
                                    label: 'Prediksi SARIMA',
                                    data: [],
                                    borderColor: '#dc2626',
                                    borderDash: [5, 5],
                                    borderWidth: 2,
                                    tension: 0.3,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: '#dc2626',
                                    fill: false
                                },
                                {
                                    label: 'Batas Bawah',
                                    data: [],
                                    borderColor: 'transparent',
                                    pointRadius: 0,
                                    fill: false
                                },
                                {
                                    label: 'Area Ketidakpastian (95%)',
                                    data: [],
                                    borderColor: 'transparent',
                                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                                    pointRadius: 0,
                                    fill: '-1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            if (context.dataset.label && context.dataset.label.includes(
                                                    'Batas')) return null;
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += new Intl.NumberFormat('id-ID', {
                                                    style: 'currency',
                                                    currency: 'IDR',
                                                    maximumFractionDigits: 0
                                                }).format(context.parsed.y);
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: false,
                                    grid: {
                                        borderDash: [2, 4]
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                },

                updateChartData(histLabels, histData, futLabels, futData, upperData, lowerData) {
                    if (!chart) return;

                    const allLabels = [...histLabels, ...futLabels];
                    const lastHist = histData[histData.length - 1];

                    const setHistory = [...histData, ...Array(futLabels.length).fill(null)];
                    const setForecast = [...Array(histLabels.length - 1).fill(null), lastHist, ...futData];
                    const setUpper = [...Array(histLabels.length - 1).fill(null), lastHist, ...upperData];
                    const setLower = [...Array(histLabels.length - 1).fill(null), lastHist, ...lowerData];

                    chart.data.labels = allLabels;
                    chart.data.datasets[0].data = setHistory;
                    chart.data.datasets[1].data = setForecast;
                    chart.data.datasets[2].data = setLower;
                    chart.data.datasets[3].data = setUpper;

                    chart.update();
                },

                formatRp(val) {
                    if (val === undefined || val === null || isNaN(val)) return 'Rp 0';
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(val);
                }
            }
        }
    </script>
@endpush

@section('content')
    <div x-data="forecastingData()" class="space-y-6">

        <!-- 1. HEADER & KONTROL PARAMETER -->
        <div class="card bg-white shadow-sm border border-slate-200">
            <div class="card-body p-6 flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                        <i class="ph-fill ph-sliders-horizontal text-blue-600"></i> Konfigurasi Model
                    </h3>
                    <p class="text-xs text-slate-500">Pilih komoditas untuk menjalankan simulasi proyeksi harga.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <select x-model="selectedRegion" @change="generateForecast()"
                        class="select select-bordered select-sm w-full md:w-48 bg-slate-50">
                        <template x-for="r in regions" :key="r">
                            <option :value="r" x-text="r"></option>
                        </template>
                    </select>

                    <select x-model="selectedCommodity" @change="generateForecast()"
                        class="select select-bordered select-sm w-full md:w-48 bg-slate-50">
                        <template x-for="c in commodities" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>

                    <button @click="generateForecast()" class="btn btn-primary btn-sm gap-2 shadow-lg shadow-blue-200"
                        :disabled="isLoading">
                        <i class="ph-bold ph-lightning" :class="isLoading ? 'animate-pulse' : ''"></i>
                        <span x-text="isLoading ? 'Processing...' : 'Generate Proyeksi'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. AREA UTAMA (GRAFIK & ANALISIS) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- GRAFIK VISUALISASI (Kiri 2/3) -->
            <div class="lg:col-span-2 card bg-white shadow-sm border border-slate-200">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-xl">
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Visualisasi Time Series</h3>
                        <p class="text-[10px] text-slate-500">Data Historis (Biru) vs Prediksi Model (Merah Putus-putus)</p>
                    </div>
                    <div class="flex gap-2">
                        <div
                            class="badge badge-outline badge-sm font-mono text-[10px] bg-white text-slate-600 border-slate-300">
                            Params: <span x-text="forecastResult.modelParams" class="font-bold ml-1"></span>
                        </div>
                        <div class="badge badge-error badge-sm text-white text-[10px]">
                            MAPE: <span x-text="forecastResult.mape + '%'" class="font-bold ml-1"></span>
                        </div>
                    </div>
                </div>

                <div class="p-5 relative h-[400px]">
                    <!-- Loading Overlay -->
                    <div x-show="isLoading" x-transition
                        class="absolute inset-0 flex flex-col items-center justify-center bg-white/90 z-10 backdrop-blur-sm rounded-b-xl">
                        <span class="loading loading-bars loading-lg text-blue-600"></span>
                        <span class="text-xs font-bold text-slate-500 mt-3 animate-pulse">Menjalankan Proyeksi
                            Inflasi</span>
                        <span class="text-[10px] text-slate-400 mt-1">Fitting Model & Calculating Confidence
                            Intervals</span>
                    </div>

                    <!-- Chart Canvas -->
                    <canvas id="sarimaChart"></canvas>
                </div>
            </div>

            <!-- EXECUTIVE SUMMARY (Kanan 1/3) -->
            <div class="card bg-white shadow-sm border border-slate-200 flex flex-col">
                <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white rounded-t-xl">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="ph-fill ph-file-text text-purple-600"></i> Analisis Hasil
                    </h3>
                </div>

                <div class="flex-1 p-6 space-y-6">
                    <!-- Status Box -->
                    <div class="text-center p-5 rounded-xl border border-dashed relative overflow-hidden"
                        :class="forecastResult.trend === 'up' ? 'bg-red-50 border-red-200' :
                            'bg-green-50 border-green-200'">

                        <i class="ph-fill absolute -right-4 -bottom-4 text-6xl opacity-10"
                            :class="forecastResult.trend === 'up' ? 'ph-trend-up' : 'ph-trend-down'"></i>

                        <div class="text-[10px] font-bold uppercase tracking-wider mb-1"
                            :class="forecastResult.trend === 'up' ? 'text-red-500' : 'text-green-500'">
                            Sinyal Peringatan Dini (H+7)
                        </div>

                        <div class="text-3xl font-black text-slate-800 flex justify-center items-center gap-2">
                            <i class="ph-fill text-2xl"
                                :class="forecastResult.trend === 'up' ? 'ph-trend-up text-red-600' :
                                    'ph-trend-down text-green-600'"></i>
                            <span x-text="forecastResult.trend === 'up' ? 'NAIK' : 'TURUN'"></span>
                        </div>

                        <div class="text-xs mt-2 text-slate-600">
                            Estimasi Harga: <span class="font-bold bg-white/50 px-2 py-0.5 rounded"
                                x-text="formatRp(forecastResult.predictedPrice)"></span>
                        </div>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                            <div class="text-[10px] text-slate-500 uppercase font-bold mb-1">Akurasi Model</div>
                            <div class="text-sm font-bold text-blue-600 flex items-center gap-1">
                                <i class="ph-fill ph-check-circle"></i>
                                <span x-text="(100 - forecastResult.mape).toFixed(2) + '%'"></span>
                            </div>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                            <div class="text-[10px] text-slate-500 uppercase font-bold mb-1">Confidence</div>
                            <div class="text-sm font-bold text-slate-700 flex items-center gap-1">
                                <i class="ph-fill ph-chart-bar"></i> 95% CI
                            </div>
                        </div>
                    </div>

                    <!-- Recommendation -->
                    <div class="text-xs text-slate-600 leading-relaxed border-t pt-4 border-slate-100">
                        <p class="font-bold mb-1 text-slate-800"><i class="ph-bold ph-sparkle text-yellow-500"></i> Insight
                            AI:</p>
                        <div x-show="forecastResult.trend === 'up'">
                            <p>
                                Grafik menunjukkan pola <span class="font-bold text-red-600">uptrend</span> melewati ambang
                                batas wajar. Terdapat probabilitas 85%
                                harga akan terus naik minggu depan.
                            </p>
                            <br>
                            <p class="font-bold text-red-600">Rekomendasi:</p>
                            <p>Segera lakukan Operasi Pasar Murah.</p>
                        </div>
                        <div x-show="forecastResult.trend === 'down'">
                            <p>Grafik menunjukkan pola <span class="font-bold text-green-600">downtrend</span> stabil.
                                Pasokan di pasar terindikasi surplus.</p>
                                <br>
                            <p class="font-bold text-green-600">Rekomendasi:</p>
                            <p>Fokus pada penyerapan gabah/hasil panen untuk menjaga NTP petani.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. DATA TABULAR (Detail Angka) -->
        <div class="card bg-white shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50 text-xs font-bold text-slate-500 uppercase">
                Rincian Data Prediksi
            </div>
            <div class="overflow-x-auto">
                <table class="table table-sm text-xs">
                    <thead>
                        <tr class="bg-white text-slate-600 border-b border-slate-100">
                            <th>Periode</th>
                            <th>Prediksi</th>
                            <th>Batas Bawah</th>
                            <th>Batas Atas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in forecastList">
                            <tr class="hover:bg-slate-50">
                                <td class="font-bold" x-text="row.period"></td>
                                <td class="text-blue-600 font-bold" x-text="formatRp(row.mean)"></td>
                                <td class="text-slate-500" x-text="formatRp(row.lower)"></td>
                                <td class="text-slate-500" x-text="formatRp(row.upper)"></td>
                                <td><span class="badge badge-ghost badge-xs font-bold">Forecast</span></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
