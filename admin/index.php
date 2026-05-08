<?php
// admin/index.php
// Vendor admin dashboard — login with WhatsApp number and password (0000 for all)

session_start();
require_once '../backend/db.php';

// Redirect if not logged in
$is_logged_in = isset($_SESSION['vendor_logged_in']) && $_SESSION['vendor_logged_in'] === true;
$vendor_id = $_SESSION['vendor_id'] ?? null;
$vendor_data = null;

// Get base path for correct URL construction
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');

if ($is_logged_in && $vendor_id) {
    // Fetch vendor data
    $conn = getDB();
    $stmt = $conn->prepare("SELECT v.*, c.name as category FROM vendors v LEFT JOIN categories c ON c.id = v.category_id WHERE v.id = ?");
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vendor_data = $result->fetch_assoc();
    $stmt->close();
}

// Handle login
$login_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($password === '0000') {
        $clean_whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
        $conn = getDB();
        $stmt = $conn->prepare("SELECT id, name, whatsapp FROM vendors WHERE whatsapp LIKE ? AND active = 1");
        $search = "%{$clean_whatsapp}%";
        $stmt->bind_param("s", $search);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $_SESSION['vendor_logged_in'] = true;
            $_SESSION['vendor_id'] = $row['id'];
            $_SESSION['vendor_name'] = $row['name'];
            $_SESSION['vendor_whatsapp'] = $row['whatsapp'];
            header('Location: index.php');
            exit;
        } else {
            $login_error = "Vendor not found with that WhatsApp number.";
        }
        $stmt->close();
    } else {
        $login_error = "Invalid password. All passwords are 0000 for demo.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Create necessary directories
$logo_dir = $_SERVER['DOCUMENT_ROOT'] . '/logo';
if (!file_exists($logo_dir)) {
    mkdir($logo_dir, 0777, true);
}
$product_images_dir = $_SERVER['DOCUMENT_ROOT'] . '/product_images';
if (!file_exists($product_images_dir)) {
    mkdir($product_images_dir, 0777, true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $is_logged_in ? 'Admin · ' . htmlspecialchars($vendor_data['name'] ?? 'Dashboard') : 'Vendor Login · RapidOrders'; ?></title>
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
                        'mono-bg': '#fafafa',
                        'mono-card': '#ffffff',
                        'mono-border': '#eaeef2',
                        'mono-subtle': '#5b6e8c',
                        'mono-accent': '#0a0c10',
                        'mono-dark': '#11151c',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'bounce-subtle': 'bounce 1s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
    <style>
        * { -webkit-font-smoothing: antialiased; }
        body { background: linear-gradient(135deg, #fafafa 0%, #f5f5f7 100%); }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .glass-header {
            background: rgba(250,250,250,0.82);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }
        
        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeIn 0.5s ease-out;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0,0,0,0.1);
        }
        
        .product-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideUp 0.4s ease-out;
        }
        
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px -12px rgba(0,0,0,0.15);
        }
        
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);
            z-index: 200; display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: all 0.3s ease;
        }
        
        .modal-overlay.active { opacity: 1; visibility: visible; }
        
        .modal-card {
            background: white; max-width: 600px; width: 90%; border-radius: 28px; 
            padding: 32px; max-height: 85vh; overflow-y: auto;
            transform: scale(0.95); transition: transform 0.3s ease;
            animation: slideUp 0.3s ease-out;
        }
        
        .modal-overlay.active .modal-card { transform: scale(1); }
        
        .image-upload-box {
            border: 2px dashed #eaeef2;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .image-upload-box:hover { 
            border-color: #0a0c10; 
            background: #f5f5f5;
            transform: translateY(-2px);
        }
        
        .image-upload-box.required {
            border-color: #f59e0b;
            background: #fffbeb;
        }
        
        .preview-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: transform 0.2s;
        }
        
        .preview-img:hover { transform: scale(1.02); }
        
        .btn-primary {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.2);
        }
        
        .btn-primary:active { transform: translateY(0); }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Loading shimmer */
        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
    </style>
</head>
<body class="font-sans antialiased">

<!-- Header -->
<header class="glass-header sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <a href="<?php echo $basePath ?: '/'; ?>" class="flex items-center gap-2 group">
            <span class="text-2xl font-semibold tracking-tight text-mono-dark">Rapid<span class="font-light">Orders</span></span>
            <span class="ml-2 text-[11px] font-medium text-mono-subtle uppercase tracking-wide">Admin</span>
            <div class="w-1 h-1 bg-mono-accent rounded-full opacity-0 group-hover:opacity-100 transition"></div>
        </a>
        <?php if ($is_logged_in): ?>
        <div class="flex items-center gap-4">
            <span class="text-sm text-mono-subtle flex items-center gap-2">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                <?php echo htmlspecialchars($vendor_data['name'] ?? ''); ?>
            </span>
            <a href="?logout=1" class="text-sm text-red-500 hover:text-red-700 transition hover:scale-105 inline-block">Logout</a>
        </div>
        <?php endif; ?>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-8">

<?php if (!$is_logged_in): ?>
    <!-- Login Form with animation -->
    <div class="min-h-[70vh] flex items-center justify-center">
        <div class="bg-white rounded-2xl border border-mono-border p-8 max-w-md w-full shadow-xl" data-aos="fade-up" data-aos-duration="600">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-gradient-to-br from-mono-accent/10 to-mono-accent/5 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce-subtle">
                    <svg class="w-10 h-10 text-mono-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <h2 class="text-3xl font-bold text-mono-dark">Welcome Back</h2>
                <p class="text-mono-subtle text-sm mt-2">Enter your credentials to access your store</p>
            </div>
            <?php if ($login_error): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm animate-shake"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div class="group">
                    <label class="block text-sm font-medium text-mono-dark mb-1">WhatsApp Number</label>
                    <input type="tel" name="whatsapp" required placeholder="e.g., 233244000001" 
                           class="w-full px-4 py-3 rounded-xl border border-mono-border focus:outline-none focus:ring-2 focus:ring-mono-accent/20 transition-all duration-200 group-hover:border-mono-accent/30">
                </div>
                <div class="group">
                    <label class="block text-sm font-medium text-mono-dark mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Enter 0000" 
                           class="w-full px-4 py-3 rounded-xl border border-mono-border focus:outline-none focus:ring-2 focus:ring-mono-accent/20 transition-all duration-200 group-hover:border-mono-accent/30">
                </div>
                <button type="submit" name="login" class="btn-primary w-full py-3 rounded-xl bg-mono-accent text-white font-medium hover:opacity-90 transition">Login →</button>
            </form>
            <p class="text-center text-xs text-mono-subtle mt-6">Demo: TechHub GH (233244000001) • Password: 0000</p>
        </div>
    </div>
<?php else: ?>
    <!-- Admin Dashboard -->
    <div class="space-y-8">
        <!-- Stats Row with animations -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
            <div class="stat-card bg-white rounded-xl border border-mono-border p-5 cursor-pointer" data-aos="fade-up" data-aos-delay="0">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-mono-subtle text-sm">Total Products</p>
                        <p class="text-3xl font-bold text-mono-dark" id="stat-products">-</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-mono-accent/10 to-mono-accent/5 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-mono-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-white rounded-xl border border-mono-border p-5 cursor-pointer" data-aos="fade-up" data-aos-delay="50">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-mono-subtle text-sm">In Stock</p>
                        <p class="text-3xl font-bold text-emerald-600" id="stat-instock">-</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-white rounded-xl border border-mono-border p-5 cursor-pointer" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-mono-subtle text-sm">Out of Stock</p>
                        <p class="text-3xl font-bold text-red-500" id="stat-outstock">-</p>
                    </div>
                    <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-white rounded-xl border border-mono-border p-5 cursor-pointer" data-aos="fade-up" data-aos-delay="150">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-mono-subtle text-sm">Total Orders</p>
                        <p class="text-3xl font-bold text-mono-dark" id="stat-orders">-</p>
                    </div>
                    <div class="w-12 h-12 bg-mono-accent/5 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-mono-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-white rounded-xl border border-mono-border p-5 cursor-pointer" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-mono-subtle text-sm">Page Visits</p>
                        <p class="text-3xl font-bold text-blue-600" id="stat-visits">-</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Settings Card -->
        <div class="bg-white rounded-xl border border-mono-border overflow-hidden shadow-sm hover:shadow-md transition-shadow" data-aos="fade-up" data-aos-delay="100">
            <div class="border-b border-mono-border px-6 py-4 flex justify-between items-center bg-gradient-to-r from-white to-gray-50">
                <h2 class="font-semibold text-mono-dark flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    Store Settings
                </h2>
                <button onclick="openStoreModal()" class="text-xs px-3 py-1.5 rounded-full bg-mono-accent text-white hover:opacity-90 transition transform hover:scale-105">Edit</button>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex flex-col items-center text-center group">
                    <p class="text-xs text-mono-subtle uppercase tracking-wide mb-2">Store Logo</p>
                    <div id="logo-container" class="relative">
                        <?php 
                        $logo_path = $vendor_data['cover_image'] ?? null;
                        $project_root = dirname(__DIR__);
                        $full_logo_path = $logo_path ? $project_root . $logo_path : null;
                        $logo_exists = ($logo_path && file_exists($full_logo_path));
                        $base_url = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                        $logo_url = $logo_exists ? $base_url . $logo_path : 'https://placehold.co/100x100?text=Logo';
                        $cache_buster = $logo_exists ? '?v=' . filemtime($full_logo_path) : '';
                        ?>
                        <img id="store-logo-img" src="<?php echo $logo_url . $cache_buster; ?>" 
                             class="w-24 h-24 rounded-xl object-cover border-2 border-mono-border mb-2 transition-transform group-hover:scale-105" alt="Store Logo">
                    </div>
                    <button onclick="document.getElementById('logo-upload').click()" class="text-xs text-mono-accent underline hover:no-underline">Upload Logo</button>
                    <input type="file" id="logo-upload" accept="image/*" style="display:none" onchange="uploadLogo(this)">
                </div>
                <div>
                    <p class="text-xs text-mono-subtle uppercase tracking-wide mb-1">Store Name</p>
                    <p class="font-medium text-mono-dark text-lg" id="store-name"><?php echo htmlspecialchars($vendor_data['name'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-xs text-mono-subtle uppercase tracking-wide mb-1">WhatsApp Business</p>
                    <p class="font-medium text-mono-dark font-mono" id="store-whatsapp"><?php echo htmlspecialchars($vendor_data['whatsapp'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-xs text-mono-subtle uppercase tracking-wide mb-1">Category</p>
                    <p class="font-medium text-mono-dark" id="store-category"><?php echo htmlspecialchars($vendor_data['category'] ?? 'Electronics'); ?></p>
                </div>
                <div>
                    <p class="text-xs text-mono-subtle uppercase tracking-wide mb-1">Status</p>
                    <span id="store-status-badge" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium <?php echo ($vendor_data['is_open'] ?? 1) ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'; ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?php echo ($vendor_data['is_open'] ?? 1) ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'; ?>"></span>
                        <?php echo ($vendor_data['is_open'] ?? 1) ? 'Open' : 'Closed'; ?>
                    </span>
                </div>
                <div class="md:col-span-3">
                    <p class="text-xs text-mono-subtle uppercase tracking-wide mb-1">Description</p>
                    <p class="text-sm text-mono-dark leading-relaxed" id="store-description"><?php echo htmlspecialchars($vendor_data['description'] ?? ''); ?></p>
                </div>
            </div>
        </div>

        <!-- Payment Settings Card -->
        <div class="bg-white rounded-xl border border-mono-border overflow-hidden shadow-sm hover:shadow-md transition-shadow" data-aos="fade-up" data-aos-delay="150">
            <div class="border-b border-mono-border px-6 py-4 flex justify-between items-center bg-gradient-to-r from-white to-gray-50">
                <h2 class="font-semibold text-mono-dark flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Payment Settings
                </h2>
                <button onclick="openPaymentModal()" class="text-xs px-3 py-1.5 rounded-full bg-mono-accent text-white hover:opacity-90 transition transform hover:scale-105">Edit</button>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs text-mono-subtle uppercase tracking-wide mb-1">Mobile Money Number</p>
                    <p class="font-medium text-mono-dark font-mono text-lg" id="momo-number"><?php echo htmlspecialchars($vendor_data['whatsapp'] ?? ''); ?></p>
                    <p class="text-xs text-mono-subtle mt-1">Payments will be sent to this number</p>
                </div>
                <div>
                    <p class="text-xs text-mono-subtle uppercase tracking-wide mb-1">Momo Provider</p>
                    <p class="font-medium text-mono-dark" id="momo-provider">MTN / Vodafone / AirtelTigo</p>
                </div>
            </div>
        </div>

        <!-- Products Management -->
        <div class="bg-white rounded-xl border border-mono-border overflow-hidden shadow-sm hover:shadow-md transition-shadow" data-aos="fade-up" data-aos-delay="200">
            <div class="border-b border-mono-border px-6 py-4 flex justify-between items-center flex-wrap gap-3 bg-gradient-to-r from-white to-gray-50">
                <h2 class="font-semibold text-mono-dark flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    Products
                </h2>
                <button onclick="openProductModal()" class="btn-primary text-sm px-5 py-2 rounded-full bg-mono-accent text-white hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Add Product
                </button>
            </div>
            <div class="p-6">
                <div id="products-list" class="space-y-3">
                    <div class="text-center py-8 text-mono-subtle shimmer rounded-lg p-8">Loading products...</div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</main>

<!-- MODALS -->
<!-- Store Edit Modal -->
<div id="store-modal" class="modal-overlay" onclick="if(event.target===this) closeStoreModal()">
    <div class="modal-card">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-xl font-semibold text-mono-dark">Edit Store</h3>
            <button onclick="closeStoreModal()" class="text-2xl text-gray-400 hover:text-black transition">×</button>
        </div>
        <form id="store-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-1">Store Name</label>
                <input type="text" id="edit-store-name" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-1">WhatsApp Number</label>
                <input type="tel" id="edit-store-whatsapp" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-1">Description</label>
                <textarea id="edit-store-description" rows="3" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-1">Store Status</label>
                <select id="edit-store-open" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition">
                    <option value="1">Open</option>
                    <option value="0">Closed</option>
                </select>
            </div>
            <div class="flex gap-3 pt-3">
                <button type="submit" class="flex-1 py-3 rounded-xl bg-mono-accent text-white font-medium hover:opacity-90 transition">Save Changes</button>
                <button type="button" onclick="closeStoreModal()" class="flex-1 py-3 rounded-xl border border-mono-border text-mono-dark hover:bg-gray-50 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Settings Modal -->
<div id="payment-modal" class="modal-overlay" onclick="if(event.target===this) closePaymentModal()">
    <div class="modal-card">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-xl font-semibold text-mono-dark">Payment Settings</h3>
            <button onclick="closePaymentModal()" class="text-2xl text-gray-400 hover:text-black transition">×</button>
        </div>
        <form id="payment-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-1">Mobile Money Number</label>
                <input type="tel" id="edit-momo-number" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition" required>
                <p class="text-xs text-mono-subtle mt-1">Customer payments will be sent to this number</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-1">Momo Network</label>
                <select id="edit-momo-provider" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition">
                    <option>MTN Mobile Money</option>
                    <option>Vodafone Cash</option>
                    <option>AirtelTigo Money</option>
                </select>
            </div>
            <div class="flex gap-3 pt-3">
                <button type="submit" class="flex-1 py-3 rounded-xl bg-mono-accent text-white font-medium hover:opacity-90 transition">Save Payment Info</button>
                <button type="button" onclick="closePaymentModal()" class="flex-1 py-3 rounded-xl border border-mono-border text-mono-dark hover:bg-gray-50 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Product Modal with 3 Image Uploads -->
<div id="product-modal" class="modal-overlay" onclick="if(event.target===this) closeProductModal()">
    <div class="modal-card">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-xl font-semibold text-mono-dark" id="product-modal-title">Add Product</h3>
            <button onclick="closeProductModal()" class="text-2xl text-gray-400 hover:text-black transition">×</button>
        </div>
        <form id="product-form" class="space-y-4" enctype="multipart/form-data">
            <input type="hidden" id="product-id" value="">
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-1">Product Name <span class="text-red-500">*</span></label>
                <input type="text" id="product-name" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-1">Category</label>
                <select id="product-category" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition">
                    <option value="">Loading categories...</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-1">Description</label>
                <textarea id="product-description" rows="2" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-mono-dark mb-1">Price (GHS) <span class="text-red-500">*</span></label>
                    <input type="number" id="product-price" step="0.01" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-mono-dark mb-1">In Stock</label>
                    <select id="product-stock" class="w-full px-4 py-3 rounded-xl border border-mono-border focus:ring-2 focus:ring-mono-accent/20 transition">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            
            <!-- 3 Image Uploads -->
            <div>
                <label class="block text-sm font-medium text-mono-dark mb-2">Product Images <span class="text-red-500">* (First image required)</span></label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Image 1 (Mandatory) -->
                    <div>
                        <div class="image-upload-box required" id="upload-box-1" onclick="document.getElementById('product-image-1').click()">
                            <img id="preview-1" class="preview-img hidden">
                            <div id="placeholder-1" class="py-8">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <p class="text-sm text-mono-subtle">Image 1</p>
                                <p class="text-xs text-orange-500 mt-1">Required *</p>
                            </div>
                        </div>
                        <input type="file" id="product-image-1" accept="image/*" style="display:none" onchange="previewImage(this, 1)">
                    </div>
                    <!-- Image 2 (Optional) -->
                    <div>
                        <div class="image-upload-box" id="upload-box-2" onclick="document.getElementById('product-image-2').click()">
                            <img id="preview-2" class="preview-img hidden">
                            <div id="placeholder-2" class="py-8">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <p class="text-sm text-mono-subtle">Image 2</p>
                                <p class="text-xs text-mono-subtle">Optional</p>
                            </div>
                        </div>
                        <input type="file" id="product-image-2" accept="image/*" style="display:none" onchange="previewImage(this, 2)">
                    </div>
                    <!-- Image 3 (Optional) -->
                    <div>
                        <div class="image-upload-box" id="upload-box-3" onclick="document.getElementById('product-image-3').click()">
                            <img id="preview-3" class="preview-img hidden">
                            <div id="placeholder-3" class="py-8">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <p class="text-sm text-mono-subtle">Image 3</p>
                                <p class="text-xs text-mono-subtle">Optional</p>
                            </div>
                        </div>
                        <input type="file" id="product-image-3" accept="image/*" style="display:none" onchange="previewImage(this, 3)">
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 pt-3">
                <button type="submit" class="flex-1 py-3 rounded-xl bg-mono-accent text-white font-medium hover:opacity-90 transition">Save Product</button>
                <button type="button" onclick="closeProductModal()" class="flex-1 py-3 rounded-xl border border-mono-border text-mono-dark hover:bg-gray-50 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration: 500, once: true, offset: 20 });

<?php if ($is_logged_in): ?>
const VENDOR_ID = <?php echo json_encode($vendor_id); ?>;
const BASE_PATH = <?php echo json_encode($basePath); ?>;
let image1File = null, image2File = null, image3File = null;

// Preview image function
function previewImage(input, num) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        
        // Store file for upload
        if (num === 1) image1File = file;
        else if (num === 2) image2File = file;
        else if (num === 3) image3File = file;
        
        reader.onload = function(e) {
            const preview = document.getElementById(`preview-${num}`);
            const placeholder = document.getElementById(`placeholder-${num}`);
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            
            // Remove required styling if image 1 is uploaded
            if (num === 1) {
                document.getElementById('upload-box-1').classList.remove('required');
                document.getElementById('upload-box-1').style.borderColor = '#eaeef2';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Upload logo
async function uploadLogo(input) {
    const file = input.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('logo', file);
    formData.append('vendor_id', VENDOR_ID);
    
    try {
        const res = await fetch(`${BASE_PATH}/backend/upload_logo.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ title: 'Success!', text: 'Logo uploaded successfully. Page will refresh.', icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch(e) {
        Swal.fire('Error', 'Failed to upload logo', 'error');
    }
    input.value = '';
}

// Helper function for API calls
async function apiCall(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: options.body instanceof FormData ? {} : { 'Content-Type': 'application/json', ...options.headers }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const text = await response.text();
        if (!text || text.trim() === '') throw new Error('Empty response');
        return JSON.parse(text);
    } catch (e) {
        console.error('API Error:', e);
        throw e;
    }
}

// Load products
async function loadProducts() {
    try {
        const url = `${BASE_PATH}/backend/products.php?vendor_id=${VENDOR_ID}`;
        const data = await apiCall(url);
        if (data.success) {
            renderProducts(data.data || []);
            updateStats(data.data || []);
        } else {
            document.getElementById('products-list').innerHTML = '<div class="text-center py-8 text-red-500">Failed to load products.</div>';
        }
    } catch(e) {
        document.getElementById('products-list').innerHTML = '<div class="text-center py-8 text-red-500">Error loading products.</div>';
    }
}

function renderProducts(products) {
    const container = document.getElementById('products-list');
    if (!container) return;
    if (!products.length) {
        container.innerHTML = '<div class="text-center py-8 text-mono-subtle">✨ No products yet. Add your first product!</div>';
        return;
    }
    container.innerHTML = products.map((p, idx) => `
        <div class="product-card bg-gray-50 rounded-xl p-4 flex flex-wrap justify-between items-center gap-4" data-aos="fade-up" data-aos-delay="${idx * 50}">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="font-semibold text-mono-dark">${escapeHtml(p.name)}</h3>
                    <span class="text-xs px-2 py-0.5 rounded-full ${p.in_stock ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'}">${p.in_stock ? 'In Stock' : 'Out of Stock'}</span>
                </div>
                <p class="text-sm text-mono-subtle mt-1">${escapeHtml(p.category || 'Uncategorized')}</p>
                <p class="text-sm font-medium text-mono-dark mt-1">GHS ${Number(p.price).toLocaleString()}</p>
            </div>
            <div class="flex gap-2">
                <button onclick="editProduct(${p.id})" class="px-3 py-1.5 rounded-lg border border-mono-border text-sm hover:bg-white transition">Edit</button>
                <button onclick="deleteProduct(${p.id})" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-500 text-sm hover:bg-red-50 transition">Delete</button>
            </div>
        </div>
    `).join('');
}

function updateStats(products) {
    const total = products.length;
    const inStock = products.filter(p => p.in_stock == 1).length;
    const outStock = total - inStock;
    document.getElementById('stat-products').textContent = total;
    document.getElementById('stat-instock').textContent = inStock;
    document.getElementById('stat-outstock').textContent = outStock;
}

// Load categories
async function loadCategories() {
    try {
        const url = `${BASE_PATH}/backend/categories.php`;
        const data = await apiCall(url);
        if (data.success && data.data && data.data.categories) {
            const select = document.getElementById('product-category');
            if (select) {
                select.innerHTML = '<option value="">Select category</option>' + 
                    data.data.categories.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
            }
        }
    } catch(e) {
        console.error('Error loading categories:', e);
    }
}

// Save product with up to 3 images
async function saveProduct(productData) {
    try {
        // Check if at least first image is provided
        if (!image1File && !document.getElementById('product-id').value) {
            Swal.fire('Error', 'Please upload at least one product image (Image 1 is required)', 'error');
            return;
        }
        
        // Upload images
        let uploadedImages = [];
        const imageFiles = [image1File, image2File, image3File].filter(f => f !== null);
        
        if (imageFiles.length > 0) {
            const formData = new FormData();
            formData.append('vendor_id', VENDOR_ID);
            formData.append('product_name', productData.name);
            for (let img of imageFiles) {
                formData.append('images[]', img);
            }
            const uploadRes = await fetch(`${BASE_PATH}/backend/upload_product_images.php`, { method: 'POST', body: formData });
            const uploadData = await uploadRes.json();
            if (uploadData.success) {
                uploadedImages = uploadData.images;
            }
        }
        
        // Pad to 3 images (empty strings for missing)
        while (uploadedImages.length < 3) uploadedImages.push('');
        
        const url = `${BASE_PATH}/backend/products.php`;
        const data = await apiCall(url, {
            method: 'POST',
            body: JSON.stringify({ 
                ...productData, 
                vendor_id: VENDOR_ID, 
                image1: uploadedImages[0] || '',
                image2: uploadedImages[1] || '',
                image3: uploadedImages[2] || ''
            })
        });
        
        if (data.success) {
            Swal.fire({ title: 'Success!', text: data.message, icon: 'success', timer: 1500, showConfirmButton: false });
            closeProductModal();
            loadProducts();
        } else {
            Swal.fire({ title: 'Error', text: data.message || 'Something went wrong', icon: 'error' });
        }
    } catch(e) {
        Swal.fire({ title: 'Error', text: 'Failed to save product', icon: 'error' });
    }
}

async function deleteProduct(productId) {
    const result = await Swal.fire({
        title: 'Delete product?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Delete'
    });
    if (result.isConfirmed) {
        try {
            const url = `${BASE_PATH}/backend/products.php?id=${productId}`;
            const data = await apiCall(url, { method: 'DELETE' });
            if (data.success) {
                Swal.fire('Deleted', '', 'success');
                loadProducts();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch(e) {
            Swal.fire('Error', 'Failed to delete product', 'error');
        }
    }
}

async function editProduct(id) {
    try {
        const url = `${BASE_PATH}/backend/products.php?id=${id}`;
        const data = await apiCall(url);
        if (data.success && data.data) {
            const product = data.data;
            document.getElementById('product-modal-title').textContent = 'Edit Product';
            document.getElementById('product-id').value = product.id;
            document.getElementById('product-name').value = product.name;
            document.getElementById('product-description').value = product.description || '';
            document.getElementById('product-price').value = product.price;
            document.getElementById('product-stock').value = product.in_stock;
            if (product.category_id) document.getElementById('product-category').value = product.category_id;
            
            // Reset images
            image1File = image2File = image3File = null;
            const placeholders = [1,2,3].forEach(num => {
                document.getElementById(`preview-${num}`).classList.add('hidden');
                document.getElementById(`placeholder-${num}`).classList.remove('hidden');
            });
            openProductModal();
        }
    } catch(e) {
        Swal.fire('Error', 'Failed to load product details', 'error');
    }
}

async function updateStore(storeData) {
    try {
        const url = `${BASE_PATH}/backend/vendors_info.php`;
        const data = await apiCall(url, {
            method: 'PUT',
            body: JSON.stringify({ id: VENDOR_ID, ...storeData })
        });
        if (data.success) {
            Swal.fire({ title: 'Updated!', text: 'Store settings saved', icon: 'success', timer: 1500, showConfirmButton: false });
            setTimeout(() => location.reload(), 1500);
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch(e) {
        Swal.fire('Error', 'Failed to update store', 'error');
    }
}

async function updatePayment(paymentData) {
    try {
        const url = `${BASE_PATH}/backend/vendors_info.php`;
        const data = await apiCall(url, {
            method: 'PUT',
            body: JSON.stringify({ id: VENDOR_ID, ...paymentData })
        });
        if (data.success) {
            Swal.fire({ title: 'Updated!', text: 'Payment settings saved', icon: 'success', timer: 1500, showConfirmButton: false });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch(e) {
        Swal.fire('Error', 'Failed to update payment settings', 'error');
    }
}

// Modal handlers
function openProductModal() { document.getElementById('product-modal').classList.add('active'); }
function closeProductModal() { 
    document.getElementById('product-modal').classList.remove('active');
    document.getElementById('product-form').reset();
    document.getElementById('product-id').value = '';
    document.getElementById('product-modal-title').textContent = 'Add Product';
    image1File = image2File = image3File = null;
    [1,2,3].forEach(num => {
        document.getElementById(`preview-${num}`).classList.add('hidden');
        document.getElementById(`placeholder-${num}`).classList.remove('hidden');
    });
    document.getElementById('upload-box-1').classList.add('required');
}
function openStoreModal() {
    document.getElementById('edit-store-name').value = document.getElementById('store-name').textContent;
    document.getElementById('edit-store-whatsapp').value = document.getElementById('store-whatsapp').textContent;
    document.getElementById('edit-store-description').value = document.getElementById('store-description').textContent;
    const isOpen = document.getElementById('store-status-badge').textContent.trim() === 'Open';
    document.getElementById('edit-store-open').value = isOpen ? '1' : '0';
    document.getElementById('store-modal').classList.add('active');
}
function closeStoreModal() { document.getElementById('store-modal').classList.remove('active'); }
function openPaymentModal() {
    document.getElementById('edit-momo-number').value = document.getElementById('momo-number').textContent;
    document.getElementById('payment-modal').classList.add('active');
}
function closePaymentModal() { document.getElementById('payment-modal').classList.remove('active'); }

async function loadOrdersAndVisits() {
    try {
        const url = `${BASE_PATH}/backend/stats.php?vendor_id=${VENDOR_ID}`;
        const data = await apiCall(url);
        if (data.success && data.data) {
            document.getElementById('stat-orders').textContent = data.data.orders || 0;
            document.getElementById('stat-visits').textContent = data.data.visits || 0;
        }
    } catch(e) {
        document.getElementById('stat-orders').textContent = '0';
        document.getElementById('stat-visits').textContent = '0';
    }
}

// Form submissions
document.addEventListener('DOMContentLoaded', function() {
    const productForm = document.getElementById('product-form');
    if (productForm) {
        productForm.addEventListener('submit', (e) => {
            e.preventDefault();
            saveProduct({
                id: document.getElementById('product-id').value || null,
                name: document.getElementById('product-name').value,
                category_id: document.getElementById('product-category').value || null,
                description: document.getElementById('product-description').value,
                price: parseFloat(document.getElementById('product-price').value),
                in_stock: parseInt(document.getElementById('product-stock').value)
            });
        });
    }
    
    const storeForm = document.getElementById('store-form');
    if (storeForm) {
        storeForm.addEventListener('submit', (e) => {
            e.preventDefault();
            updateStore({
                name: document.getElementById('edit-store-name').value,
                whatsapp: document.getElementById('edit-store-whatsapp').value,
                description: document.getElementById('edit-store-description').value,
                is_open: parseInt(document.getElementById('edit-store-open').value)
            });
            closeStoreModal();
        });
    }
    
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', (e) => {
            e.preventDefault();
            updatePayment({
                momo_number: document.getElementById('edit-momo-number').value,
                momo_provider: document.getElementById('edit-momo-provider').value
            });
            closePaymentModal();
        });
    }
});

function escapeHtml(str) { 
    if (!str) return ''; 
    return str.replace(/[&<>]/g, function(m) { 
        if (m === '&') return '&amp;'; 
        if (m === '<') return '&lt;'; 
        if (m === '>') return '&gt;'; 
        return m; 
    }); 
}

// Initialize
loadCategories();
loadProducts();
loadOrdersAndVisits();
<?php endif; ?>
</script>
</body>
</html>