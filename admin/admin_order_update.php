<!DOCTYPE html>
<html lang="en">

<head>
    <?php
session_start();
include("../conn_db.php");
if ($_SESSION["utype"] != "ADMIN") {
    header("location: ../restricted.php");
    exit(1);
}

// Include scratch card functions for points (only if file exists)
$scratchSystem = null;
if (file_exists("../scratch_card_functions.php")) {
    include_once("../scratch_card_functions.php");
    $scratchSystem = new ScratchCardSystem($mysqli);
}

// Handle form submission - MODIFIED TO INCLUDE SCRATCH CARD CREATION + EMAIL
if (isset($_POST["upd_confirm"])) {
    $tid = $_POST["tid"];
    $status = $_POST["os"];

    try {
        // Start transaction for data integrity
        $mysqli->begin_transaction();

        // Get current order status and customer ID before updating
        $check_query = "SELECT order_status, c_id FROM transaction WHERE tid = ?";
        $check_stmt = $mysqli->prepare($check_query);
        $check_stmt->bind_param("s", $tid);
        $check_stmt->execute();
        $order_result = $check_stmt->get_result();
        $order_data = $order_result->fetch_assoc();

        $old_status = $order_data ? $order_data['order_status'] : null;
        $c_id = $order_data ? $order_data['c_id'] : null;

        // Update transaction status
        if ($status == 'FNSH') {
            $fnsh_date = date('Y-m-d H:i:s');
            $query = "UPDATE transaction SET order_status = ?, finished_time = ? WHERE tid = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("sss", $status, $fnsh_date, $tid);
        }
        else {
            $query = "UPDATE transaction SET order_status = ?, finished_time = NULL WHERE tid = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("ss", $status, $tid);
        }

        $result = $stmt->execute();

        if ($result) {
            // Log the status change for admin tracking (if table exists)
            try {
                $admin_id = $_SESSION['cid'] ?? 'admin'; // Fallback if no cid
                $log_query = "INSERT INTO admin_status_log (tid, old_status, new_status, changed_by, change_time, notes) 
                                      VALUES (?, ?, ?, ?, NOW(), 'Status updated by admin')";
                $log_stmt = $mysqli->prepare($log_query);
                $log_stmt->bind_param("ssss", $tid, $old_status, $status, $admin_id);
                $log_stmt->execute();
            }
            catch (Exception $log_error) {
                // Continue if logging fails - it's not critical
                error_log("Admin log failed: " . $log_error->getMessage());
            }

            // Variables to track what happened
            $scratch_created = false;
            $scratch_error = null;
            $email_sent = false;
            $email_failed = false;

            // 🆕 NEW: Create scratch card when order is APPROVED (ACPT)
            if ($status == 'ACPT' && $old_status != 'ACPT' && $scratchSystem && $c_id) {
                try {
                    $scratch_result = $scratchSystem->createScratchCard($tid, $c_id);
                    if ($scratch_result['success']) {
                        $scratch_created = true;
                        error_log("Scratch card created for order $tid: ₹" . $scratch_result['amount']);
                    }
                    else {
                        $scratch_error = $scratch_result['message'];
                        error_log("Scratch card creation failed for order $tid: " . $scratch_error);
                    }
                }
                catch (Exception $scratch_exception) {
                    $scratch_error = $scratch_exception->getMessage();
                    error_log("Scratch card exception for order $tid: " . $scratch_error);
                }
            }

            // 🆕 EXISTING: Send email when status becomes "RDPK" (Ready for Pick-Up)
            if ($status == 'RDPK') {
                try {
                    // Include the email notification file
                    if (file_exists('email_notification.php')) {
                        include_once('email_notification.php');

                        // Create email sender object
                        $notifier = new OrderNotification($mysqli);

                        // Try to send email
                        $email_result = $notifier->sendReadyNotification($tid);

                        if ($email_result['success']) {
                            $email_sent = true;
                        }
                        else {
                            $email_failed = true;
                        }
                    }
                    else {
                        $email_failed = true;
                        error_log("Email notification file not found");
                    }
                }
                catch (Exception $email_exception) {
                    $email_failed = true;
                    error_log("Email notification exception: " . $email_exception->getMessage());
                }
            }

            // Commit the transaction
            $mysqli->commit();

            // Build redirect URL based on what happened
            $redirect_params = ["up_ods=1"];

            if ($email_sent)
                $redirect_params[] = "email_sent=1";
            if ($email_failed)
                $redirect_params[] = "email_failed=1";
            if ($scratch_created)
                $redirect_params[] = "scratch_created=1";
            if ($scratch_error)
                $redirect_params[] = "scratch_error=" . urlencode($scratch_error);

            $redirect_url = "admin_order_list.php?" . implode("&", $redirect_params);
            header("location: " . $redirect_url);

        }
        else {
            $mysqli->rollback();
            header("location: admin_order_list.php?up_ods=0");
        }
    }
    catch (Exception $e) {
        $mysqli->rollback();
        error_log("Order update error: " . $e->getMessage());
        header("location: admin_order_list.php?up_ods=0&error=" . urlencode($e->getMessage()));
    }
    exit(1);
}
include('../head.php');
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/main.css" rel="stylesheet">
    <link href="../css/login.css" rel="stylesheet">
    <link href="../img/Color Icon with background.png" rel="icon">
    <title>Update Order Status | FOODCAVE</title>
</head>

<body class="d-flex flex-column h-100">
    <?php include('nav_header_admin.php')?>

    <div class="container form-signin mt-auto w-50">
        <a class="nav nav-item text-decoration-none text-muted" href="#" onclick="history.back();">
            <i class="bi bi-arrow-left-square me-2"></i>Go back
        </a>
        <?php
// Get transaction details from transaction table
$tid = $_GET["tid"];
$query = "SELECT t.tid, t.name, t.email, t.rollno, t.year, t.branch_section, 
                             t.order_cost, t.order_status, t.pickup_time, t.pickup_notes,
                             t.created_at, t.finished_time, t.c_id, t.points_used, t.coupon_discount
                      FROM transaction t 
                      WHERE t.tid = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $tid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("location: admin_order_list.php?error=transaction_not_found");
    exit(1);
}

// Check if scratch card exists for this order
$scratch_card = null;
if ($scratchSystem && $row['c_id']) {
    try {
        $scratch_check_query = "SELECT sc_id, scratch_amount, is_scratched, created_at FROM scratch_cards WHERE tid = ? AND c_id = ?";
        $scratch_stmt = $mysqli->prepare($scratch_check_query);
        $scratch_stmt->bind_param("si", $tid, $row['c_id']);
        $scratch_stmt->execute();
        $scratch_result = $scratch_stmt->get_result();
        $scratch_card = $scratch_result->fetch_assoc();
    }
    catch (Exception $e) {
        // Ignore scratch card check errors
        error_log("Scratch card check error: " . $e->getMessage());
    }
}
?>
        <form method="POST" action="admin_order_update.php" class="form-floating">
            <h2 class="mt-4 mb-3 fw-normal text-bold"><i class="bi bi-pencil-square me-2"></i>Update Order Status</h2>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="transactionid" placeholder="Transaction ID"
                    value="<?php echo $row['tid']; ?>" disabled>
                <label for="transactionid">Transaction ID</label>
            </div>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="customername" placeholder="Customer Name"
                    value="<?php echo $row['name']; ?>" disabled>
                <label for="customername">Customer Name</label>
            </div>

            <div class="form-floating mb-2">
                <input type="email" class="form-control" id="customeremail" placeholder="Customer Email"
                    value="<?php echo $row['email']; ?>" disabled>
                <label for="customeremail">Customer Email</label>
            </div>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="rollno" placeholder="Roll Number" value="<?php echo $row['rollno']; ?>" disabled>
                <label for="rollno">Roll Number</label>
            </div>

            <?php if (isset($row['year']) && isset($row['branch_section'])): ?>
            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="academic" placeholder="Academic Info"
                    value="<?php echo $row['year'] . " - " . $row['branch_section']; ?>" disabled>
                <label for="academic">Year & Branch</label>
            </div>
            <?php
endif; ?>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="ordercost" placeholder="Order Cost"
                    value="₹<?php echo number_format($row['order_cost'], 2); ?>" disabled>
                <label for="ordercost">Original Order Cost</label>
            </div>

            <?php if (isset($row['points_used']) && $row['points_used'] > 0): ?>
            <div class="form-floating mb-2">
                <input type="text" class="form-control text-success" id="pointsused" placeholder="Points Used"
                    value="-₹<?php echo number_format($row['points_used'], 2); ?> (
                <?php echo $row['points_used']; ?> pts)" disabled>
                <label for="pointsused">Points Used</label>
            </div>
            <?php
endif; ?>

            <?php if (isset($row['coupon_discount']) && $row['coupon_discount'] > 0): ?>
            <div class="form-floating mb-2">
                <input type="text" class="form-control text-success" id="coupondiscount" placeholder="Coupon Discount"
                    value="-₹<?php echo number_format($row['coupon_discount'], 2); ?>" disabled>
                <label for="coupondiscount">Coupon Discount</label>
            </div>
            <?php
endif; ?>

            <div class="form-floating mb-2">
                <?php
$final_calc = $row['order_cost'] - ($row['points_used'] ?? 0) - ($row['coupon_discount'] ?? 0);
?>
                <input type="text" class="form-control text-success fw-bold fs-5" id="finalcost"
                    placeholder="Final Paid" value="₹<?php echo number_format($final_calc, 2); ?>" disabled>
                <label for="finalcost">Final Paid Amount</label>
            </div>

            <?php if ($row['pickup_time']): ?>
            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="pickuptime" placeholder="Pickup Time"
                    value="<?php echo $row['pickup_time']; ?>" disabled>
                <label for="pickuptime">Pickup Time</label>
            </div>
            <?php
endif; ?>

            <?php if ($row['pickup_notes']): ?>
            <div class="form-floating mb-2">
                <textarea class="form-control" id="pickupnotes" placeholder="Pickup Notes" style="height: 60px"
                    disabled><?php echo $row['pickup_notes']; ?></textarea>
                <label for="pickupnotes">Pickup Notes</label>
            </div>
            <?php
endif; ?>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="orderdate" placeholder="Order Date"
                    value="<?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?>" disabled>
                <label for="orderdate">Order Date</label>
            </div>

            <div class="form-floating mb-2">
                <select class="form-select" id="orderstatus" name="os" required>
                    <option value="">Select Order Status</option>
                    <option value="VRFY" <?php if ($row['order_status'] == "VRFY" ) {
    echo "selected" ;
}?>>VRFY | Order
                        Verifying</option>
                    <option value="ACPT" <?php if ($row['order_status'] == "ACPT" ) {
    echo "selected" ;
}?>>ACPT | Order
                        Accepted
                        <?php if ($scratchSystem): ?> (🎁 Creates Scratch Card)
                        <?php
endif; ?>
                    </option>
                    <option value="PREP" <?php if ($row['order_status'] == "PREP" ) {
    echo "selected" ;
}?>>PREP | Order
                        Preparing</option>
                    <option value="RDPK" <?php if ($row['order_status'] == "RDPK" ) {
    echo "selected" ;
}?>>RDPK | Ready
                        for
                        Pick-Up (📧 Email will be sent)</option>
                    <option value="FNSH" <?php if ($row['order_status'] == "FNSH" ) {
    echo "selected" ;
}?>>FNSH | Order
                        Finished</option>
                    <option value="CNCL" <?php if ($row['order_status'] == "CNCL" ) {
    echo "selected" ;
}?>>CNCL | Order
                        Cancelled</option>
                </select>
                <label for="orderstatus">Order Status</label>
            </div>

            <!-- Current Status Display -->
            <div class="alert alert-info mb-3">
                <strong>Current Status:</strong>
                <?php
$status_map = [
    'VRFY' => 'Order Verifying',
    'ACPT' => 'Order Accepted',
    'PREP' => 'Order Preparing',
    'RDPK' => 'Ready for Pick-Up',
    'FNSH' => 'Order Finished',
    'CNCL' => 'Order Cancelled'
];
echo $status_map[$row['order_status']] ?? $row['order_status'];
?>
                <?php if ($row['finished_time']): ?>
                <br><small>Finished:
                    <?php echo date('M d, Y h:i A', strtotime($row['finished_time'])); ?>
                </small>
                <?php
endif; ?>
            </div>

            <!-- Scratch Card Status -->
            <?php if ($scratchSystem): ?>
            <?php if ($scratch_card): ?>
            <div class="alert alert-success mb-3">
                <i class="bi bi-gift me-2"></i>
                <strong>Scratch Card Status:</strong>
                Scratch card created! Amount: ₹
                <?php echo $scratch_card['scratch_amount']; ?>
                <br><small>Created:
                    <?php echo date('M d, Y h:i A', strtotime($scratch_card['created_at'])); ?>
                </small>
                <?php if ($scratch_card['is_scratched'] == '1'): ?>
                <br><small class="text-success">✅ Already scratched by customer</small>
                <?php
        else: ?>
                <br><small class="text-warning">⏳ Waiting for customer to scratch</small>
                <?php
        endif; ?>
            </div>
            <?php
    else: ?>
            <div class="alert alert-warning mb-3">
                <i class="bi bi-gift me-2"></i>
                <strong>Scratch Card:</strong>
                <?php if ($row['order_status'] == "ACPT"): ?>
                <span class="text-danger">No scratch card found. There might be an issue with the scratch card
                    system.</span>
                <?php
        else: ?>
                No scratch card yet. Will be created when status changes to "Order Accepted".
                <?php
        endif; ?>
            </div>
            <?php
    endif; ?>

            <!-- Scratch Card System Info -->
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Scratch Card System:</strong> When you approve this order (ACPT), the customer will
                automatically
                receive a scratch card with rewards. Probability: 95% (₹1-5), 4% (₹6-10), 1% (₹20).
            </div>
            <?php
else: ?>
            <div class="alert alert-secondary mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Scratch Card System:</strong> Not available (scratch_card_functions.php not found)
            </div>
            <?php
endif; ?>

            <!-- Email Notification Info -->
            <div class="alert alert-warning mb-3">
                <i class="bi bi-envelope me-2"></i>
                <strong>Email Notification:</strong> When you change status to "Ready for Pick-Up",
                the customer will automatically receive an email notification at <strong>
                    <?php echo $row['email']; ?>
                </strong>
            </div>

            <input type="hidden" name="tid" value="<?php echo $tid; ?>">
            <button class="w-100 btn btn-success mb-3" name="upd_confirm" type="submit">
                <i class="bi bi-check-circle me-2"></i>Update Order Status
            </button>

            <div class="text-center">
                <a href="admin_order_list.php" class="btn btn-outline-secondary">Cancel & Return to List</a>
            </div>
        </form>
    </div>

    <?php include('admin_footer.php')?>
</body>

</html>