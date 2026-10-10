<?php
/**
 * feedback.php - نقطة نهاية خاصة لاستقبال تقييمات وملاحظات الزبائن عبر AJAX
 * Private Customer Feedback Submission Endpoint
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'طريقة الطلب غير مسموح بها.']);
    exit;
}

require_once __DIR__ . '/db.php';

// قراءة البيانات سواء كانت JSON أو POST عادي
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

$rating = 0;
$comment = '';
$customer_name = '';

if (is_array($json_data)) {
    $rating = intval($json_data['rating'] ?? 0);
    $comment = trim($json_data['comment'] ?? '');
    $customer_name = trim($json_data['customer_name'] ?? '');
} else {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $customer_name = trim($_POST['customer_name'] ?? '');
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'يرجى اختيار تقييم من 1 إلى 5 نجوم.']);
    exit;
}

if (empty($comment)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'يرجى كتابة رأيك أو ملاحظتك.']);
    exit;
}

if (empty($customer_name)) {
    $customer_name = 'زبون عصملي';
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO customer_feedback (rating, comment, customer_name) VALUES (?, ?, ?)");
    $stmt->execute([$rating, $comment, $customer_name]);

    echo json_encode([
        'success' => true,
        'message' => 'شكراً جزيلاً لك! رأيك يهمنا ويسعدنا دائماً في عصملي ❤️'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'تعذر حفظ التقييم حالياً: ' . $e->getMessage()]);
}
