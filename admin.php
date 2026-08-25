<?php
/**
 * admin.php - لوحة التحكم
 * ---------------------------------------------------------------------
 * الميزات:
 *   1) إضافة منتج جديد (الاسم، السعر، المخزون، الوصف، رفع الصورة إلى uploads/)
 *   2) عرض جدول بالمنتجات مع إشارة حالة المخزون (متوفر / غير متوفر)
 *   3) تعديل المنتج (الاسم، السعر، المخزون، الوصف) وتحديث الكمية
 *   4) حذف المنتج
 */

// تضمين ملف الاتصال بقاعدة البيانات
require 'db.php';

// =====================================================================
// 1) معالجة طلبات الإضافة / التعديل / الحذف (POST / GET)
// =====================================================================

// --- (أ) إضافة منتج جديد ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {

    $name        = trim($_POST['name'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? 'electronics');
    $imagePath   = null;

    // التحقق من رفع صورة بدون أخطاء
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // مجلد الاستقبال (نتأكد من وجوده)
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // تنظيف اسم الملف وإنشاء اسم فريد لتفادي التعارض
        $originalName = basename($_FILES['image']['name']);
        $safeName      = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $originalName);
        $uniqueName    = time() . '_' . $safeName;
        $targetPath    = $uploadDir . $uniqueName;

        // نقل الملف المؤقت إلى مجلد uploads
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/' . $uniqueName;
        }
    }

    // إدخال المنتج في قاعدة البيانات
    $stmt = $conn->prepare(
        "INSERT INTO products (name, price, stock, description, image, category) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sdisss', $name, $price, $stock, $description, $imagePath, $category);
    $stmt->execute();
    $stmt->close();

    header('Location: admin.php?msg=added');
    exit;
}

// --- (ب) تعديل منتج موجود (الاسم، السعر، المخزون، الوصف) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {

    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? 'electronics');

    $stmt = $conn->prepare(
        "UPDATE products SET name = ?, price = ?, stock = ?, description = ?, category = ? WHERE id = ?"
    );
    $stmt->bind_param('sdissi', $name, $price, $stock, $description, $category, $id);
    $stmt->execute();
    $stmt->close();

    header('Location: admin.php?msg=updated');
    exit;
}

// --- (ج) حذف منتج ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // (اختياري) جلب مسار الصورة لحذفها من السيرفر
    $res = $conn->query("SELECT image FROM products WHERE id = $id");
    if ($res && $row = $res->fetch_assoc()) {
        if (!empty($row['image']) && file_exists(__DIR__ . '/' . $row['image'])) {
            unlink(__DIR__ . '/' . $row['image']);
        }
    }

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    header('Location: admin.php?msg=deleted');
    exit;
}

// =====================================================================
// 2) جلب كل المنتجات لعرضها في الجدول
// =====================================================================
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");

// رسالة الحالة (نجاح إضافة/تعديل/حذف)
$messages = [
    'added'   => 'تمت إضافة المنتج بنجاح.',
    'updated' => 'تم تحديث المنتج بنجاح.',
    'deleted' => 'تم حذف المنتج بنجاح.',
];
$alert = $messages[$_GET['msg'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - GLAKTARBO</title>

    <!-- خط عربي عصري -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- أيقونات -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #d97706;
            --bg: #f1f5f9;
            --card: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding-bottom: 40px;
        }

        /* الشريط العلوي */
        header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            padding: 20px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        header .wrap { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        header h1 { font-size: 1.4rem; font-weight: 700; }
        header a { color: #fff; text-decoration: none; background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; transition: background 0.2s; }
        header a:hover { background: rgba(255,255,255,0.25); }

        .container { max-width: 1100px; margin: 24px auto; padding: 0 16px; }

        /* رسالة التنبيه */
        .alert {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        /* بطاقة النموذج */
        .card {
            background: var(--card);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        .card h2 { font-size: 1.15rem; margin-bottom: 18px; color: var(--primary-dark); }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label { font-size: 0.85rem; font-weight: 600; color: var(--muted); }
        .field input, .field textarea, .field select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border 0.2s, box-shadow 0.2s;
            background: #fff;
        }
        .field input:focus, .field textarea:focus, .field select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }
        .field.full { grid-column: 1 / -1; }

        .btn {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 11px 22px;
            border-radius: 8px;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover { background: var(--primary-dark); }
        .btn:active { transform: scale(0.98); }
        .btn-sm { padding: 7px 12px; font-size: 0.85rem; border-radius: 6px; }
        .btn-success { background: var(--success); }
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning { background: var(--warning); }
        .btn-warning:hover { background: #b45309; }

        /* الجدول */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 12px 14px; text-align: right; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        th { background: #f8fafc; color: var(--muted); font-weight: 600; white-space: nowrap; }
        tr:hover { background: #f8fafc; }
        td img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); }
        .no-img { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px; color: var(--muted); }

        /* شارة الحالة */
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; }
        .badge-in { background: #dcfce7; color: #166534; }
        .badge-out { background: #fee2e2; color: #991b1b; }

        .actions { display: flex; gap: 6px; flex-wrap: wrap; }

        /* نموذج التعديل المضمن */
        .edit-row td { background: #eff6ff; }
        .edit-row input, .edit-row textarea {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.85rem;
        }

        @media (max-width: 600px) {
            header h1 { font-size: 1.1rem; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header>
    <div class="wrap">
        <h1><i class="bi bi-speedometer2"></i> لوحة تحكم GLAKTARBO</h1>
        <a href="index.php"><i class="bi bi-shop"></i> عرض المتجر</a>
    </div>
</header>

<div class="container">

    <!-- رسالة الحالة -->
    <?php if ($alert): ?>
        <div class="alert"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($alert) ?></div>
    <?php endif; ?>

    <!-- ============= نموذج إضافة منتج ============= -->
    <div class="card">
        <h2><i class="bi bi-plus-circle"></i> إضافة منتج جديد</h2>
        <form method="POST" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="action" value="add">
            <div class="grid">
                <div class="field">
                    <label>اسم المنتج *</label>
                    <input type="text" name="name" required placeholder="مثال: تيشيرت قطن">
                </div>
                <div class="field">
                    <label>السعر (ج.م) *</label>
                    <input type="number" name="price" step="0.01" min="0" required placeholder="0.00">
                </div>
                <div class="field">
                    <label>المخزون (الكمية) *</label>
                    <input type="number" name="stock" min="0" required placeholder="0">
                </div>
                <div class="field">
                    <label>القسم *</label>
                    <select name="category" required>
                        <option value="electronics">الأجهزة الإلكترونية</option>
                        <option value="clothing">الملابس والحقائب</option>
                    </select>
                </div>
                <div class="field">
                    <label>صورة المنتج</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="field full">
                    <label>الوصف</label>
                    <textarea name="description" rows="3" placeholder="وصف المنتج..."></textarea>
                </div>
            </div>
            <div style="margin-top:16px;">
                <button type="submit" class="btn"><i class="bi bi-save"></i> حفظ المنتج</button>
            </div>
        </form>
    </div>

    <!-- ============= جدول المنتجات ============= -->
    <div class="card">
        <h2><i class="bi bi-box-seam"></i> المنتجات (<?= $products->num_rows ?>)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الصورة</th>
                        <th>الاسم</th>
                        <th>السعر</th>
                        <th>المخزون</th>
                        <th>القسم</th>
                        <th>الحالة</th>
                        <th>الوصف</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows === 0): ?>
                        <tr><td colspan="8" style="text-align:center;color:var(--muted);">لا توجد منتجات بعد. أضف أول منتج.</td></tr>
                    <?php else: ?>
                        <?php while ($p = $products->fetch_assoc()): ?>
                            <?php
                            // تحديد حالة المخزون
                            $inStock = (int)$p['stock'] > 0;
                            // هل نحن في وضع التعديل لهذا الصف؟
                            $editing = isset($_GET['edit']) && (int)$_GET['edit'] === (int)$p['id'];
                            ?>
                            <?php if ($editing): ?>
                                <!-- ----- صف التعديل المضمن ----- -->
                                <tr class="edit-row">
                                    <form method="POST" action="admin.php">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <td>
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="<?= htmlspecialchars($p['image']) ?>" alt="">
                                            <?php else: ?>
                                                <div class="no-img"><i class="bi bi-image"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required></td>
                                        <td><input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($p['price']) ?>" required></td>
                                        <td><input type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>" required></td>
                                        <td>
                                            <select name="category">
                                                <option value="electronics" <?= ($p['category'] ?? '') === 'electronics' ? 'selected' : '' ?>>إلكترونيات</option>
                                                <option value="clothing" <?= ($p['category'] ?? '') === 'clothing' ? 'selected' : '' ?>>ملابس</option>
                                            </select>
                                        </td>
                                        <td>
                                            <?php if ($inStock): ?>
                                                <span class="badge badge-in">متوفر</span>
                                            <?php else: ?>
                                                <span class="badge badge-out">غير متوفر</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><textarea name="description" rows="2"><?= htmlspecialchars($p['description'] ?? '') ?></textarea></td>
                                        <td>
                                            <div class="actions">
                                                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i></button>
                                                <a href="admin.php" class="btn btn-sm" style="background:var(--muted);"><i class="bi bi-x-lg"></i></a>
                                            </div>
                                        </td>
                                    </form>
                                </tr>
                            <?php else: ?>
                                <!-- ----- صف العرض العادي ----- -->
                                <tr>
                                    <td>
                                        <?php if (!empty($p['image']) && file_exists(__DIR__ . '/' . $p['image'])): ?>
                                            <img src="<?= htmlspecialchars($p['image']) ?>" alt="">
                                        <?php else: ?>
                                            <div class="no-img"><i class="bi bi-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                    <td><?= number_format($p['price'], 2) ?> ج.م</td>
                                    <td><?= (int)$p['stock'] ?></td>
                                    <td>
                                        <?php
                                        $catNames = ['electronics' => 'إلكترونيات', 'clothing' => 'ملابس'];
                                        $catLabel = $catNames[$p['category'] ?? ''] ?? $p['category'];
                                        ?>
                                        <span style="font-size:0.8rem;color:var(--muted);"><?= htmlspecialchars($catLabel) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($inStock): ?>
                                            <span class="badge badge-in">متوفر</span>
                                        <?php else: ?>
                                            <span class="badge badge-out">غير متوفر</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width:200px;color:var(--muted);"><?= nl2br(htmlspecialchars($p['description'] ?? '')) ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="admin.php?edit=<?= (int)$p['id'] ?>" class="btn btn-warning btn-sm" title="تعديل"><i class="bi bi-pencil"></i></a>
                                            <a href="admin.php?delete=<?= (int)$p['id'] ?>" class="btn btn-danger btn-sm" title="حذف"
                                               onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')"><i class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>
