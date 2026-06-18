<!DOCTYPE html>
<html lang="en">

<head>
    <?php
session_start();
include("conn_db.php");

// Check if user is logged in and order details exist
if (!isset($_SESSION["cid"])) {
    header("location: restricted.php");
    exit(1);
}

// Check if we have order details from the transaction
if (!isset($_SESSION['order_details']) && !isset($_GET["orh"])) {
    header("Location: index.php");
    exit();
}

include('head.php');

// Get order details either from session or from database
$order_details = null;
$points_used = 0;

if (isset($_SESSION['order_details'])) {
    // Get from session (preferred method)
    $order_details = $_SESSION['order_details'];
    $points_used = $order_details['points_used'] ?? 0;
}
else if (isset($_GET["orh"])) {
    // Fallback: get from database using order ID
    $order_id = intval($_GET["orh"]);
    $points_used = intval($_GET["points_used"] ?? 0);

    try {
        $order_query = "SELECT * FROM transaction WHERE id = ? AND c_id = ?";
        $order_stmt = $mysqli->prepare($order_query);
        $order_stmt->bind_param("ii", $order_id, $_SESSION["cid"]);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();

        if ($order_row = $order_result->fetch_assoc()) {
            $order_details = array(
                'transaction_id' => $order_row['tid'],
                'customer_name' => $order_row['name'],
                'order_cost' => $order_row['order_cost'],
                'points_used' => $order_row['points_used'] ?? 0,
                'points_discount' => $order_row['points_used'] ?? 0,
                'coupon_discount' => $order_row['coupon_discount'] ?? 0,
                'final_amount' => $order_row['order_cost'] - ($order_row['points_used'] ?? 0) - ($order_row['coupon_discount'] ?? 0),
                'order_type' => $order_row['order_type'] ?? 'dine-in',
                'delivery_time' => $order_row['pickup_time'],
                'order_time' => $order_row['created_at']
            );
        }
    }
    catch (Exception $e) {
        error_log("Error fetching order details: " . $e->getMessage());
        header("Location: index.php");
        exit();
    }
}

// If still no order details, redirect
if (!$order_details) {
    header("Location: index.php");
    exit();
}
?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/main.css" rel="stylesheet">
    <title>Order Successful | NNRG-CAFE</title>

    <style>
        .success-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounceIn 0.8s ease-out;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }

            50% {
                transform: scale(1.05);
            }

            70% {
                transform: scale(0.9);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            0% {
                transform: translateY(30px);
                opacity: 0;
            }

            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .order-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            border-left: 4px solid #28a745;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
            font-size: 16px;
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            color: #28a745;
        }

        .points-savings {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin: 25px 0;
            animation: fadeInUp 0.6s ease-out;
        }

        .scratch-card-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            margin: 25px 0;
        }

        /* Fixed next-steps container styling */
        .next-steps {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            text-align: left;
            /* Ensure left alignment for the entire container */
        }

        .next-steps h5 {
            text-align: left;
            margin-bottom: 25px;
            color: #333;
        }

        /* Fixed step item alignment */
        .step-item {
            display: flex;
            align-items: flex-start;
            /* Changed from 'start' to 'flex-start' for better compatibility */
            margin-bottom: 20px;
            text-align: left;
            width: 100%;
        }

        .step-item:last-child {
            margin-bottom: 0;
        }

        .step-badge {
            min-width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            font-size: 14px;
            flex-shrink: 0;
            /* Prevent badge from shrinking */
        }

        .step-content {
            flex: 1;
            text-align: left;
        }

        .step-content strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-size: 16px;
        }

        .step-content p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
            line-height: 1.4;
        }

        .btn-action {
            margin: 5px;
            min-width: 140px;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .savings-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin: 20px 0;
        }

        .savings-stat {
            flex: 1;
        }

        .savings-stat h6 {
            margin-bottom: 5px;
            opacity: 0.9;
            font-size: 14px;
        }

        .savings-stat .amount {
            font-size: 24px;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .success-container {
                margin: 20px;
                padding: 20px;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }

            .savings-stats {
                flex-direction: column;
                gap: 15px;
            }

            .step-item {
                align-items: flex-start;
            }

            .step-badge {
                min-width: 30px;
                height: 30px;
                margin-right: 12px;
                font-size: 12px;
            }

            .step-content strong {
                font-size: 15px;
            }

            .step-content p {
                font-size: 13px;
            }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <?php include('nav_header.php')?>

    <div class="container" style="margin-top: 100px;">
        <div class="success-container text-center">
            <!-- Success Icon and Message -->
            <div class="success-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <h2 class="text-success mb-3">Order Placed Successfully!</h2>
            <p class="lead mb-4">Thank you for your order. Your payment has been processed and your delicious food is
                being prepared!</p>

            <!-- Savings Display -->
            <?php if ($order_details['points_used'] > 0 || ($order_details['coupon_discount'] ?? 0) > 0): ?>
            <div class="points-savings">
                <h5 class="mb-3">
                    <i class="bi bi-tag-fill me-2"></i>
                    Congratulations! You Saved Money!
                </h5>

                <div class="savings-stats">
                    <div class="savings-stat">
                        <h6>Original Total</h6>
                        <div class="amount">₹
                            <?php echo number_format($order_details['order_cost'], 2); ?>
                        </div>
                    </div>
                    <?php if ($order_details['points_used'] > 0): ?>
                    <div class="savings-stat">
                        <h6>Points Used</h6>
                        <div class="amount">-₹
                            <?php echo number_format($order_details['points_used'], 2); ?>
                        </div>
                    </div>
                    <?php
    endif; ?>
                    <?php if (($order_details['coupon_discount'] ?? 0) > 0): ?>
                    <div class="savings-stat">
                        <h6>Coupon Savings</h6>
                        <div class="amount">-₹
                            <?php echo number_format($order_details['coupon_discount'], 2); ?>
                        </div>
                    </div>
                    <?php
    endif; ?>
                    <div class="savings-stat">
                        <h6>You Paid</h6>
                        <div class="amount">₹
                            <?php echo number_format($order_details['final_amount'], 2); ?>
                        </div>
                    </div>
                </div>

                <hr style="border-color: rgba(255,255,255,0.3); margin: 20px 0;">
                <p class="mb-0">
                    <i class="bi bi-star-fill me-2"></i>
                    You saved a total of ₹
                    <?php echo number_format(($order_details['points_discount'] ?? 0) + ($order_details['coupon_discount'] ?? 0), 2); ?>
                    on this order!
                </p>
            </div>
            <?php
endif; ?>

            <!-- Order Details -->
            <div class="order-details text-left">
                <h5 class="mb-3">
                    <i class="bi bi-receipt me-2"></i>Order Details
                </h5>

                <div class="detail-row">
                    <span><i class="bi bi-hash me-2"></i>Transaction ID:</span>
                    <strong>
                        <?php echo htmlspecialchars($order_details['transaction_id']); ?>
                    </strong>
                </div>

                <div class="detail-row">
                    <span><i class="bi bi-person me-2"></i>Customer Name:</span>
                    <span>
                        <?php echo htmlspecialchars($order_details['customer_name']); ?>
                    </span>
                </div>

                <div class="detail-row">
                    <span><i class="bi bi-bag me-2"></i>Order Type:</span>
                    <span class="text-capitalize">
                        <?php
$order_type = $order_details['order_type'];
echo $order_type === 'takeaway' ? 'Takeaway' : 'Dine-In';
?>
                    </span>
                </div>

                <?php if ($order_details['delivery_time']): ?>
                <div class="detail-row">
                    <span><i class="bi bi-clock me-2"></i>Pickup Time:</span>
                    <span>
                        <?php echo date('g:i A', strtotime($order_details['delivery_time'])); ?>
                    </span>
                </div>
                <?php
endif; ?>

                <div class="detail-row">
                    <span><i class="bi bi-calendar me-2"></i>Order Time:</span>
                    <span>
                        <?php echo date('M j, Y g:i A', strtotime($order_details['order_time'])); ?>
                    </span>
                </div>

                <?php if ($order_details['points_used'] > 0): ?>
                <div class="detail-row">
                    <span><i class="bi bi-coin me-2"></i>Points Used:</span>
                    <span class="text-success">
                        <?php echo $order_details['points_used']; ?> points (₹
                        <?php echo number_format($order_details['points_discount'] ?? $order_details['points_used'], 2); ?>)
                    </span>
                </div>
                <?php
endif; ?>

                <?php if (($order_details['coupon_discount'] ?? 0) > 0): ?>
                <div class="detail-row">
                    <span><i class="bi bi-tag me-2"></i>Coupon Applied:</span>
                    <span class="text-success">-₹
                        <?php echo number_format($order_details['coupon_discount'], 2); ?>
                    </span>
                </div>
                <?php
endif; ?>

                <div class="detail-row">
                    <span><i class="bi bi-currency-rupee me-2"></i>Amount Paid:</span>
                    <strong class="text-success">₹
                        <?php echo number_format($order_details['final_amount'], 2); ?>
                    </strong>
                </div>
            </div>

            <!-- Order Process Steps -->
            <div class="next-steps">
                <h5 class="mb-4">
                    <i class="bi bi-list-check me-2"></i>What Happens Next?
                </h5>

                <div class="step-item">
                    <div class="step-badge bg-warning text-dark">1</div>
                    <div class="step-content">
                        <strong>Order Verification</strong>
                        <p>Admin will review and verify your order details.</p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-badge bg-primary text-white">2</div>
                    <div class="step-content">
                        <strong>Order Approved</strong>
                        <p>Once approved, you'll receive your scratch card and kitchen will start preparation.</p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-badge bg-info text-white">3</div>
                    <div class="step-content">
                        <strong>Food Preparation</strong>
                        <p>Our chefs will prepare your delicious order with care.</p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-badge bg-success text-white">4</div>
                    <div class="step-content">
                        <strong>Ready for Pickup/Serving</strong>
                        <p>We'll notify you when your order is ready!</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex flex-wrap justify-content-center mt-4">
                <a href="index.php" class="btn btn-outline-primary btn-action">
                    <i class="bi bi-house me-2"></i>Back to Home
                </a>
                <a href="cust_order_history.php" class="btn btn-primary btn-action">
                    <i class="bi bi-clock-history me-2"></i>Order History
                </a>
                <a href="scratch_cards.php" class="btn btn-success btn-action">
                    <i class="bi bi-gift me-2"></i>My Scratch Cards
                </a>
            </div>


        </div>
    </div>

    <?php
// Clear the order details from session after displaying
if (isset($_SESSION['order_details'])) {
    unset($_SESSION['order_details']);
}
?>

    <?php include('footer.php'); ?>

    <script>
        // Auto-redirect after 30 seconds (optional)
        setTimeout(function () {
            // You can enable this if you want auto-redirect
            // window.location.href = 'cust_order_history.php';
        }, 30000);

        // Add some celebration confetti effect (optional)
        document.addEventListener('DOMContentLoaded', function () {
            // Simple celebration effect for successful orders with points savings
            <?php if  ($order_details['points_used'] > 0): ?>
                console.log('Order completed with points savings!');
            <?php
endif; ?>
        });
    </script>
</body>

</html>