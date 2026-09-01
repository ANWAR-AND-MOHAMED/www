<?php
require 'db.php';

// ==================== لوحة التحكم ====================
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $id = (int)$_GET['delete'];
    $res = $conn->query("SELECT image FROM products WHERE id = $id");
    if ($res && ($row = $res->fetch_assoc())) {
        if (!empty($row['image']) && file_exists(__DIR__ . '/' . $row['image'])) {
            unlink(__DIR__ . '/' . $row['image']);
        }
    }
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    if (!$stmt) die("خطأ SQL: " . $conn->error);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php?admin=1&msg=deleted');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'add') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
        $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : 'electronics';
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $originalName = basename($_FILES['image']['name']);
            $safeName = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $originalName);
            $uniqueName = time() . '_' . $safeName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $uniqueName)) {
                $imagePath = 'uploads/' . $uniqueName;
            }
        }
        $stmt = $conn->prepare("INSERT INTO products (name, price, stock, description, image, category) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) die("خطأ SQL: " . $conn->error);
        $stmt->bind_param('sdisss', $name, $price, $stock, $description, $imagePath, $category);
        $stmt->execute();
        $stmt->close();
        header('Location: index.php?admin=1&msg=added');
        exit;
    }
    if ($action === 'edit') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
        $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : 'electronics';
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ?, description = ?, category = ? WHERE id = ?");
        if (!$stmt) die("خطأ SQL: " . $conn->error);
        $stmt->bind_param('sdissi', $name, $price, $stock, $description, $category, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: index.php?admin=1&msg=updated');
        exit;
    }
}

// المنتجات المعروضة في المتجر تأتي حصراً من قاعدة البيانات
$storeProducts = [];
$resultStore = $conn->query("SELECT id, name, price, category, image, description FROM products WHERE stock > 0 ORDER BY id DESC");
if ($resultStore) {
    while ($row = $resultStore->fetch_assoc()) {
        $storeProducts[] = [
            'id' => (string)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'category' => $row['category'],
            'image' => !empty($row['image']) ? $row['image'] : 'icons/1921-v2.png',
            'desc' => isset($row['description']) ? $row['description'] : ''
        ];
    }
}

$adminMode = isset($_GET['admin']) && $_GET['admin'] === '1';
$adminProducts = $adminMode ? $conn->query("SELECT * FROM products ORDER BY id DESC") : null;
$messages = ['added'=>'تمت إضافة المنتج بنجاح.','updated'=>'تم تحديث المنتج بنجاح.','deleted'=>'تم حذف المنتج بنجاح.'];
$msgKey = isset($_GET['msg']) ? $_GET['msg'] : '';
$alert = isset($messages[$msgKey]) ? $messages[$msgKey] : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0d47a1">
    <meta name="description" content="متجر GLAKTARBO — أجهزة إلكترونية، ملابس وحقائب، وبث مباشر للمنتجات.">
    <title>متجر GLAKTARBO</title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="icons/1921-v2.png">
    <link rel="apple-touch-icon" href="icons/1921-v2.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #f3f6fb;
    --surface: #ffffff;
    --text: #122033;
    --muted: #5b6b82;
    --line: #e4ebf4;
    --brand: #0d47a1;
    --brand-2: #1565c0;
    --accent: #ff8a00;
    --danger: #e53935;
    --success: #2e7d32;
    --live: #d32f2f;
    --shadow: 0 12px 32px rgba(13, 71, 161, 0.10);
    --radius: 18px;
    --header-h: 72px;
    --bottom-h: 0px;
}
[data-theme="dark"] {
    --bg: #0b1220;
    --surface: #152033;
    --text: #e8eef7;
    --muted: #9aabc2;
    --line: #24344c;
    --brand: #64b5f6;
    --brand-2: #1e88e5;
    --accent: #ffb74d;
    --shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
    font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100dvh;
    padding-bottom: var(--bottom-h);
}
img, video { max-width: 100%; display: block; }
button, input { font-family: inherit; }
button { cursor: pointer; }
a { color: inherit; }

.site-header {
    position: sticky;
    top: 0;
    z-index: 50;
    height: var(--header-h);
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0 18px;
    background: color-mix(in srgb, var(--surface) 88%, transparent);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--line);
    overflow: visible;
}
.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: max-content;
    background: none;
    border: none;
    color: var(--text);
}
.brand img {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    object-fit: cover;
}
.brand-name { font-weight: 800; font-size: 18px; letter-spacing: 0.3px; }
.brand-tag { display: block; font-size: 11px; color: var(--muted); font-weight: 600; }

.desktop-nav { display: none; gap: 6px; flex: 1; justify-content: center; }
.nav-link {
    background: none;
    border: none;
    color: var(--muted);
    font-weight: 700;
    font-size: 14px;
    padding: 8px 14px;
    border-radius: 999px;
}
.nav-link.active, .nav-link:hover { color: var(--brand); background: color-mix(in srgb, var(--brand) 10%, transparent); }
.nav-link.live { color: var(--live); }
.nav-link.live.active { background: color-mix(in srgb, var(--live) 12%, transparent); }

.header-actions { margin-inline-start: auto; display: flex; align-items: center; gap: 8px; }
.icon-btn, .cart-chip {
    border: 1px solid var(--line);
    background: var(--surface);
    color: var(--text);
    border-radius: 999px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 0 12px;
    font-weight: 700;
}
.icon-btn { width: 42px; padding: 0; font-size: 18px; }
.cart-chip { background: var(--brand); color: #fff; border-color: transparent; }
.cart-chip span {
    min-width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #fff;
    color: var(--brand-2);
    font-size: 12px;
    display: grid;
    place-items: center;
}

.search-wrap { display: none; flex: 1; max-width: 420px; }
.search-wrap input, .mobile-search input {
    width: 100%;
    height: 42px;
    border: 1px solid var(--line);
    border-radius: 999px;
    padding: 0 16px;
    background: var(--bg);
    color: var(--text);
    outline: none;
}
.mobile-search { padding: 12px 16px 0; display: none; }
.mobile-menu {
    display: none;
    position: absolute;
    top: calc(var(--header-h) + 8px);
    inset-inline-start: 12px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: var(--shadow);
    min-width: 220px;
    z-index: 60;
    overflow: hidden;
}
.mobile-menu.open { display: grid; }
.mobile-menu button {
    background: none;
    border: none;
    text-align: start;
    padding: 12px 16px;
    font-weight: 800;
    color: var(--text);
    border-bottom: 1px solid var(--line);
}
.mobile-menu button:last-child { border-bottom: 0; }

.page { display: none; max-width: 1180px; margin: 0 auto; padding: 22px 16px 40px; }
.page.active { display: block; animation: rise .35s ease; }
@keyframes rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

.hero {
    display: grid;
    gap: 18px;
    background: linear-gradient(135deg, #0d47a1 0%, #1565c0 55%, #1a237e 100%);
    color: #fff;
    border-radius: 28px;
    overflow: hidden;
    min-height: 280px;
    position: relative;
}
.hero-copy { padding: 28px 24px 20px; z-index: 1; }
.hero-copy h1 { font-size: clamp(26px, 5vw, 42px); line-height: 1.25; margin: 8px 0 12px; }
.hero-copy p { opacity: .9; max-width: 36ch; }
.hero-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
.btn {
    border: none;
    border-radius: 999px;
    padding: 11px 18px;
    font-weight: 800;
    font-size: 14px;
}
.btn-light { background: #fff; color: #0d47a1; }
.btn-ghost { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.25); }
.hero-visual {
    min-height: 180px;
    background: url('5996670327393553729_121.jpg') center/cover no-repeat;
}
.kicker {
    display: inline-flex;
    gap: 6px;
    align-items: center;
    background: rgba(255,255,255,.14);
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 700;
}

.section-title { display: flex; justify-content: space-between; align-items: end; gap: 12px; margin: 28px 0 16px; }
.section-title h2 { font-size: 22px; }
.section-title p { color: var(--muted); font-size: 13px; }

.cat-grid, .products-grid, .streams-grid {
    display: grid;
    gap: 16px;
}
.cat-grid { grid-template-columns: 1fr; }
.products-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.streams-grid { grid-template-columns: 1fr; }

.cat-card, .product-card, .stream-card, .empty-box {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
}
.cat-card { text-align: start; cursor: pointer; background: var(--surface); color: inherit; width: 100%; }
.cat-card img { width: 100%; height: 150px; object-fit: cover; }
.cat-card .body, .product-card .body { padding: 14px; }
.cat-card h3 { font-size: 18px; margin-bottom: 4px; }
.cat-card p, .product-card p { color: var(--muted); font-size: 13px; line-height: 1.6; }
.live-card { border-color: color-mix(in srgb, var(--live) 40%, var(--line)); }

.product-card { display: flex; flex-direction: column; }
.product-card img { width: 100%; height: 150px; object-fit: cover; background: #dbe7f5; }
.product-card h3 { font-size: 15px; line-height: 1.45; min-height: 44px; }
.price { color: var(--danger); font-weight: 800; font-size: 18px; margin: 8px 0 12px; }
.add-btn {
    width: 100%;
    margin-top: auto;
    background: var(--brand-2);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 10px;
    font-weight: 800;
}
.page-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
.back-btn {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 999px;
    padding: 8px 14px;
    font-weight: 700;
    color: var(--text);
}

.stream-card video { width: 100%; background: #000; aspect-ratio: 16/9; object-fit: contain; }
.stream-meta { display: flex; justify-content: space-between; padding: 12px 14px; font-weight: 800; }
.badge-live {
    background: var(--live);
    color: #fff;
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 12px;
    animation: pulse 1.6s infinite;
}
@keyframes pulse { 50% { opacity: .65; } }

.cart-modal {
    display: none !important;
    position: fixed;
    inset: 0;
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 16px;
    visibility: hidden;
    pointer-events: none;
}
.cart-modal.is-open {
    display: flex !important;
    visibility: visible;
    pointer-events: auto;
}
.cart-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(8, 14, 28, .55);
    backdrop-filter: blur(3px);
}
.cart-panel {
    position: relative;
    z-index: 1;
    width: min(100%, 420px);
    max-height: min(90dvh, 720px);
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 20px;
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.drawer-head, .drawer-foot { padding: 16px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; }
.drawer-foot { border-bottom: 0; border-top: 1px solid var(--line); }
.drawer-body { flex: 1; overflow: auto; padding: 16px 18px 24px; }
.close-x { background: none; border: none; font-size: 28px; color: var(--muted); line-height: 1; }

.cart-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 8px;
    padding: 14px 0;
    border-bottom: 1px solid var(--line);
}
.qty {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--line);
    border-radius: 999px;
    overflow: hidden;
}
.qty button { width: 32px; height: 32px; border: 0; background: var(--bg); color: var(--text); font-size: 16px; }
.qty b { min-width: 28px; text-align: center; font-size: 14px; }
.remove { background: none; border: none; color: var(--danger); font-weight: 800; }

.totals { background: var(--bg); border-radius: 14px; padding: 12px; margin: 12px 0; }
.totals div { display: flex; justify-content: space-between; margin: 6px 0; font-weight: 700; }
.coupon { display: flex; gap: 8px; margin: 12px 0; }
.coupon input, .field input {
    width: 100%;
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 11px 12px;
    background: var(--bg);
    color: var(--text);
}
.coupon button {
    min-width: 88px;
    border: none;
    border-radius: 12px;
    background: var(--brand-2);
    color: #fff;
    font-weight: 800;
}
.field { margin-bottom: 10px; }
.field label { display: block; font-size: 13px; font-weight: 800; margin-bottom: 5px; }
.confirm {
    width: 100%;
    background: var(--success);
    color: #fff;
    border: none;
    border-radius: 14px;
    padding: 13px;
    font-weight: 800;
    font-size: 16px;
}

#invoice-section {
    display: none;
    max-width: 760px;
    margin: 24px auto;
    background: #fff;
    color: #000;
    padding: 24px;
    border-radius: 16px;
    border: 2px solid #000;
}
#invoice-section * { color: #000 !important; }
.invoice-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
.invoice-table th, .invoice-table td { border: 1.5px solid #000; padding: 10px; text-align: right; }
.print-row { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
.print-row button { border: none; border-radius: 8px; padding: 10px 16px; font-weight: 800; }
.print-row .print { background: #000; color: #fff !important; }
.print-row .close { background: #555; color: #fff !important; }

.toast {
    position: fixed;
    bottom: calc(18px + var(--bottom-h));
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: #122033;
    color: #fff;
    padding: 10px 16px;
    border-radius: 999px;
    z-index: 120;
    opacity: 0;
    pointer-events: none;
    transition: .25s;
    font-weight: 700;
    white-space: nowrap;
}
.toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

.bottom-nav {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--surface);
    border-top: 1px solid var(--line);
    z-index: 40;
    padding: 6px 4px calc(6px + env(safe-area-inset-bottom));
}
.bottom-nav button {
    background: none;
    border: none;
    color: var(--muted);
    font-size: 11px;
    font-weight: 800;
    display: grid;
    gap: 2px;
    place-items: center;
}
.bottom-nav button.active { color: var(--brand); }
.bottom-nav .ico { font-size: 18px; }

footer { text-align: center; color: var(--muted); padding: 18px 16px 28px; font-size: 13px; }

@media (min-width: 768px) {
    :root { --bottom-h: 0px; }
    .bottom-nav { display: none; }
    .desktop-nav, .search-wrap { display: flex; }
    .menu-btn { display: none; }
    .hero { grid-template-columns: 1.1fr .9fr; align-items: stretch; }
    .hero-visual { min-height: 100%; }
    .cat-grid { grid-template-columns: repeat(3, 1fr); }
    .products-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .streams-grid { grid-template-columns: 1fr 1fr; }
    .streams-grid .featured { grid-column: 1 / -1; }
    .product-card img, .cat-card img { height: 200px; }
}
@media (max-width: 767px) {
    :root { --bottom-h: 68px; }
    .cart-chip span.label { display: none; }
    .mobile-search { display: block; }
}
@media print {
    .site-header, footer, .bottom-nav, .cart-modal, .toast, .page, .print-row { display: none !important; }
    #invoice-section { display: block !important; border: none; margin: 0; max-width: 100%; }
}

/* ================= ADMIN PANEL ================= */
.admin-page { max-width:1100px; margin:24px auto; padding:0 16px 40px; }
.admin-header { background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#fff; padding:20px 24px; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.1); margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
.admin-header h1 { font-size:1.35rem; }
.admin-link { color:#fff; text-decoration:none; background:rgba(255,255,255,.15); padding:8px 16px; border-radius:8px; font-weight:700; }
.admin-alert { background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:10px; margin-bottom:20px; }
.admin-card { background:var(--surface); border:1px solid var(--line); border-radius:16px; padding:24px; box-shadow:var(--shadow); margin-bottom:24px; }
.admin-card h2 { font-size:1.15rem; margin-bottom:18px; }
.admin-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; }
.admin-field { display:flex; flex-direction:column; gap:6px; }
.admin-field.full { grid-column:1/-1; }
.admin-field label { font-size:.85rem; font-weight:700; color:var(--muted); }
.admin-field input,.admin-field textarea,.admin-field select { padding:10px 12px; border:1px solid var(--line); border-radius:8px; font-family:inherit; font-size:.95rem; background:var(--bg); color:var(--text); }
.admin-btn { border:0; padding:10px 16px; border-radius:8px; font-family:inherit; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:7px; }
.admin-primary { background:#2563eb; color:#fff; } .admin-success { background:#16a34a; color:#fff; } .admin-danger { background:#dc2626; color:#fff; } .admin-warning { background:#d97706; color:#fff; }
.admin-table-wrap { overflow-x:auto; } .admin-table { width:100%; border-collapse:collapse; min-width:800px; }
.admin-table th,.admin-table td { padding:12px 14px; text-align:right; border-bottom:1px solid var(--line); font-size:.9rem; }
.admin-table th { background:var(--bg); color:var(--muted); white-space:nowrap; }
.admin-table img { width:50px; height:50px; object-fit:cover; border-radius:8px; }
.admin-actions { display:flex; gap:6px; flex-wrap:wrap; }
.admin-badge { padding:4px 12px; border-radius:20px; font-size:.8rem; font-weight:700; white-space:nowrap; }
.admin-in { background:#dcfce7; color:#166534; } .admin-out { background:#fee2e2; color:#991b1b; }
@media(max-width:600px){.admin-grid{grid-template-columns:1fr}.admin-card{padding:16px}.admin-header h1{font-size:1.05rem}}

</style>
</head><body>
<?php if ($adminMode): ?>
<div class="admin-page">
  <div class="admin-header">
    <h1>🛠️ لوحة تحكم GLAKTARBO</h1>
    <a class="admin-link" href="index.php">🛒 عرض المتجر</a>
  </div>
  <?php if ($alert): ?><div class="admin-alert">✅ <?= htmlspecialchars($alert) ?></div><?php endif; ?>

  <div class="admin-card">
    <h2>➕ إضافة منتج جديد</h2>
    <form method="POST" enctype="multipart/form-data" autocomplete="off">
      <input type="hidden" name="action" value="add">
      <div class="admin-grid">
        <div class="admin-field"><label>اسم المنتج *</label><input type="text" name="name" required placeholder="مثال: تيشيرت قطن"></div>
        <div class="admin-field"><label>السعر *</label><input type="number" name="price" step="0.01" min="0" required></div>
        <div class="admin-field"><label>المخزون *</label><input type="number" name="stock" min="0" required></div>
        <div class="admin-field"><label>القسم *</label><select name="category" required><option value="electronics">الأجهزة الإلكترونية</option><option value="clothing">الملابس والحقائب</option></select></div>
        <div class="admin-field"><label>صورة المنتج</label><input type="file" name="image" accept="image/*"></div>
        <div class="admin-field full"><label>الوصف</label><textarea name="description" rows="3" placeholder="وصف المنتج..."></textarea></div>
      </div>
      <button type="submit" class="admin-btn admin-primary" style="margin-top:16px">💾 حفظ المنتج</button>
    </form>
  </div>

  <div class="admin-card">
    <h2>📦 المنتجات (<?= $adminProducts ? $adminProducts->num_rows : 0 ?>)</h2>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>الصورة</th><th>الاسم</th><th>السعر</th><th>المخزون</th><th>القسم</th><th>الحالة</th><th>الوصف</th><th>إجراءات</th></tr></thead><tbody>
    <?php if (!$adminProducts || $adminProducts->num_rows === 0): ?>
      <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--muted)">لا توجد منتجات بعد. أضف أول منتج.</td></tr>
    <?php else: while ($p = $adminProducts->fetch_assoc()): $inStock=(int)$p['stock']>0; $editing=isset($_GET['edit']) && (int)$_GET['edit']===(int)$p['id']; ?>
      <?php if ($editing): ?>
      <tr><form method="POST" action="index.php?admin=1"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <td><?php if(!empty($p['image'])):?><img src="<?= htmlspecialchars($p['image']) ?>" alt=""><?php endif;?></td>
        <td><input name="name" value="<?= htmlspecialchars($p['name']) ?>" required></td><td><input type="number" step="0.01" name="price" value="<?= htmlspecialchars($p['price']) ?>" required></td><td><input type="number" name="stock" value="<?= (int)$p['stock'] ?>" required></td>
        <td><select name="category"><option value="electronics" <?= $p['category']==='electronics'?'selected':'' ?>>إلكترونيات</option><option value="clothing" <?= $p['category']==='clothing'?'selected':'' ?>>ملابس</option></select></td>
        <td><?= $inStock?'<span class="admin-badge admin-in">متوفر</span>':'<span class="admin-badge admin-out">غير متوفر</span>' ?></td><td><textarea name="description" rows="2"><?= htmlspecialchars(isset($p['description']) ? $p['description'] : '') ?></textarea></td>
        <td><div class="admin-actions"><button class="admin-btn admin-success" type="submit">✓</button><a class="admin-btn" href="index.php?admin=1">✕</a></div></td>
      </form></tr>
      <?php else: ?>
      <tr><td><?php if(!empty($p['image']) && file_exists(__DIR__.'/'.$p['image'])):?><img src="<?= htmlspecialchars($p['image']) ?>" alt=""><?php else: ?>—<?php endif;?></td><td><strong><?= htmlspecialchars($p['name']) ?></strong></td><td><?= number_format($p['price'],2) ?></td><td><?= (int)$p['stock'] ?></td><td><?= htmlspecialchars($p['category']) ?></td><td><?= $inStock?'<span class="admin-badge admin-in">متوفر</span>':'<span class="admin-badge admin-out">غير متوفر</span>' ?></td><td><?= nl2br(htmlspecialchars(isset($p['description']) ? $p['description'] : '')) ?></td><td><div class="admin-actions"><a class="admin-btn admin-warning" href="index.php?admin=1&edit=<?= (int)$p['id'] ?>">✎</a><a class="admin-btn admin-danger" href="index.php?admin=1&delete=<?= (int)$p['id'] ?>" onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">🗑</a></div></td></tr>
      <?php endif; ?>
    <?php endwhile; endif; ?>
    </tbody></table></div>
  </div>
</div>
<?php else: ?>

    <header class="site-header">
        <button class="icon-btn menu-btn" id="menu-btn" aria-label="القائمة">☰</button>
        <button class="brand" onclick="showPage('home')">
            <img src="icons/1921-v2.png" alt="GLAKTARBO">
            <span>
                <span class="brand-name">GLAKTARBO</span>
                <span class="brand-tag">تسوق إلكتروني متكامل</span>
            </span>
        </button>
        <nav class="desktop-nav">
            <button class="nav-link active" data-page="home" onclick="showPage('home')">الرئيسية</button>
            <button class="nav-link" data-page="electronics" onclick="showPage('electronics')">الأجهزة الإلكترونية</button>
            <button class="nav-link live" data-page="live" onclick="showPage('live')">البث المباشر</button>
            <button class="nav-link" data-page="clothing" onclick="showPage('clothing')">الملابس والحقائب</button>
        </nav>
        <div class="search-wrap">
            <input type="search" id="search-input" placeholder="ابحث عن منتج..." oninput="syncSearch(this.value)">
        </div>
        <div class="mobile-menu" id="mobile-menu">
            <button type="button" onclick="showPage('home')">الرئيسية</button>
            <button type="button" onclick="showPage('electronics')">الأجهزة الإلكترونية</button>
            <button type="button" onclick="showPage('live')">البث المباشر</button>
            <button type="button" onclick="showPage('clothing')">الملابس والحقائب</button>
        </div>
        <div class="header-actions">
            <button class="icon-btn" id="theme-btn" onclick="toggleTheme()" aria-label="الثيم">🌙</button>
            <button class="cart-chip" onclick="openCart()">
                🛒 <span class="label">السلة</span>
                <span id="cart-counter">0</span>
            </button>
        </div>
    </header>
    <div class="mobile-search">
        <input type="search" id="search-input-mobile" placeholder="ابحث عن منتج..." oninput="syncSearch(this.value)">
    </div>

    <section class="page active" id="page-home">
        <div class="hero">
            <div class="hero-copy">
                <span class="kicker">متجر GLAKTARBO الرسمي</span>
                <h1>أجهزة، أزياء، وعروض مباشرة في مكان واحد</h1>
                <p>تصفّح الأقسام الحقيقية للمتجر: الإلكترونيات، الملابس والحقائب، وغرفة البث لمشاهدة العروض المسجّلة واللايف.</p>
                <div class="hero-actions">
                    <button class="btn btn-light" onclick="showPage('electronics')">تسوق الإلكترونيات</button>
                    <button class="btn btn-ghost" onclick="showPage('clothing')">قسم الملابس</button>
                </div>
            </div>
            <div class="hero-visual" role="img" aria-label="أزياء GLAKTARBO"></div>
        </div>

        <div class="section-title">
            <div>
                <h2>أقسام المتجر</h2>
                <p>اختر القسم ثم أضف المنتجات إلى السلة</p>
            </div>
        </div>
        <div class="cat-grid">
            <button class="cat-card" onclick="showPage('electronics')">
                <img src="https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=800" alt="الأجهزة الإلكترونية">
                <div class="body">
                    <h3>الأجهزة الإلكترونية</h3>
                    <p>لابتوب HP Envy وساعة ذكية مقاومة للماء ضمن تشكيلة الإلكترونيات الحالية.</p>
                </div>
            </button>
            <button class="cat-card live-card" onclick="showPage('live')">
                <img src="https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800" alt="البث المباشر">
                <div class="body">
                    <h3>غرفة البث المباشر</h3>
                    <p>ثلاث قنوات عرض: البث الأساسي وبثان رديفان لمراجعات المنتجات والعروض.</p>
                </div>
            </button>
            <button class="cat-card" onclick="showPage('clothing')">
                <img src="5996670327393553729_121.jpg" alt="الملابس والحقائب">
                <div class="body">
                    <h3>الملابس والحقائب</h3>
                    <p>حقيبة ظهر ذكية مقاومة للسرقة والماء، وجاكيت شتوي عصري فاخر.</p>
                </div>
            </button>
        </div>
    </section>

    <section class="page" id="page-electronics">
        <div class="page-head">
            <div>
                <h2>الأجهزة الإلكترونية</h2>
                <p class="muted" style="color:var(--muted)">منتجات القسم كما هي معروضة في المتجر</p>
            </div>
            <button class="back-btn" onclick="showPage('home')">العودة للرئيسية</button>
        </div>
        <div class="products-grid" id="grid-electronics"></div>
    </section>

    <section class="page" id="page-clothing">
        <div class="page-head">
            <div>
                <h2>الملابس والحقائب</h2>
                <p style="color:var(--muted)">التشكيلة العصرية المتوفرة حالياً</p>
            </div>
            <button class="back-btn" onclick="showPage('home')">العودة للرئيسية</button>
        </div>
        <div class="products-grid" id="grid-clothing"></div>
    </section>

    <section class="page" id="page-live">
        <div class="page-head">
            <div>
                <h2>منصة البث المباشر</h2>
                <p style="color:var(--muted)">عروض ومراجعات مباشرة ومسجّلة من قنوات GLAKTARBO</p>
            </div>
            <button class="back-btn" onclick="showPage('home')">العودة للرئيسية</button>
        </div>
        <div class="streams-grid">
            <article class="stream-card featured">
                <video id="live-player" controls autoplay muted loop playsinline>
                    <source src="live.mp4" type="video/mp4">
                </video>
                <div class="stream-meta">
                    <span>العرض الأساسي للبث</span>
                    <span class="badge-live">مباشر</span>
                </div>
            </article>
            <article class="stream-card">
                <video id="live-player-secondary" controls muted loop playsinline>
                    <source src="0711 (1).mp4" type="video/mp4">
                </video>
                <div class="stream-meta"><span>البث الرديف 1</span></div>
            </article>
            <article class="stream-card">
                <video id="live-player-third" controls muted loop playsinline>
                    <source src="0711 (1)(1).mp4" type="video/mp4">
                </video>
                <div class="stream-meta"><span>البث الرديف 2</span></div>
            </article>
        </div>
    </section>
  <section id="invoice-section">
        <div style="text-align:center;border-bottom:2px solid #000;padding-bottom:12px;margin-bottom:16px;">
            <h2>فاتورة شراء رسمية - GLAKTARBO</h2>
            <p>تاريخ الفاتورة: <span id="invoice-date"></span></p>
            <p id="invoice-order-id" style="display:none;">رقم الطلب: <span></span></p>
        </div>
        <p><strong>اسم العميل:</strong> <span id="inv-name"></span></p>
        <p><strong>رقم التواصل:</strong> <span id="inv-phone"></span></p>
        <p><strong>عنوان التوصيل:</strong> <span id="inv-location"></span></p>
        <p id="inv-discount-row" style="display:none;"><strong>الخصم المطبق:</strong> <span id="inv-discount-val"></span></p>
        <div class="table-responsive">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>المنتج / الموديل</th>
                        <th>السعر الفردي</th>
                        <th>الكمية</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody id="invoice-table-body"></tbody>
            </table>
        </div>
        <h3 style="text-align:left;">الإجمالي الكلي: $ <span id="invoice-total">0</span></h3>
        <div class="print-row">
            <button class="print" onclick="window.print()">طباعة الفاتورة أو حفظها PDF</button>
            <button class="close" onclick="closeInvoice()">إغلاق</button>
        </div>
    </section>

    <div id="cart-modal" class="cart-modal" hidden aria-hidden="true">
        <div class="cart-modal-backdrop" onclick="closeCart()"></div>
        <aside id="cart-sidebar" class="cart-panel" role="dialog" aria-modal="true" aria-labelledby="cart-title" onclick="event.stopPropagation()">
            <div class="drawer-head">
                <h2 id="cart-title">سلة المشتريات</h2>
                <button type="button" class="close-x" onclick="closeCart()" aria-label="إغلاق">&times;</button>
            </div>
            <div class="drawer-body">
                <div id="cart-items-list"></div>
                <div class="coupon">
                    <input type="text" id="coupon-code" placeholder="كوبون مثل GLAK50">
                    <button type="button" onclick="applyDiscount()">تطبيق</button>
                </div>
                <p id="coupon-message" style="font-size:13px;font-weight:800;text-align:center;"></p>
                <div class="totals" id="cart-totals"></div>
                <div class="field">
                    <label for="client-name">اسم العميل بالكامل</label>
                    <input type="text" id="client-name" placeholder="الاسم الثلاثي" autocomplete="name">
                </div>
                <div class="field">
                    <label for="client-phone">رقم هاتف العميل</label>
                    <input type="tel" id="client-phone" placeholder="مثال: 0912345678" autocomplete="tel">
                </div>
                <div class="field">
                    <label for="client-location">عنوان التوصيل</label>
                    <input type="text" id="client-location" placeholder="المدينة، الحي، الشارع">
                </div>
                <button class="confirm" onclick="generateInvoice()">تأكيد الطلب وإصدار الفاتورة</button>
            </div>
        </aside>
    </div>

    <nav class="bottom-nav">
        <button class="active" data-page="home" onclick="showPage('home')"><span class="ico">⌂</span>الرئيسية</button>
        <button data-page="electronics" onclick="showPage('electronics')"><span class="ico">💻</span>إلكترونيات</button>
        <button data-page="live" onclick="showPage('live')"><span class="ico">●</span>لايف</button>
        <button data-page="clothing" onclick="showPage('clothing')"><span class="ico">👜</span>ملابس</button>
        <button onclick="openCart()"><span class="ico">🛒</span>السلة</button>
    </nav>

    <div class="toast" id="toast"></div>
    <footer>&copy; 2026 جميع الحقوق محفوظة — متجر وماركة GLAKTARBO</footer>

<script>
const PRODUCTS = <?= json_encode($storeProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

let cart = [];
let discountType = null;
let discountValue = 0;
let searchQuery = '';

try {
    cart = JSON.parse(localStorage.getItem('glak_cart') || '[]') || [];
} catch (e) {
    cart = [];
}

function saveCart() {
    localStorage.setItem('glak_cart', JSON.stringify(cart));
}

function toast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => el.classList.remove('show'), 2200);
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('glak_theme', next);
    document.getElementById('theme-btn').textContent = next === 'dark' ? '☀️' : '🌙';
}

(function initTheme() {
    const saved = localStorage.getItem('glak_theme');
    if (saved === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.getElementById('theme-btn').textContent = '☀️';
    }
})();

function productCard(p) {
    return `
        <article class="product-card">
            <img src="${p.image}" alt="${p.name}" loading="lazy">
            <div class="body">
                <h3>${p.name}</h3>
                <p>${p.desc}</p>
                <div class="price">$ ${p.price}</div>
                <button class="add-btn" onclick="addToCart('${p.id}')">إضافة إلى السلة</button>
            </div>
        </article>`;
}

function renderProducts() {
    const q = searchQuery.trim();
    ['electronics', 'clothing'].forEach((cat) => {
        const list = PRODUCTS.filter((p) => p.category === cat && (!q || p.name.includes(q) || p.desc.includes(q)));
        const grid = document.getElementById('grid-' + cat);
        grid.innerHTML = list.length ? list.map(productCard).join('') : '<div class="empty-box" style="grid-column:1/-1;padding:28px;text-align:center;">لا توجد منتجات مطابقة لبحثك</div>';
    });
}

function syncSearch(value) {
    searchQuery = value;
    const desktop = document.getElementById('search-input');
    const mobile = document.getElementById('search-input-mobile');
    if (desktop && desktop.value !== value) desktop.value = value;
    if (mobile && mobile.value !== value) mobile.value = value;
    if (searchQuery.trim() && !['electronics', 'clothing'].some((id) => document.getElementById('page-' + id).classList.contains('active'))) {
        showPage('electronics');
    }
    renderProducts();
}

function showPage(pageId) {
    document.getElementById('mobile-menu').classList.remove('open');
    document.querySelectorAll('.page').forEach((s) => s.classList.remove('active'));
    document.getElementById('page-' + pageId).classList.add('active');
    document.querySelectorAll('.nav-link, .bottom-nav button[data-page]').forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.page === pageId);
    });
    document.getElementById('invoice-section').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function addToCart(id) {
    const product = PRODUCTS.find((p) => p.id === id);
    if (!product) return;
    const existing = cart.find((item) => item.id === id);
    if (existing) existing.quantity += 1;
    else cart.push({ id: product.id, name: product.name, price: product.price, quantity: 1 });
    saveCart();
    updateCartCounter();
    toast('تمت إضافة ' + product.name + ' إلى السلة');
    openCart();
}

function updateCartCounter() {
    const total = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cart-counter').textContent = total;
}

function openCart() {
    const modal = document.getElementById('cart-modal');
    modal.hidden = false;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    renderCartItems();
}

function closeCart() {
    const modal = document.getElementById('cart-modal');
    modal.classList.remove('is-open');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCart();
});

function cartMath() {
    const subtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    let discount = 0;
    if (discountType === 'fixed') discount = Math.min(discountValue, subtotal);
    if (discountType === 'percent') discount = subtotal * (discountValue / 100);
    return { subtotal, discount, total: Math.max(0, subtotal - discount) };
}

function renderCartItems() {
    const list = document.getElementById('cart-items-list');
    if (!cart.length) {
        list.innerHTML = '<p style="text-align:center;color:var(--muted);padding:24px;">السلة فارغة حالياً</p>';
        document.getElementById('cart-totals').innerHTML = '';
        return;
    }
    list.innerHTML = cart.map((item, index) => `
        <div class="cart-item">
            <div>
                <strong>${item.name}</strong><br>
                <span style="color:var(--muted);font-size:13px;">$ ${item.price} للقطعة</span>
            </div>
            <div style="text-align:end;">
                <div class="qty">
                    <button type="button" onclick="changeQty(${index}, -1)">−</button>
                    <b>${item.quantity}</b>
                    <button type="button" onclick="changeQty(${index}, 1)">+</button>
                </div>
                <div style="margin-top:8px;font-weight:800;">$ ${item.price * item.quantity}</div>
                <button class="remove" onclick="removeFromCart(${index})">حذف</button>
            </div>
        </div>
    `).join('');
    const m = cartMath();
    document.getElementById('cart-totals').innerHTML = `
        <div><span>المجموع الفرعي</span><span>$ ${m.subtotal.toFixed(2)}</span></div>
        <div><span>الخصم</span><span>$ ${m.discount.toFixed(2)}</span></div>
        <div><span>الإجمالي</span><span>$ ${m.total.toFixed(2)}</span></div>
    `;
}

function changeQty(index, delta) {
    cart[index].quantity += delta;
    if (cart[index].quantity < 1) cart.splice(index, 1);
    if (!cart.length) {
        discountType = null;
        discountValue = 0;
        document.getElementById('coupon-message').textContent = '';
        document.getElementById('coupon-code').value = '';
    }
    saveCart();
    updateCartCounter();
    renderCartItems();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    if (!cart.length) {
        discountType = null;
        discountValue = 0;
        document.getElementById('coupon-message').textContent = '';
        document.getElementById('coupon-code').value = '';
    }
     saveCart();
    updateCartCounter();
    renderCartItems();
}

function applyDiscount() {
    const code = document.getElementById('coupon-code').value.trim().toUpperCase();
    const msg = document.getElementById('coupon-message');
    if (!cart.length) {
        msg.style.color = 'var(--danger)';
        msg.textContent = 'أضف منتجات إلى السلة أولاً';
        return;
    }
    if (code === 'GLAK50') {
        discountType = 'fixed';
        discountValue = 50;
        msg.style.color = 'var(--success)';
        msg.textContent = 'تم تطبيق خصم 50$ مباشر';
    } else if (code === 'TARBO10') {
        discountType = 'percent';
        discountValue = 10;
        msg.style.color = 'var(--success)';
        msg.textContent = 'تم تطبيق خصم 10% على الإجمالي';
    } else {
        discountType = null;
        discountValue = 0;
        msg.style.color = 'var(--danger)';
        msg.textContent = code ? 'الكود غير صحيح أو منتهي' : 'أدخل كود كوبون صحيح';
    }
    renderCartItems();
}

async function generateInvoice() {
    const name = document.getElementById('client-name').value.trim();
    const phone = document.getElementById('client-phone').value.trim();
    const location = document.getElementById('client-location').value.trim();
    if (!cart.length) { toast('السلة فارغة'); return; }
    if (!name || !phone || !location) { toast('أكمل بيانات الشحن بالكامل'); return; }

    const m = cartMath();
    const coupon = document.getElementById('coupon-code').value.trim().toUpperCase();

    document.getElementById('inv-name').textContent = name;
    document.getElementById('inv-phone').textContent = phone;
    document.getElementById('inv-location').textContent = location;
    const today = new Date();
    document.getElementById('invoice-date').textContent = today.toLocaleDateString('ar-EG') + ' ' + today.toLocaleTimeString('ar-EG');

    const discountRow = document.getElementById('inv-discount-row');
    if (discountType === 'fixed') {
        discountRow.style.display = 'block';
        document.getElementById('inv-discount-val').textContent = '$' + discountValue + ' خصم مباشر';
    } else if (discountType === 'percent') {
        discountRow.style.display = 'block';
        document.getElementById('inv-discount-val').textContent = discountValue + '% خصم النسبة';
    } else {
        discountRow.style.display = 'none';
    }

    document.getElementById('invoice-table-body').innerHTML = cart.map((item) => `
        <tr>
            <td>${item.name}</td>
            <td>$ ${item.price}</td>
            <td>${item.quantity}</td>
            <td>$ ${item.price * item.quantity}</td>
        </tr>
    `).join('');
    document.getElementById('invoice-total').textContent = m.total.toFixed(2);

    try {
        const res = await fetch('send_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name,
                phone,
                location,
                discount_code: coupon || null,
                discount_value: m.discount,
                total_price: m.total,
                cart_items: cart
            })
        });
        const data = await res.json();
        const orderEl = document.getElementById('invoice-order-id');
        if (data && data.order_id) {
            orderEl.style.display = 'block';
            orderEl.querySelector('span').textContent = data.order_id;
        } else {
            orderEl.style.display = 'none';
        }
    } catch (err) {
        document.getElementById('invoice-order-id').style.display = 'none';
    }

    closeCart();
    document.querySelectorAll('.page').forEach((s) => s.classList.remove('active'));
    document.getElementById('invoice-section').style.display = 'block';
    window.scrollTo({ top: 0 });
}

function closeInvoice() {
    document.getElementById('invoice-section').style.display = 'none';
    cart = [];
    discountType = null;
    discountValue = 0;
    saveCart();
    updateCartCounter();
    ['client-name', 'client-phone', 'client-location', 'coupon-code'].forEach((id) => {
        document.getElementById(id).value = '';
    });
    document.getElementById('coupon-message').textContent = '';
    showPage('home');
}

document.getElementById('menu-btn').addEventListener('click', (e) => {
    e.stopPropagation();
    document.getElementById('mobile-menu').classList.toggle('open');
});
document.addEventListener('click', (e) => {
    const menu = document.getElementById('mobile-menu');
    if (!menu.contains(e.target) && e.target.id !== 'menu-btn') {
        menu.classList.remove('open');
    }
});

renderProducts();
updateCartCounter();
closeCart();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js').catch(() => {});
    });
}
</script>

<?php endif; ?>
</body>
</html>