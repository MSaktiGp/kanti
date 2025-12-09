@extends('layouts.admin')

@section('title', 'KANTI - Peta Geospasial')
@section('page_title', 'Peta Neraca Pangan Regional')

@push('scripts')
<!-- Load Leaflet & Leaflet Heatmap -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>

<script>
    function mapData() {
        return {
            map: null,
            markers: [],
            disasterLayerGroup: null,
            heatmapLayerGroup: null,
            selectedRegion: null,
            filterCommodity: 'all',
            isLoading: true,
            
            // LAYER TOGGLES
            showDisasterLayer: false,
            showHeatmap: false,

            // DAFTAR 10 KOMODITAS UTAMA
            commodityList: [
                'Cabai Merah', 'Bawang Merah','Bawang Putih', 'Beras', 
                'Daging Ayam Ras', 'Telur Ayam Ras', 'Cabai Rawit', 
                'Daging Sapi', 'Gula Pasir', 'Minyak Goreng'
            ],

            // DATA WILAYAH
            // PERBAIKAN: Nama 'Tanjab' diubah menjadi 'Tanjung Jabung' agar sesuai dengan halaman Prediksi
            regions: [
                { name: 'Kota Jambi', coords: [-1.6101, 103.6131], type: 'consumen', inflasi: 2.68, status: 'stable', commodities: ['Daging Ayam Ras', 'Telur Ayam Ras', 'Gula Pasir'] },
                { name: 'Kab. Kerinci', coords: [-1.9329, 101.2524], type: 'produsen', inflasi: 6.70, status: 'critical', commodities: ['Cabai Merah', 'Cabai Rawit', 'Bawang Merah', 'Bawang Putih', 'Beras'] },
                { name: 'Kab. Merangin', coords: [-2.2000, 102.2500], type: 'produsen', inflasi: 2.10, status: 'stable', commodities: ['Beras', 'Cabai Merah', 'Minyak Goreng', 'Daging Sapi'] },
                { name: 'Muaro Jambi', coords: [-1.4500, 103.7000], type: 'netral', inflasi: 3.05, status: 'stable', commodities: ['Cabai Rawit', 'Daging Ayam Ras', 'Telur Ayam Ras', 'Minyak Goreng'] },
                { name: 'Kab. Batanghari', coords: [-1.7500, 103.1500], type: 'netral', inflasi: 3.80, status: 'warning', commodities: ['Telur Ayam Ras', 'Daging Sapi', 'Minyak Goreng', 'Beras'] },
                { name: 'Kab. Sarolangun', coords: [-2.3000, 102.7000], type: 'produsen', inflasi: 3.15, status: 'stable', commodities: ['Minyak Goreng', 'Cabai Merah', 'Daging Sapi'] },
                { name: 'Kab. Bungo', coords: [-1.4800, 102.1300], type: 'consumen', inflasi: 2.54, status: 'stable', commodities: ['Beras', 'Daging Sapi', 'Daging Ayam Ras'] },
                { name: 'Kab. Tebo', coords: [-1.4000, 102.4000], type: 'netral', inflasi: 4.68, status: 'critical', commodities: ['Minyak Goreng', 'Telur Ayam Ras', 'Cabai Rawit'] },
                { name: 'Tanjung Jabung Barat', coords: [-1.0500, 103.2000], type: 'consumen', inflasi: 3.90, status: 'warning', commodities: ['Minyak Goreng', 'Beras', 'Daging Ayam Ras'] },
                { name: 'Tanjung Jabung Timur', coords: [-1.1500, 103.7500], type: 'produsen', inflasi: 3.20, status: 'stable', commodities: ['Beras', 'Minyak Goreng', 'Cabai Merah'] },
                { name: 'Kota Sungai Penuh', coords: [-2.0600, 101.3900], type: 'consumen', inflasi: 4.10, status: 'warning', commodities: ['Bawang Merah', 'Bawang Putih', 'Cabai Merah'] }
            ],

            init() {
                setTimeout(() => {
                    this.initMap();
                    this.isLoading = false;
                }, 500);
            },

            initMap() {
                this.map = L.map('geoMap', {
                    center: [-1.70, 102.5],
                    zoom: 8,
                    zoomControl: false 
                });

                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '© OpenStreetMap & CartoDB',
                    maxZoom: 19
                }).addTo(this.map);

                // Klik di area kosong peta akan menutup panel detail
                this.map.on('click', () => {
                    this.selectedRegion = null;
                });

                // Init Groups
                this.disasterLayerGroup = L.layerGroup().addTo(this.map);
                this.heatmapLayerGroup = L.layerGroup().addTo(this.map);

                this.renderMarkers();
            },

            renderMarkers() {
                this.markers.forEach(m => this.map.removeLayer(m));
                this.markers = [];

                this.regions.forEach(reg => {
                    if (this.filterCommodity !== 'all') {
                        if (!reg.commodities || !reg.commodities.includes(this.filterCommodity)) return;
                    }

                    let colorClass = 'bg-green-500';
                    let pulseClass = '';
                    if (reg.inflasi > 4.5) {
                        colorClass = 'bg-red-500';
                        pulseClass = 'animate-ping';
                    } else if (reg.inflasi > 3.5) {
                        colorClass = 'bg-yellow-500';
                    }

                    const icon = L.divIcon({
                        className: 'bg-transparent',
                        html: `
                            <div class="relative group cursor-pointer">
                                <div class="absolute -inset-2 ${colorClass} rounded-full opacity-30 ${pulseClass}"></div>
                                <div class="w-8 h-8 ${colorClass} rounded-full flex items-center justify-center border-2 border-white shadow-lg relative z-10 transition-transform transform group-hover:scale-110">
                                    <span class="text-white font-bold text-xs">${reg.inflasi}</span>
                                </div>
                                <div class="absolute top-10 left-1/2 -translate-x-1/2 bg-white px-2 py-1 rounded shadow-md text-[10px] font-bold whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-20 pointer-events-none">
                                    ${reg.name}
                                </div>
                            </div>
                        `,
                        iconSize: [32, 32],
                        iconAnchor: [16, 16]
                    });

                    // Gunakan L.DomEvent.stopPropagation untuk mencegah klik marker menutup panel (karena bubbling ke map click)
                    const marker = L.marker(reg.coords, {icon: icon}).addTo(this.map);
                    
                    marker.on('click', (e) => { 
                        L.DomEvent.stopPropagation(e); // Stop event agar tidak dianggap klik peta kosong
                        this.selectedRegion = reg; // Langsung ganti data tanpa menutup
                    });
                    
                    this.markers.push(marker);
                });
            },

            updateFilter() {
                this.renderMarkers();
                this.updateHeatmap();
            },

            // --- FITUR BARU: LAYER BENCANA ---
            toggleDisasterLayer() {
                this.disasterLayerGroup.clearLayers();
                
                if (this.showDisasterLayer) {
                    const floodZones = [
                        { coords: [[-1.9, 101.2], [-2.0, 101.3], [-2.1, 101.25], [-2.0, 101.15]], color: 'red', desc: 'Waspada Banjir Bandang (BMKG)' },
                        { coords: [[-1.6, 103.5], [-1.55, 103.7], [-1.65, 103.8], [-1.7, 103.6]], color: 'blue', desc: 'Siaga Banjir Pasang' }
                    ];

                    floodZones.forEach(zone => {
                        L.polygon(zone.coords, {
                            color: zone.color, fillColor: zone.color, fillOpacity: 0.3, weight: 1, dashArray: '5, 5'
                        }).bindTooltip(zone.desc, {permanent: true, direction: 'center', className: 'bg-white/80 text-xs font-bold border-none shadow-sm'}).addTo(this.disasterLayerGroup);
                    });
                }
            },

            // --- FITUR BARU: HEATMAP HARGA ---
            toggleHeatmap() {
                this.heatmapLayerGroup.clearLayers();

                if (this.showHeatmap) {
                    const heatPoints = this.regions.map(r => [
                        r.coords[0], r.coords[1], (r.inflasi / 8) * 1.5 
                    ]);

                    L.heatLayer(heatPoints, {
                        radius: 40, blur: 25, maxZoom: 10,
                        gradient: {0.4: 'blue', 0.65: 'lime', 1: 'red'}
                    }).addTo(this.heatmapLayerGroup);
                }
            },

            goToProyeksi() {
                if (this.selectedRegion) {
                    // Redirect ke halaman prediksi dengan parameter query region
                    // Pastikan route sesuai dengan layout admin ('/prediksi')
                    window.location.href = `/prediksi?region=${encodeURIComponent(this.selectedRegion.name)}`;
                }
            }
        }
    }
</script>
@endpush

@section('content')
<div x-data="mapData()" class="relative h-full flex flex-col">
    
    <!-- TOOLBAR ATAS (Floating) -->
    <div class="absolute top-4 left-4 right-4 z-[1000] flex flex-col md:flex-row justify-between items-start gap-4 pointer-events-none">
        
        <!-- Filter Card -->
        <div class="bg-white/90 backdrop-blur-sm p-3 rounded-xl shadow-lg border border-slate-200 pointer-events-auto flex items-center gap-3">
            <div class="text-xs font-bold text-slate-500 uppercase whitespace-nowrap">
                <i class="ph-bold ph-funnel text-blue-600 mr-1"></i> Filter Komoditas:
            </div>
            <select x-model="filterCommodity" @change="updateFilter()" class="select select-bordered select-xs w-full max-w-xs bg-white text-slate-700 font-medium focus:outline-none focus:border-blue-500">
                <option value="all">Semua Komoditas</option>
                <template x-for="item in commodityList" :key="item">
                    <option :value="item" x-text="item"></option>
                </template>
            </select>
        </div>

        <!-- Layer Controls -->
        <div class="flex flex-col gap-2 pointer-events-auto">
            <div class="bg-white/90 backdrop-blur-sm p-3 rounded-xl shadow-lg border border-slate-200">
                <div class="text-xs font-bold text-slate-500 uppercase mb-2">Overlay Layers</div>
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3 py-1">
                        <input type="checkbox" class="toggle toggle-error toggle-xs" x-model="showDisasterLayer" @change="toggleDisasterLayer()" />
                        <span class="label-text text-xs font-medium flex items-center gap-1"><i class="ph-fill ph-cloud-rain text-blue-500"></i> Rawan Bencana</span>
                    </label>
                </div>
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3 py-1">
                        <input type="checkbox" class="toggle toggle-warning toggle-xs" x-model="showHeatmap" @change="toggleHeatmap()" />
                        <span class="label-text text-xs font-medium flex items-center gap-1"><i class="ph-fill ph-fire text-orange-500"></i> Heatmap Inflasi</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- MAP CONTAINER -->
    <div id="geoMap" class="w-full h-[calc(100vh-140px)] rounded-xl border border-slate-300 z-0 shadow-inner bg-slate-100 relative">
        <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-slate-100 z-[2000]">
            <span class="loading loading-spinner loading-lg text-blue-600"></span>
        </div>
    </div>

    <!-- FLOATING DETAIL CARD (Gaya Baru: Tidak Full Height) -->
    <div x-show="selectedRegion" 
         x-transition:enter="transform transition ease-out duration-300"
         x-transition:enter-start="translate-x-10 opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transform transition ease-in duration-200"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="translate-x-10 opacity-0"
         class="absolute top-32 right-4 w-80 bg-white/95 backdrop-blur-md shadow-2xl border border-slate-200 rounded-2xl z-[1001] p-5 overflow-y-auto max-h-[calc(100vh-180px)]"
         x-cloak>
        
        <!-- Header -->
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="font-bold text-lg text-slate-800 leading-tight" x-text="selectedRegion?.name"></h3>
                <div class="badge badge-sm font-bold text-white mt-1 shadow-sm" 
                     :class="selectedRegion?.inflasi > 4.5 ? 'badge-error' : (selectedRegion?.inflasi > 3.5 ? 'badge-warning' : 'badge-success')"
                     x-text="selectedRegion?.status === 'critical' ? 'Bahaya Inflasi' : (selectedRegion?.status === 'warning' ? 'Perlu Atensi' : 'Stabil')"></div>
            </div>
            <!-- Close Button -->
            <button @click="selectedRegion = null" class="btn btn-circle btn-ghost btn-sm text-slate-400 hover:bg-slate-100">
                <i class="ph-bold ph-x text-lg"></i>
            </button>
        </div>

        <!-- Peringatan Bencana -->
        <div x-show="selectedRegion?.name === 'Kab. Kerinci' && showDisasterLayer" class="alert alert-error text-xs p-3 mb-4 shadow-sm border-none bg-red-50 text-red-700">
            <i class="ph-fill ph-warning-circle text-lg"></i>
            <div><span class="font-bold">Alert BMKG:</span> Potensi banjir bandang.</div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 gap-3 mb-5">
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
                <div class="text-[10px] text-slate-500 uppercase font-bold mb-1">Inflasi (yoy)</div>
                <div class="text-2xl font-black text-slate-800 tracking-tight" x-text="selectedRegion?.inflasi + '%'"></div>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
                <div class="text-[10px] text-slate-500 uppercase font-bold mb-1">Peran Daerah</div>
                <div class="text-sm font-bold capitalize flex items-center justify-center gap-1" 
                     :class="selectedRegion?.type === 'produsen' ? 'text-green-600' : (selectedRegion?.type === 'consumen' ? 'text-blue-600' : 'text-slate-600')">
                     <i class="ph-fill" :class="selectedRegion?.type === 'produsen' ? 'ph-plant' : (selectedRegion?.type === 'consumen' ? 'ph-shopping-cart' : 'ph-scales')"></i>
                     <span x-text="selectedRegion?.type"></span>
                </div>
            </div>
        </div>

        <!-- List Komoditas -->
        <div class="mb-6">
            <h4 class="font-bold text-xs text-slate-500 uppercase mb-3 flex items-center gap-2">
                Komoditas Unggulan
            </h4>
            <div class="flex flex-wrap gap-2" x-show="selectedRegion?.commodities && selectedRegion?.commodities.length > 0">
                <template x-for="com in selectedRegion?.commodities">
                    <span class="badge badge-outline text-xs font-semibold bg-white" x-text="com"></span>
                </template>
            </div>
            <div x-show="!selectedRegion?.commodities || selectedRegion?.commodities.length === 0" class="text-xs text-slate-400 italic text-center py-2">
                Data tidak tersedia.
            </div>
        </div>

        <!-- Action -->
        <div class="pt-4 border-t border-slate-100">
            <button @click="goToProyeksi()" class="btn btn-primary w-full gap-2 shadow-lg shadow-blue-200 btn-sm h-10 rounded-lg">
                <i class="ph-bold ph-chart-line-up"></i> Lihat Analisis & Proyeksi
            </button>
        </div>
    </div>
</div>
@endsection