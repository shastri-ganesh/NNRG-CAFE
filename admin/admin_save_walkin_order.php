<?php
// admin_save_walkin_order.php - FINAL FIXED VERSION
session_start();
include("../conn_db.php");

if ($_SESSION["utype"] != "ADMIN") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'message' => '', 'tid' => null, 'token_number' => null];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    // Validate required fields
    if (empty($input['cart_items']) || !is_array($input['cart_items'])) {
        throw new Exception('No items in cart');
    }

    if (!isset($input['total_amount']) || $input['total_amount'] <= 0) {
        throw new Exception('Invalid total amount');
    }

    // Prepare order data
    $customer_name = !empty($input['customer_name']) ? $input['customer_name'] : 'Walk-in Customer';
    $total_amount = floatval($input['total_amount']);
    $order_type = !empty($input['order_type']) ? $input['order_type'] : 'takeaway';
    
    // Generate unique order ID and token
    $tid = 'WLK_' . date('YmdHis') . rand(100, 999);
    $token_number = rand(100, 999);

    // Start transaction
    $mysqli->begin_transaction();

    // Insert into transaction table
    $order_sql = "INSERT INTO transaction 
                  (tid, c_id, order_cost, name, email, rollno, order_status, order_type, order_source, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $order_stmt = $mysqli->prepare($order_sql);
    
    // Walk-in order parameters
    $c_id = null; // No customer ID for walk-in
    $email = 'counter@nnrgcafe.com';
    $rollno = 'Counter';
    $order_status = 'FNSH'; // Walk-in orders are instantly completed
    $order_source = 'walkin'; // THIS MARKS IT AS WALK-IN ORDER!
    
    $order_stmt->bind_param('ssdssssss', 
        $tid, $c_id, $total_amount, $customer_name, $email, $rollno, 
        $order_status, $order_type, $order_source
    );
    
    if (!$order_stmt->execute()) {
        throw new Exception('Failed to save order: ' . $order_stmt->error);
    }

    // CORRECT COLUMN NAMES: tid, f_id, quantity, unit_price, total_price
    $item_sql = "INSERT INTO transaction_items (tid, f_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
    $item_stmt = $mysqli->prepare($item_sql);

    if (!$item_stmt) {
        throw new Exception('Cannot prepare item statement: ' . $mysqli->error);
    }

    foreach ($input['cart_items'] as $item) {
        $f_id = intval($item['id']); // Food item ID - CORRECT COLUMN: f_id
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['price']);
        $total_price = $unit_price * $quantity;
        
        $item_stmt->bind_param('siidd', $tid, $f_id, $quantity, $unit_price, $total_price);
        
        if (!$item_stmt->execute()) {
            throw new Exception('Failed to save order items: ' . $item_stmt->error);
        }
    }

    // Commit transaction
    $mysqli->commit();

    // Success response
    $response['success'] = true;
    $response['message'] = 'Walk-in order saved successfully';
    $response['tid'] = $tid;
    $response['token_number'] = $token_number;

} catch (Exception $e) {
    // Rollback on error
    if (isset($mysqli)) {
        $mysqli->rollback();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>