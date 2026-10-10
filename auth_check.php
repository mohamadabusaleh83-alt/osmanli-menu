<?php
/**
 * auth_check.php - وسيط حماية الصفحات والتحقق من صلاحيات المشرف
 * Osmanli Menu Project - Admin Authentication & Authorization Middleware
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من وجود الجلسة وصلاحية المشرف (role === 'admin')
$is_authenticated = isset($_SESSION['user_id']) 
    && !empty($_SESSION['role']) 
    && $_SESSION['role'] === 'admin';

if (!$is_authenticated) {
    // في حال كانت العملية استدعاء برمجياً (AJAX / Fetch API)
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذه العملية. يرجى تسجيل الدخول كمسؤول أولاً.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // حفظ الصفحة المطلوبة للرجوع إليها بعد تسجيل الدخول (اختياري)
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // التحويل إلى صفحة تسجيل الدخول
    header("Location: login.php?msg=auth_required");
    exit;
}
