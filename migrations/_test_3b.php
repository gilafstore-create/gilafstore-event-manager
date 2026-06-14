<?php
// Phase 3B test: fires all 8 new event types + seeds EMAIL_SENT source
// LOCAL DEV ONLY — delete after validation
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    http_response_code(403); exit('forbidden');
}
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../includes/em_dispatcher.php';

$results = [];

// 1. PRODUCT_CREATED
em_dispatch('PRODUCT_CREATED', [
    'product_id'  => 42,
    'name'        => 'Organic Turmeric Powder 500g',
    'category_id' => 3,
    'price'       => 349.00,
    'stock'       => 200,
]);
$results[] = 'PRODUCT_CREATED';

// 2. PRODUCT_UPDATED
em_dispatch('PRODUCT_UPDATED', [
    'product_id'  => 42,
    'name'        => 'Organic Turmeric Powder 500g',
    'category_id' => 3,
    'price'       => 299.00,
]);
$results[] = 'PRODUCT_UPDATED';

// 3. CUSTOMER_UPDATED
em_dispatch('CUSTOMER_UPDATED', [
    'user_id' => 4,
    'name'    => 'Gilaf Test User',
    'email'   => 'test@gilafstore.com',
    'phone'   => '9876543210',
]);
$results[] = 'CUSTOMER_UPDATED';

// 4. ORDER_CANCELLED
em_dispatch('ORDER_CANCELLED', [
    'order_id'   => 1002,
    'old_status' => 'pending',
    'reason'     => 'Customer requested cancellation',
    'changed_by' => 4,
]);
$results[] = 'ORDER_CANCELLED';

// 5. WEBHOOK_SENT
em_dispatch('WEBHOOK_SENT', [
    'webhook_event'      => 'payment.captured',
    'razorpay_order_id'  => 'order_test_3b_001',
    'razorpay_payment_id'=> 'pay_test_3b_001',
    'internal_order_id'  => 1002,
    'status'             => 'captured',
]);
$results[] = 'WEBHOOK_SENT';

// 6. WEBHOOK_FAILED
em_dispatch('WEBHOOK_FAILED', [
    'webhook_event'     => 'payment.failed',
    'razorpay_order_id' => 'order_test_3b_002',
    'error_message'     => 'DB connection timeout during webhook processing',
]);
$results[] = 'WEBHOOK_FAILED';

// 7. EMAIL_SENT
em_dispatch('EMAIL_SENT', [
    'task_key' => 'order_confirmation',
    'to'       => 'customer@example.com',
    'subject'  => 'Your Gilaf Store Order Confirmed!',
    'from'     => 'gilaffoods@gmail.com',
]);
$results[] = 'EMAIL_SENT';

// 8. EMAIL_FAILED
em_dispatch('EMAIL_FAILED', [
    'task_key' => 'order_shipped',
    'to'       => 'invalid@badomain.xyz',
    'subject'  => 'Your Order Has Shipped',
    'from'     => 'gilaffoods@gmail.com',
]);
$results[] = 'EMAIL_FAILED';

$count = $pdo->query("SELECT COUNT(*) FROM em_event_logs")->fetchColumn();
$types = $pdo->query("SELECT DISTINCT event_type FROM em_event_logs ORDER BY event_type")->fetchAll(PDO::FETCH_COLUMN);

echo "<pre style='font-family:monospace;font-size:14px;'>";
echo "Phase 3B Sample Events\n";
echo str_repeat("=", 40) . "\n";
foreach ($results as $r) { echo "  \xE2\x9C\x93 $r\n"; }
echo "\nem_event_logs total rows: $count\n\n";
echo "Distinct event types:\n";
foreach ($types as $t) { echo "  - $t\n"; }
echo "</pre>";
