@extends('layouts.admin')

@section('title', 'KANTI - Dashboard TPID')
@section('page_title', 'TPID Executive Dashboard')

@push('scripts')
    <script>
        function dashboardData() {
            return {
                stats: [],
                commodities: [],
                isLoading: true,
                viewInflation: 'table',
                viewPrices: 'table',

                // FETCH DATA SIMULATION
                async init() {
                    // Simulasi delay fetch data
                    await new Promise(r => setTimeout(r, 1000));

                    // 1. Generate Data Statistik Wilayah
                    const regions = [{
                            name: 'Kota Jambi',
                            type: 'consumen'
                        }, {
                            name: 'Kab. Kerinci',
                            type: 'produsen'
                        },
                        {
                            name: 'Kab. Merangin',
                            type: 'produsen'
                        }, {
                            name: 'Muaro Jambi',
                            type: 'netral'
                        },
                        {
                            name: 'Kab. Batanghari',
                            type: 'netral'
                        }, {
                            name: 'Kab. Sarolangun',
                            type: 'produsen'
                        },
                        {
                            name: 'Kab. Bungo',
                            type: 'consumen'
                        }, {
                            name: 'Kab. Tebo',
                            type: 'netral'
                        },
                        {
                            name: 'Tanjung Jabung Barat',
                            type: 'consumen'
                        }, {
                            name: 'Tanjung Jabung Timur',
                            type: 'produsen'
                        },
                        {
                            name: 'Kota Sungai Penuh',
                            type: 'consumen'
                        }
                    ];

                    // Fixed inflasi values to match `peta.blade.php`
                    const inflasiMap = {
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
                    };

                    this.stats = regions.map(reg => {
                        const fixed = inflasiMap[reg.name] !== undefined ? inflasiMap[reg.name] : (Math
                        .random() * (6.0 - 2.0) + 2.0);
                        const inflasi = Number(fixed).toFixed(2); // Ensure 2 decimals
                        const mtmNum = (Math.random() * 1.5 - 0.5);
                        const mtm = mtmNum.toFixed(2);

                        // LOGIKA STATUS (BERDASARKAN NILAI ANGKA)
                        let status, desc;
                        const inflVal = Number(inflasi);
                        if (inflVal > 4.5) {
                            status = 'critical';
                            desc = 'Bahaya';
                        } else if (inflVal > 3.5) {
                            status = 'warning';
                            desc = 'Waspada';
                        } else {
                            status = 'stable';
                            desc = 'Aman';
                        }

                        return {
                            region: reg.name,
                            inflation: inflasi,
                            mtm: mtmNum > 0 ? `+${mtm}%` : `${mtm}%`,
                            trend: mtmNum > 0 ? 'up' : 'down',
                            status: status,
                            desc: desc
                        };
                    });

                    // 2. Generate Data 10 Komoditas Utama
                    const items = ['Cabai Merah', 'Bawang Merah', 'Bawang Putih', 'Beras', 'Daging Ayam Ras',
                        'Telur Ayam Ras', 'Cabai Rawit', 'Daging Sapi', 'Gula Pasir', 'Minyak Goreng'
                    ];
                    const bases = [45000, 32000, 38000, 14000, 35000, 28000, 55000, 130000, 17000, 16000];

                    this.commodities = items.map((name, i) => {
                        const base = bases[i];
                        const change = Math.floor(Math.random() * 4000) - 2000;
                        const pct = ((change / base) * 100).toFixed(1);
                        return {
                            name: name,
                            price: base + change,
                            prev: base,
                            change: change,
                            pct: pct,
                            status: change > 0 ? 'up' : (change < 0 ? 'down' : 'stable')
                        };
                    });

                    this.isLoading = false;

                    // Trigger Global Toast di Layout
                    this.$dispatch('show-toast', 'Data Dashboard berhasil diperbarui');
                },

                formatRp(val) {
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
    <div x-data="dashboardData()">

        <!-- SECTION 1: CAROUSEL STATISTIK WILAYAH -->
        <div class="mb-8">
            <div class="flex justify-between items-end mb-4 px-1">
                <div>
                    <h3 class="font-bold text-slate-800 text-lg">Pantauan Inflasi Wilayah</h3>
                    <p class="text-xs text-slate-500">Geser untuk melihat status inflasi seluruh Kab/Kota</p>
                </div>
                <!-- Indikator Geser -->
                <div class="flex gap-2 text-slate-400">
                    <i class="ph-bold ph-caret-left"></i>
                    <i class="ph-bold ph-hand-swipe"></i>
                    <i class="ph-bold ph-caret-right"></i>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div x-show="isLoading" class="flex gap-4 overflow-x-hidden">
                <template x-for="i in 4">
                    <div class="skeleton h-36 w-64 shrink-0 rounded-xl bg-slate-200"></div>
                </template>
            </div>

            <!-- Carousel -->
            <div x-show="!isLoading" x-transition
                class="carousel carousel-center w-full p-4 space-x-4 bg-white rounded-box border border-slate-200 shadow-sm no-scrollbar cursor-grab active:cursor-grabbing">

                <template x-for="stat in stats" :key="stat.region">
                    <div class="carousel-item">
                        <div
                            class="card w-72 bg-base-100 shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                            <div class="card-body p-5">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex flex-col">
                                        <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider"
                                            x-text="stat.region"></span>
                                        <span class="text-[10px] text-slate-400">Oktober 2025</span>
                                    </div>
                                    <div class="badge gap-1 badge-sm font-bold border-none text-white"
                                        :class="stat.status === 'critical' ? 'bg-red-500 animate-pulse' : (stat
                                            .status === 'warning' ? 'bg-orange-400' : 'bg-green-500')">
                                        <span x-text="stat.desc"></span>
                                    </div>
                                </div>

                                <div class="flex items-end gap-2 mb-2">
                                    <div class="text-4xl font-bold text-slate-800" x-text="stat.inflation + '%'"></div>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                                    <div class="flex items-center text-xs font-medium">
                                        <span class="flex items-center gap-1"
                                            :class="stat.trend === 'up' ? 'text-red-500' : 'text-green-500'">
                                            <i class="ph-bold"
                                                :class="stat.trend === 'up' ? 'ph-trend-up' : 'ph-trend-down'"></i>
                                            <span x-text="stat.mtm"></span>
                                        </span>
                                        <span class="text-slate-400 ml-1">mtm</span>
                                    </div>
                                    <i class="ph-fill ph-chart-line-up text-slate-200 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- SECTION 2: GRID DATA (INFLASI & HARGA) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-8">

            <!-- CARD KIRI: DATA INFLASI -->
            <div class="card bg-white shadow-sm border border-slate-200 flex flex-col h-[500px]">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-blue-50 rounded-lg text-blue-600"><i class="ph-fill ph-trend-up text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Data Inflasi</h3>
                            <p class="text-xs text-slate-500">Perkembangan IHK Daerah</p>
                        </div>
                    </div>
                    <!-- Toggle -->
                    <div class="join border border-slate-200 rounded-lg p-1 bg-slate-50">
                        <button class="btn btn-xs join-item border-none shadow-none"
                            :class="viewInflation === 'table' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-400'"
                            @click="viewInflation = 'table'"><i class="ph-bold ph-table"></i></button>
                        <button class="btn btn-xs join-item border-none shadow-none"
                            :class="viewInflation === 'chart' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-400'"
                            @click="viewInflation = 'chart'"><i class="ph-bold ph-chart-bar"></i></button>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden relative">
                    <!-- Table View -->
                    <div x-show="viewInflation === 'table'" class="h-full overflow-y-auto custom-scroll" x-transition>
                        <table class="table table-pin-rows table-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th>Wilayah</th>
                                    <th class="text-right">Inflasi (yoy)</th>
                                    <th class="text-right">MtM</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="s in stats" :key="s.region">
                                    <tr class="hover:bg-slate-50">
                                        <td class="font-medium text-slate-700" x-text="s.region"></td>
                                        <td class="text-right font-bold" x-text="s.inflation + '%'"></td>
                                        <td class="text-right text-xs"
                                            :class="s.trend === 'up' ? 'text-red-500' : 'text-green-500'"
                                            x-text="s.mtm"></td>
                                        <td class="text-center"><span class="badge badge-xs font-bold text-white"
                                                :class="s.status === 'critical' ? 'bg-red-500' : (s.status === 'warning' ?
                                                    'bg-orange-400' : 'bg-green-500')"
                                                x-text="s.desc"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <!-- Chart View -->
                    <div x-show="viewInflation === 'chart'" class="h-full overflow-y-auto custom-scroll p-5 space-y-4"
                        x-transition>
                        <template x-for="s in stats" :key="s.region">
                            <div>
                                <div class="flex justify-between text-xs mb-1"><span class="font-medium text-slate-600"
                                        x-text="s.region"></span><span class="font-bold text-slate-800"
                                        x-text="s.inflation + '%'"></span></div>
                                <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000"
                                        :class="s.status === 'critical' ? 'bg-red-500' : (s.status === 'warning' ?
                                            'bg-orange-400' : 'bg-green-500')"
                                        :style="`width: ${(s.inflation / 8) * 100}%`"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- CARD KANAN: HARGA PANGAN -->
            <div class="card bg-white shadow-sm border border-slate-200 flex flex-col h-[500px]">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-green-50 rounded-lg text-green-600"><i
                                class="ph-fill ph-shopping-cart text-xl"></i></div>
                        <div>
                            <h3 class="font-bold text-slate-800">Harga Pangan</h3>
                            <p class="text-xs text-slate-500">10 Komoditas Strategis</p>
                        </div>
                    </div>
                    <!-- Toggle -->
                    <div class="join border border-slate-200 rounded-lg p-1 bg-slate-50">
                        <button class="btn btn-xs join-item border-none shadow-none"
                            :class="viewPrices === 'table' ? 'bg-white text-green-600 shadow-sm' : 'text-slate-400'"
                            @click="viewPrices = 'table'"><i class="ph-bold ph-table"></i></button>
                        <button class="btn btn-xs join-item border-none shadow-none"
                            :class="viewPrices === 'chart' ? 'bg-white text-green-600 shadow-sm' : 'text-slate-400'"
                            @click="viewPrices = 'chart'"><i class="ph-bold ph-chart-bar"></i></button>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden relative">
                    <!-- Table View -->
                    <div x-show="viewPrices === 'table'" class="h-full overflow-y-auto custom-scroll" x-transition>
                        <table class="table table-pin-rows table-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th>Komoditas</th>
                                    <th class="text-right">Harga (Rp)</th>
                                    <th class="text-right">Perubahan</th>
                                    <th class="text-center">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in commodities" :key="item.name">
                                    <tr class="hover:bg-slate-50">
                                        <td class="font-medium text-slate-700 text-xs" x-text="item.name"></td>
                                        <td class="text-right font-bold text-slate-800 text-xs"
                                            x-text="formatRp(item.price)"></td>
                                        <td class="text-right text-[10px]">
                                            <div class="flex flex-col items-end">
                                                <span
                                                    :class="item.status === 'up' ? 'text-red-500' : (item
                                                        .status === 'down' ? 'text-green-500' :
                                                        'text-slate-400')">
                                                    <span x-text="item.change > 0 ? '+' : ''"></span><span
                                                        x-text="formatRp(item.change)"></span>
                                                </span>
                                                <span class="opacity-70" x-text="'(' + item.pct + '%)'"></span>
                                            </div>
                                        </td>
                                        <td class="text-center"><i class="ph-bold"
                                                :class="item.status === 'up' ? 'ph-trend-up text-red-500' : (item
                                                    .status === 'down' ? 'ph-trend-down text-green-500' :
                                                    'ph-minus text-slate-300')"></i>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <!-- Chart View -->
                    <div x-show="viewPrices === 'chart'" class="h-full overflow-y-auto custom-scroll p-5 space-y-6"
                        x-transition>
                        <template x-for="item in commodities" :key="item.name">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-bold text-slate-700" x-text="item.name"></span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded"
                                        :class="item.status === 'up' ? 'bg-red-100 text-red-600' : (item
                                            .status === 'down' ? 'bg-green-100 text-green-600' :
                                            'bg-slate-100 text-slate-500')"
                                        x-text="item.status === 'up' ? 'Naik' : (item.status === 'down' ? 'Turun' : 'Tetap')"></span>
                                </div>
                                <!-- Bar Chart Visual -->
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 text-[10px] text-slate-400 text-right">Kemarin</div>
                                        <div class="flex-1 h-2 bg-slate-100 rounded-r-full overflow-hidden">
                                            <div class="h-full bg-slate-300" style="width: 70%"></div>
                                        </div>
                                        <div class="w-14 text-[10px] text-slate-400 text-right"
                                            x-text="formatRp(item.prev)"></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 text-[10px] text-slate-500 font-bold text-right">Hari Ini</div>
                                        <div class="flex-1 h-2 bg-slate-100 rounded-r-full overflow-hidden">
                                            <div class="h-full transition-all duration-1000"
                                                :class="item.status === 'up' ? 'bg-red-500' : (item
                                                    .status === 'down' ? 'bg-green-500' : 'bg-blue-500')"
                                                :style="`width: ${70 * (item.price / item.prev)}%`"></div>
                                        </div>
                                        <div class="w-14 text-[10px] font-bold text-right" x-text="formatRp(item.price)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
