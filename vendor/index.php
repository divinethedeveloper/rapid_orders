<?php
// vendor/index.php
// Reads ?name=vendor-slug from the URL
// Fetches vendor + products from ../backend/vendors.php

$slug = trim($_GET['name'] ?? '');

// basic slug validation — redirect home if empty or malformed
if (!$slug || !preg_match('/^[a-z0-9\-]+$/', $slug)) {
    header('Location: /');
    exit;
}

// base path for JS fetch calls (works on localhost subfolders + live root)
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Loading... · RapidOrders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                        shimmer:    'shimmer 2.2s infinite linear',
                        'slide-in': 'slideIn 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards',
                    },
                    keyframes: {
                        shimmer:  { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
                        slideIn:  { '0%': { transform: 'translateX(100%)' }, '100%': { transform: 'translateX(0)' } },
                    }
                }
            }
        }
    </script>
    <style>
        * { -webkit-font-smoothing: antialiased; }
        body { background-color: #fafafa; }

        .bg-noise {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 0;
            background-image: radial-gradient(#e2e8f0 0.6px, transparent 0.6px);
            background-size: 24px 24px; opacity: 0.3;
        }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .img-placeholder {
            background: linear-gradient(110deg, #f3f6fc 8%, #eef2f9 18%, #f3f6fc 33%);
            background-size: 200% 100%;
            animation: shimmer 2.2s infinite linear;
        }
        .skeleton {
            background: linear-gradient(110deg, #f3f6fc 8%, #eef2f9 18%, #f3f6fc 33%);
            background-size: 200% 100%;
            animation: shimmer 2.2s infinite linear;
            border-radius: 6px;
        }

        .product-card { transition: all 0.35s cubic-bezier(0.2, 0.9, 0.4, 1.1); }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px -12px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.02);
            border-color: #dce3ec;
        }

        .cat-pill {
            background-color: #f1f4f9; color: #1e2a3e;
            padding: 0.125rem 0.625rem; border-radius: 999px;
            font-size: 0.7rem; font-weight: 500; letter-spacing: 0.3px;
        }
        .filter-pill-active   { background-color: #0a0c10; color: white; border-color: #0a0c10; }
        .filter-pill-inactive { background-color: #ffffff; color: #2c3e50; border: 1px solid #e2e8f0; }
        .filter-pill-inactive:hover { border-color: #cbd5e1; background-color: #f8fafc; }

        .glass-header {
            background: rgba(250,250,250,0.82);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }
        .sticky-filter-bar {
            position: sticky; top: 68px; z-index: 40;
            background: rgba(250,250,250,0.96);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #eaeef2;
        }
        @media (max-width: 640px) { .sticky-filter-bar { top: 64px; } }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* Gallery */
        .gallery-modal {
            position: fixed; inset: 0; background: rgba(0,0,0,0.92);
            z-index: 200; display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: all 0.25s ease;
            backdrop-filter: blur(8px);
        }
        .gallery-modal.active { opacity: 1; visibility: visible; }
        .gallery-container { max-width: 90vw; max-height: 85vh; position: relative; }
        .gallery-main-img { max-width: 85vw; max-height: 75vh; object-fit: contain; border-radius: 20px; box-shadow: 0 25px 40px rgba(0,0,0,0.3); }
        .gallery-thumbnails { display: flex; gap: 12px; justify-content: center; margin-top: 24px; flex-wrap: wrap; }
        .thumb-img { width: 70px; height: 70px; object-fit: cover; border-radius: 12px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; opacity: 0.7; }
        .thumb-img.active-thumb { border-color: white; opacity: 1; transform: scale(1.02); }
        .close-gallery { position: absolute; top: -40px; right: -10px; background: white; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold; color: #000; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .nav-arrow { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 28px; font-weight: 300; color: white; transition: 0.2s; }
        .nav-arrow:hover { background: rgba(255,255,255,0.4); }
        .nav-left { left: -60px; } .nav-right { right: -60px; }
        @media (max-width: 768px) { .nav-left { left: 10px; } .nav-right { right: 10px; } .close-gallery { top: 10px; right: 10px; } .thumb-img { width: 50px; height: 50px; } }

        /* Payment modal */
        .payment-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 150; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: all 0.25s; }
        .payment-modal-overlay.active { opacity: 1; visibility: visible; }
        .payment-card { background: white; max-width: 480px; width: 90%; border-radius: 28px; padding: 28px 24px; box-shadow: 0 30px 40px rgba(0,0,0,0.2); transform: scale(0.96); transition: transform 0.2s; }
        .payment-modal-overlay.active .payment-card { transform: scale(1); }
        .method-option { border: 1px solid #eaeef2; border-radius: 20px; padding: 14px 18px; cursor: pointer; transition: all 0.2s; }
        .method-option.selected { border-color: #0a0c10; background: #fafafa; }
    </style>
</head>
<body class="font-sans antialiased relative">

<div class="bg-noise"></div>

<!-- ── HEADER ──────────────────────────────────────────────── -->
<header id="main-header" class="sticky top-0 z-50 transition-all duration-300 glass-header">
    <div class="max-w-6xl mx-auto px-6 md:px-8 py-5 flex justify-between items-center">
        <a href="<?php echo $basePath ?: '/'; ?>" class="flex items-center gap-1 group">
            <span class="text-2xl font-semibold tracking-tight text-mono-dark">Rapid<span class="font-light">Orders</span></span>
            <div class="ml-2 h-1 w-1 bg-mono-subtle rounded-full opacity-50"></div>
            <span class="ml-2 text-[11px] font-medium text-mono-subtle uppercase tracking-wide hidden sm:block">local</span>
        </a>
        <button id="cart-icon-btn" class="relative p-2 rounded-full hover:bg-black/5 transition-all duration-200" aria-label="Shopping cart">
            <svg class="w-5 h-5 text-mono-dark/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
            <span id="cart-badge" class="hidden absolute -top-1.5 -right-1.5 bg-mono-accent text-white text-[10px] font-semibold w-5 h-5 flex items-center justify-center rounded-full shadow-sm">0</span>
        </button>
    </div>
</header>

<!-- ── VENDOR HERO ─────────────────────────────────────────── -->
<section class="border-b border-mono-border bg-white w-full">
    <div class="max-w-6xl mx-auto px-6 md:px-8 py-10 md:py-12">
        <div class="flex flex-col md:flex-row justify-between md:items-end gap-6">

            <!-- left: vendor info -->
            <div data-aos="fade-up" data-aos-duration="500">
                <div class="text-[12px] text-mono-subtle/70 mb-2 tracking-wide">
                    <a href="<?php echo $basePath ?: '/'; ?>" class="hover:text-mono-accent transition">Home</a>
                    <span class="mx-1">/</span>
                    <span id="vendor-name-breadcrumb">
                        <span class="skeleton inline-block w-24 h-3 align-middle"></span>
                    </span>
                </div>
                <h1 id="vendor-name" class="text-3xl md:text-4xl font-bold text-mono-dark tracking-tight">
                    <span class="skeleton inline-block w-48 h-9 align-middle"></span>
                </h1>
                <div class="flex flex-wrap items-center gap-3 mt-3">
                    <span id="vendor-category-pill" class="cat-pill text-[11px]">
                        <span class="skeleton inline-block w-20 h-3 align-middle"></span>
                    </span>
                    <span class="text-mono-subtle text-sm flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        <span id="vendor-wa-number" class="skeleton inline-block w-32 h-3 align-middle"></span>
                    </span>
                </div>
                <p id="vendor-description" class="text-mono-subtle text-[15px] max-w-xl mt-3 leading-relaxed">
                    <span class="skeleton inline-block w-full h-3 mb-1 align-middle"></span>
                    <span class="skeleton inline-block w-3/4 h-3 align-middle"></span>
                </p>
            </div>

            <!-- right: stats -->
            <div class="flex md:flex-col items-start md:items-end gap-3" data-aos="fade-up" data-aos-delay="80">
                <div id="open-badge" class="flex items-center gap-2 bg-white border border-mono-border rounded-full px-4 py-2 shadow-sm">
                    <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                    <span class="text-xs font-medium text-mono-subtle">Loading...</span>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-semibold text-mono-dark" id="product-count-display">—</div>
                    <div class="text-[11px] text-mono-subtle uppercase tracking-wide">products available</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── STICKY FILTER BAR ───────────────────────────────────── -->
<div id="filter-bar" class="sticky-filter-bar">
    <div class="max-w-6xl mx-auto px-6 md:px-8 py-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2" id="filter-pills-container">
            <span class="skeleton inline-block w-10 h-6 rounded-full"></span>
            <span class="skeleton inline-block w-20 h-6 rounded-full"></span>
            <span class="skeleton inline-block w-16 h-6 rounded-full"></span>
        </div>
        <div class="text-xs text-mono-subtle bg-white/60 px-3 py-1 rounded-full" id="showing-count">Loading...</div>
    </div>
</div>

<!-- ── PRODUCT GRID ────────────────────────────────────────── -->
<main class="relative z-10 max-w-6xl mx-auto px-6 md:px-8 py-12">
    <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7 auto-rows-fr">
        <!-- skeleton cards while loading -->
        <?php for ($i = 0; $i < 6; $i++): ?>
        <div class="bg-white rounded-2xl border border-mono-border overflow-hidden">
            <div class="skeleton h-[180px] w-full"></div>
            <div class="p-5 space-y-3">
                <div class="skeleton h-3 w-20"></div>
                <div class="skeleton h-5 w-3/4"></div>
                <div class="skeleton h-3 w-full"></div>
                <div class="skeleton h-3 w-2/3"></div>
                <div class="skeleton h-8 w-full rounded-full mt-2"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- 404 vendor not found -->
    <div id="vendor-not-found" class="hidden text-center py-24">
        <p class="text-2xl font-semibold text-mono-dark mb-2">Vendor not found</p>
        <p class="text-mono-subtle text-sm mb-6">The store you're looking for doesn't exist or has been removed.</p>
        <a href="<?php echo $basePath ?: '/'; ?>" class="inline-block px-6 py-3 rounded-full bg-mono-accent text-white text-sm font-medium hover:opacity-90 transition">Back to home</a>
    </div>
</main>

<!-- ── CART SIDEBAR ────────────────────────────────────────── -->
<div id="cart-overlay" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[100] opacity-0 invisible transition-all duration-300"></div>
<div id="cart-drawer" class="fixed top-0 right-0 h-full w-full max-w-md bg-white shadow-2xl z-[101] transform translate-x-full transition-transform duration-300 flex flex-col border-l border-mono-border">
    <div class="flex items-center justify-between p-5 border-b border-mono-border">
        <h2 class="text-lg font-semibold text-mono-dark">Your cart <span id="cart-sidebar-count" class="text-mono-subtle text-sm font-normal ml-1">(0)</span></h2>
        <button id="close-cart-btn" class="p-1 rounded-full hover:bg-black/5 transition">✕</button>
    </div>
    <div id="cart-items-list" class="flex-1 overflow-y-auto p-5 space-y-4"></div>
    <div class="border-t border-mono-border p-5 bg-white">
        <div class="flex justify-between text-mono-dark mb-4">
            <span class="font-medium">Subtotal</span>
            <span class="font-semibold text-lg" id="cart-subtotal">GHS 0</span>
        </div>
        <div class="flex gap-3 flex-col">
            <button id="make-payment-btn" class="w-full text-center py-3 rounded-xl border-2 border-mono-accent bg-transparent text-mono-accent text-sm font-medium hover:bg-black/5 transition">Make payment 💳</button>
            <a href="<?php echo $basePath; ?>/cart" class="w-full text-center py-3 rounded-xl border border-mono-border text-mono-dark text-sm font-medium hover:bg-gray-50 transition">View full cart →</a>
            <button id="whatsapp-checkout-btn" class="w-full py-3 rounded-xl bg-mono-accent text-white text-sm font-medium hover:bg-mono-dark/90 transition flex items-center justify-center gap-2">
                Order via WhatsApp
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            </button>
        </div>
    </div>
</div>

<!-- ── PAYMENT MODAL ───────────────────────────────────────── -->
<div id="payment-modal" class="payment-modal-overlay">
    <div class="payment-card">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-xl font-semibold text-mono-dark">Complete payment</h3>
            <button id="close-payment-modal" class="text-gray-400 hover:text-black text-2xl leading-5">×</button>
        </div>
        <p class="text-mono-subtle text-sm mb-5">Total: <span id="payment-total-amount" class="font-bold text-mono-dark">GHS 0</span></p>
        <div class="space-y-3 mb-6">
            <div data-method="card" class="method-option flex items-center gap-3">
                <div class="w-5 h-5 rounded-full border border-gray-300 flex items-center justify-center"><div class="w-2.5 h-2.5 rounded-full bg-mono-accent hidden"></div></div>
                <span>💳 Credit / Debit Card</span>
            </div>
            <div data-method="momo" class="method-option flex items-center gap-3">
                <div class="w-5 h-5 rounded-full border border-gray-300 flex items-center justify-center"><div class="w-2.5 h-2.5 rounded-full bg-mono-accent hidden"></div></div>
                <span>📱 Mobile Money (MTN, Vodafone, AirtelTigo)</span>
            </div>
        </div>
        <button id="simulate-payment-btn" class="w-full bg-mono-accent text-white py-3 rounded-xl font-medium hover:opacity-90 transition">Pay GHS <span id="pay-dynamic-amount">0</span></button>
        <p class="text-center text-[11px] text-mono-subtle mt-4">*Demo simulation — no real charge</p>
    </div>
</div>

<!-- ── GALLERY MODAL ───────────────────────────────────────── -->
<div id="gallery-modal" class="gallery-modal">
    <div class="gallery-container">
        <div class="close-gallery" id="close-gallery-btn">✕</div>
        <div class="nav-arrow nav-left" id="gallery-prev">‹</div>
        <img id="gallery-main-img" class="gallery-main-img" src="" alt="Product image">
        <div class="nav-arrow nav-right" id="gallery-next">›</div>
        <div id="gallery-thumbnails" class="gallery-thumbnails"></div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration: 500, once: true, offset: 20, easing: 'ease-out' });

// ── PHP passes the slug into JS safely ───────────────────────
const VENDOR_SLUG = <?php echo json_encode($slug); ?>;
const BASE_PATH   = <?php echo json_encode($basePath); ?>;

// ── State ─────────────────────────────────────────────────────
let vendor         = null;
let allProducts    = [];
let activeCategory = 'All';
let cart           = [];
const STORAGE_KEY  = 'ro_cart';

// ── Helper: Check if cart has items from another vendor ──────
function getCurrentVendorInCart() {
    for (let item of cart) {
        if (item.vendorSlug) return item.vendorSlug;
    }
    return null;
}

function hasOtherVendorItems() {
    const currentVendor = getCurrentVendorInCart();
    return currentVendor !== null && currentVendor !== VENDOR_SLUG;
}

// ── Cart helpers with single-vendor enforcement ───────────────
function loadCart() {
    try { cart = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); } catch(e) { cart = []; }
    updateCartUI();
}
function saveCart() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
    updateCartUI();
}
function findCartItem(productId) {
    return cart.find(i => i.productId == productId && i.vendorSlug === VENDOR_SLUG);
}
function addToCart(product) {
    // Check if cart has items from a different vendor
    const otherVendor = getCurrentVendorInCart();
    if (otherVendor !== null && otherVendor !== VENDOR_SLUG) {
        Swal.fire({
            title: 'Cart from another vendor',
            text: `Your cart already contains items from another store. Please complete or clear that order before shopping here.`,
            icon: 'warning',
            confirmButtonColor: '#0a0c10',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    const existing = findCartItem(product.id);
    if (existing) { existing.qty++; }
    else {
        cart.push({
            productId:     product.id,
            vendorSlug:    vendor.slug,
            vendorName:    vendor.name,
            vendorWhatsapp: vendor.whatsapp,
            name:          product.name,
            price:         product.price,
            qty:           1
        });
    }
    saveCart();
    renderProductGrid();
    
    // Show success toast
    Swal.fire({
        title: 'Added to cart!',
        text: `${product.name} added to your cart`,
        icon: 'success',
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}
function removeFromCart(productId) {
    cart = cart.filter(i => !(i.productId == productId && i.vendorSlug === VENDOR_SLUG));
    saveCart();
    renderProductGrid();
}
function updateQty(productId, newQty) {
    if (newQty <= 0) { removeFromCart(productId); return; }
    const item = findCartItem(productId);
    if (item) { item.qty = newQty; saveCart(); renderProductGrid(); }
}
function cartTotal() {
    return cart.filter(i => i.vendorSlug === VENDOR_SLUG).reduce((s,i) => s + i.price * i.qty, 0);
}
function vendorCartItems() {
    return cart.filter(i => i.vendorSlug === VENDOR_SLUG);
}
function clearCart() {
    cart = cart.filter(i => i.vendorSlug !== VENDOR_SLUG);
    saveCart();
    renderProductGrid();
}

function updateCartUI() {
    const items = vendorCartItems();
    const totalQty = items.reduce((s,i) => s + i.qty, 0);
    const badge = document.getElementById('cart-badge');
    if (totalQty > 0) { badge.textContent = totalQty; badge.classList.remove('hidden'); }
    else badge.classList.add('hidden');
    document.getElementById('cart-sidebar-count').textContent = `(${totalQty})`;
    document.getElementById('cart-subtotal').textContent = `GHS ${cartTotal().toLocaleString()}`;
    renderCartSidebar();
}

function renderCartSidebar() {
    const container  = document.getElementById('cart-items-list');
    const items      = vendorCartItems();
    if (items.length === 0) {
        container.innerHTML = `<div class="text-center text-mono-subtle text-sm py-8">Your cart is empty</div>`;
        return;
    }
    container.innerHTML = items.map(item => `
        <div class="flex justify-between items-start border-b border-mono-border pb-3" data-id="${item.productId}">
            <div class="flex-1 min-w-0 pr-3">
                <p class="font-medium text-sm text-mono-dark truncate">${escapeHtml(item.name)}</p>
                <p class="text-xs text-mono-subtle mt-0.5">${escapeHtml(item.vendorName)}</p>
                <p class="text-sm font-semibold mt-1">GHS ${(item.price * item.qty).toLocaleString()}</p>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button class="cart-minus w-7 h-7 rounded-full border border-mono-border text-mono-dark hover:bg-gray-50 transition text-sm">−</button>
                <span class="text-sm w-6 text-center">${item.qty}</span>
                <button class="cart-plus w-7 h-7 rounded-full border border-mono-border text-mono-dark hover:bg-gray-50 transition text-sm">+</button>
                <button class="cart-remove ml-1 text-mono-subtle hover:text-red-500 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </div>
        </div>
    `).join('');

    items.forEach(item => {
        const row = container.querySelector(`[data-id="${item.productId}"]`);
        if (!row) return;
        row.querySelector('.cart-minus').addEventListener('click', () => updateQty(item.productId, item.qty - 1));
        row.querySelector('.cart-plus').addEventListener('click',  () => updateQty(item.productId, item.qty + 1));
        row.querySelector('.cart-remove').addEventListener('click', () => removeFromCart(item.productId));
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// ── Gallery ───────────────────────────────────────────────────
let galleryImages = [];
let galleryIndex  = 0;

function openGallery(images, startIdx = 0) {
    galleryImages = images;
    galleryIndex  = startIdx;
    updateGalleryView();
    document.getElementById('gallery-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeGallery() {
    document.getElementById('gallery-modal').classList.remove('active');
    document.body.style.overflow = '';
}
function updateGalleryView() {
    document.getElementById('gallery-main-img').src = galleryImages[galleryIndex] || '';
    const thumbsEl = document.getElementById('gallery-thumbnails');
    thumbsEl.innerHTML = galleryImages.map((img, i) =>
        `<img class="thumb-img ${i === galleryIndex ? 'active-thumb' : ''}" src="${img}" data-i="${i}">`
    ).join('');
    thumbsEl.querySelectorAll('.thumb-img').forEach(t =>
        t.addEventListener('click', () => { galleryIndex = parseInt(t.dataset.i); updateGalleryView(); })
    );
}
document.getElementById('close-gallery-btn').addEventListener('click', closeGallery);
document.getElementById('gallery-prev').addEventListener('click', () => {
    galleryIndex = (galleryIndex - 1 + galleryImages.length) % galleryImages.length;
    updateGalleryView();
});
document.getElementById('gallery-next').addEventListener('click', () => {
    galleryIndex = (galleryIndex + 1) % galleryImages.length;
    updateGalleryView();
});

// ── Filter pills ──────────────────────────────────────────────
function renderFilterPills(categories) {
    const container = document.getElementById('filter-pills-container');
    container.innerHTML = '';
    ['All', ...categories].forEach(cat => {
        const btn = document.createElement('button');
        btn.textContent = cat;
        const isActive = activeCategory === cat;
        btn.className = `rounded-full px-4 py-1.5 text-xs font-medium transition-all
            ${isActive ? 'filter-pill-active bg-mono-accent text-white' : 'filter-pill-inactive bg-white border border-mono-border text-mono-dark'}`;
        btn.addEventListener('click', () => {
            activeCategory = cat;
            renderFilterPills(categories);
            renderProductGrid();
        });
        container.appendChild(btn);
    });
}

// ── Build product image path from product_images folder ──────
function getProductImagePath(productId, productName, imageIndex = 0) {
    // Try to find images in product_images folder
    // Format: product_images/{vendor_slug}/{product_id}_{index}.jpg or .png
    // For demo, we'll use a systematic approach
    const possibleExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    // Return a path that will be checked by the browser
    return `${BASE_PATH}/product_images/${VENDOR_SLUG}/${productId}_${imageIndex}.jpg`;
}

function getProductImages(product) {
    // Try to load up to 3 images per product
    const images = [];
    // For demo purposes, we check if images exist. Since we can't check server-side,
    // we'll generate placeholder URLs that will 404 gracefully
    for (let i = 0; i < 3; i++) {
        images.push(`${BASE_PATH}/product_images/${VENDOR_SLUG}/${product.id}_${i}.jpg`);
    }
    // Remove duplicates and filter out obviously broken ones? We'll keep them and let onerror handle
    return images;
}

// ── Product grid ──────────────────────────────────────────────
function renderProductGrid() {
    const grid = document.getElementById('product-grid');
    let filtered = allProducts.filter(p =>
        activeCategory === 'All' || p.category === activeCategory
    );
    document.getElementById('showing-count').textContent = `Showing ${filtered.length} product${filtered.length !== 1 ? 's' : ''}`;

    if (filtered.length === 0) {
        grid.innerHTML = `<div class="col-span-full text-center py-16 text-mono-subtle text-sm">No products in this category.</div>`;
        return;
    }

    grid.innerHTML = '';
    filtered.forEach((prod, idx) => {
        const card       = document.createElement('div');
        card.className   = 'product-card bg-white rounded-2xl border border-mono-border overflow-hidden';
        card.setAttribute('data-aos', 'fade-up');
        card.setAttribute('data-aos-delay', String((idx % 3) * 60));

        const cartItem   = findCartItem(prod.id);
        const currentQty = cartItem ? cartItem.qty : 0;
        
        // Build image path from product_images folder
        const imagePath = `${BASE_PATH}/product_images/${VENDOR_SLUG}/${prod.id}_0.jpg`;
        
        // Placeholder text if image not found
        const initials = prod.name.slice(0,2).toUpperCase();

        const imageHtml = `<img src="${imagePath}" 
                                alt="${prod.name}" 
                                class="w-full h-full object-cover transition-transform duration-300 group-hover/img:scale-105" 
                                onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'img-placeholder w-full h-full flex items-center justify-center text-mono-subtle/30 text-xs font-semibold\'>${initials}</div>'; this.remove()"
                                loading="lazy">`;

        const stockBadge = !prod.in_stock
            ? `<span class="text-red-600 text-[10px] bg-red-50 px-2 py-0.5 rounded-full font-medium">Out of stock</span>`
            : '';

        const actionHtml = !prod.in_stock
            ? `<button disabled class="w-full rounded-full bg-gray-100 text-mono-subtle text-xs py-2.5 cursor-not-allowed">Unavailable</button>`
            : currentQty === 0
                ? `<button class="add-to-cart-btn w-full rounded-full bg-mono-accent text-white text-xs font-medium py-2.5 hover:opacity-90 transition" data-id="${prod.id}">Add to cart</button>`
                : `<div class="flex items-center justify-between gap-3 bg-gray-50 rounded-full border border-mono-border p-1">
                       <button class="qty-btn w-8 h-8 rounded-full hover:bg-white transition text-lg leading-none" data-id="${prod.id}" data-op="minus">−</button>
                       <span class="text-sm font-medium w-5 text-center">${currentQty}</span>
                       <button class="qty-btn w-8 h-8 rounded-full hover:bg-white transition text-lg leading-none" data-id="${prod.id}" data-op="plus">+</button>
                   </div>`;

        // Prepare gallery images (try to find multiple images)
        const galleryImageUrls = [];
        for (let i = 0; i < 3; i++) {
            galleryImageUrls.push(`${BASE_PATH}/product_images/${VENDOR_SLUG}/${prod.id}_${i}.jpg`);
        }

        card.innerHTML = `
            <div class="relative h-[180px] w-full bg-white overflow-hidden cursor-pointer group/img">
                ${imageHtml}
                <div class="absolute inset-0 bg-black/0 group-hover/img:bg-black/8 transition"></div>
            </div>
            <div class="p-5">
                <div class="flex justify-between items-start gap-2">
                    <span class="cat-pill text-[10px]">${escapeHtml(prod.category || 'General')}</span>
                    ${stockBadge}
                </div>
                <h3 class="text-base font-semibold mt-2 text-mono-dark">${escapeHtml(prod.name)}</h3>
                <p class="text-mono-subtle text-[13px] mt-1 line-clamp-2">${escapeHtml(prod.description || '')}</p>
                <div class="mt-3">
                    <span class="text-lg font-bold text-mono-dark">GHS ${Number(prod.price).toLocaleString()}</span>
                </div>
                <div class="mt-4">${actionHtml}</div>
            </div>
        `;

        // image click → gallery
        card.querySelector('.relative').addEventListener('click', () => {
            openGallery(galleryImageUrls, 0);
        });

        // add to cart button
        card.querySelector('.add-to-cart-btn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            addToCart(prod);
        });

        // qty buttons
        card.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const item = findCartItem(btn.dataset.id);
                if (!item) return;
                const newQty = btn.dataset.op === 'plus' ? item.qty + 1 : item.qty - 1;
                updateQty(btn.dataset.id, newQty);
            });
        });

        grid.appendChild(card);
    });

    if (typeof AOS !== 'undefined') AOS.refresh();
}

// ── Populate vendor hero from data ────────────────────────────
function populateVendorHero(v) {
    document.title = `${v.name} · RapidOrders`;

    document.getElementById('vendor-name-breadcrumb').textContent = v.name;
    document.getElementById('vendor-name').textContent            = v.name;
    document.getElementById('vendor-category-pill').textContent   = v.category || 'General';
    document.getElementById('vendor-description').textContent     = v.description || '';

    // format WhatsApp number nicely
    const wa = String(v.whatsapp);
    document.getElementById('vendor-wa-number').textContent =
        `+${wa.slice(0,3)} ${wa.slice(3,6)} ${wa.slice(6,9)} ${wa.slice(9)}`;

    // open/closed badge
    const badge = document.getElementById('open-badge');
    if (v.is_open == 1) {
        badge.innerHTML = `<div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div><span class="text-xs font-medium text-mono-dark">Open now</span>`;
    } else {
        badge.innerHTML = `<div class="w-2 h-2 bg-gray-300 rounded-full"></div><span class="text-xs font-medium text-mono-subtle">Closed</span>`;
    }

    document.getElementById('product-count-display').textContent = v.product_count;
}

// ── WhatsApp checkout link ────────────────────────────────────
function getWhatsAppLink() {
    const items = vendorCartItems();
    if (!items.length || !vendor) return null;
    let msg = `Hello ${vendor.name}! I'd like to order:\n\n`;
    items.forEach(i => { msg += `- ${i.name} x${i.qty} — GHS ${(i.price * i.qty).toLocaleString()}\n`; });
    msg += `\nTotal: GHS ${cartTotal().toLocaleString()}\n\nPlease confirm availability.`;
    return `https://wa.me/${vendor.whatsapp}?text=${encodeURIComponent(msg)}`;
}

// ── Payment modal with cart clearing ─────────────────────────
function openPaymentModal() {
    const total = cartTotal();
    if (total === 0) { 
        Swal.fire({
            title: 'Cart is empty',
            text: 'Please add items to your cart before making a payment.',
            icon: 'info',
            confirmButtonColor: '#0a0c10'
        });
        return; 
    }
    document.getElementById('payment-total-amount').textContent = `GHS ${total.toLocaleString()}`;
    document.getElementById('pay-dynamic-amount').textContent   = total.toLocaleString();
    document.getElementById('payment-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closePaymentModal() {
    document.getElementById('payment-modal').classList.remove('active');
    document.body.style.overflow = '';
}

let selectedMethod = 'card';
document.querySelectorAll('[data-method]').forEach(el => {
    const dot = el.querySelector('.w-5.h-5 div');
    if (dot) dot.classList.add('hidden');
    if (el.dataset.method === 'card') dot?.classList.remove('hidden');
    el.addEventListener('click', () => {
        selectedMethod = el.dataset.method;
        document.querySelectorAll('[data-method]').forEach(m => {
            m.querySelector('.w-5.h-5 div')?.classList.add('hidden');
        });
        el.querySelector('.w-5.h-5 div')?.classList.remove('hidden');
    });
});

document.getElementById('make-payment-btn').addEventListener('click', openPaymentModal);
document.getElementById('close-payment-modal').addEventListener('click', closePaymentModal);
document.getElementById('simulate-payment-btn').addEventListener('click', () => {
    const total = cartTotal();
    const method = selectedMethod === 'card' ? 'Card' : 'Mobile Money';
    
    // Clear cart for this vendor after successful payment
    clearCart();
    closePaymentModal();
    closeCartDrawer();
    
    Swal.fire({
        title: 'Payment successful! 💰',
        html: `Paid <strong>GHS ${total.toLocaleString()}</strong> via ${method}<br><br>The seller has been notified of your payment. You will receive a confirmation shortly.`,
        icon: 'success',
        confirmButtonColor: '#0a0c10',
        confirmButtonText: 'Great!'
    });
});

// ── Cart drawer ───────────────────────────────────────────────
const overlay    = document.getElementById('cart-overlay');
const drawer     = document.getElementById('cart-drawer');
const closeCartBtn = document.getElementById('close-cart-btn');
const cartIconBtn  = document.getElementById('cart-icon-btn');

function openCartDrawer()  { overlay.classList.remove('invisible','opacity-0'); drawer.classList.replace('translate-x-full','translate-x-0'); document.body.style.overflow='hidden'; renderCartSidebar(); }
function closeCartDrawer() { overlay.classList.add('invisible','opacity-0');    drawer.classList.replace('translate-x-0','translate-x-full');  document.body.style.overflow=''; }

cartIconBtn.addEventListener('click', openCartDrawer);
closeCartBtn.addEventListener('click', closeCartDrawer);
overlay.addEventListener('click', closeCartDrawer);
document.getElementById('whatsapp-checkout-btn').addEventListener('click', () => {
    const link = getWhatsAppLink();
    if (link) window.open(link, '_blank');
    else {
        Swal.fire({
            title: 'Cart is empty',
            text: 'Please add items to your cart first.',
            icon: 'info',
            confirmButtonColor: '#0a0c10'
        });
    }
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeCartDrawer(); closePaymentModal(); closeGallery(); }
});

// ── Load vendor + products from backend ───────────────────────
async function loadVendorPage() {
    try {
        const res = await fetch(`${BASE_PATH}/backend/vendors.php?slug=${VENDOR_SLUG}`);

        if (res.status === 404) {
            document.getElementById('product-grid').innerHTML = '';
            document.getElementById('vendor-not-found').classList.remove('hidden');
            return;
        }
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Unexpected response');

        vendor      = data.vendor;
        allProducts = data.products;

        populateVendorHero(vendor);
        renderFilterPills(data.categories);
        renderProductGrid();

    } catch (err) {
        console.error('Failed to load vendor:', err);
        document.getElementById('product-grid').innerHTML =
            `<div class="col-span-full text-center py-16 text-red-400 text-sm">Could not load this store. Please refresh.</div>`;
    }
}

// ── Boot ──────────────────────────────────────────────────────
loadCart();
loadVendorPage();
</script>
</body>
</html>