@extends('layouts.admin')

@section('title', 'KANTI - Smart Matching')
@section('page_title', 'Rekomendasi KAD (Smart Matching)')

@push('scripts')
    <script>
        function matchingData() {
            return {
                matches: [],
                isLoading: true,
                filterType: 'all', // all, high_priority, medium

                async init() {
                    // Simulasi Fetch Data Rekomendasi AI
                    await new Promise(r => setTimeout(r, 800));

                    // Use same fixed inflasi values as dashboard (index)
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

                    const commodities = ['Cabai Merah', 'Bawang Merah', 'Daging Ayam Ras', 'Telur Ayam',
                        'Beras Premium'
                    ];
                    const sources = [{
                            name: 'Kab. Kerinci',
                            type: 'Surplus',
                            inflasi: inflasiMap['Kab. Kerinci']
                        },
                        {
                            name: 'Kab. Merangin',
                            type: 'Surplus',
                            inflasi: inflasiMap['Kab. Merangin']
                        },
                        {
                            name: 'Kab. Sarolangun',
                            type: 'Surplus',
                            inflasi: inflasiMap['Kab. Sarolangun']
                        }
                    ];
                    const targets = [{
                            name: 'Kota Jambi',
                            type: 'Defisit',
                            inflasi: inflasiMap['Kota Jambi']
                        },
                        {
                            name: 'Kab. Bungo',
                            type: 'Defisit',
                            inflasi: inflasiMap['Kab. Bungo']
                        },
                        {
                            name: 'Tanjung Jabung Barat',
                            type: 'Defisit',
                            inflasi: inflasiMap['Tanjung Jabung Barat']
                        }
                    ];

                    this.matches = Array.from({
                        length: 8
                    }).map((_, i) => {
                        const comm = commodities[Math.floor(Math.random() * commodities.length)];
                        const src = sources[Math.floor(Math.random() * sources.length)];
                        const trg = targets[Math.floor(Math.random() * targets.length)];

                        const priceSrc = Math.floor(Math.random() * 20000) + 15000;
                        const priceTrg = priceSrc + Math.floor(Math.random() * 15000) + 5000;
                        const spread = priceTrg - priceSrc;
                        const gapPct = ((spread / priceTrg) * 100).toFixed(1);

                        // Logic Prioritas: Jika selisih harga > 30%, maka High Priority
                        const priority = gapPct > 30 ? 'high' : 'medium';

                        return {
                            id: i + 1,
                            commodity: comm,
                            source: src.name,
                            sourceInflation: src.inflasi ?? src.inflasi === 0 ? src.inflasi : src
                                .inflasi, // kept for compatibility
                            target: trg.name,
                            targetInflation: trg.inflasi ?? trg.inflasi === 0 ? trg.inflasi : trg.inflasi,
                            qty: Math.floor(Math.random() * 8) + 2 + ' Ton',
                            priceSrc: priceSrc,
                            priceTrg: priceTrg,
                            spread: spread,
                            gapPct: gapPct,
                            distance: Math.floor(Math.random() * 300) + 50 + ' Km',
                            priority: priority
                        };
                    });

                    this.isLoading = false;
                },

                formatRp(val) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(val);
                },

                get filteredMatches() {
                    if (this.filterType === 'all') return this.matches;
                    return this.matches.filter(m => m.priority === (this.filterType === 'high_priority' ? 'high' :
                        'medium'));
                }
            }
        }
    </script>
@endpush

@section('content')
    <div x-data="matchingData()">

        <!-- HEADER & SUMMARY -->
        <div class="flex flex-col md:flex-row justify-between items-end mb-6 gap-4">
            <div>
                <h3 class="font-bold text-slate-800 text-lg">Peluang Kerjasama Antar Daerah</h3>
                <p class="text-sm text-slate-500 max-w-2xl mt-1">
                    Sistem mendeteksi potensi <span class="font-bold text-blue-600">Surplus</span> dan <span
                        class="font-bold text-red-500">Defisit</span> antar wilayah.
                </p>
            </div>

            <!-- Filter Tabs -->
            <div role="tablist" class="tabs tabs-boxed bg-white border border-slate-200 p-1">
                <a role="tab" class="tab tab-sm"
                    :class="filterType === 'all' ? 'tab-active bg-blue-600 text-white' : ''"
                    @click="filterType = 'all'">Semua</a>
                <a role="tab" class="tab tab-sm"
                    :class="filterType === 'high_priority' ? 'tab-active bg-red-500 text-white' : ''"
                    @click="filterType = 'high_priority'">Prioritas Tinggi</a>
                <a role="tab" class="tab tab-sm"
                    :class="filterType === 'medium' ? 'tab-active bg-orange-400 text-white' : ''"
                    @click="filterType = 'medium'">Menengah</a>
            </div>
        </div>

        <!-- LOADING SKELETON -->
        <div x-show="isLoading" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <template x-for="i in 4">
                <div class="skeleton h-40 w-full bg-slate-200 rounded-xl"></div>
            </template>
        </div>

        <!-- MATCHING CARDS GRID -->
        <div x-show="!isLoading" class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-transition>
            <template x-for="match in filteredMatches" :key="match.id">
                <div class="card bg-white border hover:shadow-lg transition-all duration-300 group"
                    :class="match.priority === 'high' ? 'border-red-200 bg-red-50/20' : 'border-slate-200'">

                    <div class="card-body p-6">
                        <!-- Header Card -->
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 text-slate-600">
                                    <i class="ph-fill ph-package text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800" x-text="match.commodity"></h4>
                                    <span class="text-xs text-slate-500 font-mono bg-slate-100 px-2 py-0.5 rounded">Volume:
                                        <span x-text="match.qty"></span></span>
                                </div>
                            </div>

                            <!-- Priority Badge -->
                            <div class="badge badge-lg border-none text-white font-bold gap-1"
                                :class="match.priority === 'high' ? 'bg-red-500 animate-pulse' : 'bg-orange-400'">
                                <i class="ph-fill ph-warning-circle"></i>
                                <span x-text="match.priority === 'high' ? 'High Priority' : 'Medium'"></span>
                            </div>
                        </div>

                        <!-- Flow Visualization (Source -> Target) -->
                        <div
                            class="relative bg-slate-50 rounded-xl p-4 border border-slate-100 flex items-center justify-between gap-4 mb-4">
                            <!-- Source -->
                            <div class="flex-1 text-center">
                                <div class="text-[10px] text-slate-400 font-bold uppercase mb-1">Daerah Surplus</div>
                                <div class="font-bold text-green-700 text-sm" x-text="match.source"></div>
                                <div class="text-xs text-slate-500 mt-1" x-text="formatRp(match.priceSrc)"></div>
                                <div class="text-xs text-slate-400 mt-1">Inflasi: <span
                                        class="font-semibold text-sm text-slate-700"
                                        x-text="match.sourceInflation ? (Number(match.sourceInflation).toFixed(2) + '%') : '—'"></span>
                                </div>
                            </div>

                            <!-- Arrow & Gap -->
                            <div class="flex flex-col items-center justify-center relative z-10 w-24">
                                <div class="text-[10px] font-bold text-slate-400 mb-1" x-text="match.distance"></div>
                                <div class="w-full h-0.5 bg-slate-300 relative">
                                    <i class="ph-fill ph-caret-right absolute -right-1 -top-1.5 text-slate-300 text-sm"></i>
                                </div>
                                <div class="mt-1 badge badge-sm bg-blue-100 text-blue-700 border-none font-bold"
                                    x-text="'Gap: ' + match.gapPct + '%'"></div>
                            </div>

                            <!-- Target -->
                            <div class="flex-1 text-center">
                                <div class="text-[10px] text-slate-400 font-bold uppercase mb-1">Daerah Defisit</div>
                                <div class="font-bold text-red-700 text-sm" x-text="match.target"></div>
                                <div class="text-xs text-slate-500 mt-1" x-text="formatRp(match.priceTrg)"></div>
                                <div class="text-xs text-slate-400 mt-1">Inflasi: <span
                                        class="font-semibold text-sm text-slate-700"
                                        x-text="match.targetInflation ? (Number(match.targetInflation).toFixed(2) + '%') : '—'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Info & Action -->
                        <div class="flex items-center justify-between pt-2">
                            <div class="flex flex-col">
                                <span class="text-xs text-slate-400">Potensi Penurunan Harga</span>
                                <span class="font-bold text-green-600 text-lg flex items-center gap-1">
                                    <i class="ph-bold ph-trend-down"></i>
                                    <span x-text="formatRp(match.spread)"></span>/kg
                                </span>
                            </div>

                            <!-- Read Only Action -->
                            <div class="tooltip" data-tip="Unduh detail rekomendasi untuk rapat TPID">
                                <button class="btn btn-sm btn-outline gap-2 hover:bg-slate-800 hover:text-white transition">
                                    <i class="ph-bold ph-file-pdf"></i>
                                    Lihat Detail
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!isLoading && filteredMatches.length === 0" class="text-center py-20" x-cloak>
            <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ph-fill ph-check-circle text-4xl text-green-500"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Tidak ada rekomendasi baru</h3>
            <p class="text-slate-500">Pasar saat ini terpantau stabil dan seimbang.</p>
        </div>

    </div>
@endsection
