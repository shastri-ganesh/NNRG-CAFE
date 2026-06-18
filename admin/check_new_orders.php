<?php
// check_new_orders.php
session_start();
include("../conn_db.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

if($_SESSION["utype"]!="ADMIN" && $_SESSION["utype"]!="SUPERADMIN"){
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(["error" => "Access denied"]));
}

header('Content-Type: application/json');

// Get last check time from request
$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-1 hour'));

try {
    // Query to count new orders since last check
    $query = "SELECT COUNT(*) as new_orders_count, 
                     MAX(created_at) as latest_order_time,
                     NOW() as current_time
              FROM transaction 
              WHERE created_at > ?";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $last_check);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    // Also get details of new orders for notification
    $detail_query = "SELECT tid, name, order_cost, order_type 
                     FROM transaction 
                     WHERE created_at > ? 
                     ORDER BY created_at DESC 
                     LIMIT 5";
    
    $detail_stmt = $mysqli->prepare($detail_query);
    $detail_stmt->bind_param("s", $last_check);
    $detail_stmt->execute();
    $detail_result = $detail_stmt->get_result();
    
    $new_orders = [];
    while($row = $detail_result->fetch_assoc()) {
        $new_orders[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'new_orders' => (int)$data['new_orders_count'],
        'latest_order_time' => $data['latest_order_time'],
        'current_time' => $data['current_time'],
        'order_details' => $new_orders
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>