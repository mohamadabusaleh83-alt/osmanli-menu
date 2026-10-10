<?php
/**
 * admin_menu.php - لوحة تحكم إدارة المنيو الكاملة (Admin CRUD)
 * يدعم تعدد الأحجام والأسعار لكل صنف (Multiple Sizes & Dynamic Pricing)
 * Osmanli Menu Project (1854) - Menu Management Dashboard
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';

$pdo = getDBConnection();

$message = '';
$error = '';

if (isset($_SESSION['flash_success'])) {
    $message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// دالة مساعدة لتحديد ترتيب القسم
function getCategorySortOrder($category): int {
    $map = [
        'الكنافة' => 1,
        'حلويات شرقية وتركية وشامية' => 2,
        'البوكسات والمشكل' => 3,
        'النثريات والإضافات (البوظة، القشطة، النواشف)' => 4
    ];
    return $map[$category] ?? 1;
}

// دالة مساعدة لرفع الصور والتحقق منها
function handleImageUpload($file, $existingPath = null) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingPath ?: 'images/knafeh_charcoal.jpg';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("خطأ أثناء رفع الصورة: رمز الخطأ " . $file['error']);
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception("حجم الصورة كبير جداً، الحد الأقصى المسموح به هو 10 ميغابايت.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (!array_key_exists($mime, $allowed_mimes)) {
        throw new Exception("صيغة الصورة غير مدعومة. الصيغ المسموحة: JPG, PNG, WEBP, GIF.");
    }

    $ext = $allowed_mimes[$mime];
    $upload_dir = __DIR__ . '/uploads';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = 'dish_' . uniqid() . '_' . time() . '.' . $ext;
    $destination = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("فشل نقل الملف المرفوع إلى مجلد uploads.");
    }

    if ($existingPath && strpos($existingPath, 'uploads/') === 0 && file_exists(__DIR__ . '/' . $existingPath)) {
        @unlink(__DIR__ . '/' . $existingPath);
    }

    return 'uploads/' . $filename;
}

// دالة مساعدة لمعالجة مصفوفة الأحجام والأسعار القادمة من النموذج
function extractVariantsFromPost(): array {
    $names = $_POST['size_names'] ?? [];
    $prices = $_POST['size_prices'] ?? [];
    $variants = [];

    if (is_array($names) && is_array($prices)) {
        for ($i = 0; $i < count($names); $i++) {
            $sname = trim($names[$i] ?? '');
            $sprice = trim($prices[$i] ?? '');
            if ($sname !== '' && is_numeric($sprice) && floatval($sprice) >= 0) {
                $variants[] = [
                    'size_name' => $sname,
                    'price'     => floatval($sprice)
                ];
            }
        }
    }

    // إذا لم يُدخل المستخدم أحجاماً، نعتمد الحجم والسعر الافتراضي
    if (empty($variants)) {
        $single_price = floatval($_POST['single_price'] ?? 0);
        $variants[] = [
            'size_name' => 'سعر موحد',
            'price'     => $single_price
        ];
    }

    return $variants;
}

// ========================================================
// معالجة طلبات POST (Create, Update, Delete, Toggle, Addons)
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. إضافة صنف جديد (Create with Multiple Sizes)
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $custom_category = trim($_POST['custom_category'] ?? '');
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        if (!empty($custom_category)) {
            $category = $custom_category;
        }

        $variants = extractVariantsFromPost();
        $base_price = min(array_column($variants, 'price'));

        if (empty($name)) {
            $_SESSION['flash_error'] = 'يرجى إدخال اسم الصنف.';
        } elseif (empty($category)) {
            $_SESSION['flash_error'] = 'يرجى اختيار أو إدخال قسم للصنف.';
        } else {
            try {
                $image_url = handleImageUpload($_FILES['image'] ?? null);

                $pdo->beginTransaction();

                $sort_order = getCategorySortOrder($category);

                // إضافة الصنف في جدول menu_items مع ترتيب القسم
                $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, category, sort_order, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $base_price, $category, $sort_order, $image_url, $is_available]);
                $new_item_id = $pdo->lastInsertId();

                // إضافة الأحجام والأسعار في جدول item_variants
                $stmt_var = $pdo->prepare("INSERT INTO item_variants (item_id, size_name, price) VALUES (?, ?, ?)");
                foreach ($variants as $v) {
                    $stmt_var->execute([$new_item_id, $v['size_name'], $v['price']]);
                }

                $pdo->commit();
                $_SESSION['flash_success'] = "تمت إضافة الصنف [{$name}] مع " . count($variants) . " خيارات حجم وسعر بنجاح!";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['flash_error'] = "فشلت الإضافة: " . $e->getMessage();
            }
        }
        header("Location: admin_menu.php");
        exit;
    }

    // 2. تعديل صنف وأحجامه (Update with Multiple Sizes)
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $custom_category = trim($_POST['custom_category'] ?? '');
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $existing_image = trim($_POST['existing_image'] ?? 'images/knafeh_charcoal.jpg');

        if (!empty($custom_category)) {
            $category = $custom_category;
        }

        $variants = extractVariantsFromPost();
        $base_price = min(array_column($variants, 'price'));

        if ($id <= 0 || empty($name)) {
            $_SESSION['flash_error'] = 'بيانات التعديل غير مكتملة.';
        } else {
            try {
                $image_url = handleImageUpload($_FILES['image'] ?? null, $existing_image);

                $pdo->beginTransaction();

                $sort_order = getCategorySortOrder($category);

                // تحديث بيانات الصنف الأساسية وترتيبه
                $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, sort_order = ?, image_url = ?, is_available = ? WHERE id = ?");
                $stmt->execute([$name, $description, $base_price, $category, $sort_order, $image_url, $is_available, $id]);

                // مسح الأحجام القديمة للصنف وإعادة إدخال الأحجام المعدلة
                $del_var = $pdo->prepare("DELETE FROM item_variants WHERE item_id = ?");
                $del_var->execute([$id]);

                $stmt_var = $pdo->prepare("INSERT INTO item_variants (item_id, size_name, price) VALUES (?, ?, ?)");
                foreach ($variants as $v) {
                    $stmt_var->execute([$id, $v['size_name'], $v['price']]);
                }

                $pdo->commit();
                $_SESSION['flash_success'] = "تم تحديث بيانات وأسعار الصنف [{$name}] بنجاح (" . count($variants) . " أحجام)!";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['flash_error'] = "فشل التعديل: " . $e->getMessage();
            }
        }
        header("Location: admin_menu.php");
        exit;
    }

    // 3. حذف صنف (Delete)
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT name, image_url FROM menu_items WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();

                if ($item) {
                    if (strpos($item['image_url'], 'uploads/') === 0 && file_exists(__DIR__ . '/' . $item['image_url'])) {
                        @unlink(__DIR__ . '/' . $item['image_url']);
                    }
                    $del_stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
                    $del_stmt->execute([$id]);
                    $_SESSION['flash_success'] = "تم حذف الصنف [{$item['name']}] نهائياً مع كافة أحجامه.";
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "فشل الحذف: " . $e->getMessage();
            }
        }
        header("Location: admin_menu.php");
        exit;
    }

    // 4. تبديل حالة توفر الصنف (Toggle Availability)
    elseif ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE menu_items SET is_available = 1 - is_available WHERE id = ?");
                $stmt->execute([$id]);

                $fetch_stmt = $pdo->prepare("SELECT name, is_available FROM menu_items WHERE id = ?");
                $fetch_stmt->execute([$id]);
                $row = $fetch_stmt->fetch();

                $status_text = $row['is_available'] ? 'متوفر حالياً للزبائن' : 'غير متوفر (مخفي من المنيو العام)';
                $_SESSION['flash_success'] = "تم تغيير حالة الصنف [{$row['name']}] إلى: {$status_text}.";
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "فشل تبديل الحالة: " . $e->getMessage();
            }
        }
        header("Location: admin_menu.php");
        exit;
    }

    // 5. إدارة الإضافات (Add / Toggle / Delete Addons)
    elseif ($action === 'add_addon') {
        $addon_name = trim($_POST['addon_name'] ?? '');
        $addon_price = floatval($_POST['addon_price'] ?? 0);
        if (!empty($addon_name) && $addon_price >= 0) {
            $stmt = $pdo->prepare("INSERT INTO item_addons (name, price, is_available) VALUES (?, ?, 1)");
            $stmt->execute([$addon_name, $addon_price]);
            $_SESSION['flash_success'] = "تمت إضافة الإضافة [{$addon_name}] بسعر {$addon_price} ₪ بنجاح!";
        }
        header("Location: admin_menu.php#addons-section");
        exit;
    }
    elseif ($action === 'toggle_addon') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE item_addons SET is_available = 1 - is_available WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = "تم تغيير حالة توفر الإضافة بنجاح.";
        }
        header("Location: admin_menu.php#addons-section");
        exit;
    }
    elseif ($action === 'delete_addon') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM item_addons WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = "تم حذف الإضافة بنجاح.";
        }
        header("Location: admin_menu.php#addons-section");
        exit;
    }
}

// ========================================================
// جلب الإحصائيات والأصناف والأحجام والإضافات
// ========================================================

// 1. الإحصائيات
$stats = $pdo->query("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) AS available_count,
    SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) AS hidden_count,
    COUNT(DISTINCT category) AS categories_count
FROM menu_items")->fetch();

$variants_total_count = $pdo->query("SELECT COUNT(*) FROM item_variants")->fetchColumn();
$addons_total_count = $pdo->query("SELECT COUNT(*) FROM item_addons")->fetchColumn();

// 2. قائمة الأقسام الفريدة بالترتيب المعتمد
$categories_list = $pdo->query("SELECT category FROM menu_items GROUP BY category ORDER BY MIN(sort_order) ASC, category ASC")->fetchAll(PDO::FETCH_COLUMN);

// 3. فلترة البيانات
$filter_category = trim($_GET['category'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$search_query = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM menu_items WHERE 1=1";
$params = [];

if (!empty($filter_category)) {
    $sql .= " AND category = ?";
    $params[] = $filter_category;
}

if ($filter_status === 'available') {
    $sql .= " AND is_available = 1";
} elseif ($filter_status === 'hidden') {
    $sql .= " AND is_available = 0";
}

if (!empty($search_query)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}

$sql .= " ORDER BY sort_order ASC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// 4. جلب جميع الأحجام والأسعار وربطها بالأصناف
$stmt_all_vars = $pdo->query("SELECT * FROM item_variants ORDER BY price ASC");
$all_variants = $stmt_all_vars->fetchAll();
$variants_by_item = [];
foreach ($all_variants as $v) {
    $variants_by_item[$v['item_id']][] = $v;
}

foreach ($items as &$item) {
    $item['variants'] = $variants_by_item[$item['id']] ?? [];
}
unset($item);

// 5. جلب الإضافات
$addons = $pdo->query("SELECT * FROM item_addons ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة تحكم إدارة المنيو | عَصْمَلِي - أبو سير 1854</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    :root {
      --bg-dark: #071712;
      --bg-surface: #0c231b;
      --bg-card: #0e2a22;
      --color-gold: #d4af37;
      --color-gold-bright: #f7e7be;
      --border-gold: rgba(212, 175, 55, 0.35);
      --text-muted: #b3c2be;
    }
    body {
      background: #05130e;
      color: #fff;
      font-family: 'Tajawal', sans-serif;
      margin: 0;
      padding: 0;
      min-height: 100vh;
    }
    
    /* Navbar */
    .admin-nav {
      background: linear-gradient(180deg, #071712 0%, #0c231b 100%);
      border-bottom: 2px solid var(--color-gold);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 20px rgba(0,0,0,0.5);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .admin-brand {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .admin-brand-icon {
      width: 44px;
      height: 44px;
      background: var(--color-gold);
      color: #071712;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      font-weight: bold;
    }
    .admin-brand-title {
      font-family: 'Cinzel', serif;
      font-size: 1.4rem;
      color: var(--color-gold-bright);
      margin: 0;
      line-height: 1;
    }
    .admin-brand-sub {
      font-family: 'Amiri', serif;
      font-size: 0.95rem;
      color: var(--color-gold);
      margin-top: 2px;
    }
    .admin-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .admin-user-pill {
      background: rgba(255,255,255,0.08);
      border: 1px solid var(--border-gold);
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.9rem;
      color: var(--color-gold-bright);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .btn-nav {
      padding: 8px 16px;
      border-radius: 20px;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.25s;
    }
    .btn-nav-view {
      background: transparent;
      border: 1px solid var(--color-gold);
      color: var(--color-gold);
    }
    .btn-nav-view:hover {
      background: var(--color-gold);
      color: #071712;
    }
    .btn-nav-logout {
      background: rgba(220, 53, 69, 0.15);
      border: 1px solid rgba(220, 53, 69, 0.5);
      color: #ff8b94;
    }
    .btn-nav-logout:hover {
      background: #dc3545;
      color: #fff;
    }

    /* Main Container */
    .dashboard-container {
      max-width: 1400px;
      margin: 30px auto;
      padding: 0 20px;
    }

    /* KPI Cards */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .kpi-card {
      background: linear-gradient(145deg, #0e2a22 0%, #081a14 100%);
      border: 1px solid var(--border-gold);
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.3);
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: transform 0.3s;
    }
    .kpi-card:hover {
      transform: translateY(-3px);
      border-color: var(--color-gold);
    }
    .kpi-info h3 {
      font-size: 0.88rem;
      color: var(--text-muted);
      margin: 0 0 5px 0;
      font-weight: 500;
    }
    .kpi-info .number {
      font-size: 2.1rem;
      font-weight: 800;
      color: #fff;
      font-family: 'Cinzel', sans-serif;
    }
    .kpi-icon-wrap {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
    }
    .kpi-total .kpi-icon-wrap { background: rgba(212, 175, 55, 0.15); color: var(--color-gold); }
    .kpi-available .kpi-icon-wrap { background: rgba(25, 135, 84, 0.2); color: #28a745; }
    .kpi-variants .kpi-icon-wrap { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
    .kpi-addons .kpi-icon-wrap { background: rgba(13, 110, 253, 0.2); color: #0d6efd; }

    /* Action & Search Bar */
    .toolbar-card {
      background: #0c231b;
      border: 1px solid var(--border-gold);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 15px;
    }
    .toolbar-left {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      flex: 1;
    }
    .search-input-wrap {
      position: relative;
      min-width: 260px;
    }
    .search-input-wrap input {
      width: 100%;
      background: #061510;
      border: 1px solid var(--border-gold);
      color: #fff;
      padding: 10px 38px 10px 15px;
      border-radius: 25px;
      font-family: inherit;
      font-size: 0.95rem;
      box-sizing: border-box;
    }
    .search-input-wrap i {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--color-gold);
    }
    .filter-select {
      background: #061510;
      border: 1px solid var(--border-gold);
      color: #fff;
      padding: 10px 15px;
      border-radius: 25px;
      font-family: inherit;
      font-size: 0.9rem;
      cursor: pointer;
    }
    .btn-add-item {
      background: linear-gradient(135deg, var(--color-gold) 0%, #c69214 100%);
      color: #071712;
      border: none;
      padding: 11px 24px;
      border-radius: 25px;
      font-size: 1rem;
      font-weight: 800;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 15px rgba(198, 146, 20, 0.4);
      transition: all 0.25s;
    }
    .btn-add-item:hover {
      background: linear-gradient(135deg, #f0c952 0%, var(--color-gold) 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
    }

    /* Flash Messages */
    .alert-flash {
      padding: 14px 20px;
      border-radius: 12px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 1rem;
      animation: fadeIn 0.3s ease;
    }
    .alert-flash-success {
      background: rgba(25, 135, 84, 0.25);
      border: 1px solid #28a745;
      color: #8ce3b3;
    }
    .alert-flash-error {
      background: rgba(220, 53, 69, 0.25);
      border: 1px solid #dc3545;
      color: #ff8b94;
    }

    /* Data Table Card */
    .table-card {
      background: #0c231b;
      border: 1px solid var(--border-gold);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 10px 35px rgba(0,0,0,0.4);
      margin-bottom: 35px;
    }
    .table-responsive {
      overflow-x: auto;
    }
    .custom-table {
      width: 100%;
      border-collapse: collapse;
      text-align: right;
    }
    .custom-table th {
      background: #081a14;
      color: var(--color-gold);
      padding: 16px 18px;
      font-weight: 700;
      font-size: 0.92rem;
      border-bottom: 2px solid var(--border-gold);
      white-space: nowrap;
    }
    .custom-table td {
      padding: 14px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      vertical-align: middle;
      font-size: 0.92rem;
    }
    .custom-table tbody tr {
      transition: background 0.2s;
    }
    .custom-table tbody tr:hover {
      background: rgba(212, 175, 55, 0.05);
    }

    /* Dish Thumb */
    .dish-thumb-wrap {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      overflow: hidden;
      border: 2px solid var(--border-gold);
      background: #05130e;
      box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    }
    .dish-thumb {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s;
    }
    .dish-thumb-wrap:hover .dish-thumb {
      transform: scale(1.1);
    }

    /* Category Badges */
    .cat-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 15px;
      font-size: 0.8rem;
      font-weight: 700;
      background: rgba(212, 175, 55, 0.15);
      color: var(--color-gold-bright);
      border: 1px solid var(--border-gold);
      white-space: nowrap;
    }

    /* Status Badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      white-space: nowrap;
    }
    .status-badge.available {
      background: rgba(40, 167, 69, 0.2);
      color: #4cd964;
      border: 1px solid rgba(40, 167, 69, 0.5);
    }
    .status-badge.hidden {
      background: rgba(220, 53, 69, 0.2);
      color: #ff6b6b;
      border: 1px solid rgba(220, 53, 69, 0.5);
    }

    /* Action Buttons in Table */
    .table-actions {
      display: flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }
    .btn-action-icon {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      border: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }
    .btn-toggle-on {
      background: rgba(40, 167, 69, 0.2);
      color: #4cd964;
      border: 1px solid rgba(40, 167, 69, 0.4);
    }
    .btn-toggle-on:hover {
      background: #28a745;
      color: #fff;
    }
    .btn-toggle-off {
      background: rgba(108, 117, 125, 0.2);
      color: #b3c2be;
      border: 1px solid rgba(108, 117, 125, 0.4);
    }
    .btn-toggle-off:hover {
      background: #6c757d;
      color: #fff;
    }
    .btn-edit-item {
      background: rgba(13, 110, 253, 0.2);
      color: #5ea4ff;
      border: 1px solid rgba(13, 110, 253, 0.4);
    }
    .btn-edit-item:hover {
      background: #0d6efd;
      color: #fff;
    }
    .btn-delete-item {
      background: rgba(220, 53, 69, 0.2);
      color: #ff6b6b;
      border: 1px solid rgba(220, 53, 69, 0.4);
    }
    .btn-delete-item:hover {
      background: #dc3545;
      color: #fff;
    }

    /* Add-ons Management Card */
    .addons-mgmt-card {
      background: #0c231b;
      border: 1px solid var(--border-gold);
      border-radius: 18px;
      padding: 25px;
      margin-top: 30px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.4);
    }
    .addons-mgmt-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 15px;
    }
    .addons-mgmt-header h3 {
      font-size: 1.25rem;
      color: var(--color-gold-bright);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .addons-grid-admin {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 15px;
    }
    .addon-card-admin {
      background: #081a14;
      border: 1px solid rgba(212, 175, 55, 0.3);
      border-radius: 12px;
      padding: 15px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: border-color 0.2s;
    }
    .addon-card-admin:hover {
      border-color: var(--color-gold);
    }
    .addon-card-admin.disabled {
      opacity: 0.6;
      border-style: dashed;
    }
    .addon-admin-info strong {
      display: block;
      color: #fff;
      font-size: 1rem;
      margin-bottom: 3px;
    }
    .addon-admin-info span {
      color: var(--color-gold);
      font-weight: 800;
      font-size: 1.05rem;
    }

    /* Modal Styles */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(6px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 999;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
      padding: 20px;
    }
    .modal-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }
    .modal-dialog {
      background: linear-gradient(160deg, #0e2a22, #071712);
      border: 2px solid var(--color-gold);
      border-radius: 20px;
      width: 100%;
      max-width: 680px;
      max-height: 92vh;
      overflow-y: auto;
      box-shadow: 0 25px 60px rgba(0,0,0,0.85);
      position: relative;
      transform: translateY(-20px);
      transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-dialog {
      transform: translateY(0);
    }
    .modal-header {
      padding: 20px 25px;
      border-bottom: 1px solid var(--border-gold);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .modal-title {
      font-size: 1.3rem;
      color: var(--color-gold-bright);
      margin: 0;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn-close-modal {
      background: none;
      border: none;
      color: #8c9e97;
      font-size: 1.3rem;
      cursor: pointer;
      transition: color 0.2s;
    }
    .btn-close-modal:hover {
      color: #fff;
    }
    .modal-body {
      padding: 25px;
    }
    .modal-footer {
      padding: 15px 25px 20px;
      border-top: 1px solid rgba(255,255,255,0.06);
      display: flex;
      justify-content: flex-end;
      gap: 12px;
    }

    /* Modal Form Controls */
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }
    .modal-form-group {
      margin-bottom: 18px;
    }
    .modal-form-group label {
      display: block;
      margin-bottom: 7px;
      font-size: 0.92rem;
      color: #f7e7be;
      font-weight: 600;
    }
    .modal-form-group input[type="text"],
    .modal-form-group input[type="number"],
    .modal-form-group select,
    .modal-form-group textarea {
      width: 100%;
      background: #061510;
      border: 1px solid var(--border-gold);
      color: #fff;
      padding: 10px 14px;
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.95rem;
      box-sizing: border-box;
      transition: border-color 0.2s;
    }
    .modal-form-group textarea {
      resize: vertical;
      min-height: 70px;
    }
    .modal-form-group input:focus,
    .modal-form-group select:focus,
    .modal-form-group textarea:focus {
      outline: none;
      border-color: var(--color-gold);
      box-shadow: 0 0 8px rgba(212,175,55,0.3);
    }

    /* Dynamic Variants Box in Modal */
    .variants-card-modal {
      background: rgba(0, 0, 0, 0.35);
      border: 1px solid var(--border-gold);
      border-radius: 14px;
      padding: 16px;
      margin-bottom: 20px;
    }
    .variants-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .variants-modal-header h4 {
      margin: 0;
      color: var(--color-gold-bright);
      font-size: 0.98rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .file-input-wrapper {
      border: 2px dashed var(--border-gold);
      padding: 15px;
      border-radius: 12px;
      text-align: center;
      background: rgba(0,0,0,0.2);
      cursor: pointer;
      position: relative;
    }
    .file-input-wrapper input[type="file"] {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      cursor: pointer;
    }
    .preview-box {
      margin-top: 10px;
      display: none;
      text-align: center;
    }
    .preview-box img {
      max-width: 120px;
      max-height: 90px;
      border-radius: 8px;
      border: 1px solid var(--color-gold);
      object-fit: cover;
    }
    .checkbox-label {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      user-select: none;
      font-size: 0.95rem;
      color: #fff;
    }
    .checkbox-label input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: var(--color-gold);
      cursor: pointer;
    }
    .btn-modal-cancel {
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.2);
      color: #fff;
      padding: 10px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-family: inherit;
      font-size: 0.95rem;
    }
    .btn-modal-submit {
      background: linear-gradient(135deg, var(--color-gold) 0%, #c69214 100%);
      color: #071712;
      border: none;
      padding: 10px 24px;
      border-radius: 10px;
      cursor: pointer;
      font-family: inherit;
      font-size: 0.95rem;
      font-weight: 700;
    }
    .btn-modal-delete {
      background: #dc3545;
      color: #fff;
      border: none;
      padding: 10px 24px;
      border-radius: 10px;
      cursor: pointer;
      font-family: inherit;
      font-size: 0.95rem;
      font-weight: 700;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-5px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
      .admin-nav {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      .toolbar-card {
        flex-direction: column;
        align-items: stretch;
      }
      .form-row {
        grid-template-columns: 1fr;
      }
      .variant-row {
        flex-direction: column;
        align-items: stretch;
      }
    }
  </style>
</head>
<body>

  <!-- شريط التنقل العلوي للمشرف -->
  <nav class="admin-nav">
    <div class="admin-brand">
      <div class="admin-brand-icon">
        <i class="fa-solid fa-crown"></i>
      </div>
      <div>
        <h1 class="admin-brand-title">OSMANLI 1854</h1>
        <div class="admin-brand-sub">لوحة إدارة المنيو والأسعار المتعددة (Multiple Sizes CRUD)</div>
      </div>
    </div>

    <div class="admin-actions">
      <div class="admin-user-pill">
        <i class="fa-solid fa-user-shield" style="color: var(--color-gold);"></i>
        <span>المشرف: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?></strong></span>
      </div>

      <a href="index.php" target="_blank" class="btn-nav btn-nav-view" title="عرض صفحة المنيو العامة كما يراها الزبائن">
        <i class="fa-solid fa-eye"></i>
        <span>معاينة المنيو العام</span>
      </a>

      <a href="logout.php" class="btn-nav btn-nav-logout" title="تسجيل الخروج">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>خروج</span>
      </a>
    </div>
  </nav>

  <div class="dashboard-container">

    <!-- رسائل التنبيه الفورية (Flash Alerts) -->
    <?php if (!empty($message)): ?>
      <div class="alert-flash alert-flash-success">
        <div><i class="fa-solid fa-circle-check" style="margin-left: 8px;"></i> <?= htmlspecialchars($message) ?></div>
        <button type="button" class="btn-close-modal" onclick="this.parentElement.remove()">&times;</button>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert-flash alert-flash-error">
        <div><i class="fa-solid fa-circle-exclamation" style="margin-left: 8px;"></i> <?= htmlspecialchars($error) ?></div>
        <button type="button" class="btn-close-modal" onclick="this.parentElement.remove()">&times;</button>
      </div>
    <?php endif; ?>

    <!-- بطاقات الإحصائيات السريعة (KPIs) -->
    <div class="kpi-grid">
      <div class="kpi-card kpi-total">
        <div class="kpi-info">
          <h3>إجمالي الأصناف</h3>
          <div class="number"><?= number_format($stats['total'] ?? 0) ?></div>
        </div>
        <div class="kpi-icon-wrap">
          <i class="fa-solid fa-utensils"></i>
        </div>
      </div>

      <div class="kpi-card kpi-available">
        <div class="kpi-info">
          <h3>أصناف متوفرة</h3>
          <div class="number"><?= number_format($stats['available_count'] ?? 0) ?></div>
        </div>
        <div class="kpi-icon-wrap">
          <i class="fa-solid fa-check-double"></i>
        </div>
      </div>

      <div class="kpi-card kpi-variants">
        <div class="kpi-info">
          <h3>إجمالي الأحجام والأسعار</h3>
          <div class="number"><?= number_format($variants_total_count ?? 0) ?></div>
        </div>
        <div class="kpi-icon-wrap">
          <i class="fa-solid fa-tags"></i>
        </div>
      </div>

      <div class="kpi-card kpi-addons">
        <div class="kpi-info">
          <h3>الإضافات الاختيارية</h3>
          <div class="number"><?= number_format($addons_total_count ?? 0) ?></div>
        </div>
        <div class="kpi-icon-wrap">
          <i class="fa-solid fa-ice-cream"></i>
        </div>
      </div>
    </div>

    <!-- شريط الأدوات والبحث والفلترة -->
    <div class="toolbar-card">
      <div class="toolbar-left">
        <!-- البحث -->
        <div class="search-input-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="liveSearchInput" placeholder="بحث بالاسم أو المكونات أو الأحجام..." value="<?= htmlspecialchars($search_query) ?>">
        </div>

        <!-- فلتر الأقسام -->
        <select class="filter-select" id="categoryFilter" onchange="applyFilters()">
          <option value="">جميع الأقسام (الكل)</option>
          <?php foreach ($categories_list as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $filter_category === $cat ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <!-- فلتر الحالة -->
        <select class="filter-select" id="statusFilter" onchange="applyFilters()">
          <option value="">جميع الحالات</option>
          <option value="available" <?= $filter_status === 'available' ? 'selected' : '' ?>>متوفر فقط</option>
          <option value="hidden" <?= $filter_status === 'hidden' ? 'selected' : '' ?>>مخفي فقط</option>
        </select>
      </div>

      <div style="display: flex; gap: 10px;">
        <button type="button" class="btn-add-item" onclick="openAddModal()">
          <i class="fa-solid fa-plus"></i>
          <span>إضافة صنف جديد</span>
        </button>
      </div>
    </div>

    <!-- جدول بيانات الأصناف الرئيسي -->
    <div class="table-card">
      <div class="table-responsive">
        <table class="custom-table" id="menuTable">
          <thead>
            <tr>
              <th style="width: 45px;">#</th>
              <th style="width: 75px;">الصورة</th>
              <th>اسم الصنف</th>
              <th>القسم</th>
              <th style="min-width: 200px;">الأحجام والأسعار (₪)</th>
              <th>الوصف والمكونات</th>
              <th>الحالة</th>
              <th style="text-align: center; width: 135px;">إجراءات التحكم</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($items)): ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                  <i class="fa-solid fa-inbox" style="font-size: 2.5rem; display: block; margin-bottom: 10px; color: var(--color-gold);"></i>
                  لا توجد أصناف مطابقة لخيارات البحث الحالية.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($items as $item): ?>
                <?php 
                  $v_count = count($item['variants']);
                  $prices = array_column($item['variants'], 'price');
                  $min_p = !empty($prices) ? min($prices) : $item['price'];
                  $max_p = !empty($prices) ? max($prices) : $item['price'];
                ?>
                <tr class="item-row" data-name="<?= htmlspecialchars($item['name']) ?>" data-desc="<?= htmlspecialchars($item['description'] ?? '') ?>">
                  <td style="color: var(--text-muted); font-family: 'Cinzel', serif;"><?= $item['id'] ?></td>
                  <td>
                    <div class="dish-thumb-wrap">
                      <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="dish-thumb" onerror="this.src='images/knafeh_charcoal.jpg'">
                    </div>
                  </td>
                  <td>
                    <strong style="font-size: 1.05rem; color: #fff;"><?= htmlspecialchars($item['name']) ?></strong>
                  </td>
                  <td>
                    <span class="cat-badge"><?= htmlspecialchars($item['category']) ?></span>
                  </td>
                  <td>
                    <?php if ($v_count <= 1): ?>
                      <strong style="color: var(--color-gold-bright); font-size: 1.1rem;"><?= number_format($min_p, 0) ?> ₪</strong>
                      <?php if ($v_count === 1 && $item['variants'][0]['size_name'] !== 'سعر موحد'): ?>
                        <span class="v-pill" style="margin-right: 5px;"><?= htmlspecialchars($item['variants'][0]['size_name']) ?></span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="price-range-badge">من <?= number_format($min_p, 0) ?> ₪ إلى <?= number_format($max_p, 0) ?> ₪</span>
                      <div class="variants-pills-list">
                        <?php foreach ($item['variants'] as $var): ?>
                          <span class="v-pill"><strong><?= htmlspecialchars($var['size_name']) ?>:</strong> <?= number_format($var['price'], 0) ?> ₪</span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td style="max-width: 250px; color: #c4d3ce; font-size: 0.88rem; line-height: 1.4;">
                    <?= htmlspecialchars($item['description'] ?: '—') ?>
                  </td>
                  <td>
                    <?php if ($item['is_available']): ?>
                      <span class="status-badge available">
                        <i class="fa-solid fa-circle-check"></i> متوفر
                      </span>
                    <?php else: ?>
                      <span class="status-badge hidden">
                        <i class="fa-solid fa-circle-xmark"></i> مخفي
                      </span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: center;">
                    <div class="table-actions" style="justify-content: center;">
                      <!-- زر تبديل الحالة السريع -->
                      <form method="POST" action="admin_menu.php" style="display: inline;">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn-action-icon <?= $item['is_available'] ? 'btn-toggle-on' : 'btn-toggle-off' ?>" title="<?= $item['is_available'] ? 'إخفاء الصنف من المنيو' : 'إظهار الصنف في المنيو' ?>">
                          <i class="fa-solid <?= $item['is_available'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                        </button>
                      </form>

                      <!-- زر التعديل -->
                      <button type="button" class="btn-action-icon btn-edit-item" title="تعديل الصنف وأسعاره" onclick='openEditModal(<?= json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>

                      <!-- زر الحذف -->
                      <button type="button" class="btn-action-icon btn-delete-item" title="حذف الصنف" onclick="openDeleteModal(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['name'])) ?>')">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- قسم إدارة الإضافات الاختيارية (Add-ons Management) -->
    <section class="addons-mgmt-card" id="addons-section">
      <div class="addons-mgmt-header">
        <div>
          <h3><i class="fa-solid fa-ice-cream gold-icon"></i> الإضافات الاختيارية (بوظة، قشطة، نوتيلا...)</h3>
          <span style="color: var(--text-muted); font-size: 0.88rem;">تظهر هذه الإضافات تلقائياً في بانر المنيو للزبائن</span>
        </div>
        <button type="button" class="btn-add-variant-row" onclick="openModal('addAddonModal')">
          <i class="fa-solid fa-plus"></i> إضافة خيار إضافي
        </button>
      </div>

      <div class="addons-grid-admin">
        <?php foreach ($addons as $ad): ?>
          <div class="addon-card-admin <?= $ad['is_available'] ? '' : 'disabled' ?>">
            <div class="addon-admin-info">
              <strong><?= htmlspecialchars($ad['name']) ?></strong>
              <span><?= number_format($ad['price'], 0) ?> ₪</span>
            </div>
            <div class="table-actions">
              <form method="POST" action="admin_menu.php" style="display:inline;">
                <input type="hidden" name="action" value="toggle_addon">
                <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                <button type="submit" class="btn-action-icon <?= $ad['is_available'] ? 'btn-toggle-on' : 'btn-toggle-off' ?>" title="<?= $ad['is_available'] ? 'تعطيل الإضافة' : 'تفعيل الإضافة' ?>">
                  <i class="fa-solid <?= $ad['is_available'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                </button>
              </form>
              <form method="POST" action="admin_menu.php" style="display:inline;" onsubmit="return confirm('هل تريد حذف هذه الإضافة بالتأكيد؟');">
                <input type="hidden" name="action" value="delete_addon">
                <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                <button type="submit" class="btn-action-icon btn-delete-item" title="حذف الإضافة">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  </div>

  <!-- ========================================================
       مودال 1: إضافة صنف جديد مع الأحجام والأسعار (Add Item Modal)
       ======================================================== -->
  <div class="modal-overlay" id="addModal">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title"><i class="fa-solid fa-circle-plus gold-icon"></i> إضافة صنف جديد إلى المنيو</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('addModal')">&times;</button>
      </div>
      <form method="POST" action="admin_menu.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="form-row">
            <div class="modal-form-group">
              <label for="add_name">اسم الصنف <span style="color:#ff6b6b;">*</span></label>
              <input type="text" id="add_name" name="name" placeholder="مثال: كنافة إسطنبولية فاخرة" required>
            </div>
            <div class="modal-form-group">
              <label for="add_category">القسم / الفئة <span style="color:#ff6b6b;">*</span></label>
              <select id="add_category" name="category" onchange="toggleCustomCategory(this, 'add_custom_cat_wrap')">
                <?php foreach ($categories_list as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
                <option value="__custom__">+ قسم جديد مخصص...</option>
              </select>
            </div>
          </div>

          <div class="modal-form-group" id="add_custom_cat_wrap" style="display: none;">
            <label for="add_custom_category">اسم القسم الجديد</label>
            <input type="text" id="add_custom_category" name="custom_category" placeholder="مثال: بوظة ومثلجات">
          </div>

          <!-- قسم الأحجام والأسعار الديناميكية -->
          <div class="variants-card-modal">
            <div class="variants-modal-header">
              <h4><i class="fa-solid fa-tags gold-icon"></i> الأحجام والأسعار (Dynamic Sizes & Prices)</h4>
              <button type="button" class="btn-add-variant-row" onclick="addVariantRow('add')">
                <i class="fa-solid fa-plus"></i> إضافة حجم وسعر
              </button>
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 10px;">
              يمكنك إضافة عدة أحجام وسعر لكل حجم، أو استخدام الأزرار الجاهزة السريعة أدناه:
            </div>
            
            <div id="add_variants_container"></div>

            <div class="quick-presets-wrap">
              <span style="font-size: 0.78rem; color: var(--color-gold);">نماذج سريعة:</span>
              <button type="button" class="preset-chip-btn" onclick="applyPreset('add', 'knafeh')">+ خماسي الكنافة (XS, S, M, L, XL)</button>
              <button type="button" class="preset-chip-btn" onclick="applyPreset('add', 'weight')">+ شخصي و 1 كيلو</button>
              <button type="button" class="preset-chip-btn" onclick="applyPreset('add', 'single')">+ سعر موحد (طلب واحد)</button>
            </div>
          </div>

          <div class="modal-form-group">
            <label for="add_desc">الوصف والمكونات</label>
            <textarea id="add_desc" name="description" placeholder="مكونات الصنف، طريقة التقديم، النكهات المميزة..."></textarea>
          </div>

          <div class="modal-form-group">
            <label>صورة الصنف</label>
            <div class="file-input-wrapper">
              <i class="fa-solid fa-cloud-arrow-up" style="font-size: 1.8rem; color: var(--color-gold); display: block; margin-bottom: 5px;"></i>
              <span>انقر هنا لاختيار صورة من جهازك (JPG, PNG, WEBP)</span>
              <input type="file" name="image" accept="image/*" onchange="previewImage(this, 'add_preview')">
            </div>
            <div class="preview-box" id="add_preview">
              <img src="" alt="معاينة الصورة">
            </div>
          </div>

          <div class="modal-form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="is_available" value="1" checked>
              <span>إتاحة هذا الصنف للزبائن فوراً في المنيو العام (متوفر)</span>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-modal-cancel" onclick="closeModal('addModal')">إلغاء</button>
          <button type="submit" class="btn-modal-submit">حفظ وإضافة الصنف</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ========================================================
       مودال 2: تعديل صنف مع أحجامه وأسعاره (Edit Item Modal)
       ======================================================== -->
  <div class="modal-overlay" id="editModal">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title"><i class="fa-solid fa-pen-to-square gold-icon"></i> تعديل بيانات الصنف وأسعاره</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('editModal')">&times;</button>
      </div>
      <form method="POST" action="admin_menu.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <input type="hidden" name="existing_image" id="edit_existing_image">

        <div class="modal-body">
          <div class="form-row">
            <div class="modal-form-group">
              <label for="edit_name">اسم الصنف <span style="color:#ff6b6b;">*</span></label>
              <input type="text" id="edit_name" name="name" required>
            </div>
            <div class="modal-form-group">
              <label for="edit_category">القسم / الفئة</label>
              <select id="edit_category" name="category" onchange="toggleCustomCategory(this, 'edit_custom_cat_wrap')">
                <?php foreach ($categories_list as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
                <option value="__custom__">+ قسم جديد مخصص...</option>
              </select>
            </div>
          </div>

          <div class="modal-form-group" id="edit_custom_cat_wrap" style="display: none;">
            <label for="edit_custom_category">اسم القسم الجديد</label>
            <input type="text" id="edit_custom_category" name="custom_category" placeholder="اسم قسم جديد...">
          </div>

          <!-- تعديل الأحجام والأسعار ديناميكياً -->
          <div class="variants-card-modal">
            <div class="variants-modal-header">
              <h4><i class="fa-solid fa-tags gold-icon"></i> تعديل الأحجام والأسعار (Edit Sizes & Prices)</h4>
              <button type="button" class="btn-add-variant-row" onclick="addVariantRow('edit')">
                <i class="fa-solid fa-plus"></i> إضافة حجم وسعر
              </button>
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 10px;">
              يمكنك تعديل الأسعار، تغيير أسماء الأحجام، أو حذف وإضافة أحجام جديدة:
            </div>

            <div id="edit_variants_container"></div>

            <div class="quick-presets-wrap">
              <span style="font-size: 0.78rem; color: var(--color-gold);">إضافة نموذج سريع:</span>
              <button type="button" class="preset-chip-btn" onclick="applyPreset('edit', 'knafeh')">+ خماسي الكنافة (XS, S, M, L, XL)</button>
              <button type="button" class="preset-chip-btn" onclick="applyPreset('edit', 'weight')">+ شخصي و 1 كيلو</button>
            </div>
          </div>

          <div class="modal-form-group">
            <label for="edit_desc">الوصف والمكونات</label>
            <textarea id="edit_desc" name="description"></textarea>
          </div>

          <div class="modal-form-group">
            <label>الصورة الحالية / استبدال الصورة</label>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
              <img id="edit_current_img" src="" alt="الصورة الحالية" style="width: 70px; height: 70px; object-fit: cover; border-radius: 10px; border: 1px solid var(--color-gold);">
              <span style="font-size: 0.85rem; color: var(--text-muted);">إذا كنت لا تريد تغيير الصورة، اترك حقل الرفع فارغاً.</span>
            </div>
            <div class="file-input-wrapper">
              <i class="fa-solid fa-cloud-arrow-up" style="font-size: 1.5rem; color: var(--color-gold); display: block; margin-bottom: 5px;"></i>
              <span>رفع صورة بديلة جديدة (JPG, PNG, WEBP)</span>
              <input type="file" name="image" accept="image/*" onchange="previewImage(this, 'edit_preview')">
            </div>
            <div class="preview-box" id="edit_preview">
              <img src="" alt="معاينة الصورة الجديدة">
            </div>
          </div>

          <div class="modal-form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="is_available" id="edit_is_available" value="1">
              <span>الصنف متوفر حالياً للزبائن في المنيو العام</span>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-modal-cancel" onclick="closeModal('editModal')">إلغاء</button>
          <button type="submit" class="btn-modal-submit">حفظ التعديلات والأحجام</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ========================================================
       مودال 3: تأكيد الحذف (Delete Confirmation Modal)
       ======================================================== -->
  <div class="modal-overlay" id="deleteModal">
    <div class="modal-dialog" style="max-width: 440px;">
      <div class="modal-header" style="border-bottom-color: rgba(220, 53, 69, 0.4);">
        <h3 class="modal-title" style="color: #ff6b6b;"><i class="fa-solid fa-triangle-exclamation"></i> تأكيد حذف الصنف</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('deleteModal')">&times;</button>
      </div>
      <form method="POST" action="admin_menu.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
        <div class="modal-body" style="text-align: center; padding: 25px 20px;">
          <i class="fa-solid fa-trash-can" style="font-size: 3rem; color: #dc3545; margin-bottom: 15px;"></i>
          <p style="font-size: 1.1rem; margin: 0 0 10px 0;">هل أنت متأكد من رغبتك في حذف هذا الصنف نهائياً؟</p>
          <div id="delete_item_name" style="color: var(--color-gold-bright); font-weight: bold; font-size: 1.2rem; margin-bottom: 15px;"></div>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">سيتم حذف الصنف وجميع الأحجام والأسعار المرتبطة به من قاعدة البيانات.</p>
        </div>
        <div class="modal-footer" style="justify-content: center;">
          <button type="button" class="btn-modal-cancel" onclick="closeModal('deleteModal')">إلغاء الأمر</button>
          <button type="submit" class="btn-modal-delete">نعم، احذف الصنف</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ========================================================
       مودال 4: إضافة خيار إضافي (Add Addon Modal)
       ======================================================== -->
  <div class="modal-overlay" id="addAddonModal">
    <div class="modal-dialog" style="max-width: 450px;">
      <div class="modal-header">
        <h3 class="modal-title"><i class="fa-solid fa-ice-cream gold-icon"></i> إضافة خيار إضافي</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('addAddonModal')">&times;</button>
      </div>
      <form method="POST" action="admin_menu.php">
        <input type="hidden" name="action" value="add_addon">
        <div class="modal-body">
          <div class="modal-form-group">
            <label for="addon_name">اسم الإضافة</label>
            <input type="text" id="addon_name" name="addon_name" placeholder="مثال: إضافة بوظة مستكة تركية" required>
          </div>
          <div class="modal-form-group">
            <label for="addon_price">السعر (₪)</label>
            <input type="number" id="addon_price" name="addon_price" step="0.5" min="0" placeholder="مثال: 5" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-modal-cancel" onclick="closeModal('addAddonModal')">إلغاء</button>
          <button type="submit" class="btn-modal-submit">حفظ الإضافة</button>
        </div>
      </form>
    </div>
  </div>

  <!-- JavaScript تفاعلي لإدارة النوافذ والأحجام الديناميكية -->
  <script>
    function openModal(id) {
      document.getElementById(id).classList.add('active');
    }
    function closeModal(id) {
      document.getElementById(id).classList.remove('active');
    }

    document.querySelectorAll('.modal-overlay').forEach(modal => {
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          this.classList.remove('active');
        }
      });
    });

    // دالة إضافة سطر حجم وسعر ديناميكياً
    function addVariantRow(modalType, sizeName = '', price = '') {
      const container = document.getElementById(modalType + '_variants_container');
      const row = document.createElement('div');
      row.className = 'variant-row';
      row.innerHTML = `
        <input type="text" name="size_names[]" value="${sizeName}" placeholder="اسم الحجم (مثال: XS أو شخصي)" required>
        <input type="number" name="size_prices[]" value="${price}" step="0.5" min="0" placeholder="السعر بـ ₪" required style="max-width: 130px;">
        <button type="button" class="btn-remove-variant" onclick="this.closest('.variant-row').remove()" title="حذف هذا الحجم">
          <i class="fa-solid fa-trash-can"></i>
        </button>
      `;
      container.appendChild(row);
    }

    // نماذج الأحجام السريعة
    function applyPreset(modalType, presetType) {
      const container = document.getElementById(modalType + '_variants_container');
      if (presetType === 'single') {
        container.innerHTML = '';
        addVariantRow(modalType, 'سعر موحد', 15);
      } else if (presetType === 'knafeh') {
        container.innerHTML = '';
        addVariantRow(modalType, 'XS', 8);
        addVariantRow(modalType, 'S', 12);
        addVariantRow(modalType, 'M', 22);
        addVariantRow(modalType, 'L', 42);
        addVariantRow(modalType, 'XL', 60);
      } else if (presetType === 'weight') {
        container.innerHTML = '';
        addVariantRow(modalType, 'شخصي', 7);
        addVariantRow(modalType, '1 كيلو', 40);
      }
    }

    // فتح نافذة الإضافة وتجهيز صف افتراضي
    function openAddModal() {
      document.getElementById('add_preview').style.display = 'none';
      const container = document.getElementById('add_variants_container');
      container.innerHTML = '';
      addVariantRow('add', 'XS', 8);
      addVariantRow('add', 'S', 12);
      addVariantRow('add', 'M', 22);
      openModal('addModal');
    }

    // فتح نافذة التعديل وتعبئة البيانات والأحجام
    function openEditModal(item) {
      document.getElementById('edit_id').value = item.id;
      document.getElementById('edit_name').value = item.name;
      document.getElementById('edit_desc').value = item.description || '';
      document.getElementById('edit_existing_image').value = item.image_url;
      document.getElementById('edit_current_img').src = item.image_url;
      document.getElementById('edit_is_available').checked = parseInt(item.is_available) === 1;

      // اختيار القسم
      const catSelect = document.getElementById('edit_category');
      let found = false;
      for (let i = 0; i < catSelect.options.length; i++) {
        if (catSelect.options[i].value === item.category) {
          catSelect.selectedIndex = i;
          found = true;
          break;
        }
      }
      if (!found) {
        catSelect.value = '__custom__';
        document.getElementById('edit_custom_cat_wrap').style.display = 'block';
        document.getElementById('edit_custom_category').value = item.category;
      } else {
        document.getElementById('edit_custom_cat_wrap').style.display = 'none';
      }

      // تعبئة الأحجام والأسعار
      const container = document.getElementById('edit_variants_container');
      container.innerHTML = '';

      if (item.variants && item.variants.length > 0) {
        item.variants.forEach(v => {
          addVariantRow('edit', v.size_name, v.price);
        });
      } else {
        addVariantRow('edit', 'سعر موحد', item.price);
      }

      document.getElementById('edit_preview').style.display = 'none';
      openModal('editModal');
    }

    // فتح نافذة تأكيد الحذف
    function openDeleteModal(id, name) {
      document.getElementById('delete_id').value = id;
      document.getElementById('delete_item_name').textContent = '« ' + name + ' »';
      openModal('deleteModal');
    }

    // معاينة الصورة المرفوعة
    function previewImage(input, previewBoxId) {
      const box = document.getElementById(previewBoxId);
      const img = box.querySelector('img');
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          img.src = e.target.result;
          box.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
      } else {
        box.style.display = 'none';
      }
    }

    function toggleCustomCategory(select, wrapId) {
      const wrap = document.getElementById(wrapId);
      if (select.value === '__custom__') {
        wrap.style.display = 'block';
      } else {
        wrap.style.display = 'none';
      }
    }

    function applyFilters() {
      const cat = document.getElementById('categoryFilter').value;
      const status = document.getElementById('statusFilter').value;
      const search = document.getElementById('liveSearchInput').value;

      const params = new URLSearchParams();
      if (cat) params.set('category', cat);
      if (status) params.set('status', status);
      if (search) params.set('search', search);

      window.location.href = 'admin_menu.php?' + params.toString();
    }

    const liveSearchInput = document.getElementById('liveSearchInput');
    if (liveSearchInput) {
      liveSearchInput.addEventListener('keyup', function() {
        const term = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.item-row');
        rows.forEach(row => {
          const name = (row.getAttribute('data-name') || '').toLowerCase();
          const desc = (row.getAttribute('data-desc') || '').toLowerCase();
          const text = row.innerText.toLowerCase();
          if (name.includes(term) || desc.includes(term) || text.includes(term)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
    }
  </script>
</body>
</html>
