<?php
header('Content-Type: application/json');
session_start();
include("conn_db.php");

// Check if user is logged in
if (!isset($_SESSION['cid'])) {
    echo json_encode([
        'error' => true,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if transaction ID is provided
if (!isset($_POST['tid']) || empty(trim($_POST['tid']))) {
    echo json_encode([
        'error' => true,
        'message' => 'Transaction ID is required'
    ]);
    exit;
}

$transaction_id = trim($_POST['tid']);

try {
    // Check if transaction ID already exists
    $check_query = "SELECT COUNT(*) as count FROM order_header WHERE t_id = ?";
    $stmt = $mysqli->prepare($check_query);
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("s", $transaction_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'error' => false,
        'exists' => ($row['count'] > 0),
        'message' => ($row['count'] > 0) ? 'Transaction ID already exists' : 'Transaction ID is available'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>