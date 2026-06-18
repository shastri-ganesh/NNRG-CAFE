<?php
session_start();
if ($_SESSION["utype"] != "ADMIN" && $_SESSION["utype"] != "SHOP_ADMIN") {
    header("location: ../restricted.php");
    exit(1);
}
include('../conn_db.php');
$f_id = $_GET["f_id"];

// Security check for SHOP_ADMIN
if (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') {
    $check_query = "SELECT s_id FROM food WHERE f_id = '{$f_id}' LIMIT 1";
    $check_result = $mysqli->query($check_query);
    if ($check_result && $check_result->num_rows > 0) {
        $row = $check_result->fetch_array();
        if ($row['s_id'] != $_SESSION['shop_id']) {
            header("location: ../restricted.php");
            exit(1);
        }
    }
}

//DISABLE FOOD ITEM INSTEAD OF DELETE IT
$delete_query = "DELETE FROM food WHERE f_id = '{$f_id}';";
$delete_result = $mysqli->query($delete_query);
if ($delete_result) {
    header("location: admin_food_list.php?dsb_fdt=1");
}
else {
    header("location: admin_food_list.php?dsb_fdt=0");
}
?>