<?php
session_start();
// TEMPORARY DEBUG - ADD THIS RIGHT HERE
if (isset($_POST['points_to_use']) && $_POST['points_to_use'] > 0) {
    file_put_contents('payment_debug.log',
        date('Y-m-d H:i:s') . " - Payment Debug Start\n" .
        "Customer ID: " . ($_SESSION['cid'] ?? 'NOT SET') . "\n" .
        "Points to use: " . $_POST['points_to_use'] . "\n" .
        "Transaction ID: " . ($_POST['transaction_id'] ?? 'NOT SET') . "\n" .
        "POST data: " . print_r($_POST, true) . "\n\n",
        FILE_APPEND
    );
}

// Rest of your verify_transaction.php code continues...

// Debug function
function logPaymentDebug($message)
{
    $formatted = "[PAYMENT DEBUG] " . date('Y-m-d H:i:s') . " - " . $message . PHP_EOL;
    error_log($formatted);
    file_put_contents(__DIR__ . '/payment_debug.log', $formatted, FILE_APPEND);
}

logPaymentDebug("Starting transaction verification");

// Include database connection
require_once("conn_db.php");

// Include scratch card functions for points
if (file_exists("scratch_card_functions.php")) {
    include_once("scratch_card_functions.php");
    try {
        $scratchSystem = new ScratchCardSystem($mysqli);
        logPaymentDebug("Scratch card system initialized successfully");
    }
    catch (Exception $e) {
        logPaymentDebug("Scratch card system initialization failed: " . $e->getMessage());
        $scratchSystem = null;
    }
}
else {
    logPaymentDebug("scratch_card_functions.php not found");
    $scratchSystem = null;
}

// Check database connection
$db_connection = null;

if (isset($mysqli) && $mysqli instanceof mysqli) {
    $db_connection = $mysqli;
    logPaymentDebug("Using mysqli connection");
}
elseif (isset($conn) && is_object($conn)) {
    $db_connection = $conn;
    logPaymentDebug("Using conn connection");
}
else {
    logPaymentDebug("No valid database connection found");
    $_SESSION['error'] = "Database connection error. Please try again.";
    header("Location: payment.php");
    exit();
}

// Test connection
if (!$db_connection->ping()) {
    logPaymentDebug("Database connection test failed: " . $db_connection->error);
    $_SESSION['error'] = "Database connection error. Please try again.";
    header("Location: payment.php");
    exit();
}

// Check session
if (!isset($_SESSION['cid'])) {
    logPaymentDebug("User not logged in");
    header("Location: login.php");
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logPaymentDebug("Not a POST request");
    header("Location: payment.php");
    exit();
}

logPaymentDebug("User logged in: Customer ID " . $_SESSION['cid']);
logPaymentDebug("POST data: " . print_r($_POST, true));

// Validate required fields
$required_fields = ['name', 'email', 'rollno', 'tid', 'cftid', 'tandc', 'order_type', 'final_amount'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    $error_msg = "Missing required fields: " . implode(', ', $missing_fields);
    logPaymentDebug($error_msg);
    $_SESSION['error'] = $error_msg;
    header("Location: payment.php");
    exit();
}

// Validate order_type values
$valid_order_types = ['dine-in', 'takeaway'];
if (!in_array($_POST['order_type'], $valid_order_types)) {
    $error_msg = "Invalid order type: " . $_POST['order_type'];
    logPaymentDebug($error_msg);
    $_SESSION['error'] = "Invalid order type selected.";
    header("Location: payment.php");
    exit();
}

// Validate final_amount is numeric
if (!is_numeric($_POST['final_amount']) || $_POST['final_amount'] < 0) {
    $error_msg = "Invalid final amount: " . $_POST['final_amount'];
    logPaymentDebug($error_msg);
    $_SESSION['error'] = "Invalid order amount.";
    header("Location: payment.php");
    exit();
}

// Process points data
$points_to_use = 0;
$points_discount = 0;

if (isset($_POST['points_to_use']) && is_numeric($_POST['points_to_use'])) {
    $points_to_use = max(0, intval($_POST['points_to_use']));
    logPaymentDebug("Points to use: $points_to_use");
}

if (isset($_POST['points_discount']) && is_numeric($_POST['points_discount'])) {
    $points_discount = max(0, floatval($_POST['points_discount']));
    logPaymentDebug("Points discount: ₹$points_discount");
}

$coupon_discount = 0;
if (isset($_POST['coupon_discount']) && is_numeric($_POST['coupon_discount'])) {
    $coupon_discount = max(0, floatval($_POST['coupon_discount']));
    logPaymentDebug("Coupon discount: ₹$coupon_discount");
}

// Process the form data
$customer_id = $_SESSION['cid'];
$name = $db_connection->real_escape_string(trim($_POST['name']));
$email = $db_connection->real_escape_string(trim($_POST['email']));
$rollno = $db_connection->real_escape_string(trim($_POST['rollno']));
$tid = $db_connection->real_escape_string(trim($_POST['tid']));
$cftid = $db_connection->real_escape_string(trim($_POST['cftid']));
$order_type = $db_connection->real_escape_string(trim($_POST['order_type']));
$final_amount = floatval($_POST['final_amount']);
$delivery_time = !empty($_POST['delivery_time']) ? $db_connection->real_escape_string(trim($_POST['delivery_time'])) : null;
$delivery_notes = !empty($_POST['delivery_notes']) ? $db_connection->real_escape_string(trim($_POST['delivery_notes'])) : null;

logPaymentDebug("Processing data for customer $customer_id, TID: $tid, Order type: $order_type, Final amount: $final_amount");

// Validate Transaction IDs match
if ($tid !== $cftid) {
    $error_msg = "Transaction IDs do not match";
    logPaymentDebug($error_msg);
    $_SESSION['error'] = "Transaction IDs do not match. Please confirm your transaction ID.";
    header("Location: payment.php");
    exit();
}

// Check if transaction already exists
$check_query = "SELECT id, order_cost, name, points_used FROM transaction WHERE tid = ?";
$stmt = $db_connection->prepare($check_query);

if ($stmt) {
    $stmt->bind_param("s", $tid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($existing = $result->fetch_assoc()) {
        logPaymentDebug("Duplicate transaction detected: " . $existing['id']);

        // Set session data for success page
        $_SESSION['last_order_id'] = $existing['id'];
        $_SESSION['last_transaction_id'] = $tid;
        $_SESSION['points_used_in_order'] = $existing['points_used'] ?? 0;

        header("Location: order_success.php?orh=" . $existing['id'] . "&points_used=" . ($existing['points_used'] ?? 0));
        exit();
    }
    $stmt->close();
}

// Validate points usage if points are being used
if ($points_to_use > 0) {
    if (!$scratchSystem) {
        $error_msg = "Points system not available";
        logPaymentDebug($error_msg);
        $_SESSION['error'] = "Points system temporarily unavailable. Please try again without using points.";
        header("Location: payment.php");
        exit();
    }

    try {
        // Get customer's actual points balance
        $actual_balance = $scratchSystem->getCustomerPointsBalance($customer_id);
        logPaymentDebug("Customer points balance: $actual_balance");

        if ($points_to_use > $actual_balance) {
            $error_msg = "Insufficient points! Requested: $points_to_use, Available: $actual_balance";
            logPaymentDebug($error_msg);
            $_SESSION['error'] = "Insufficient points. You only have $actual_balance points available.";
            header("Location: payment.php");
            exit();
        }

        // Validate that points discount matches points used (1 point = 1 rupee)
        if (abs($points_discount - $points_to_use) > 0.01) {
            $error_msg = "Points discount mismatch! Points: $points_to_use, Discount: ₹$points_discount";
            logPaymentDebug($error_msg);
            $_SESSION['error'] = "Points calculation error. Please refresh and try again.";
            header("Location: payment.php");
            exit();
        }

        logPaymentDebug("Points validation successful");

    }
    catch (Exception $e) {
        $error_msg = "Points validation error: " . $e->getMessage();
        logPaymentDebug($error_msg);
        $_SESSION['error'] = "Error validating points. Please try again.";
        header("Location: payment.php");
        exit();
    }
}

// Process cart items and validate amounts
$cart_items = [];
$cart_total = 0;

try {
    $cart_query = "SELECT 
        ct.f_id, 
        ct.ct_amount as quantity,
        f.f_name, 
        f.f_price, 
        ct.ct_note as cart_note
    FROM cart ct 
    JOIN food f ON ct.f_id = f.f_id 
    WHERE ct.c_id = ?";

    $cart_stmt = $db_connection->prepare($cart_query);

    if (!$cart_stmt) {
        throw new Exception("Failed to prepare cart query: " . $db_connection->error);
    }

    $cart_stmt->bind_param("i", $customer_id);

    if (!$cart_stmt->execute()) {
        throw new Exception("Failed to execute cart query: " . $cart_stmt->error);
    }

    $cart_result = $cart_stmt->get_result();

    // Check if cart has items
    if ($cart_result->num_rows == 0) {
        logPaymentDebug("Empty cart for customer: $customer_id");
        $_SESSION['error'] = "Your cart is empty. Please add items before checkout.";
        header("Location: cust_cart.php");
        exit();
    }

    logPaymentDebug("Found " . $cart_result->num_rows . " cart items");

    // Store cart items in array and calculate total
    while ($cart_item = $cart_result->fetch_assoc()) {
        $cart_items[] = $cart_item;
        $item_total = $cart_item['f_price'] * $cart_item['quantity'];
        $cart_total += $item_total;
    }

    logPaymentDebug("Cart total calculated: ₹$cart_total");
    $cart_stmt->close();

}
catch (Exception $e) {
    $error_msg = "CRITICAL ERROR in cart processing: " . $e->getMessage();
    logPaymentDebug($error_msg);
    $_SESSION['error'] = "Error processing your cart. Please try again.";
    header("Location: cust_cart.php");
    exit();
}

// Calculate expected total and validate against submitted amount
$takeaway_charge = 5.00;
$subtotal = $cart_total;

if ($order_type === 'takeaway') {
    $subtotal += $takeaway_charge;
    logPaymentDebug("Takeaway charge added: ₹$takeaway_charge");
}

// Calculate expected final amount after points discount
$expected_final_amount = $subtotal - $points_discount - $coupon_discount;
$order_cost_before_discount = $subtotal;

logPaymentDebug("Amount validation - Subtotal: ₹$subtotal, Points discount: ₹$points_discount, Expected final: ₹$expected_final_amount, Submitted: ₹$final_amount");

// Validate the amounts match (allow small floating point differences)
if (abs($expected_final_amount - $final_amount) > 0.01) {
    $error_msg = "Final amount mismatch! Expected: ₹{$expected_final_amount}, Received: ₹{$final_amount}";
    logPaymentDebug($error_msg);
    $_SESSION['error'] = "Order amount verification failed. Please refresh the page and try again.";
    header("Location: payment.php");
    exit();
}

logPaymentDebug("Amount validation successful");

// Start database transaction for points usage
if ($points_to_use > 0 && $scratchSystem) {
    logPaymentDebug("Starting points deduction process");

    try {
        $db_connection->autocommit(FALSE);
        logPaymentDebug("Database transaction started");

        // Use points from customer's balance
        $points_result = $scratchSystem->useCustomerPoints($customer_id, $points_to_use, $tid);

        if (!$points_result['success']) {
            throw new Exception("Failed to use points: " . $points_result['message']);
        }

        logPaymentDebug("Points deducted successfully: " . print_r($points_result, true));

    }
    catch (Exception $e) {
        $error_msg = "ERROR in points processing: " . $e->getMessage();
        logPaymentDebug($error_msg);

        $db_connection->rollback();
        $db_connection->autocommit(TRUE);

        $_SESSION['error'] = "Error processing your points. Please try again.";
        header("Location: payment.php");
        exit();
    }
}

// Create the main transaction record
$pickup_time = $delivery_time ? $delivery_time : null;
$pickup_notes = $delivery_notes ? $delivery_notes : null;

$insert_query = "INSERT INTO transaction (tid, c_id, order_cost, points_used, name, email, rollno, pickup_time, pickup_notes, order_type, coupon_discount, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$insert_stmt = $db_connection->prepare($insert_query);

if (!$insert_stmt) {
    $error_msg = "Prepare failed: " . $db_connection->error;
    logPaymentDebug($error_msg);

    if ($points_to_use > 0) {
        $db_connection->rollback();
        $db_connection->autocommit(TRUE);
    }
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: payment.php");
    exit();
}

// Bind parameters
$insert_stmt->bind_param("sidissssssd",
    $tid,
    $customer_id,
    $order_cost_before_discount,
    $points_to_use,
    $name,
    $email,
    $rollno,
    $pickup_time,
    $pickup_notes,
    $order_type,
    $coupon_discount
);

// Execute the transaction insert
if (!$insert_stmt->execute()) {
    $error_msg = "Transaction insert failed: " . $insert_stmt->error;
    logPaymentDebug($error_msg);

    if ($points_to_use > 0) {
        $db_connection->rollback();
        $db_connection->autocommit(TRUE);
    }
    $_SESSION['error'] = "Failed to process your order. Please try again.";
    header("Location: payment.php");
    exit();
}

$insert_id = $db_connection->insert_id;
logPaymentDebug("Transaction inserted with ID: $insert_id");
$insert_stmt->close();

// Commit points transaction if we used points
if ($points_to_use > 0 && $scratchSystem) {
    $db_connection->commit();
    $db_connection->autocommit(TRUE);
    logPaymentDebug("Points transaction committed successfully");
}

// Insert cart items into transaction_items (if table exists)
try {
    $table_check = $db_connection->query("SHOW TABLES LIKE 'transaction_items'");
    if ($table_check && $table_check->num_rows > 0) {
        $item_insert_query = "INSERT INTO transaction_items (tid, f_id, quantity, unit_price, total_price, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $item_stmt = $db_connection->prepare($item_insert_query);

        if ($item_stmt) {
            $items_added = 0;

            foreach ($cart_items as $cart_item) {
                $f_id = $cart_item['f_id'];
                $quantity = $cart_item['quantity'];
                $unit_price = $cart_item['f_price'];
                $total_price = $unit_price * $quantity;
                $notes = $cart_item['cart_note'] ?? '';

                $item_stmt->bind_param("siidds", $tid, $f_id, $quantity, $unit_price, $total_price, $notes);

                if ($item_stmt->execute()) {
                    $items_added++;
                }
            }

            logPaymentDebug("Added $items_added items to transaction_items");
            $item_stmt->close();
        }
    }
}
catch (Exception $e) {
    logPaymentDebug("ERROR inserting transaction items: " . $e->getMessage());
}

// Clear cart after successful order
$clear_cart_query = "DELETE FROM cart WHERE c_id = ?";
$clear_stmt = $db_connection->prepare($clear_cart_query);
if ($clear_stmt) {
    $clear_stmt->bind_param("i", $customer_id);
    if ($clear_stmt->execute()) {
        logPaymentDebug("Cart cleared successfully");
    }
    else {
        logPaymentDebug("Failed to clear cart: " . $clear_stmt->error);
    }
    $clear_stmt->close();
}

// Record coupon usage if a coupon was applied
$coupon_id_post = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
$coupon_code_post = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : '';
logPaymentDebug("Checking coupon usage. Coupon discount: $coupon_discount, POST coupon_id: $coupon_id_post, POST coupon_code: '$coupon_code_post'");

if ($coupon_discount > 0) {
    $coupon_id = $coupon_id_post;

    // Fallback: if coupon_id is missing but we have the coupon code, look it up
    if ($coupon_id <= 0 && !empty($coupon_code_post)) {
        $lookup_stmt = $db_connection->prepare("SELECT coupon_id FROM coupons WHERE coupon_code = ?");
        if ($lookup_stmt) {
            $lookup_stmt->bind_param("s", $coupon_code_post);
            $lookup_stmt->execute();
            $lookup_result = $lookup_stmt->get_result();
            if ($lookup_row = $lookup_result->fetch_assoc()) {
                $coupon_id = intval($lookup_row['coupon_id']);
                logPaymentDebug("Coupon ID looked up from code '$coupon_code_post': $coupon_id");
            }
            $lookup_stmt->close();
        }
    }

    if ($coupon_id > 0) {
        $usage_stmt = $db_connection->prepare("INSERT IGNORE INTO coupon_usage (coupon_id, c_id) VALUES (?, ?)");
        if ($usage_stmt) {
            $usage_stmt->bind_param("ii", $coupon_id, $customer_id);
            if ($usage_stmt->execute()) {
                logPaymentDebug("Coupon usage recorded: coupon_id=$coupon_id, c_id=$customer_id, affected_rows=" . $usage_stmt->affected_rows);
            }
            else {
                logPaymentDebug("ERROR recording coupon usage: " . $usage_stmt->error);
            }
            $usage_stmt->close();
        }
        else {
            logPaymentDebug("ERROR preparing coupon usage statement: " . $db_connection->error);
        }
    }
    else {
        logPaymentDebug("WARNING: Coupon discount > 0 but could not determine coupon_id");
    }
}

// Set session success data
$_SESSION['last_order_id'] = $insert_id;
$_SESSION['last_transaction_id'] = $tid;
$_SESSION['transaction_success'] = true;
$_SESSION['order_type'] = $order_type;
$_SESSION['order_total'] = $order_cost_before_discount;
$_SESSION['points_used_in_order'] = $points_to_use;
$_SESSION['points_discount_applied'] = $points_discount;
$_SESSION['final_paid_amount'] = $final_amount;

// Store order details for success page
$_SESSION['order_details'] = array(
    'transaction_id' => $tid,
    'customer_name' => $name,
    'order_cost' => $order_cost_before_discount,
    'points_used' => $points_to_use,
    'points_discount' => $points_discount,
    'coupon_discount' => $coupon_discount,
    'final_amount' => $final_amount,
    'order_type' => $order_type,
    'delivery_time' => $delivery_time,
    'order_time' => date('Y-m-d H:i:s')
);

logPaymentDebug("Session data set for success page");

// Generate Scratch Card After Successful Order
if ($scratchSystem) {
    try {
        $scratch_result = $scratchSystem->createScratchCard($tid, $customer_id);

        if ($scratch_result['success']) {
            $_SESSION['new_scratch_card'] = true;
            $_SESSION['new_scratch_amount'] = $scratch_result['amount'];
            logPaymentDebug("Scratch card created: ₹{$scratch_result['amount']}");
        }
        else {
            logPaymentDebug("Scratch card creation failed: " . $scratch_result['message']);
        }

    }
    catch (Exception $e) {
        logPaymentDebug("Scratch card generation exception: " . $e->getMessage());
    }
}

logPaymentDebug("Final verification complete - redirecting to success page");

// Clear any previous errors
unset($_SESSION['error']);

// Build redirect URL with parameters
$redirect_url = "order_success.php?orh=" . $insert_id;
if ($points_to_use > 0) {
    $redirect_url .= "&points_used=" . $points_to_use;
    $redirect_url .= "&points_saved=" . $points_discount;
}
if ($coupon_discount > 0) {
    $redirect_url .= "&coupon_saved=" . $coupon_discount;
}

logPaymentDebug("Redirecting to: $redirect_url");

header("Location: " . $redirect_url);
exit();

?>