<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KANTI - Admin TPID')</title>
    
    <!-- LIBRARIES (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- GLOBAL STYLES -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        
        /* Utility Scrollbar */
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        
        /* Hide Scrollbar */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <!-- STACK FOR PAGE SPECIFIC SCRIPTS -->
    @stack('scripts')
</head>

<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col md:flex-row overflow-hidden" 
      x-data="{ sidebarOpen: true, globalToast: { show: false, msg: '' } }"
      @show-toast.window="globalToast.show = true; globalToast.msg = $event.detail; setTimeout(() => globalToast.show = false, 3000)">

    <!-- TOAST NOTIFICATION GLOBAL -->
    <div class="toast toast-bottom toast-end z-[100]" x-cloak x-show="globalToast.show" 
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2">
        <div class="alert alert-success bg-slate-800 text-white border-none shadow-lg flex gap-2 p-3">
            <i class="ph-fill ph-check-circle text-green-400 text-xl"></i>
            <span class="text-sm font-medium" x-text="globalToast.msg"></span>
        </div>
    </div>

    <!-- SIDEBAR -->
    <aside class="bg-slate-900 text-slate-300 flex flex-col h-screen fixed left-0 top-0 z-40 transition-all duration-300"
           :class="sidebarOpen ? 'w-64' : 'w-20'">
        
        <!-- Brand -->
        <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-slate-950">
            <div class="flex items-center gap-3 text-white font-bold text-2xl tracking-tight overflow-hidden whitespace-nowrap">
                <i class="ph-fill ph-activity text-blue-500 text-3xl shrink-0"></i>
                <span x-show="sidebarOpen" x-transition>KANTI</span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="flex-1 py-6 px-3 overflow-y-auto custom-scroll space-y-1">
            
            <!-- Group Label -->
            <div class="px-3 mb-2 text-xs font-bold text-slate-500 uppercase tracking-wider overflow-hidden whitespace-nowrap" x-show="sidebarOpen">
                Inflation Control
            </div>

            <!-- 1. Dashboard -->
            <a href="/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('dashboard') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'hover:bg-slate-800 hover:text-white' }}">
                <i class="ph ph-squares-four text-xl shrink-0"></i>
                <span class="text-sm font-medium whitespace-nowrap" x-show="sidebarOpen">Dashboard</span>
            </a>

            <!-- 2. Peta Geospasial -->
            <a href="/map" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('map') ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                <i class="ph ph-map-trifold text-xl shrink-0"></i>
                <span class="text-sm font-medium whitespace-nowrap" x-show="sidebarOpen">Peta Geospasial</span>
            </a>

            <!-- 3. Smart Matching -->
            <a href="/matching" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('matching') ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                <div class="relative shrink-0">
                    <i class="ph ph-package text-xl"></i>
                    {{-- <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span> --}}
                </div>
                <span class="text-sm font-medium whitespace-nowrap" x-show="sidebarOpen">Smart Matching</span>
            </a>

            <!-- 4. Proyeksi (Forecasting) -->
            <a href="/prediksi" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('forecasting') ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                <i class="ph ph-chart-line-up text-xl shrink-0"></i>
                <span class="text-sm font-medium whitespace-nowrap" x-show="sidebarOpen">Proyeksi Inflasi</span>
            </a>

        </div>

        <!-- LOGOUT BUTTON (Footer Baru) -->
        <div class="p-4 border-t border-slate-800 bg-slate-950">
            <form action="/dashboard" method="">
                @csrf
                <button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors group">
                    <i class="ph-bold ph-sign-out text-xl shrink-0 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold whitespace-nowrap" x-show="sidebarOpen">Keluar Sistem</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden transition-all duration-300"
         :class="sidebarOpen ? 'md:ml-64' : 'md:ml-20'">
        
        <!-- HEADER -->
        <header class="navbar bg-white border-b border-slate-200 px-6 h-16 flex-none justify-between sticky top-0 z-30">
            <!-- Left Side: Toggle & Title -->
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="btn btn-square btn-ghost btn-sm text-slate-500">
                    <i class="ph ph-list text-xl"></i>
                </button>
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    @yield('page_title', 'Dashboard')
                    <span class="badge badge-error gap-1 text-white text-xs font-bold animate-pulse">Live</span>
                </h2>
            </div>
            
            <!-- Right Side: AI, Search & Profile -->
            <div class="flex items-center gap-4">
                
                <!-- AI Advisor Button -->
                <button class="btn btn-sm btn-primary bg-gradient-to-r from-purple-600 to-blue-600 border-none text-white gap-2 shadow-md hidden md:flex"
                        @click="$dispatch('open-ai')">
                    <i class="ph ph-sparkle-fill"></i> AI Advisor
                </button>


                <!-- Separator -->
                <div class="h-6 w-px bg-slate-200 hidden md:block"></div>

                <!-- USER PROFILE (Pindah ke Atas) -->
                <div class="flex items-center gap-3 cursor-pointer hover:bg-slate-50 p-1 rounded-lg transition pr-2">
                    <div class="text-right hidden md:block">
                        <div class="text-xs font-bold text-slate-700">Admin TPID</div>
                        <div class="text-[10px] text-slate-500">Bank Indonesia Jambi</div>
                    </div>
                    <div class="avatar placeholder">
                        <div class="bg-blue-600 text-white rounded-full w-9 h-9 flex items-center justify-center shadow-sm border-2 border-white ring-1 ring-slate-100">
                            <span class="text-xs font-bold">BI</span>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="flex-1 overflow-y-auto p-6 bg-slate-50/50 relative custom-scroll">
            @yield('content')
        </main>
    </div>

    <!-- GLOBAL AI MODAL -->
    <div x-data="{ open: false, loading: false, msg: '' }" 
         @open-ai.window="open = true; loading = true; setTimeout(() => { loading = false; msg = 'Rekomendasi Strategis: Berdasarkan tren data, lakukan Operasi Pasar di Kota Jambi H-3 Idul Fitri.' }, 2000)"
         class="relative z-[60]">
        
        <!-- Modal Backdrop & Dialog -->
        <dialog class="modal modal-bottom sm:modal-middle" :class="open ? 'modal-open' : ''">
            <div class="modal-box bg-white">
                <h3 class="font-bold text-lg flex items-center gap-2 text-purple-700">
                    <i class="ph-fill ph-sparkle"></i> KANTI Intelligent Advisor
                </h3>
                <div class="py-6 min-h-[100px]">
                    <div x-show="loading" class="flex flex-col items-center justify-center h-full">
                        <span class="loading loading-dots loading-lg text-purple-600"></span>
                        <span class="text-xs text-slate-400 mt-2 animate-pulse">Menganalisis Big Data...</span>
                    </div>
                    <div x-show="!loading" class="prose prose-sm text-slate-700" x-transition>
                        <p class="font-bold">Insight:</p>
                        <p x-text="msg"></p>
                    </div>
                </div>
                <div class="modal-action">
                    <button class="btn btn-sm btn-ghost" @click="open = false">Tutup</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop" @click="open = false">
                <button>close</button>
            </form>
        </dialog>
    </div>

</body>
</html>