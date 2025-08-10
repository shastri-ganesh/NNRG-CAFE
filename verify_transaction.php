<?php
// verify_transaction.php - Process and verify the transaction before adding the order
session_start();
include("conn_db.php");

// Check if user is logged in
if (!isset($_SESSION['cid'])) {
    header("location: login.php");
    exit(1);
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: payment.php");
    exit(1);
}

// FIXED: Validate that all required fields are present - matching payment form field names
$required_fields = ['name', 'email', 'rollno', 'year', 'branch_section', 'delivery_time', 'order_type', 'tid', 'cftid', 'tandc'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    $_SESSION['error'] = "Missing required fields: " . implode(', ', $missing_fields);
    header("location: payment.php");
    exit(1);
}

// Sanitize input data
$customer_name = trim($_POST['name']);
$customer_email = trim($_POST['email']);
$roll_number = trim($_POST['rollno']);
$academic_year = trim($_POST['year']);
$branch_section = trim($_POST['branch_section']);
$delivery_time = trim($_POST['delivery_time']);
$order_type = trim($_POST['order_type']);
$delivery_notes = isset($_POST['delivery_notes']) ? trim($_POST['delivery_notes']) : '';
$transaction_id = trim($_POST['tid']);
$confirm_tid = trim($_POST['cftid']);

// Validate transaction IDs match
if ($transaction_id !== $confirm_tid) {
    $_SESSION['error'] = "Transaction IDs do not match";
    header("location: payment.php");
    exit(1);
}

// Validate email format
if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    header("location: payment.php");
    exit(1);
}

// Validate transaction ID format (should be alphanumeric and proper length)
if (strlen($transaction_id) < 12 || strlen($transaction_id) > 45) {
    $_SESSION['error'] = "Invalid transaction ID format";
    header("location: payment.php");
    exit(1);
}

// FIXED: Improved delivery time validation
$current_time = new DateTime();
$current_time_plus_30 = clone $current_time;
$current_time_plus_30->modify('+30 minutes');

// Create selected delivery time for today
$selected_delivery_time = DateTime::createFromFormat('H:i', $delivery_time);
if ($selected_delivery_time === false) {
    $_SESSION['error'] = "Invalid delivery time format";
    header("location: payment.php");
    exit(1);
}

// Set the date to today
$selected_delivery_time->setDate(
    $current_time->format('Y'), 
    $current_time->format('m'), 
    $current_time->format('d')
);

// Check if selected time is at least 30 minutes from now
if ($selected_delivery_time <= $current_time_plus_30) {
    $_SESSION['error'] = "Delivery time must be at least 30 minutes from now";
    header("location: payment.php");
    exit(1);
}

// Check if transaction ID already exists in order_header table
$check_tid_query = "SELECT COUNT(*) as count FROM order_header WHERE t_id = ?";
$check_stmt = $mysqli->prepare($check_tid_query);

if (!$check_stmt) {
    $_SESSION['error'] = "Database error: " . $mysqli->error;
    header("location: payment.php");
    exit(1);
}

$check_stmt->bind_param("s", $transaction_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    $_SESSION['error'] = "Transaction ID already exists. Please use a different transaction ID.";
    header("location: payment.php");
    exit(1);
}
$check_stmt->close();

// Also check if transaction ID exists in transaction table
$check_trans_query = "SELECT COUNT(*) as count FROM transaction WHERE tid = ?";
$check_trans_stmt = $mysqli->prepare($check_trans_query);

if (!$check_trans_stmt) {
    $_SESSION['error'] = "Database error: " . $mysqli->error;
    header("location: payment.php");
    exit(1);
}

$check_trans_stmt->bind_param("s", $transaction_id);
$check_trans_stmt->execute();
$trans_result = $check_trans_stmt->get_result();
$trans_row = $trans_result->fetch_assoc();

if ($trans_row['count'] > 0) {
    $_SESSION['error'] = "This transaction ID has already been used.";
    header("location: payment.php");
    exit(1);
}
$check_trans_stmt->close();

// Get customer's cart items and calculate total
$cart_query = "SELECT ct.f_id, ct.ct_amount, f.f_price, ct.ct_note, f.s_id 
               FROM cart ct 
               INNER JOIN food f ON ct.f_id = f.f_id 
               WHERE ct.c_id = ?";
$cart_stmt = $mysqli->prepare($cart_query);

if (!$cart_stmt) {
    $_SESSION['error'] = "Database error: " . $mysqli->error;
    header("location: payment.php");
    exit(1);
}

$cart_stmt->bind_param("i", $_SESSION['cid']);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows == 0) {
    $_SESSION['error'] = "Your cart is empty";
    header("location: cart.php");
    exit(1);
}

// Get shop ID from first item (assuming all items are from the same shop)
$first_item = $cart_result->fetch_assoc();
$shop_id = $first_item['s_id'];

// Reset result pointer
$cart_result->data_seek(0);

// Calculate grand total
$grand_total = 0;
$cart_items = [];
while ($cart_row = $cart_result->fetch_assoc()) {
    $cart_items[] = $cart_row;
    $grand_total += ($cart_row['ct_amount'] * $cart_row['f_price']);
}
$cart_stmt->close();

// Start database transaction for consistency
$mysqli->autocommit(FALSE);

try {
    // FIXED: Insert into transaction table with all the details - using correct column names
    $insert_transaction_query = "INSERT INTO transaction (tid, c_id, order_cost, name, email, rollno, year, branch_section, delivery_time, delivery_notes, order_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $trans_stmt = $mysqli->prepare($insert_transaction_query);
    
    if (!$trans_stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    // FIXED: Added order_type to the bind_param
    $trans_stmt->bind_param("sidssssssss", 
        $transaction_id, 
        $_SESSION['cid'], 
        $grand_total, 
        $customer_name, 
        $customer_email, 
        $roll_number, 
        $academic_year, 
        $branch_section, 
        $delivery_time, 
        $delivery_notes,
        $order_type
    );
    
    if (!$trans_stmt->execute()) {
        throw new Exception("Transaction insert failed: " . $trans_stmt->error);
    }
    
    $transaction_db_id = $mysqli->insert_id;
    $trans_stmt->close();

    // Store transaction details in session for use in add_order.php
    $_SESSION['current_transaction_id'] = $transaction_id;
    $_SESSION['transaction_db_id'] = $transaction_db_id;
    
    // Store order details in session
    $_SESSION['order_details'] = [
        'name' => $customer_name,
        'email' => $customer_email,
        'rollno' => $roll_number,
        'year' => $academic_year,
        'branch_section' => $branch_section,
        'delivery_time' => $delivery_time,
        'order_type' => $order_type,
        'delivery_notes' => $delivery_notes,
        'order_total' => $grand_total,
        'shop_id' => $shop_id,
        'cart_items' => $cart_items
    ];

    // Commit the transaction
    $mysqli->commit();
    
    // Success message for debugging
    $_SESSION['success'] = "Transaction verified successfully! Redirecting to add order...";
    
    // Redirect to add_order.php to complete the order process
    header("location: add_order.php");
    exit(0);

} catch (Exception $e) {
    // Rollback transaction on error
    $mysqli->rollback();
    
    // Log error for debugging
    error_log("Transaction verification error: " . $e->getMessage());
    
    $_SESSION['error'] = "Failed to process transaction: " . $e->getMessage();
    header("location: payment.php");
    exit(1);
    
} finally {
    // Reset autocommit
    $mysqli->autocommit(TRUE);
}
?>