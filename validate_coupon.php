<?php
session_start();
include("conn_db.php");

header('Content-Type: application/json');

// Debug logging function
function logCouponDebug($message)
{
    file_put_contents(__DIR__ . '/coupon_debug.log',
        "[COUPON] " . date('Y-m-d H:i:s') . " - " . $message . PHP_EOL,
        FILE_APPEND);
}

// Log which database we're connected to
$db_info = $mysqli->query("SELECT DATABASE() as db_name");
$db_row = $db_info->fetch_assoc();
logCouponDebug("Connected to database: " . $db_row['db_name']);

if (!isset($_SESSION['cid'])) {
    logCouponDebug("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit();
}

$customer_id = $_SESSION['cid'];
$data = json_decode(file_get_contents('php://input'), true);
$code = isset($data['code']) ? strtoupper(trim($data['code'])) : '';
$base_cost = isset($data['base_cost']) ? floatval($data['base_cost']) : 0;

logCouponDebug("Validating coupon '$code' for customer_id=$customer_id, base_cost=$base_cost");

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit();
}

// Check coupon in database
$stmt = $mysqli->prepare("SELECT coupon_id, discount_amount, min_order_amount FROM coupons WHERE coupon_code = ? AND status = 'active'");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $coupon = $result->fetch_assoc();
    logCouponDebug("Coupon found: coupon_id=" . $coupon['coupon_id'] . ", discount=" . $coupon['discount_amount']);

    // Log ALL rows in coupon_usage for this coupon (diagnostic)
    $all_usage = $mysqli->query("SELECT * FROM coupon_usage WHERE coupon_id = " . intval($coupon['coupon_id']));
    logCouponDebug("Total usage rows for coupon_id=" . $coupon['coupon_id'] . ": " . $all_usage->num_rows);
    while ($u = $all_usage->fetch_assoc()) {
        logCouponDebug("  Usage row: id=" . $u['id'] . ", coupon_id=" . $u['coupon_id'] . ", c_id=" . $u['c_id']);
    }

    // Check if this user already used this coupon
    $usage_stmt = $mysqli->prepare("SELECT id FROM coupon_usage WHERE coupon_id = ? AND c_id = ?");
    $usage_stmt->bind_param("ii", $coupon['coupon_id'], $customer_id);
    $usage_stmt->execute();
    $usage_result = $usage_stmt->get_result();

    logCouponDebug("Usage check for coupon_id=" . $coupon['coupon_id'] . " AND c_id=$customer_id: found " . $usage_result->num_rows . " rows");

    if ($usage_result->num_rows > 0) {
        logCouponDebug("BLOCKED: Customer already used this coupon");
        echo json_encode(['success' => false, 'message' => "You've already used this coupon."]);
        $usage_stmt->close();
        $stmt->close();
        exit();
    }
    $usage_stmt->close();

    if ($base_cost >= $coupon['min_order_amount']) {
        logCouponDebug("APPROVED: Coupon valid, returning coupon_id=" . intval($coupon['coupon_id']));
        echo json_encode([
            'success' => true,
            'discount' => floatval($coupon['discount_amount']),
            'coupon_id' => intval($coupon['coupon_id']),
            'message' => "Coupon '{$code}' applied! You saved ₹" . number_format($coupon['discount_amount'], 2)
        ]);
    }
    else {
        logCouponDebug("REJECTED: Min order not met");
        echo json_encode([
            'success' => false,
            'message' => "Minimum order of ₹" . number_format($coupon['min_order_amount'], 2) . " required for this coupon."
        ]);
    }
}
else {
    logCouponDebug("Coupon code '$code' not found or inactive");
    echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code.']);
}
$stmt->close();
?>