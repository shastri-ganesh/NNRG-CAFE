<!DOCTYPE html>
<html lang="en">

<head>
    <?php
session_start();
include("conn_db.php");

if (!isset($_SESSION['cid'])) {
    header("Location: login.php");
    exit();
}

// Include scratch card functions for points
if (file_exists("scratch_card_functions.php")) {
    include_once("scratch_card_functions.php");
    try {
        $scratchSystem = new ScratchCardSystem($mysqli);
        $points_balance = $scratchSystem->getCustomerPointsBalance($_SESSION['cid']);
    }
    catch (Exception $e) {
        error_log("Scratch system error: " . $e->getMessage());
        $points_balance = 0;
    }
}
else {
    $points_balance = 0;
}

include('head.php');
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/login.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .payment-success {
            display: none;
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .payment-processing {
            display: none;
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .payment-error {
            display: none;
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* QR Code styling */
        .qr-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        #payment-qr-code {
            width: 250px;
            height: 250px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Form container styling */
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            max-width: 800px;
            margin: 0 auto;
        }

        /* Payment header */
        .payment-header {
            background: white;
            padding: 25px 30px 20px;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0;
        }

        .payment-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
        }

        /* Order Summary Card */
        .order-summary-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 30px;
        }

        .order-summary-title {
            font-size: 18px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            border-bottom: none;
            padding-bottom: 0;
        }

        .cost-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .cost-item.total {
            border-top: 1px solid #dee2e6;
            padding-top: 12px;
            margin-top: 12px;
            font-weight: bold;
            font-size: 18px;
        }

        .cost-item.subtotal {
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: 500;
            color: #6c757d;
        }

        .cost-item.points-discount {
            color: #28a745;
            font-weight: 500;
        }

        /* Points Section Styling */
        .points-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 30px;
        }

        .points-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .points-balance {
            font-size: 24px;
            font-weight: bold;
        }

        .points-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .points-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 8px 12px;
        }

        .points-input {
            background: transparent;
            border: none;
            color: white;
            width: 80px;
            text-align: center;
            font-weight: 500;
        }

        .points-input:focus {
            outline: none;
        }

        .points-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .points-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .points-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Section styling */
        .form-section {
            padding: 25px 30px;
            border-bottom: 1px solid #f0f0f0;
        }

        .form-section:last-child {
            border-bottom: none;
            border-radius: 0 0 12px 12px;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header i {
            font-size: 20px;
            margin-right: 10px;
            color: #6c757d;
        }

        .section-header h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #495057;
        }

        /* Form controls */
        .form-floating {
            margin-bottom: 20px;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 16px;
            height: auto;
            min-height: 50px;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.1);
        }

        .form-floating label {
            padding: 12px 16px;
            color: #6c757d;
            font-weight: 500;
        }

        /* Auto-filled field styling */
        .form-control[readonly] {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            color: #6c757d;
        }

        .form-control[readonly]:focus {
            background-color: #f8f9fa;
            border-color: #ced4da;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.1);
        }

        /* Form row for side-by-side inputs */
        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-floating {
            flex: 1;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        /* Order Time Notice */
        .order-time-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #f39c12;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            color: #856404;
            font-size: 15px;
        }

        .order-time-notice i {
            color: #f39c12;
            margin-right: 8px;
        }

        /* Order Type Selection */
        .order-type-container {
            margin-bottom: 25px;
        }

        .order-type-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .order-type-options {
            display: flex;
            gap: 15px;
        }

        .order-type-option {
            flex: 1;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            background-color: white;
            cursor: pointer;
            position: relative;
        }

        .order-type-option:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
        }

        .order-type-option.selected {
            border-color: #28a745;
            background-color: #f8fff9;
            box-shadow: 0 2px 12px rgba(40, 167, 69, 0.15);
        }

        .order-type-option label {
            cursor: pointer;
            margin-bottom: 0;
            width: 100%;
            display: flex;
            align-items: flex-start;
        }

        .order-type-option input[type="radio"] {
            margin-right: 12px;
            margin-top: 3px;
            transform: scale(1.1);
        }

        .order-type-content {
            flex: 1;
        }

        .order-type-title {
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .order-type-subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .takeaway-charge {
            color: #dc3545;
            font-size: 13px;
            font-weight: 500;
        }

        /* Time input styling */
        .time-warning {
            color: #856404;
            font-size: 14px;
            margin-top: 8px;
            display: flex;
            align-items: center;
        }

        .time-warning i {
            margin-right: 6px;
        }

        /* Transaction ID Validation */
        .transaction-input.error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
        }

        .transaction-input.success {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.15) !important;
        }

        .transaction-error {
            color: #dc3545;
            font-size: 14px;
            margin-top: 6px;
            display: none;
        }

        .transaction-success {
            color: #28a745;
            font-size: 14px;
            margin-top: 6px;
            display: none;
        }

        .digit-counter {
            font-size: 13px;
            color: #6c757d;
            text-align: right;
            margin-top: 6px;
        }

        .example-text {
            font-size: 14px;
            color: #6c757d;
            font-style: italic;
            margin-top: 6px;
        }

        /* Submit button */
        .submit-button {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .submit-button:hover:not(:disabled) {
            background: linear-gradient(135deg, #218838, #1ea384);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .submit-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Terms checkbox */
        .terms-container {
            margin: 20px 0;
        }

        .form-check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .form-check-input {
            margin-top: 4px;
            transform: scale(1.1);
        }

        .form-check-label {
            font-size: 14px;
            color: #6c757d;
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            .order-type-options {
                flex-direction: column;
                gap: 12px;
            }

            .form-container {
                margin: 0 15px;
            }

            .form-section {
                padding: 20px;
            }

            .order-summary-card {
                margin: 15px 20px;
                padding: 15px;
            }

            .points-section {
                margin: 15px 20px;
                padding: 15px;
            }

            .payment-header {
                padding: 20px;
            }

            #payment-qr-code {
                width: 200px;
                height: 200px;
            }

            .points-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
        }

        /* Container adjustments */
        #shop-body {
            max-width: 1200px;
            margin: 0 auto;
        }

        #mismatch-warning {
            display: none;
            background-color: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>

    <title>Payment | NNRG-CAFE</title>
</head>

<body class="d-flex flex-column">
    <header class="navbar navbar-light fixed-top bg-light shadow-sm mb-auto">
        <div class="container-fluid mx-4">
            <a href="index.php">
                <img src="img/Color logo - no background.png" width="125" class="me-2" alt="FOODCAVE Logo">
            </a>
        </div>
    </header>

    <div class="container px-5 py-4" id="shop-body">
        <div class="row my-4">
            <a class="nav nav-item text-decoration-none text-muted mb-2" href="#" onclick="history.back();">
                <i class="bi bi-arrow-left-square me-2"></i>Go back
            </a>
        </div>

        <!-- Display session error messages if any -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo htmlspecialchars($_SESSION['error']);
    unset($_SESSION['error']); ?>
        </div>
        <?php
endif; ?>

        <div id="payment-success" class="payment-success">
            <i class="bi bi-check-circle-fill me-2"></i> Payment Successful! Redirecting to order confirmation...
        </div>

        <div id="payment-processing" class="payment-processing">
            <i class="bi bi-hourglass-split me-2"></i> Processing your payment, please wait...
        </div>

        <div id="payment-error" class="payment-error">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <span id="error-message">Error processing
                payment.</span>
        </div>

        <?php
// Your UPI details
$merchant_vpa = "9059988828@idfcfirst"; // Your UPI ID
$merchant_name = "NNRG-CAFE";
$transaction_note = "Food Order Payment";

// Get cart total
$gt_query = "SELECT SUM(ct.ct_amount*f.f_price) AS grandtotal FROM cart ct INNER JOIN food f 
        ON ct.f_id = f.f_id WHERE ct.c_id = {$_SESSION['cid']} GROUP BY ct.c_id";
$gt_result = $mysqli->query($gt_query);

if ($gt_result && $gt_result->num_rows > 0) {
    $gt_arr = $gt_result->fetch_array();
    $base_cost = $gt_arr["grandtotal"];
}
else {
    $base_cost = 0;
    $_SESSION['error'] = "Your cart is empty. Please add items before checkout.";
    header("Location: cust_cart.php");
    exit();
}

// Base amount
$amount = $base_cost;

// Create UPI payment string
$upi_string = "upi://pay?pa={$merchant_vpa}&pn=" . urlencode($merchant_name) . "&am={$amount}&cu=INR&tn=" . urlencode($transaction_note);

// Generate QR code URL
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($upi_string);
?>

        <!-- Form Section -->
        <form id="payment-form" method="POST" action="verify_transaction.php" class="form-container">
            <!-- Payment Header -->
            <div class="payment-header">
                <h2><i class="bi bi-qr-code-scan me-2"></i>Payment</h2>
            </div>

            <!-- Points Section (only show if customer has points) -->
            <?php if ($points_balance > 0): ?>
            <div class="points-section">
                <div class="points-header">
                    <div>
                        <i class="bi bi-coin" style="font-size: 1.5rem; margin-right: 8px;"></i>
                        <span style="font-size: 18px; font-weight: 500;">Available Points</span>
                    </div>
                    <div class="points-balance">
                        <?php echo $points_balance; ?> pts
                    </div>
                </div>
                <div class="points-controls">
                    <div class="points-input-group">
                        <span>Use:</span>
                        <input type="number" id="points-to-use" name="points_to_use" class="points-input" min="0"
                            max="<?php echo min($points_balance, $base_cost); ?>" value="0" placeholder="0">
                        <span>points</span>
                    </div>
                    <button type="button" class="points-btn" onclick="useMaxPoints()">Use Max</button>
                    <button type="button" class="points-btn" onclick="clearPoints()">Clear</button>
                </div>
                <div style="margin-top: 10px; font-size: 14px; opacity: 0.9;">
                    💡 1 point = ₹1 discount | Max usable:
                    <?php echo min($points_balance, $base_cost); ?> points
                </div>
            </div>
            <?php
else: ?>
            <input type="hidden" name="points_to_use" value="0">
            <?php
endif; ?>

            <!-- Order Summary Card -->
            <div class="order-summary-card">
                <h6 class="order-summary-title">Order Summary</h6>
                <div class="cost-item">
                    <span>Food Items:</span>
                    <span id="base-cost">
                        <?php printf("%.2f INR", $base_cost); ?>
                    </span>
                </div>
                <div class="cost-item" id="takeaway-charge-item" style="display: none;">
                    <span>Takeaway Charges:</span>
                    <span class="text-danger">5.00 INR</span>
                </div>
                <div class="cost-item subtotal" id="subtotal-item">
                    <span>Subtotal:</span>
                    <span id="subtotal-amount">
                        <?php printf("%.2f INR", $base_cost); ?>
                    </span>
                </div>
                <!-- Coupon Section -->
                <div class="mt-3 pt-3 border-top" style="border-top: 1px dashed #dee2e6 !important;">
                    <div class="input-group mb-2">
                        <input type="text" id="coupon-code-input" class="form-control text-uppercase"
                            placeholder="Enter Coupon Code" style="min-height: 40px;">
                        <button class="btn btn-outline-primary" type="button" id="apply-coupon-btn"
                            onclick="applyCoupon()" style="font-weight: bold;">Apply</button>
                    </div>
                    <div id="coupon-message" style="font-size: 13px; font-weight: 500; display: none;"></div>
                </div>

                <div class="cost-item points-discount" id="coupon-discount-item" style="display: none; color: #ff4757;">
                    <span>Coupon Discount:</span>
                    <span id="coupon-discount-amount">-0.00 INR</span>
                </div>
                <div class="cost-item total">
                    <span>Amount to Pay:</span>
                    <span id="grand-total">
                        <?php printf("%.2f INR", $base_cost); ?>
                    </span>
                </div>
            </div>

            <!-- Hidden fields to store calculated amounts -->
            <input type="hidden" name="final_amount" id="final-amount" value="<?php echo $base_cost; ?>">
            <input type="hidden" name="points_discount" id="points-discount" value="0">
            <input type="hidden" name="coupon_discount" id="coupon-discount-val" value="0">
            <input type="hidden" name="coupon_id" id="coupon-id-val" value="0">
            <input type="hidden" name="coupon_code" id="coupon-code-val" value="">
            <input type="hidden" name="base_cost" value="<?php echo $base_cost; ?>">

            <!-- Personal Information Section -->
            <?php
// Fetch user details from database
$user_query = "SELECT c_username, c_firstname, c_lastname, c_email FROM customer WHERE c_id = {$_SESSION['cid']} LIMIT 0,1";
$user_result = $mysqli->query($user_query);

if ($user_result && $user_result->num_rows > 0) {
    $user_data = $user_result->fetch_array();
    $full_name = $user_data["c_firstname"] . " " . $user_data["c_lastname"];
    $user_email = $user_data["c_email"];
    $roll_number = $user_data["c_username"];
}
else {
    $_SESSION['error'] = "Unable to fetch user details. Please login again.";
    header("Location: login.php");
    exit();
}
?>
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-person-fill"></i>
                    <h5>Personal Information</h5>
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control" id="name" placeholder="Full Name" name="name"
                        value="<?php echo htmlspecialchars($full_name); ?>" readonly>
                    <label for="name">Full Name</label>
                </div>

                <div class="form-floating">
                    <input type="email" class="form-control" id="email" placeholder="E-mail" name="email"
                        value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                    <label for="email">E-mail</label>
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control" id="rollno" placeholder="Roll Number" name="rollno"
                        value="<?php echo htmlspecialchars($roll_number); ?>" readonly>
                    <label for="rollno">Roll Number</label>
                </div>
            </div>

            <!-- Pickup Information Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-geo-alt-fill" id="pickup-icon"></i>
                    <h5 id="pickup-title">Pickup Information</h5>
                </div>

                <!-- Order Time Restriction Notice -->
                <div class="order-time-notice">
                    <i class="bi bi-clock-fill"></i>
                    <strong>Important:</strong> Orders are accepted only before 11:45 AM. Please place your order
                    accordingly.
                </div>

                <!-- Order Type Selection -->
                <div class="order-type-container">
                    <div class="order-type-label">Order Type <span class="text-danger">*</span></div>
                    <div class="order-type-options">
                        <div class="order-type-option" onclick="selectOrderType('dine-in')">
                            <label for="dine-in">
                                <input type="radio" id="dine-in" name="order_type" value="dine-in" required>
                                <div class="order-type-content">
                                    <div class="order-type-title">
                                        <i class="bi bi-house-door-fill me-2 text-primary"></i>Dine-In
                                    </div>
                                    <div class="order-type-subtitle">Eat at restaurant</div>
                                </div>
                            </label>
                        </div>
                        <div class="order-type-option" onclick="selectOrderType('takeaway')">
                            <label for="takeaway">
                                <input type="radio" id="takeaway" name="order_type" value="takeaway" required>
                                <div class="order-type-content">
                                    <div class="order-type-title">
                                        <i class="bi bi-bag-fill me-2 text-success"></i>Takeaway
                                    </div>
                                    <div class="order-type-subtitle">Pick up to go</div>
                                    <div class="takeaway-charge">+ ₹5.00 takeaway charges</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="time" class="form-control" id="delivery_time" name="delivery_time" required>
                    <label for="delivery_time" id="time-label">Preferred Pickup Time</label>
                    <div class="time-warning" id="time-warning">
                        <i class="bi bi-info-circle"></i>
                        Minimum pickup time: <span id="min-time-display"></span>
                    </div>
                </div>

                <div class="form-floating">
                    <textarea class="form-control" id="delivery_notes" placeholder="Special instructions"
                        name="delivery_notes" style="height: 100px"></textarea>
                    <label for="delivery_notes">Special Instructions (Optional)</label>
                </div>
            </div>

            <!-- Payment Information Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-credit-card-fill"></i>
                    <h5>Payment Information</h5>
                </div>
                <div class="example-text" style="color: red; font-weight: bold; text-transform: uppercase;">
                    Note : For order acceptance please note down the transaction id then submit the order
                </div>

                <!-- QR Code Section -->
                <div class="qr-container">
                    <img id="payment-qr-code" src="<?php echo $qr_code_url; ?>" alt="Payment QR Code"
                        data-base-amount="<?php echo $base_cost; ?>" data-merchant-vpa="<?php echo $merchant_vpa; ?>"
                        data-merchant-name="<?php echo urlencode($merchant_name); ?>"
                        data-transaction-note="<?php echo urlencode($transaction_note); ?>">
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control transaction-input" id="tid" placeholder="Transaction ID"
                        name="tid" maxlength="12" required autocomplete="off">
                    <label for="tid">Enter 12 Digit Transaction ID</label>
                    <div class="digit-counter" id="tid-counter">0/12 digits</div>
                    <div class="example-text">
                        Example: 123456789012
                    </div>
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control transaction-input" id="cftid"
                        placeholder="Confirm Transaction ID" maxlength="12" name="cftid" required autocomplete="off">
                    <label for="cftid">Confirm Transaction ID</label>
                    <div class="digit-counter" id="cftid-counter">0/12 digits</div>
                    <div class="example-text">
                        Re-enter the same 12-digit transaction ID
                    </div>
                </div>

                <div id="mismatch-warning" class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Transaction IDs do not match. Please check and re-enter.
                </div>

                <div class="terms-container">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="tandc" name="tandc" required>
                        <label class="form-check-label" for="tandc">I agree to the terms and conditions and the privacy
                            policy</label>
                    </div>
                </div>

                <button class="submit-button btn-secondary" id="submit-btn" type="submit" disabled>Complete Payment
                    (Disabled)</button>
            </div>
        </form>
    </div>

    <?php include('footer.php')?>

    <?php
echo "<script>\n";
echo "const baseCost = " . (isset($base_cost) ? $base_cost : 0) . ";\n";
echo "const maxPoints = " . (isset($points_balance) ? $points_balance : 0) . ";\n";
echo "</script>\n";
?>

    <script>
        // Global variables
        let minPickupTime;
        let minTimeString;
        let isFormSubmitting = false;
        const takeawayCharge = 5.00;

        function convertTo12HourFormat(time24) {
            if (!time24) return '';

            const [hours, minutes] = time24.split(':');
            let hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';

            hour = hour % 12;
            hour = hour ? hour : 12; // Convert 0 to 12

            return `${hour}:${minutes} ${ampm}`;
        }

        // Points management functions
        function useMaxPoints() {
            const pointsInput = document.getElementById('points-to-use');
            if (pointsInput) {
                const maxUsable = Math.min(maxPoints, getCurrentSubtotal());
                pointsInput.value = maxUsable;
            }
            updateCostDisplay();
        }

        function clearPoints() {
            const pointsInput = document.getElementById('points-to-use');
            if (pointsInput) {
                pointsInput.value = 0;
            }
            updateCostDisplay();
        }

        function getCurrentSubtotal() {
            const orderType = document.querySelector('input[name="order_type"]:checked');
            let subtotal = baseCost;
            if (orderType && orderType.value === 'takeaway') {
                subtotal += takeawayCharge;
            }
            return subtotal;
        }

        function updateCostDisplay(orderType = null) {
            const takeawayChargeItem = document.getElementById('takeaway-charge-item');
            const subtotalItem = document.getElementById('subtotal-item');
            const subtotalAmount = document.getElementById('subtotal-amount');
            const pointsDiscountItem = document.getElementById('points-discount-item');
            const pointsDiscountAmount = document.getElementById('points-discount-amount');
            const grandTotalSpan = document.getElementById('grand-total');
            const finalAmountInput = document.getElementById('final-amount');
            const pointsDiscountInput = document.getElementById('points-discount');

            // Get points to use
            const pointsInput = document.getElementById('points-to-use');
            let pointsToUse = 0;
            if (pointsInput) {
                pointsToUse = parseInt(pointsInput.value) || 0;
                const maxUsable = Math.min(maxPoints, getCurrentSubtotal());
                if (pointsToUse > maxUsable) {
                    pointsToUse = maxUsable;
                    pointsInput.value = pointsToUse;
                }
            }

            let subtotal = baseCost;

            // Handle takeaway charges
            if (!orderType) {
                const selectedType = document.querySelector('input[name="order_type"]:checked');
                orderType = selectedType ? selectedType.value : 'dine-in';
            }

            if (orderType === 'takeaway') {
                if (takeawayChargeItem) takeawayChargeItem.style.display = 'flex';
                subtotal += takeawayCharge;
            } else {
                if (takeawayChargeItem) takeawayChargeItem.style.display = 'none';
            }

            // Update subtotal
            if (subtotalAmount) subtotalAmount.textContent = subtotal.toFixed(2) + ' INR';

            // Handle points discount
            if (pointsToUse > 0) {
                if (pointsDiscountItem) pointsDiscountItem.style.display = 'flex';
                if (pointsDiscountAmount) pointsDiscountAmount.textContent = '-' + pointsToUse.toFixed(2) + ' INR';
                if (pointsDiscountInput) pointsDiscountInput.value = pointsToUse;
            } else {
                if (pointsDiscountItem) pointsDiscountItem.style.display = 'none';
                if (pointsDiscountInput) pointsDiscountInput.value = 0;
            }

            // Calculate final amount after points and coupon
            let finalAmount = Math.max(0, subtotal - pointsToUse);
            if (window.activeCouponDiscount && window.activeCouponDiscount > 0) {
                finalAmount = Math.max(0, finalAmount - window.activeCouponDiscount);
            }

            if (grandTotalSpan) grandTotalSpan.textContent = finalAmount.toFixed(2) + ' INR';
            if (finalAmountInput) finalAmountInput.value = finalAmount.toFixed(2);

            // Update QR code if needed
            updateQRCode(orderType, finalAmount);
        }

        // Update QR code
        function updateQRCode(orderType, finalAmount = null) {
            const qrImg = document.getElementById('payment-qr-code');
            if (!qrImg) return;

            const merchantVpa = qrImg.getAttribute('data-merchant-vpa');
            const merchantName = qrImg.getAttribute('data-merchant-name');
            const transactionNote = qrImg.getAttribute('data-transaction-note');

            let paymentAmount = finalAmount !== null ? finalAmount : getCurrentSubtotal();

            const pointsInput = document.getElementById('points-to-use');
            if (pointsInput) {
                const pointsToUse = parseInt(pointsInput.value) || 0;
                if (finalAmount === null) {
                    paymentAmount = Math.max(0, paymentAmount - pointsToUse);
                }
            }

            if (window.activeCouponDiscount && window.activeCouponDiscount > 0 && finalAmount === null) {
                paymentAmount = Math.max(0, paymentAmount - window.activeCouponDiscount);
            }

            const upiString = `upi://pay?pa=${merchantVpa}&pn=${merchantName}&am=${paymentAmount}&cu=INR&tn=${transactionNote}`;
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(upiString)}`;

            qrImg.src = qrUrl;
        }

        // Transaction ID validation
        function validateTransactionId() {
            const tidInput = document.getElementById('tid');
            const cftidInput = document.getElementById('cftid');
            const counter = document.getElementById('tid-counter');
            const confirmCounter = document.getElementById('cftid-counter');
            const submitBtn = document.getElementById('submit-btn');
            const tandc = document.getElementById('tandc');

            let tidValue = '';
            let cftidValue = '';

            if (tidInput && counter) {
                tidValue = tidInput.value.replace(/\D/g, '');
                tidInput.value = tidValue;
                counter.textContent = `${tidValue.length}/12 digits`;

                if (tidValue.length === 12) {
                    counter.style.color = '#28a745';
                    counter.classList.add('text-success');
                    counter.classList.remove('text-muted');
                } else {
                    counter.style.color = '#6c757d';
                    counter.classList.remove('text-success');
                    counter.classList.add('text-muted');
                }
            }

            if (cftidInput && confirmCounter) {
                cftidValue = cftidInput.value.replace(/\D/g, '');
                cftidInput.value = cftidValue;
                confirmCounter.textContent = `${cftidValue.length}/12 digits`;

                if (cftidValue.length === 12) {
                    confirmCounter.style.color = '#28a745';
                    confirmCounter.classList.add('text-success');
                    confirmCounter.classList.remove('text-muted');
                } else {
                    confirmCounter.style.color = '#6c757d';
                    confirmCounter.classList.remove('text-success');
                    confirmCounter.classList.add('text-muted');
                }
            }

            // Enable submit button
            if (submitBtn) {
                const tidValid = tidValue.length === 12;
                const cftidValid = cftidValue.length === 12;
                const idsMatch = tidValue === cftidValue && tidValue !== '';
                const termsAccepted = tandc ? tandc.checked : false;

                if (tidValid && cftidValid && idsMatch && termsAccepted) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.classList.add('btn-success');
                    submitBtn.textContent = 'Complete Payment';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.classList.remove('btn-success');
                    submitBtn.classList.add('btn-secondary');
                    submitBtn.textContent = 'Complete Payment (Disabled)';
                }

                const mismatchWarning = document.getElementById('mismatch-warning');
                if (mismatchWarning) {
                    if (tidValue && cftidValue && tidValue !== cftidValue) {
                        mismatchWarning.style.display = 'block';
                    } else {
                        mismatchWarning.style.display = 'none';
                    }
                }
            }
        }

        // Order type selection
        function selectOrderType(type) {
            document.querySelectorAll('.order-type-option').forEach(option => {
                option.classList.remove('selected');
            });

            event.currentTarget.classList.add('selected');
            document.getElementById(type).checked = true;

            updateLabelsBasedOnOrderType(type);
            updateCostDisplay(type);
        }

        // Update labels based on order type
        function updateLabelsBasedOnOrderType(orderType) {
            const pickupIcon = document.getElementById('pickup-icon');
            const pickupTitle = document.getElementById('pickup-title');
            const timeLabel = document.getElementById('time-label');

            if (orderType === 'takeaway') {
                pickupIcon.className = 'bi bi-bag-fill';
                pickupTitle.textContent = 'Pickup Information';
                timeLabel.textContent = 'Preferred Pickup Time';
            } else {
                pickupIcon.className = 'bi bi-geo-alt-fill';
                pickupTitle.textContent = 'Dining Information';
                timeLabel.textContent = 'Preferred Dining Time';
            }
        }

        // Calculate minimum pickup time
        function updateMinimumPickupTime() {
            const now = new Date();
            const minTime = new Date(now.getTime() + 30 * 60000);

            const hours = String(minTime.getHours()).padStart(2, '0');
            const minutes = String(minTime.getMinutes()).padStart(2, '0');
            minTimeString = `${hours}:${minutes}`;

            const timeDisplay = document.getElementById('min-time-display');
            if (timeDisplay) {
                // Convert to 12-hour format for display
                timeDisplay.textContent = convertTo12HourFormat(minTimeString);
            }

            const timeInput = document.getElementById('delivery_time');
            if (timeInput) {
                timeInput.min = minTimeString;
            }
        }

        // Validate pickup time
        function validatePickupTime(selectedTime) {
            if (!selectedTime) return false;

            const now = new Date();
            const minTime = new Date(now.getTime() + 30 * 60000);

            const [hours, minutes] = selectedTime.split(':');
            const selectedDateTime = new Date();
            selectedDateTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);

            return selectedDateTime >= minTime;
        }

        // Show error message
        function showError(message) {
            const errorDiv = document.getElementById('payment-error');
            const errorMessage = document.getElementById('error-message');

            if (errorDiv && errorMessage) {
                errorMessage.textContent = message;
                errorDiv.style.display = 'block';

                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 5000);
            }
        }

        // Hide all messages
        function hideAllMessages() {
            document.getElementById('payment-success').style.display = 'none';
            document.getElementById('payment-processing').style.display = 'none';
            document.getElementById('payment-error').style.display = 'none';
        }

        // DOM Content Loaded
        document.addEventListener('DOMContentLoaded', function () {
            updateMinimumPickupTime();
            updateCostDisplay('dine-in');
            setInterval(updateMinimumPickupTime, 60000);

            const tidInput = document.getElementById('tid');
            const cftidInput = document.getElementById('cftid');
            const tandc = document.getElementById('tandc');
            const deliveryTimeInput = document.getElementById('delivery_time');
            const pointsInput = document.getElementById('points-to-use');

            if (tidInput) {
                tidInput.addEventListener('input', validateTransactionId);
                tidInput.addEventListener('paste', function () {
                    setTimeout(validateTransactionId, 50);
                });
            }

            if (cftidInput) {
                cftidInput.addEventListener('input', validateTransactionId);
                cftidInput.addEventListener('paste', function () {
                    setTimeout(validateTransactionId, 50);
                });
            }

            if (tandc) {
                tandc.addEventListener('change', validateTransactionId);
            }

            if (pointsInput) {
                pointsInput.addEventListener('input', function () {
                    const value = parseInt(this.value) || 0;
                    const maxUsable = Math.min(maxPoints, getCurrentSubtotal());
                    if (value > maxUsable) {
                        this.value = maxUsable;
                    }
                    if (value < 0) {
                        this.value = 0;
                    }
                    updateCostDisplay();
                });
            }

            if (deliveryTimeInput) {
                deliveryTimeInput.addEventListener('change', function () {
                    validatePickupTime(this.value);
                });
                deliveryTimeInput.addEventListener('input', function () {
                    validatePickupTime(this.value);
                });
            }

            document.querySelectorAll('input[name="order_type"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    updateLabelsBasedOnOrderType(this.value);
                    updateCostDisplay(this.value);
                });
            });

            // Form submission handling
            document.getElementById('payment-form').addEventListener('submit', function (e) {
                e.preventDefault();

                if (isFormSubmitting) {
                    return false;
                }

                // Validate required fields
                const requiredFields = [
                    { id: 'delivery_time', name: 'Pickup Time' }
                ];

                let missingFields = [];
                for (let field of requiredFields) {
                    const fieldValue = document.getElementById(field.id).value.trim();
                    if (!fieldValue) {
                        missingFields.push(field.name);
                    }
                }

                const orderType = document.querySelector('input[name="order_type"]:checked');
                if (!orderType) {
                    missingFields.push('Order Type');
                }

                if (missingFields.length > 0) {
                    showError('Please fill in the following required fields: ' + missingFields.join(', '));
                    return false;
                }

                if (!document.getElementById('tandc').checked) {
                    showError('Please accept the terms and conditions.');
                    return false;
                }

                const pickupTime = document.getElementById('delivery_time').value;
                if (!validatePickupTime(pickupTime)) {
                    showError('Please select a pickup time at least 30 minutes from now.');
                    return false;
                }

                const tidValue = document.getElementById('tid').value.replace(/\D/g, '');
                const cftidValue = document.getElementById('cftid').value.replace(/\D/g, '');

                if (tidValue.length !== 12) {
                    showError('Please enter a valid 12-digit transaction ID.');
                    return false;
                }

                if (cftidValue.length !== 12) {
                    showError('Please confirm your 12-digit transaction ID.');
                    return false;
                }

                if (tidValue !== cftidValue) {
                    showError('Transaction IDs do not match. Please confirm your transaction ID.');
                    return false;
                }

                updateCostDisplay(orderType.value);

                isFormSubmitting = true;
                hideAllMessages();
                document.getElementById('payment-processing').style.display = 'block';

                const submitBtn = document.getElementById('submit-btn');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';

                setTimeout(() => {
                    this.submit();
                }, 500);
            });

            setTimeout(validateTransactionId, 100);
        });

        // ===== COUPON LOGIC =====
        window.activeCouponDiscount = 0;

        function applyCoupon() {
            const codeInput = document.getElementById('coupon-code-input');
            const btn = document.getElementById('apply-coupon-btn');
            const msgBox = document.getElementById('coupon-message');
            const discountRow = document.getElementById('coupon-discount-item');
            const discountAmtSpan = document.getElementById('coupon-discount-amount');
            const hiddenCouponVal = document.getElementById('coupon-discount-val');

            const code = codeInput.value.trim().toUpperCase();

            if (!code) {
                msgBox.style.display = 'block';
                msgBox.style.color = '#dc3545';
                msgBox.textContent = 'Please enter a coupon code.';
                // Reset if they cleared it
                window.activeCouponDiscount = 0;
                discountRow.style.display = 'none';
                hiddenCouponVal.value = 0;
                updateCostDisplay();
                return;
            }

            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            btn.disabled = true;

            const requestData = {
                code: code,
                base_cost: getCurrentSubtotal() // Check against current cart cost including takeaway
            };

            fetch('validate_coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            })
                .then(response => response.json())
                .then(data => {
                    btn.innerHTML = 'Apply';
                    btn.disabled = false;

                    msgBox.style.display = 'block';
                    if (data.success) {
                        msgBox.style.color = '#28a745';
                        msgBox.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + data.message;

                        window.activeCouponDiscount = data.discount;
                        discountRow.style.display = 'flex';
                        discountAmtSpan.textContent = '-' + data.discount.toFixed(2) + ' INR';
                        hiddenCouponVal.value = data.discount;
                        document.getElementById('coupon-id-val').value = data.coupon_id;
                        document.getElementById('coupon-code-val').value = code;

                        // Disable input so they don't change it without clearing
                        codeInput.disabled = true;
                        btn.textContent = 'Remove';
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-outline-danger');
                        btn.onclick = removeCoupon;

                        updateCostDisplay();
                    } else {
                        msgBox.style.color = '#dc3545';
                        msgBox.innerHTML = '<i class="bi bi-x-circle-fill"></i> ' + data.message;

                        window.activeCouponDiscount = 0;
                        discountRow.style.display = 'none';
                        hiddenCouponVal.value = 0;
                        document.getElementById('coupon-id-val').value = 0; // Added this line
                        updateCostDisplay();
                    }
                })
                .catch(error => {
                    btn.innerHTML = 'Apply';
                    btn.disabled = false;
                    console.error('Error applying coupon:', error);
                    msgBox.style.display = 'block';
                    msgBox.style.color = '#dc3545';
                    msgBox.textContent = 'Server error checking coupon. Please try again.';
                });
        }

        function removeCoupon() {
            const codeInput = document.getElementById('coupon-code-input');
            const btn = document.getElementById('apply-coupon-btn');
            const msgBox = document.getElementById('coupon-message');
            const discountRow = document.getElementById('coupon-discount-item');
            const hiddenCouponVal = document.getElementById('coupon-discount-val');
            const hiddenCouponId = document.getElementById('coupon-id-val');

            // Revert UI to empty state
            codeInput.disabled = false;
            codeInput.value = '';

            btn.textContent = 'Apply';
            btn.classList.remove('btn-outline-danger');
            btn.classList.add('btn-outline-primary');
            btn.onclick = applyCoupon;

            msgBox.style.display = 'none';
            discountRow.style.display = 'none';

            window.activeCouponDiscount = 0;
            hiddenCouponVal.value = 0;
            hiddenCouponId.value = 0;
            document.getElementById('coupon-code-val').value = '';

            updateCostDisplay();
        }
    </script>
</body>

</html>