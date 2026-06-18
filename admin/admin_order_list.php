<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../conn_db.php");
include('../head.php');

if ($_SESSION["utype"] != "ADMIN" && $_SESSION["utype"] != "SHOP_ADMIN") {
    header("location: ../restricted.php");
    exit(1);
}

// Handle delete order request FIRST - before any HTML output
$delete_message = "";
$delete_success = false;

if (isset($_GET["delete_order"]) && isset($_GET["tid"]) && $_GET["delete_order"] == "1") {

    $tid_to_delete = mysqli_real_escape_string($mysqli, $_GET["tid"]);

    // Check if mysqli connection exists
    if (!$mysqli || $mysqli->connect_error) {
        $delete_message = "Database connection failed";
        $delete_success = false;
    }
    else {
        // Start transaction for data integrity
        $mysqli->autocommit(FALSE);

        try {
            // First check if the order exists
            $check_query = "SELECT tid FROM transaction WHERE tid = ?";
            $check_stmt = $mysqli->prepare($check_query);

            if (!$check_stmt) {
                throw new Exception("Prepare statement failed: " . $mysqli->error);
            }

            $check_stmt->bind_param("s", $tid_to_delete);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows == 0) {
                throw new Exception("Order not found");
            }

            // Delete from transaction_items first (due to foreign key constraints)
            $delete_items = "DELETE FROM transaction_items WHERE tid = ?";
            $stmt1 = $mysqli->prepare($delete_items);

            if (!$stmt1) {
                throw new Exception("Prepare statement failed for transaction_items: " . $mysqli->error);
            }

            $stmt1->bind_param("s", $tid_to_delete);
            if (!$stmt1->execute()) {
                throw new Exception("Failed to delete transaction items: " . $stmt1->error);
            }

            // Then delete from transaction table
            $delete_transaction = "DELETE FROM transaction WHERE tid = ?";
            $stmt2 = $mysqli->prepare($delete_transaction);

            if (!$stmt2) {
                throw new Exception("Prepare statement failed for transaction: " . $mysqli->error);
            }

            $stmt2->bind_param("s", $tid_to_delete);
            if (!$stmt2->execute()) {
                throw new Exception("Failed to delete transaction: " . $stmt2->error);
            }

            // Check if any rows were actually deleted
            if ($stmt2->affected_rows == 0) {
                throw new Exception("No rows were deleted - order may not exist");
            }

            // Commit transaction
            $mysqli->commit();
            $delete_success = true;
            $delete_message = "Order deleted successfully";

            // Close statements
            if ($check_stmt)
                $check_stmt->close();
            if ($stmt1)
                $stmt1->close();
            if ($stmt2)
                $stmt2->close();

        }
        catch (Exception $e) {
            // Rollback on error
            $mysqli->rollback();
            $delete_success = false;
            $delete_message = $e->getMessage();

            // Close statements if they exist
            if (isset($check_stmt) && $check_stmt)
                $check_stmt->close();
            if (isset($stmt1) && $stmt1)
                $stmt1->close();
            if (isset($stmt2) && $stmt2)
                $stmt2->close();
        }

        // Re-enable autocommit
        $mysqli->autocommit(TRUE);
    }
}

// Get current order count for JavaScript
$query_count = "SELECT COUNT(*) as order_count FROM transaction";
$result_count = $mysqli->query($query_count);
$row_count = $result_count->fetch_assoc();
$current_order_count = $row_count['order_count'];
?>

<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../img/ICON_F.png" rel="icon">
    <link href="../css/main.css" rel="stylesheet">
    <link href="../img/Color Icon with background.png" rel="icon">
    <title>Order List | NNRG-CÁFE</title>
</head>

<body class="d-flex flex-column h-100">

    <?php include('nav_header_admin.php')?>

    <div class="container p-2 pb-0" id="admin-dashboard">
        <div class="mt-4 border-bottom">
            <a class="nav nav-item text-decoration-none text-muted mb-2" href="#" onclick="history.back();">
                <i class="bi bi-arrow-left-square me-2"></i>Go back
            </a>

            <?php
// Show delete notifications using the variables set above
if (!empty($delete_message)) {
    if ($delete_success) {
?>
            <!-- START SUCCESSFULLY DELETED ORDER -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                    <i class="bi bi-check-circle ms-2"></i>
                    <span class="ms-2 mt-2">
                        <?php echo htmlspecialchars($delete_message); ?>
                    </span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_order_list.php">X</a></span>
                </div>
            </div>
            <!-- END SUCCESSFULLY DELETED ORDER -->
            <?php
    }
    else {
?>
            <!-- START FAILED DELETE ORDER -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                    <i class="bi bi-x-circle ms-2"></i>
                    <span class="ms-2 mt-2">Failed to delete order. Error:
                        <?php echo htmlspecialchars($delete_message); ?>
                    </span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_order_list.php">X</a></span>
                </div>
            </div>
            <!-- END FAILED DELETE ORDER -->
            <?php
    }
}

// Show other notifications (keeping the old ones for compatibility)
if (isset($_GET["delete_success"]) && $_GET["delete_success"] == "1") {
?>
            <!-- START SUCCESSFULLY DELETED ORDER -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                    <i class="bi bi-check-circle ms-2"></i>
                    <span class="ms-2 mt-2">Order deleted successfully.</span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_order_list.php">X</a></span>
                </div>
            </div>
            <!-- END SUCCESSFULLY DELETED ORDER -->
            <?php
}

if (isset($_GET["delete_error"])) {
?>
            <!-- START FAILED DELETE ORDER -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                    <i class="bi bi-x-circle ms-2"></i>
                    <span class="ms-2 mt-2">Failed to delete order. Error:
                        <?php echo htmlspecialchars($_GET['delete_error']); ?>
                    </span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_order_list.php">X</a></span>
                </div>
            </div>
            <!-- END FAILED DELETE ORDER -->
            <?php
}

// Original status update notifications
if (isset($_GET["up_ods"])) {
    if ($_GET["up_ods"] == 1) {
?>
            <!-- START SUCCESSFULLY UPDATE ORDER STATUS -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                    <i class="bi bi-check-circle ms-2"></i>
                    <span class="ms-2 mt-2">Successfully updated order status.</span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_order_list.php">X</a></span>
                </div>
            </div>
            <!-- END SUCCESSFULLY UPDATE ORDER STATUS -->
            <?php
    }
    else { ?>
            <!-- START FAILED UPDATE ORDER STATUS -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                    <i class="bi bi-x-circle ms-2"></i><span class="ms-2 mt-2">Failed to update order status.</span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_order_list.php">X</a></span>
                </div>
            </div>
            <!-- END FAILED UPDATE ORDER STATUS -->
            <?php
    }
}

// Email notification success message
if (isset($_GET["email_sent"]) && $_GET["email_sent"] == 1) {
?>
            <!-- EMAIL NOTIFICATION SUCCESS -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                    <i class="bi bi-envelope-check ms-2"></i>
                    <span class="ms-2 mt-2">Order status updated and customer notified via email! 📧</span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_order_list.php">X</a></span>
                </div>
            </div>
            <?php
}

// Email notification failed message
if (isset($_GET["email_failed"]) && $_GET["email_failed"] == 1) {
?>
            <!-- EMAIL NOTIFICATION FAILED -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-warning text-dark rounded text-start">
                    <i class="bi bi-envelope-x ms-2"></i>
                    <span class="ms-2 mt-2">Order status updated but email notification failed to send.</span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-dark"
                            href="admin_order_list.php">X</a></span>
                </div>
            </div>
            <?php
}
?>

            <h2 class="pt-3 display-6">Order List</h2>
            <form class="form-floating mb-3" method="GET" action="admin_order_list.php">
                <div class="row g-2">
                    <div class="col">
                        <select class="form-select" id="c_id" name="c_id">
                            <option selected value="">Customer Name</option>
                            <?php
$option_query = "SELECT DISTINCT t.c_id, t.name
                                FROM transaction t ORDER BY t.name;";
$option_result = $mysqli->query($option_query);
$opt_row = $option_result->num_rows;
if ($option_result->num_rows != 0) {
    while ($option_arr = $option_result->fetch_array()) {
?>
                            <option value="<?php echo $option_arr[" c_id"]?>"
                                <?php if (isset($_GET["c_id"]) && $_GET["c_id"] == $option_arr["c_id"]) {
            echo "selected";
        }?>>
                                <?php echo $option_arr["name"]?>
                            </option>
                            <?php
    }
}
?>
                        </select>
                    </div>

                    <div class="col">
                        <select class="form-select" id="orderstatus" name="os">
                            <?php if (isset($_GET["search"])) { ?>
                            <option selected value="">Order Status</option>
                            <option value="VRFY" <?php if ($_GET["os"] == "VRFY" ) {
        echo "selected" ;
    }?>>Order Verifying
                            </option>
                            <option value="ACPT" <?php if ($_GET["os"] == "ACPT" ) {
        echo "selected" ;
    }?>>Order Accepted
                            </option>
                            <option value="PREP" <?php if ($_GET["os"] == "PREP" ) {
        echo "selected" ;
    }?>>Order Preparing
                            </option>
                            <option value="RDPK" <?php if ($_GET["os"] == "RDPK" ) {
        echo "selected" ;
    }?>>Ready for Pick-Up
                            </option>
                            <option value="FNSH" <?php if ($_GET["os"] == "FNSH" ) {
        echo "selected" ;
    }?>>Order Finished
                            </option>
                            <option value="CNCL" <?php if ($_GET["os"] == "CNCL" ) {
        echo "selected" ;
    }?>>Order Cancelled
                            </option>
                            <?php
}
else { ?>
                            <option selected value="">Order Status</option>
                            <option value="VRFY">Order Verifying</option>
                            <option value="ACPT">Order Accepted</option>
                            <option value="PREP">Order Preparing</option>
                            <option value="RDPK">Ready for Pick-Up</option>
                            <option value="FNSH">Order Finished</option>
                            <option value="CNCL">Order Cancelled</option>
                            <?php
}?>
                        </select>
                    </div>
                    <div class="col">
                        <select class="form-select" id="ordertype" name="ot">
                            <?php if (isset($_GET["search"])) { ?>
                            <option selected value="">Order Type</option>
                            <option value="dine-in" <?php if (isset($_GET["ot"]) && $_GET["ot"] == "dine-in" ) {
        echo "selected" ;
    }?>>Dine-In</option>
                            <option value="takeaway" <?php if (isset($_GET["ot"]) && $_GET["ot"] == "takeaway" ) {
        echo "selected" ;
    }?>>Takeaway</option>
                            <?php
}
else { ?>
                            <option selected value="">Order Type</option>
                            <option value="dine-in">Dine-In</option>
                            <option value="takeaway">Takeaway</option>
                            <?php
}?>
                        </select>
                    </div>
                </div>

                <!-- Second Row for Filters -->
                <div class="row g-2 mt-2">
                    <!-- Date Filter Options -->
                    <div class="col">
                        <select class="form-select" id="datefilter" name="date_filter" onchange="toggleDateInputs()">
                            <?php if (isset($_GET["search"])) { ?>
                            <option selected value="">Date Filter</option>
                            <option value="today" <?php if (isset($_GET["date_filter"]) && $_GET["date_filter"] == "today"
                                ) {
        echo "selected" ;
    }?>>Today</option>
                            <option value="yesterday" <?php if (isset($_GET["date_filter"]) &&
                                $_GET["date_filter"] == "yesterday" ) {
        echo "selected" ;
    }?>>Yesterday</option>
                            <option value="last_7_days" <?php if (isset($_GET["date_filter"]) &&
                                $_GET["date_filter"] == "last_7_days" ) {
        echo "selected" ;
    }?>>Last 7 Days</option>
                            <option value="last_30_days" <?php if (isset($_GET["date_filter"]) &&
                                $_GET["date_filter"] == "last_30_days" ) {
        echo "selected" ;
    }?>>Last 30 Days</option>
                            <option value="custom_date" <?php if (isset($_GET["date_filter"]) &&
                                $_GET["date_filter"] == "custom_date" ) {
        echo "selected" ;
    }?>>Specific Date</option>
                            <option value="date_range" <?php if (isset($_GET["date_filter"]) &&
                                $_GET["date_filter"] == "date_range" ) {
        echo "selected" ;
    }?>>Date Range</option>
                            <?php
}
else { ?>
                            <option selected value="">Date Filter</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="last_7_days">Last 7 Days</option>
                            <option value="last_30_days">Last 30 Days</option>
                            <option value="custom_date">Specific Date</option>
                            <option value="date_range">Date Range</option>
                            <?php
}?>
                        </select>
                    </div>

                    <!-- Custom Date Input -->
                    <div class="col" id="custom_date_input"
                        style="display: <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] == 'custom_date') ? 'block' : 'none'; ?>;">
                        <input type="date" class="form-control" id="specific_date" name="specific_date"
                            value="<?php echo isset($_GET['specific_date']) ? $_GET['specific_date'] : ''; ?>"
                            max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="col-auto">
                        <button type="submit" name="search" value="1" class="btn btn-success" <?php
                            if ($opt_row == 0) {
    echo "disabled" ;
}?>>
                            <i class="bi bi-search me-1"></i>Search</button>
                        <button type="reset" class="btn btn-danger"
                            onclick="javascript: window.location='admin_order_list.php'">
                            <i class="bi bi-x-circle me-1"></i>Clear</button>
                    </div>
                </div>

                <!-- Date Range Inputs -->
                <div class="row g-2 mt-2" id="date_range_inputs"
                    style="display: <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] == 'date_range') ? 'flex' : 'none'; ?>;">
                    <div class="col">
                        <label for="start_date" class="form-label small text-muted">From Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date"
                            value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>"
                            max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col">
                        <label for="end_date" class="form-label small text-muted">To Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date"
                            value="<?php echo isset($_Get['end_date']) ? $_GET['end_date'] : ''; ?>"
                            max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col"></div>
                    <div class="col"></div>
                </div>
            </form>
        </div>
    </div>

    <?php
// Build query based on search parameters
$where_conditions = array();

if (isset($_GET["search"])) {
    if (!empty($_GET["c_id"])) {
        $where_conditions[] = "t.c_id = '{$_GET['c_id']}'";
    }
    if (!empty($_GET["os"])) {
        $where_conditions[] = "t.order_status = '{$_GET['os']}'";
    }
    if (!empty($_GET["ot"])) {
        $where_conditions[] = "t.order_type = '{$_GET['ot']}'";
    }

    // Date filtering logic
    if (!empty($_GET["date_filter"])) {
        $date_filter = $_GET["date_filter"];
        $current_date = date('Y-m-d');

        switch ($date_filter) {
            case 'today':
                $where_conditions[] = "DATE(t.created_at) = '$current_date'";
                break;

            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $where_conditions[] = "DATE(t.created_at) = '$yesterday'";
                break;

            case 'last_7_days':
                $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
                $where_conditions[] = "DATE(t.created_at) BETWEEN '$seven_days_ago' AND '$current_date'";
                break;

            case 'last_30_days':
                $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
                $where_conditions[] = "DATE(t.created_at) BETWEEN '$thirty_days_ago' AND '$current_date'";
                break;

            case 'custom_date':
                if (!empty($_GET["specific_date"])) {
                    $specific_date = $_GET["specific_date"];
                    $where_conditions[] = "DATE(t.created_at) = '$specific_date'";
                }
                break;

            case 'date_range':
                if (!empty($_GET["start_date"]) && !empty($_GET["end_date"])) {
                    $start_date = $_GET["start_date"];
                    $end_date = $_GET["end_date"];
                    $where_conditions[] = "DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";
                }
                break;
        }
    }
}

// For SHOP_ADMIN, only show orders that contain items from their shop
if (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') {
    $shop_id = intval($_SESSION['shop_id']);
    $where_conditions[] = "t.tid IN (SELECT DISTINCT ti.tid FROM transaction_items ti JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = $shop_id)";
} else {
    // For main ADMIN, hide bakery orders (s_id = 11)
    $where_conditions[] = "t.tid NOT IN (SELECT DISTINCT ti.tid FROM transaction_items ti JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = 11)";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$query = "SELECT t.id, t.tid, t.c_id, t.order_cost, t.name, t.email, t.rollno, 
                         t.pickup_time, t.pickup_notes, t.created_at, t.order_status, t.order_type,
                         t.points_used, t.coupon_discount
                  FROM transaction t 
                  $where_clause 
                  ORDER BY t.created_at DESC";

$result = $mysqli->query($query);
$numrow = $result->num_rows;
if ($numrow > 0) {
?>
    <div class="container align-items-stretch pt-2">
        <div class="table-responsive">
            <table class="table rounded-5 table-light table-striped table-hover align-middle caption-top mb-3">
                <caption>
                    <?php echo $numrow; ?> order(s) found
                    <?php
    // Show active date filter info
    if (isset($_GET["search"]) && !empty($_GET["date_filter"])) {
        $filter_text = "";
        switch ($_GET["date_filter"]) {
            case 'today':
                $filter_text = "for Today (" . date('F j, Y') . ")";
                break;
            case 'yesterday':
                $filter_text = "for Yesterday (" . date('F j, Y', strtotime('-1 day')) . ")";
                break;
            case 'last_7_days':
                $filter_text = "for Last 7 Days";
                break;
            case 'last_30_days':
                $filter_text = "for Last 30 Days";
                break;
            case 'custom_date':
                if (!empty($_GET["specific_date"])) {
                    $filter_text = "for " . date('F j, Y', strtotime($_GET["specific_date"]));
                }
                break;
            case 'date_range':
                if (!empty($_GET["start_date"]) && !empty($_GET["end_date"])) {
                    $filter_text = "from " . date('F j, Y', strtotime($_GET["start_date"])) .
                        " to " . date('F j, Y', strtotime($_GET["end_date"]));
                }
                break;
        }
        echo " <span class='text-info'>$filter_text</span>";
    }
?>
                    <?php if (isset($_GET["search"])) { ?><br /><a href="admin_order_list.php"
                        class="text-decoration-none text-danger">
                        <i class="bi bi-x-circle me-1"></i>Clear Search Result</a>
                    <?php
    }?>
                </caption>
                <thead class="bg-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Transaction ID / Invoice</th>
                        <th scope="col">Customer Details</th>
                        <th scope="col">Food Items</th>
                        <th scope="col">Order Type</th>
                        <th scope="col">Order Status</th>
                        <th scope="col">Order Date</th>
                        <th scope="col">Order Cost</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
    while ($row = $result->fetch_array()) {

        // Get food items for each order separately
        $food_query = "SELECT f.f_name, ti.quantity 
                                       FROM transaction_items ti 
                                       INNER JOIN food f ON ti.f_id = f.f_id 
                                       WHERE ti.tid = ?
                                       ORDER BY f.f_name";
        $food_stmt = $mysqli->prepare($food_query);
        $food_stmt->bind_param("s", $row['tid']);
        $food_stmt->execute();
        $food_result = $food_stmt->get_result();

        $food_items_array = array();
        $item_count = 0;
        $total_quantity = 0;

        while ($food_row = $food_result->fetch_array()) {
            $food_items_array[] = htmlspecialchars($food_row['f_name']) . ' (x' . $food_row['quantity'] . ')';
            $item_count++;
            $total_quantity += $food_row['quantity'];
        }

        // Create the food items display string
        if (count($food_items_array) > 0) {
            $food_items_display = implode(', ', $food_items_array);
        }
        else {
            $food_items_display = '<span class="text-muted">No items found</span>';
            $item_count = 0;
        }

        // INVOICE NUMBER LOGIC - Same as print page
        $is_walkin = (strpos($row['tid'], 'WLK_') === 0);
        $invoice_number = '';

        if ($is_walkin) {
            // Calculate invoice number for walk-in orders
            $invoice_q = "SELECT COUNT(*) AS invoice_no FROM transaction WHERE created_at <= ?";
            $invoice_stmt = $mysqli->prepare($invoice_q);
            $invoice_stmt->bind_param("s", $row['created_at']);
            $invoice_stmt->execute();
            $invoice_result = $invoice_stmt->get_result();
            $invoice_count = $invoice_result->fetch_assoc()['invoice_no'];
            $invoice_number = 23000 + $invoice_count;
            $invoice_stmt->close();
        }
?>
                    <tr>
                        <th>
                            <?php echo $i++; ?>
                        </th>
                        <td>
                            <?php if ($is_walkin): ?>
                            <!-- WALK-IN ORDER: Show Invoice Number -->
                            <strong class="text-success fw-bold">🧾 INV-
                                <?php echo $invoice_number; ?>
                            </strong><br>
                            <small class="text-muted">Walk-in Order</small>
                            <?php
        else: ?>
                            <!-- ONLINE ORDER: Show TID -->
                            <small class="text-primary fw-bold">
                                <?php echo htmlspecialchars($row['tid']); ?>
                            </small><br>
                            <small class="text-muted">Roll:
                                <?php echo htmlspecialchars($row['rollno']); ?>
                            </small>
                            <?php
        endif; ?>
                        </td>
                        <td>
                            <strong>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </strong><br>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($row['email']); ?>
                            </small>
                        </td>
                        <td style="max-width: 200px;">
                            <small>
                                <?php
        echo $food_items_display;
        if ($item_count > 0) {
            echo "<br><span class='text-muted'>(" . $item_count . " items, " . $total_quantity . " total quantity)</span>";
        }
?>
                            </small>
                        </td>
                        <td>
                            <?php
        $order_type = $row['order_type'] ?? 'takeaway';
        if ($order_type == 'dine-in'): ?>
                            <span class="badge bg-primary rounded-pill">
                                <i class="bi bi-house-door-fill me-1"></i>Dine-In
                            </span>
                            <?php
        else: ?>
                            <span class="badge bg-success rounded-pill">
                                <i class="bi bi-bag-fill me-1"></i>Takeaway
                            </span>
                            <?php
        endif; ?>
                        </td>
                        <td>
                            <?php if ($row['order_status'] == "VRFY") { ?>
                            <span class="fw-bold badge rounded-pill bg-info text-dark">
                                <i class="bi bi-hourglass-split me-1"></i>Verifying
                            </span>
                            <?php
        }
        else if ($row['order_status'] == "ACPT") { ?>
                            <span class="fw-bold badge rounded-pill bg-secondary text-white">
                                <i class="bi bi-check-circle me-1"></i>Accepted
                            </span>
                            <?php
        }
        else if ($row['order_status'] == "PREP") { ?>
                            <span class="fw-bold badge rounded-pill bg-warning text-dark">
                                <i class="bi bi-clock-history me-1"></i>Preparing
                            </span>
                            <?php
        }
        else if ($row['order_status'] == "RDPK") { ?>
                            <span class="fw-bold badge rounded-pill bg-primary text-white">
                                <i class="bi bi-bell me-1"></i>Ready - Customer Notified 📧
                            </span>
                            <?php
        }
        else if ($row['order_status'] == "FNSH") { ?>
                            <span class="fw-bold badge rounded-pill bg-success text-white">
                                <i class="bi bi-check2-all me-1"></i>Completed
                            </span>
                            <?php
        }
        else if ($row['order_status'] == "CNCL") { ?>
                            <span class="fw-bold badge rounded-pill bg-danger text-white">
                                <i class="bi bi-x-circle me-1"></i>Cancelled
                            </span>
                            <?php
        }?>
                        </td>
                        <td>
                            <?php
        $order_time = (new DateTime($row['created_at']))->format("F j, Y H:i");
        echo $order_time;
?>
                        </td>
                        <td>
                            <?php
        $final_calc = $row['order_cost'] - ($row['points_used'] ?? 0) - ($row['coupon_discount'] ?? 0);
?>
                            <strong class="text-success">₹
                                <?php echo number_format($final_calc, 2); ?>
                            </strong>
                            <?php if (($row['points_used'] ?? 0) > 0 || ($row['coupon_discount'] ?? 0) > 0): ?>
                            <br><small class="text-secondary text-decoration-line-through">₹
                                <?php echo number_format($row['order_cost'], 2); ?>
                            </small>
                            <?php
        endif; ?>
                        </td>
                        <td>
                            <div class="btn-group-vertical" role="group">
                                <a href="admin_order_detail.php?tid=<?php echo urlencode($row['tid']); ?>"
                                    class="btn btn-sm btn-primary mb-1"
                                    title="View Order Details - TID:
                                    <?php echo htmlspecialchars($row['tid']); ?>">View
                                </a>
                                <a href="admin_order_update.php?tid=<?php echo urlencode($row['tid']); ?>"
                                    class="btn btn-sm btn-outline-success mb-1">
                                    <?php if ($row['order_status'] == "RDPK") { ?>
                                    📧 Update Status
                                    <?php
        }
        else { ?>
                                    Update Status
                                    <?php
        }?>
                                </a>

                                <!-- PRINT BUTTON -->
                                <a href="print_bluetooth.php?tid=<?php echo urlencode($row['tid']); ?>"
                                    class="btn btn-sm btn-success"
                                    target="_blank"
                                    title="Print Receipt/KOT via Bluetooth">
                                    <i class="bi bi-printer"></i> Print
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
    }?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
else { ?>
    <div class="container">
        <div class="row">
            <div class="col m-2 p-2 bg-danger text-white rounded text-start">
                <i class="bi bi-x-circle ms-2"></i><span class="ms-2 mt-2">No order found</span>
                <?php if (isset($_GET["search"])) { ?>
                <a href="admin_order_list.php" class="text-white ms-3">Clear Search Result</a>
                <?php
    }?>
            </div>
        </div>
    </div>
    <?php
}?>

    <!-- Optional: Include footer if it exists -->
    <?php
if (file_exists('../foot.php')) {
    include('../foot.php');
}
?>

    <!-- JavaScript for form handling and SIMPLE AUTO-REFRESH -->
    <script>
        // Function to toggle date inputs based on filter selection
        function toggleDateInputs() {
            const dateFilter = document.getElementById('datefilter').value;
            const customDateInput = document.getElementById('custom_date_input');
            const dateRangeInputs = document.getElementById('date_range_inputs');

            // Hide all date inputs first
            customDateInput.style.display = 'none';
            dateRangeInputs.style.display = 'none';

            // Show appropriate inputs based on selection
            if (dateFilter === 'custom_date') {
                customDateInput.style.display = 'block';
            } else if (dateFilter === 'date_range') {
                dateRangeInputs.style.display = 'flex';
            }
        }

        // SIMPLE SOUND FUNCTION - FIXED PATH
        function playOrderSound() {
            // CORRECT PATH: ../sounds/ to go up from admin folder
            const audio = new Audio('../sounds/zomato_ring_4.mp3');
            audio.volume = 1.0;
            audio.play().catch(e => {
                console.log('Sound play failed:', e);
            });
        }

        // Check if new orders arrived by comparing order count
        let currentOrderCount = <?php echo $current_order_count; ?>;

        // Auto-refresh every 15 seconds
        setInterval(function () {
            window.location.reload();
        }, 15000);

        // Play sound if new orders detected
        document.addEventListener('DOMContentLoaded', function () {
            // Get previous order count from sessionStorage
            const previousOrderCount = sessionStorage.getItem('previousOrderCount');

            // If we have a previous count and current count is higher, PLAY SOUND!
            if (previousOrderCount && currentOrderCount > parseInt(previousOrderCount)) {
                const newOrders = currentOrderCount - parseInt(previousOrderCount);

                // Play Zomato sound
                playOrderSound();

                // Show simple notification
                if (newOrders > 0) {
                    const alertHTML = `
                        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
                            <i class="bi bi-bell-fill me-2"></i>
                            <strong>${newOrders} New Order(s) Received!</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    document.body.insertAdjacentHTML('afterbegin', alertHTML);
                }
            }

            // Store current count for next comparison
            sessionStorage.setItem('previousOrderCount', currentOrderCount);

            // Auto-hide existing notification bars after 5 seconds
            const notifications = document.querySelectorAll('.notibar');
            notifications.forEach(function (notification) {
                setTimeout(function () {
                    notification.style.opacity = '0';
                    setTimeout(function () {
                        notification.style.display = 'none';
                    }, 300);
                }, 5000);
            });

            // Initialize date inputs visibility on page load
            toggleDateInputs();
        });

        // Date validation for date range inputs
        document.addEventListener('DOMContentLoaded', function () {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (startDateInput) {
                startDateInput.addEventListener('change', function () {
                    const startDate = this.value;
                    if (endDateInput) {
                        endDateInput.min = startDate;
                    }
                });
            }

            if (endDateInput) {
                endDateInput.addEventListener('change', function () {
                    const endDate = this.value;
                    if (startDateInput) {
                        startDateInput.max = endDate;
                    }
                });
            }
        });
    </script>

</body>

</html>