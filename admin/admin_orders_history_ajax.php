<?php
// admin_orders_history_ajax.php
session_start();
include("../conn_db.php");

if ($_SESSION["utype"] != "ADMIN") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'orders' => [], 'stats' => []];

try {
    if (isset($_GET['action']) && $_GET['action'] == 'get_orders') {
        // Get filter parameters
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        $status = $_GET['status'] ?? 'all';
        $order_type = $_GET['order_type'] ?? 'all';

        // Build WHERE conditions - FILTER FOR WALK-IN ORDERS
        $where_conditions = [
            "DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'",
            "t.order_source = 'walkin'"  // ONLY SHOW WALK-IN ORDERS
        ];

        // Status filter
        if ($status != 'all') {
            $where_conditions[] = "t.order_status = '$status'";
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Get orders from transaction table
        $orders_sql = "SELECT t.* FROM transaction t WHERE $where_clause ORDER BY t.created_at DESC";
        $orders_result = $mysqli->query($orders_sql);

        $orders = [];
        while ($order = $orders_result->fetch_assoc()) {
            // Get order items from transaction_items
            $items_sql = "SELECT ti.*, f.f_name 
                         FROM transaction_items ti 
                         LEFT JOIN food f ON ti.f_id = f.f_id 
                         WHERE ti.tid = '{$order['tid']}'";
            $items_result = $mysqli->query($items_sql);
            
            $order['items'] = [];
            while ($item = $items_result->fetch_assoc()) {
                $order['items'][] = $item;
            }
            
            $orders[] = $order;
        }

        // Get statistics for walk-in orders
        $stats_sql = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(t.order_cost) as total_revenue,
                        AVG(t.order_cost) as avg_order_value,
                        SUM(CASE WHEN t.order_status = 'FNSH' THEN 1 ELSE 0 END) as completed_orders
                      FROM transaction t 
                      WHERE $where_clause";
        
        $stats_result = $mysqli->query($stats_sql);
        $stats = $stats_result->fetch_assoc();

        $response['success'] = true;
        $response['orders'] = $orders;
        $response['stats'] = $stats;
        $response['message'] = count($orders) . ' walk-in orders found';

    } else {
        $response['message'] = 'Invalid action';
    }

} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Orders History Error: " . $e->getMessage());
}

echo json_encode($response);
?>