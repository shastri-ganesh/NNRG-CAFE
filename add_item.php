<?php
session_start();
include('conn_db.php');

if (!isset($_SESSION["cid"])) {
    header("location: cust_login.php");
    exit(1);
}

$f_id = $_POST["f_id"];
$s_id = $_POST["s_id"];
$c_id = $_SESSION["cid"];
$amount = $_POST["amount"];
$request = $_POST["request"];

// REMOVED: The problematic shop checking logic
// Now we support multi-shop carts!

// Check if this specific item (from this shop) already exists in cart
$cartsearch = "SELECT ct_amount FROM cart WHERE c_id = {$c_id} AND f_id = {$f_id} AND s_id = {$s_id}";
$cartsearch_result = $mysqli->query($cartsearch);
$cartsearch_row = $cartsearch_result->num_rows;

if ($cartsearch_row == 0) {
    // Item not in cart yet - add new item
    $insert_query = "INSERT INTO cart (c_id, s_id, f_id, ct_amount, ct_note) 
        VALUES ({$c_id},{$s_id},{$f_id},{$amount},'{$request}')";
    $atc_result = $mysqli->query($insert_query);
}
else {
    // Item already exists - update quantity
    $cartsearch_arr = $cartsearch_result->fetch_array();
    $incart_amount = $cartsearch_arr["ct_amount"];
    $new_amount = $incart_amount + $amount;
    $update_query = "UPDATE cart SET ct_amount = {$new_amount}, ct_note = '{$request}' 
                        WHERE c_id = {$c_id} AND f_id = {$f_id} AND s_id = {$s_id}";
    $atc_result = $mysqli->query($update_query);
}

if ($atc_result) {
    // If it's a specials item, redirect to cart instead of shop menu
    if ($s_id == 10) {
        header("location: cust_cart.php?atc=1");
    }
    else {
        header("location: shop_menu.php?s_id={$s_id}&atc=1");
    }
    exit(1);
}
else {
    header("location: shop_menu.php?s_id={$s_id}&atc=0");
    exit(1);
}
?>