<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>RapidOrders | Local Vendors</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        'mono-bg':     '#fafafa',
                        'mono-card':   '#ffffff',
                        'mono-border': '#eaeef2',
                        'mono-subtle': '#5b6e8c',
                        'mono-accent': '#0a0c10',
                        'mono-dark':   '#11151c',
                    },
                    animation: {
                        'float':      'float 6s ease-in-out infinite',
                        'pulse-soft': 'pulse-soft 3s ease-in-out infinite',
                        'shimmer':    'shimmer 2s infinite',
                        'fade-in-up': 'fadeInUp 0.65s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards',
                    },
                    keyframes: {
                        float:      { '0%, 100%': { transform: 'translateY(0px) rotate(0deg)' }, '50%': { transform: 'translateY(-12px) rotate(1deg)' } },
                        'pulse-soft':{ '0%, 100%': { opacity: '0.4' }, '50%': { opacity: '0.7' } },
                        shimmer:    { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
                        fadeInUp:   { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                }
            }
        }
    </script>
    <style>
        * { -webkit-font-smoothing: antialiased; }
        body { background-color: #fafafa; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #eef2f5; border-radius: 12px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 12px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .bg-noise {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 0;
            background-image: radial-gradient(#e2e8f0 0.6px, transparent 0.6px);
            background-size: 24px 24px; opacity: 0.3;
        }
        .orb-1 {
            position: fixed; top: 10%; right: -5%; width: 32rem; height: 32rem;
            background: radial-gradient(circle, rgba(0,0,0,0.02) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%; pointer-events: none; z-index: 0;
            animation: float 18s infinite ease-in-out;
        }
        .orb-2 {
            position: fixed; bottom: 0%; left: -8%; width: 28rem; height: 28rem;
            background: radial-gradient(circle, rgba(0,0,0,0.02) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%; pointer-events: none; z-index: 0;
            animation: float 22s infinite reverse ease-in-out;
        }

        .vendor-card {
            transition: all 0.35s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }
        .vendor-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px -12px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.02);
            border-color: #dce3ec;
        }

        .cat-pill {
            background-color: #f1f4f9; color: #1e2a3e;
            padding: 0.125rem 0.5rem; border-radius: 999px;
            font-size: 0.68rem; font-weight: 500; letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        /* shimmer for skeleton loading */
        .img-placeholder {
            background: linear-gradient(110deg, #f3f6fc 8%, #eef2f9 18%, #f3f6fc 33%);
            background-size: 200% 100%;
            animation: shimmer 2.2s infinite linear;
        }
        /* skeleton pulse for text blocks */
        .skeleton {
            background: linear-gradient(110deg, #f3f6fc 8%, #eef2f9 18%, #f3f6fc 33%);
            background-size: 200% 100%;
            animation: shimmer 2.2s infinite linear;
            border-radius: 6px;
        }

        .glass-header {
            background: rgba(250,250,250,0.82);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        .vendor-grid > * {
            animation: fade-in-up 0.5s cubic-bezier(0.12, 0.71, 0.33, 1) forwards;
            opacity: 0;
        }

        /* cover image with fallback */
        .cover-img { object-fit: cover; width: 100%; height: 100%; }
    </style>
</head>
<body class="font-sans antialiased relative">

    <div class="bg-noise"></div>
    <div class="orb-1"></div>
    <div class="orb-2"></div>

    <!-- ── HEADER ────────────────────────────────────────────── -->
    <header id="main-header" class="sticky top-0 z-50 transition-all duration-300 glass-header">
        <div class="max-w-6xl mx-auto px-6 md:px-8 py-5 flex justify-between items-center">
            <a href="/" class="flex items-center gap-1">
                <span class="text-2xl font-semibold tracking-tight text-mono-dark">Rapid<span class="font-light">Orders</span></span>
                <div class="ml-2 h-1 w-1 bg-mono-subtle rounded-full opacity-50"></div>
                <span class="ml-2 text-[11px] font-medium text-mono-subtle uppercase tracking-wide hidden sm:block">local</span>
            </a>
            <button id="cart-btn" onclick="window.location.href='/cart'" class="relative p-2 rounded-full hover:bg-black/5 transition-all duration-200 focus:outline-none" aria-label="Shopping cart">
                <svg class="w-5 h-5 text-mono-dark/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <span id="cart-badge" class="hidden absolute -top-1.5 -right-1.5 bg-mono-accent text-white text-[10px] font-semibold w-5 h-5 flex items-center justify-center rounded-full shadow-sm">0</span>
            </button>
        </div>
    </header>

    <!-- ── MAIN ──────────────────────────────────────────────── -->
    <main class="relative z-10 max-w-6xl mx-auto px-6 md:px-8 py-12 md:py-20">

        <!-- Hero -->
        <div class="text-center max-w-2xl mx-auto mb-14" data-aos="fade-up" data-aos-duration="600">
            <div class="inline-flex items-center gap-2 bg-white/60 backdrop-blur-sm rounded-full px-3 py-1 border border-mono-border shadow-sm mb-5">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                <span class="text-[12px] font-medium text-mono-subtle tracking-wide">WhatsApp integrated · instant ordering</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-[#0a0c10] leading-tight">Order from local vendors</h1>
            <p class="text-mono-subtle text-base md:text-lg mt-4 max-w-lg mx-auto">Discover neighborhood stores, fresh groceries, electronics & more — delivered via WhatsApp.</p>
        </div>

        <!-- Search + filter chips -->
        <div class="max-w-md mx-auto mb-16" data-aos="fade-up" data-aos-delay="80" data-aos-duration="500">
            <div class="relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" id="search-input" placeholder="Search vendor or category..."
                    class="w-full pl-11 pr-5 py-3.5 rounded-2xl border border-gray-200 bg-white/90 focus:bg-white text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200 text-sm font-light">
            </div>
            <!-- Dynamic category filter chips injected by JS -->
            <div id="filter-chips" class="flex justify-center gap-2 mt-4 flex-wrap"></div>
        </div>

        <!-- Vendor grid -->
        <div id="vendor-grid" class="vendor-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7 auto-rows-fr">
            <!-- Skeleton placeholders shown while loading -->
            <?php for ($i = 0; $i < 6; $i++): ?>
            <div class="bg-white rounded-2xl border border-mono-border overflow-hidden">
                <div class="skeleton h-44 w-full"></div>
                <div class="p-5 space-y-3">
                    <div class="skeleton h-3 w-20"></div>
                    <div class="skeleton h-5 w-3/4"></div>
                    <div class="skeleton h-3 w-full"></div>
                    <div class="skeleton h-3 w-2/3"></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Empty state -->
        <div id="empty-state" class="text-center py-16 hidden flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-mono-subtle/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <p class="text-mono-subtle text-sm">No vendors match your search.</p>
            <button id="reset-search" class="mt-3 text-xs font-medium text-mono-accent/70 underline-offset-2 hover:underline">Clear search</button>
        </div>

        <!-- Error state -->
        <div id="error-state" class="text-center py-16 hidden">
            <p class="text-red-400 text-sm">Could not load vendors. Please refresh the page.</p>
        </div>
    </main>

    <!-- ── FOOTER ─────────────────────────────────────────────── -->
    <footer class="relative z-10 text-center py-10 text-mono-subtle/50 text-xs border-t border-mono-border/40 max-w-6xl mx-auto mt-8">
        <p>© 2025 RapidOrders — seamless local ordering · powered by WhatsApp business</p>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 500, once: true, offset: 20, easing: 'ease-out' });

        // ── Cart badge from localStorage ──────────────────────
        const cartBadge = document.getElementById('cart-badge');
        function refreshCartBadge() {
            try {
                const cart = JSON.parse(localStorage.getItem('ro_cart') || '[]');
                const total = cart.reduce((sum, item) => sum + (item.qty || 1), 0);
                if (total > 0) {
                    cartBadge.textContent = total;
                    cartBadge.classList.remove('hidden');
                } else {
                    cartBadge.classList.add('hidden');
                }
            } catch(e) {
                cartBadge.classList.add('hidden');
            }
        }
        refreshCartBadge();

        // ── Category pill color map ───────────────────────────
        const categoryColors = {
            'Electronics':   'bg-[#f0f2f5] text-[#1f2a3e]',
            'Groceries':     'bg-[#edf2f7] text-[#1f2a3e]',
            'Fashion':       'bg-[#f1f0f4] text-[#1f2a3e]',
            'Food':          'bg-[#f2efe9] text-[#1f2a3e]',
            'Home & Living': 'bg-[#eef2ef] text-[#1f2a3e]',
        };
        function getCategoryClass(cat) {
            return 'cat-pill ' + (categoryColors[cat] || 'bg-gray-100 text-gray-700');
        }

        // ── Build a single vendor card ────────────────────────
        function buildVendorCard(vendor, index) {
            const card = document.createElement('div');
            card.className = 'vendor-card bg-mono-card rounded-2xl border border-mono-border overflow-hidden cursor-pointer group';
            card.setAttribute('data-aos', 'fade-up');
            card.setAttribute('data-aos-delay', String((index % 3) * 60));
            card.setAttribute('data-aos-duration', '500');

            // cover image or shimmer placeholder
            const imageHtml = vendor.cover_image
                ? `<img src="${vendor.cover_image}" alt="${vendor.name}" class="cover-img" loading="lazy" onerror="this.parentElement.innerHTML=fallbackCover('${vendor.name}')">`
                : fallbackCover(vendor.name);

            // open/closed badge
            const openBadge = vendor.is_open == 1
                ? `<span class="flex items-center gap-1 text-[10px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full"><span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>Open</span>`
                : `<span class="text-[10px] font-medium text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Closed</span>`;

            // product count
            const countLabel = vendor.product_count > 0
                ? `<span class="text-[11px] text-mono-subtle">${vendor.product_count} product${vendor.product_count != 1 ? 's' : ''}</span>`
                : '';

            card.innerHTML = `
                <div class="relative h-44 w-full bg-white overflow-hidden border-b border-mono-border/40">
                    ${imageHtml}
                    <div class="absolute top-3 right-3">${openBadge}</div>
                </div>
                <div class="p-5">
                    <div class="flex items-center justify-between">
                        <span class="${getCategoryClass(vendor.category)}">${vendor.category || 'General'}</span>
                        ${countLabel}
                    </div>
                    <h3 class="text-lg font-semibold text-mono-dark mt-2.5 tracking-tight">${vendor.name}</h3>
                    <p class="text-mono-subtle text-sm leading-relaxed mt-1.5 line-clamp-2">${vendor.description || ''}</p>
                    <div class="mt-4 pt-3 flex items-center justify-between border-t border-mono-border/30">
                        <span class="text-[12px] font-medium text-mono-accent/80 group-hover:text-mono-accent transition flex items-center gap-1">
                            Shop now
                            <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <button data-wa="${vendor.whatsapp}" data-name="${vendor.name}"
                            class="quick-order-btn text-[11px] font-medium px-3 py-1.5 rounded-full bg-black/5 hover:bg-black/10 text-mono-dark transition-colors">
                            Quick order
                        </button>
                    </div>
                </div>
            `;

            // whole card click → vendor page
            card.addEventListener('click', (e) => {
                if (e.target.closest('.quick-order-btn')) return;
                
                // Using a trailing slash forces the browser to treat 'vendor' as a directory
                // This tells the server to look for the index file inside ./vendor/
                window.location.href = `./vendor/?name=${vendor.slug}`;
            });

            // quick order button → open WhatsApp directly
            card.querySelector('.quick-order-btn').addEventListener('click', (e) => {
                e.stopPropagation();
                const msg = encodeURIComponent(`Hello ${vendor.name}! I'd like to place an order. Can you share your product list?`);
                window.open(`https://wa.me/${vendor.whatsapp}?text=${msg}`, '_blank');
            });

            return card;
        }

        // shimmer fallback for vendors without a cover image
        function fallbackCover(name) {
            const initials = name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
            return `<div class="img-placeholder w-full h-full flex items-center justify-center">
                        <div class="flex flex-col items-center text-mono-subtle/30">
                            <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 9l9-6 9 6v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 22V12h6v10" />
                            </svg>
                            <span class="text-[10px] font-semibold">${initials}</span>
                        </div>
                    </div>`;
        }

        // ── State ─────────────────────────────────────────────
        let allVendors = [];
        let activeFilter = 'all';
        const grid         = document.getElementById('vendor-grid');
        const emptyState   = document.getElementById('empty-state');
        const errorState   = document.getElementById('error-state');
        const searchInput  = document.getElementById('search-input');
        const filterChips  = document.getElementById('filter-chips');
        const resetBtn     = document.getElementById('reset-search');

        // ── Render vendors into grid ──────────────────────────
        function renderVendors() {
            const term = searchInput.value.trim().toLowerCase();

            let filtered = allVendors.filter(v => {
                const matchesSearch =
                    !term ||
                    v.name.toLowerCase().includes(term) ||
                    (v.category || '').toLowerCase().includes(term) ||
                    (v.description || '').toLowerCase().includes(term);

                const matchesFilter =
                    activeFilter === 'all' ||
                    (v.category || '').toLowerCase() === activeFilter.toLowerCase();

                return matchesSearch && matchesFilter;
            });

            grid.innerHTML = '';

            if (filtered.length === 0) {
                emptyState.classList.remove('hidden');
                emptyState.classList.add('flex');
                return;
            }

            emptyState.classList.add('hidden');
            emptyState.classList.remove('flex');

            filtered.forEach((vendor, idx) => {
                grid.appendChild(buildVendorCard(vendor, idx));
            });

            if (typeof AOS !== 'undefined') AOS.refresh();
        }

        // ── Build category filter chips from live data ────────
        function buildFilterChips(vendors) {
            const categories = ['All', ...new Set(vendors.map(v => v.category).filter(Boolean))];
            filterChips.innerHTML = '';
            categories.forEach(cat => {
                const btn = document.createElement('button');
                const key = cat.toLowerCase();
                const isActive = key === activeFilter;
                btn.textContent = cat;
                btn.className = `text-[11px] font-medium px-3 py-1 rounded-full border transition
                    ${isActive
                        ? 'bg-mono-accent text-white border-mono-accent'
                        : 'bg-white border-gray-200 text-gray-600 hover:border-indigo-300'}`;
                btn.addEventListener('click', () => {
                    activeFilter = key;
                    buildFilterChips(allVendors); // re-render chips to update active state
                    renderVendors();
                });
                filterChips.appendChild(btn);
            });
        }

        // ── Fetch vendors from backend ────────────────────────
async function loadVendors() {
    try {
        <?php
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        ?>
        const res = await fetch('<?php echo $basePath; ?>/backend/homepage.php');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();

        if (!data.success || !Array.isArray(data.vendors)) {
            throw new Error(data.error || 'Unexpected response');
        }

        allVendors = data.vendors;
        buildFilterChips(allVendors);
        renderVendors();

    } catch (err) {
        console.error('Failed to load vendors:', err);
        grid.innerHTML = '';
        errorState.classList.remove('hidden');
    }
}

        // ── Search (debounced) ────────────────────────────────
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(renderVendors, 120);
        });

        resetBtn?.addEventListener('click', () => {
            searchInput.value = '';
            activeFilter = 'all';
            buildFilterChips(allVendors);
            renderVendors();
            searchInput.focus();
        });

        // ── Header scroll shadow ──────────────────────────────
        const header = document.getElementById('main-header');
        window.addEventListener('scroll', () => {
            header.style.borderBottom = window.scrollY > 15
                ? '1px solid rgba(0,0,0,0.05)'
                : '1px solid rgba(0,0,0,0)';
        });

        // ── Boot ──────────────────────────────────────────────
        loadVendors();
    </script>
</body>
</html>