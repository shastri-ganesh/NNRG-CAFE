<?php
session_start();
include("../conn_db.php");

// Check if user is admin
if($_SESSION["utype"] != "ADMIN"){
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Check if request is POST and has required data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['f_id']) && isset($_POST['status'])) {
    $f_id = intval($_POST['f_id']);
    $status = intval($_POST['status']);
    
    // Validate status (should be 0 or 1)
    if ($status !== 0 && $status !== 1) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Invalid status']));
    }
    
    // Update food stock status
    $update_query = "UPDATE food SET f_stock_status = ? WHERE f_id = ?";
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param("ii", $status, $f_id);
    
    if ($stmt->execute()) {
        $status_text = $status ? 'In Stock' : 'Out of Stock';
        echo json_encode([
            'success' => true, 
            'message' => 'Status updated successfully',
            'new_status' => $status,
            'status_text' => $status_text
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$mysqli->close();
?>