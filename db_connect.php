<?php
/**
 * db_connect.php - نقطة اتصال موحدة بقاعدة البيانات (PDO & mysqli)
 * Osmanli Menu Project (1854)
 */

require_once __DIR__ . '/db.php';

// توفير كائن mysqli للتوافقية الكاملة إذا تطلبه أي كود
$conn = null;
try {
    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
    if ($conn->connect_error) {
        $alt_host = ($db_host === 'localhost') ? '127.0.0.1' : 'localhost';
        $conn = @new mysqli($alt_host, $db_user, $db_pass, $db_name, (int)$db_port);
    }
    if ($conn && !$conn->connect_error) {
        $conn->set_charset('utf8mb4');
    }
} catch (Throwable $e) {}
