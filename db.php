<?php
/**
 * db.php - إعداد والاتصال بقاعدة البيانات باستخدام PDO
 * Osmanli Menu Project (1854) - Database Connection
 */

// إعدادات الاتصال (localhost / 127.0.0.1, user: root, بدون كلمة مرور)
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'osmanli_db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

$pdo = null;
$db_error = null;

// محاولة الاتصال بـ localhost أولاً ثم 127.0.0.1 كبديل فوري
$hosts_to_try = array_unique([$db_host, 'localhost', '127.0.0.1']);

foreach ($hosts_to_try as $h) {
    try {
        $dsn = "mysql:host={$h};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
        $db_host = $h;
        $db_error = null;
        break;
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}

/**
 * دالة مساعدة للحصول على كائن PDO والتحقق من سلامة الاتصال
 *
 * @return PDO
 * @throws Exception
 */
function getDBConnection(): PDO {
    global $pdo, $db_error, $db_host, $db_name;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    
    $msg = "تعذر الاتصال بقاعدة بيانات مشروع 'عصملي' ({$db_name}@{$db_host}).\n";
    $msg .= "السبب: " . ($db_error ?: 'غير محدد') . "\n";
    $msg .= "يرجى التأكد من تشغيل خادم MySQL في XAMPP واستيراد ملف schema.sql أو زيارة setup.php للتهيئة الآلية.";
    throw new Exception($msg);
}
