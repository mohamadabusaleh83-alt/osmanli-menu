<?php
/**
 * setup.php - سكريبت تهيئة وزرع قاعدة البيانات آلياً
 * Osmanli Menu Project - Database Installer & Seeder
 */

header('Content-Type: text/html; charset=utf-8');

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'osmanli_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$messages = [];
$success = false;

try {
    // 1. الاتصال بدون تحديد اسم القاعدة لإنشائها إن لم تكن موجودة
    $pdo_root = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdo_root->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $messages[] = " تم التحقق من وجود قاعدة البيانات [{$dbname}].";
    
    // 2. الاتصال بالقاعدة مباشرة
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 3. قراءة وتنفيذ ملف schema.sql
    $schema_file = __DIR__ . '/schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("ملف schema.sql غير موجود في المجلد الحالي.");
    }

    $sql = file_get_contents($schema_file);
    // تقسيم الاستعلامات وتنفيذها
    $pdo->exec($sql);
    $messages[] = " تم إنشاء وتحديث الجداول بنجاح (users, menu_items, item_variants, item_addons).";

    // 4. التحقق من حساب الأدمن الافتراضي
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();

    if ($admin) {
        $messages[] = " حساب الأدمن الافتراضي جاهز: اسم المستخدم (<strong>admin</strong>) | البريد (<strong>admin@osmanli.ps</strong>) | كلمة المرور (<strong>admin123</strong>).";
    }

    // 5. عدد الأصناف والأحجام والإضافات
    $count = $pdo->query("SELECT COUNT(*) AS total FROM menu_items")->fetch()['total'];
    $var_count = $pdo->query("SELECT COUNT(*) AS total FROM item_variants")->fetch()['total'];
    $add_count = $pdo->query("SELECT COUNT(*) AS total FROM item_addons")->fetch()['total'];
    $messages[] = " تم زرع الأصناف الأولية (الإجمالي: <strong>{$count}</strong> صنف، <strong>{$var_count}</strong> حجماً وسعراً).";
    $messages[] = " تم زرع الإضافات الاختيارية (الإجمالي: <strong>{$add_count}</strong> إضافات: بوظة، قشطة، نوتيلا).";

    // 6. التحقق من مجلد uploads
    $uploads_dir = __DIR__ . '/uploads';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }
    $messages[] = " مجلد رفع الصور [uploads/] جاهز وصالح للكتابة.";

    $success = true;

} catch (Exception $e) {
    $messages[] = " خطأ أثناء التثبيت: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تهيئة نظام عصملي 1854 | إعداد قاعدة البيانات</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: #071712;
      color: #fff;
      font-family: 'Amiri', 'Traditional Arabic', serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }
    .setup-box {
      background: linear-gradient(145deg, #0d281e, #05130f);
      border: 2px solid #d4af37;
      border-radius: 16px;
      padding: 35px 30px;
      max-width: 650px;
      width: 100%;
      box-shadow: 0 15px 40px rgba(0,0,0,0.6);
      text-align: center;
    }
    .setup-title {
      color: #d4af37;
      font-size: 2rem;
      margin-bottom: 5px;
    }
    .setup-subtitle {
      color: #b2c2bc;
      font-size: 1rem;
      margin-bottom: 25px;
    }
    .log-list {
      background: rgba(0,0,0,0.3);
      border-radius: 10px;
      padding: 15px;
      text-align: right;
      list-style: none;
      margin: 0 0 25px 0;
      border: 1px solid rgba(212,175,55,0.2);
    }
    .log-list li {
      padding: 8px 10px;
      font-size: 1.05rem;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      line-height: 1.6;
    }
    .log-list li:last-child {
      border-bottom: none;
    }
    .cred-badge {
      background: rgba(212,175,55,0.15);
      border: 1px dashed #d4af37;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 25px;
      color: #f7e7be;
      font-size: 1.1rem;
    }
    .btn-group {
      display: flex;
      gap: 15px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .btn-action {
      background: #d4af37;
      color: #071712;
      padding: 12px 25px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: bold;
      font-size: 1.1rem;
      transition: 0.3s;
    }
    .btn-action:hover {
      background: #f0c952;
      transform: translateY(-2px);
    }
    .btn-secondary {
      background: transparent;
      border: 1px solid #d4af37;
      color: #d4af37;
    }
    .btn-secondary:hover {
      background: rgba(212,175,55,0.15);
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="setup-box">
    <h1 class="setup-title">عَصْمَلِي - أبو سير 1854</h1>
    <p class="setup-subtitle">تقرير تهيئة وتثبيت قاعدة البيانات (Database Setup)</p>

    <ul class="log-list">
      <?php foreach ($messages as $msg): ?>
        <li><?= $msg ?></li>
      <?php endforeach; ?>
    </ul>

    <?php if ($success): ?>
      <div class="cred-badge">
        🔑 <strong>بيانات تسجيل الدخول للوحة التحكم:</strong><br>
        اسم المستخدم: <code style="color:#fff; background:#071712; padding:2px 8px; border-radius:4px;">admin</code> | 
        كلمة المرور: <code style="color:#fff; background:#071712; padding:2px 8px; border-radius:4px;">admin123</code>
      </div>
      <div class="btn-group">
        <a href="login.php" class="btn-action">تسجيل الدخول للوحة التحكم ➔</a>
        <a href="index.php" class="btn-action btn-secondary">تصفح المنيو العام</a>
      </div>
    <?php else: ?>
      <div class="btn-group">
        <a href="setup.php" class="btn-action">إعادة المحاولة</a>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
