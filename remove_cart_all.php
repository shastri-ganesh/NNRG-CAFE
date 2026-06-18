<?php
    session_start();
    include('conn_db.php');

    if(!isset($_SESSION["cid"])){
        header("location: cust_login.php");
        exit(1);
    }

    if(!isset($_GET["rmv"])){
        header("location: cust_cart.php");
        exit(1);
    }

    $c_id = $_SESSION["cid"];

    // Delete ALL items from cart for this customer (from all shops)
    $delete_query = "DELETE FROM cart WHERE c_id = {$c_id}";
    $delete_result = $mysqli->query($delete_query);

    if($delete_result){
        header("location: cust_cart.php?rmv_crt=1");
    } else {
        header("location: cust_cart.php?rmv_crt=0");
    }
    exit(1);
?>