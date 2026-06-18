<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
session_start();
include("../conn_db.php");
include('../head.php');
if ($_SESSION['utype'] != 'ADMIN' && $_SESSION['utype'] != 'SHOP_ADMIN' && $_SESSION['utype'] != 'SUPERADMIN') {
    header("location: ../restricted.php");
    exit(1);
}
?>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../img/Color Icon with background.png" rel="icon">
    <link href="../css/main.css" rel="stylesheet">
    <link href="../css/main1.css" rel="stylesheet">
    <title>Admin Dashboard | NNRG-CÁFE</title>
</head>

<body class="d-flex flex-column">

    <?php include('nav_header_admin.php')?>

    <div class="d-flex text-center text-white promo-banner-bg py-3">
        <div class="p-lg-2 mx-auto my-3">
            <?php if (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN'): ?>
            <h1 class="display-5 fw-normal"><i class="bi bi-shop me-2"></i>
                <?= htmlspecialchars($_SESSION['shop_name'])?>
            </h1>
            <p class="lead fw-normal">Shop Admin Dashboard</p>
            <?php
else: ?>
            <h1 class="display-5 fw-normal">ADMIN DASHBOARD</h1>
            <p class="lead fw-normal">Food ordering system of NNRG</p>
            <?php
endif; ?>
        </div>
    </div>

    <div class="container p-5" id="admin-dashboard">

        <h2 class="border-bottom pb-2"><i class="bi bi-graph-up"></i> System Status</h2>

        <!-- ADMIN GRID DASHBOARD -->
        <div class="row row-cols-1 row-cols-lg-2 align-items-stretch g-4 py-3">

            <?php if (!isset($_SESSION['utype']) || $_SESSION['utype'] === 'ADMIN' || $_SESSION['utype'] === 'SUPERADMIN'): ?>
            <!-- GRID OF CUSTOMER (ADMIN ONLY) -->
            <div class="col">
                <a href="admin_customer_list.php" class="text-decoration-none text-dark">
                    <div class="card rounded-5 border-danger p-2">
                        <div class="card-body">
                            <h4 class="card-title">
                                <i class="bi bi-person-fill"></i>
                                Customer
                            </h4>
                            <p class="card-text my-2">
                                <span class="h5">
                                    <?php
    try {
        $cust_query = "SELECT COUNT(*) AS cnt FROM customer";
        $result = $mysqli->query($cust_query);
        if ($result) {
            $cust_arr = $result->fetch_array();
            echo $cust_arr["cnt"] ? $cust_arr["cnt"] : "0";
        }
        else {
            echo "0";
        }
    }
    catch (Exception $e) {
        echo "0";
    }
?>
                                </span>
                                customer(s) in the system
                            </p>
                            <div class="text-end">
                                <a href="admin_customer_list.php" class="btn btn-sm btn-outline-dark">Go to Customer
                                    List</a>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <!-- END GRID OF CUSTOMER -->

            <!-- GRID OF SHOP (ADMIN ONLY) -->
            <div class="col">
                <a href="admin_shop_list.php" class="text-decoration-none text-dark">
                    <div class="card rounded-5 border-success p-2">
                        <div class="card-body">
                            <h4 class="card-title">
                                <i class="bi bi-shop"></i>
                                Food Shop
                            </h4>
                            <p class="card-text my-2">
                                <span class="h5">
                                    <?php
    try {
        $admin_filter = ($_SESSION["utype"] == "SUPERADMIN") ? "" : " WHERE s_id <= 10";
        $shop_query = "SELECT COUNT(*) AS cnt FROM shop{$admin_filter}";
        $result = $mysqli->query($shop_query);
        if ($result) {
            $shop_arr = $result->fetch_array();
            echo $shop_arr["cnt"] ? $shop_arr["cnt"] : "0";
        }
        else {
            echo "0";
        }
    }
    catch (Exception $e) {
        echo "0";
    }
?>
                                </span>
                                food shop(s) in the system
                            </p>
                            <div class="text-end">
                                <a href="admin_shop_list.php" class="btn btn-sm btn-outline-dark">Go to Food Shop
                                    List</a>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <!-- END GRID OF SHOP -->
            <?php
endif; ?>

            <!-- GRID OF FOOD -->
            <div class="col">
                <a href="admin_food_list.php" class="text-decoration-none text-dark">
                    <div class="card rounded-5 border-primary p-2">
                        <div class="card-body">
                            <h4 class="card-title">
                                <i class="bi bi-card-list"></i>
                                Menu
                            </h4>
                            <p class="card-text my-2">
                                <span class="h5">
                                    <?php
try {
    if (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') {
        $food_query = "SELECT COUNT(*) AS cnt FROM food WHERE s_id = " . intval($_SESSION['shop_id']);
    }
    else {
        $food_query = "SELECT COUNT(*) AS cnt FROM food";
    }
    $result = $mysqli->query($food_query);
    if ($result) {
        $food_arr = $result->fetch_array();
        echo $food_arr["cnt"] ? $food_arr["cnt"] : "0";
    }
    else {
        echo "0";
    }
}
catch (Exception $e) {
    echo "0";
}
?>
                                </span>
                                menu(s) in
                                <?php echo (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') ? 'your shop' : 'the system'; ?>
                            </p>
                            <div class="text-end">
                                <a href="admin_food_list.php" class="btn btn-sm btn-outline-dark">Go to Menu List</a>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <!-- END GRID OF FOOD -->

            <!-- GRID OF ORDER -->
            <div class="col">
                <a href="admin_order_list.php" class="text-decoration-none text-dark">
                    <div class="card rounded-5 border-warning p-2">
                        <div class="card-body">
                            <h4 class="card-title">
                                <i class="bi bi-card-list"></i>
                                Order
                            </h4>
                            <p class="card-text my-2">
                                <span class="h5">
                                    <?php
try {
    if (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') {
        $shop_id = intval($_SESSION['shop_id']);
        $order_query = "SELECT COUNT(DISTINCT t.tid) AS cnt FROM transaction t JOIN transaction_items ti ON t.tid = ti.tid JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = $shop_id";
    }
    else {
        // Exclude bakery orders (s_id = 11) for main ADMIN
        $order_query = "SELECT COUNT(DISTINCT t.tid) AS cnt FROM transaction t WHERE t.tid NOT IN (SELECT DISTINCT ti.tid FROM transaction_items ti JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = 11)";
    }
    $result = $mysqli->query($order_query);
    if ($result) {
        $order_arr = $result->fetch_array();
        echo $order_arr["cnt"] ? $order_arr["cnt"] : "0";
    }
    else {
        echo "0";
    }
}
catch (Exception $e) {
    echo "0";
}
?>
                                </span>
                                order(s)
                                <?php echo (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') ? 'for your shop' : 'in the system'; ?>
                            </p>
                            <div class="text-end">
                                <a href="admin_order_list.php" class="btn btn-sm btn-outline-dark">Go to Order List</a>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <!-- END GRID OF ORDER -->

        </div>
        <!-- END ADMIN GRID DASHBOARD -->

    </div>
    <?php include('admin_footer.php')?>
</body>

</html>