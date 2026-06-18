<?php
session_start();
include("conn_db.php");

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION["cid"])){
    echo json_encode(['count' => 0]);
    exit;
}

$c_id = (int)$_SESSION["cid"];

// Get cart count with proper error handling
$cart_query = "SELECT COALESCE(SUM(ct_amount), 0) AS incart_amt FROM cart WHERE c_id = {$c_id}";
$cart_result = $mysqli->query($cart_query);

if($cart_result && $cart_result->num_rows > 0){
    $cart_row = $cart_result->fetch_array();
    $cart_count = (int)$cart_row["incart_amt"];
} else {
    $cart_count = 0;
}

// Return JSON response
echo json_encode([
    'count' => $cart_count,
    'success' => true
]);

$mysqli->close();
?>