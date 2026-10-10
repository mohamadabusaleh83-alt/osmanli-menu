<?php
/**
 * logout.php - تسجيل الخروج وتدمير الجلسة بالكامل
 * Osmanli Menu Project - Admin Logout
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. تفريغ مصفوفة الجلسة
$_SESSION = [];

// 2. حذف ملف تعريف ارتباط الجلسة (Session Cookie)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. تدمير الجلسة في الخادم
session_destroy();

// 4. التحويل إلى صفحة تسجيل الدخول مع رسالة تأكيد
header("Location: login.php?msg=logged_out");
exit;
