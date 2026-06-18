<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
    session_start();
    if(!isset($_SESSION["cid"])){
        header("location: restricted.php");
        exit(1);
    }
    include("conn_db.php");
    include("scratch_card_functions.php");
    include('head.php');
    
    // Initialize scratch card system with error handling
    try {
        $scratchSystem = new ScratchCardSystem($mysqli);
        $points_balance = $scratchSystem->getCustomerPointsBalance($_SESSION["cid"]);
    } catch (Exception $e) {
        error_log("Error initializing scratch card system: " . $e->getMessage());
        $points_balance = 0;
        $error_message = "Unable to load points balance. Please try refreshing the page.";
    }
    
    // Get cart total with error handling
    $cart_total = 0;
    try {
        if(isset($_SESSION['cart_total'])) {
            $cart_total = $_SESSION['cart_total'];
        } else {
            // Calculate cart total from cart table
            $cart_query = "SELECT SUM(ct.ct_amount * f.f_price) as total 
                           FROM cart ct 
                           JOIN food f ON ct.f_id = f.f_id 
                           WHERE ct.c_id = ?";
            $cart_stmt = $mysqli->prepare($cart_query);
            if (!$cart_stmt) {
                throw new Exception("Failed to prepare cart query: " . $mysqli->error);
            }
            
            $cart_stmt->bind_param("i", $_SESSION["cid"]);
            if (!$cart_stmt->execute()) {
                throw new Exception("Failed to execute cart query: " . $cart_stmt->error);
            }
            
            $cart_result = $cart_stmt->get_result()->fetch_assoc();
            $cart_total = $cart_result['total'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Error calculating cart total: " . $e->getMessage());
        $cart_total = 0;
        $error_message = "Unable to calculate cart total. Please try refreshing the page.";
    }
    
    // Handle points usage form submission
    if(isset($_POST['use_points'])) {
        try {
            $points_to_use = intval($_POST['points_amount']);
            
            // Validation
            if($points_to_use <= 0) {
                $error_message = "Please enter a valid points amount (greater than 0).";
            } elseif($points_to_use > $points_balance) {
                $error_message = "You don't have enough points. Available: " . $points_balance . " points, Requested: " . $points_to_use . " points.";
            } elseif($points_to_use > $cart_total) {
                $error_message = "Points cannot exceed cart total. Cart total: ₹" . number_format($cart_total, 2) . ", Requested points: " . $points_to_use;
            } else {
                // Store points usage in session
                $_SESSION['points_to_use'] = $points_to_use;
                $_SESSION['final_amount'] = max(0, $cart_total - $points_to_use);
                $success_message = "Points applied successfully! You'll save ₹" . number_format($points_to_use, 2);
                
                // Log successful points application
                error_log("Points applied successfully - Customer: {$_SESSION['cid']}, Points: $points_to_use, Cart: $cart_total, Final: " . $_SESSION['final_amount']);
            }
        } catch (Exception $e) {
            error_log("Error applying points: " . $e->getMessage());
            $error_message = "An error occurred while applying points. Please try again.";
        }
    }
    
    // Clear points usage
    if(isset($_POST['clear_points'])) {
        try {
            unset($_SESSION['points_to_use']);
            unset($_SESSION['final_amount']);
            $success_message = "Points usage cleared successfully.";
            error_log("Points usage cleared for customer: {$_SESSION['cid']}");
        } catch (Exception $e) {
            error_log("Error clearing points: " . $e->getMessage());
            $error_message = "An error occurred while clearing points.";
        }
    }
    
    $points_to_use = $_SESSION['points_to_use'] ?? 0;
    $final_amount = $_SESSION['final_amount'] ?? $cart_total;
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/menu.css" rel="stylesheet">
    <title>Checkout with Points | NNRG-CAFÉ</title>
    
    <style>
        .points-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .points-input {
            max-width: 150px;
            display: inline-block;
        }
        
        .savings-highlight {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 15px 0;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #007bff;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .amount-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
            color: #28a745;
        }
        
        .quick-points {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .quick-point-btn {
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quick-point-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .empty-cart-message {
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .validation-error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
        }
        
        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php include('nav_header.php')?>

    <div class="container px-5 py-4" id="cart-body">
        <div class="row my-4 pb-2 border-bottom">
            <a class="nav nav-item text-decoration-none text-muted mb-2" href="#" onclick="history.back();">
                <i class="bi bi-arrow-left-square me-2"></i>Go back
            </a>

            <!-- Success/Error Messages -->
            <?php if(isset($success_message)): ?>
                <div class="row row-cols-1 notibar">
                    <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                        <i class="bi bi-check-circle"></i>
                        <span class="ms-2 mt-2"><?php echo htmlspecialchars($success_message); ?></span>
                        <span class="me-2 float-end"><a class="text-decoration-none link-light" href="checkout_with_points.php">X</a></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="row row-cols-1 notibar">
                    <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                        <i class="bi bi-x-circle"></i>
                        <span class="ms-2 mt-2"><?php echo htmlspecialchars($error_message); ?></span>
                        <span class="me-2 float-end"><a class="text-decoration-none link-light" href="checkout_with_points.php">X</a></span>
                    </div>
                </div>
            <?php endif; ?>

            <h2 class="pt-3 display-6"><i class="bi bi-credit-card"></i> Checkout with Points</h2>
        </div>

        <?php if($cart_total <= 0): ?>
            <!-- Empty Cart Message -->
            <div class="empty-cart-message">
                <i class="bi bi-cart-x" style="font-size: 3rem; color: #6c757d;"></i>
                <h4 class="mt-3 mb-2">Your Cart is Empty</h4>
                <p class="text-muted mb-3">Add some delicious items to your cart before proceeding to checkout.</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-shop"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Order Summary -->
                <div class="order-summary mb-4">
                    <h5 class="mb-3"><i class="bi bi-receipt"></i> Order Summary</h5>
                    
                    <div class="amount-row">
                        <span>Cart Total:</span>
                        <span>₹<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <?php if($points_to_use > 0): ?>
                        <div class="amount-row">
                            <span>Points Used:</span>
                            <span class="text-success">-₹<?php echo number_format($points_to_use, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="amount-row">
                        <span>Final Amount:</span>
                        <span>₹<?php echo number_format($final_amount, 2); ?></span>
                    </div>
                </div>

                <!-- Points Usage Section -->
                <div class="points-section">
                    <h5 class="mb-3"><i class="bi bi-coin"></i> Use Your Points</h5>
                    
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <p class="mb-2">Available Balance: <strong><?php echo number_format($points_balance); ?> Points</strong></p>
                            <p class="mb-0 opacity-75">1 Point = ₹1 | Max usable: <?php echo min($points_balance, $cart_total); ?> points</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <?php if($points_balance > 0): ?>
                                <a href="scratch_cards.php" class="btn btn-light btn-sm">
                                    <i class="bi bi-gift"></i> Get More Points
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if($points_balance > 0): ?>
                        <form method="POST" action="checkout_with_points.php" class="mb-3" id="points-form">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label for="points_amount" class="form-label">Points to Use:</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="points_amount" 
                                           name="points_amount" 
                                           min="1" 
                                           max="<?php echo min($points_balance, $cart_total); ?>" 
                                           value="<?php echo $points_to_use; ?>" 
                                           placeholder="Enter points"
                                           oninput="validatePointsInput(this)">
                                    <div class="invalid-feedback" id="points-error"></div>
                                </div>
                                <div class="col-md-8 mt-2">
                                    <button type="submit" name="use_points" class="btn btn-light me-2" id="apply-points-btn">
                                        <i class="bi bi-check-circle"></i> Apply Points
                                    </button>
                                    <?php if($points_to_use > 0): ?>
                                        <button type="submit" name="clear_points" class="btn btn-outline-light">
                                            <i class="bi bi-x-circle"></i> Clear
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>

                        <!-- Quick Points Selection -->
                        <?php 
                        $max_usable = min($points_balance, $cart_total);
                        if($max_usable > 0): 
                        ?>
                        <div class="mb-3">
                            <label class="form-label mb-2">Quick Select:</label>
                            <div class="quick-points">
                                <?php
                                $quick_amounts = [];
                                
                                if($max_usable >= 25) $quick_amounts[] = 25;
                                if($max_usable >= 50) $quick_amounts[] = 50;
                                if($max_usable >= 100) $quick_amounts[] = 100;
                                if($max_usable >= 200) $quick_amounts[] = 200;
                                if($max_usable > 0) $quick_amounts[] = $max_usable; // Use all
                                
                                // Remove duplicates and sort
                                $quick_amounts = array_unique($quick_amounts);
                                sort($quick_amounts);
                                
                                foreach($quick_amounts as $amount):
                                ?>
                                    <button type="button" class="quick-point-btn" 
                                            onclick="setPointsAmount(<?php echo $amount; ?>)">
                                        <?php echo $amount == $max_usable ? 'Use All (' . $amount . ')' : $amount . ' Points'; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-gift" style="font-size: 3rem; opacity: 0.5;"></i>
                            <p class="mt-2 mb-3 opacity-75">You don't have any points yet</p>
                            <a href="scratch_cards.php" class="btn btn-light">
                                <i class="bi bi-gift"></i> Get Points from Scratch Cards
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Savings Highlight -->
                <?php if($points_to_use > 0): ?>
                    <div class="savings-highlight">
                        <h5 class="mb-2"><i class="bi bi-piggy-bank"></i> You're Saving!</h5>
                        <p class="mb-0">You'll save ₹<?php echo number_format($points_to_use, 2); ?> on this order using your points!</p>
                    </div>
                <?php endif; ?>

                <!-- Information Box -->
                <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Points Information</h6>
                    <ul class="mb-0">
                        <li>1 Point = ₹1 discount</li>
                        <li>Points can be used partially or fully</li>
                        <li>Points expire after 1 year from earned date</li>
                        <li>Get more points by scratching cards from completed orders</li>
                        <li>Points are applied on a first-in, first-out basis</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Cart Items Display -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-cart3"></i> Cart Items</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get cart items with error handling
                        try {
                            $cart_items_query = "SELECT c.ct_amount as quantity, f.f_name, f.f_price, (c.ct_amount * f.f_price) as item_total
                                               FROM cart c 
                                               JOIN food f ON c.f_id = f.f_id 
                                               WHERE c.c_id = ?
                                               ORDER BY f.f_name";
                            $items_stmt = $mysqli->prepare($cart_items_query);
                            
                            if (!$items_stmt) {
                                throw new Exception("Failed to prepare cart items query");
                            }
                            
                            $items_stmt->bind_param("i", $_SESSION["cid"]);
                            
                            if (!$items_stmt->execute()) {
                                throw new Exception("Failed to execute cart items query");
                            }
                            
                            $cart_items = $items_stmt->get_result();
                            
                            if($cart_items->num_rows > 0):
                                while($item = $cart_items->fetch_assoc()):
                        ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['f_name']); ?></strong>
                                    <small class="text-muted d-block">Qty: <?php echo intval($item['quantity']); ?> × ₹<?php echo number_format($item['f_price'], 2); ?></small>
                                </div>
                                <div class="text-end">
                                    <strong>₹<?php echo number_format($item['item_total'], 2); ?></strong>
                                </div>
                            </div>
                        <?php 
                                endwhile;
                            else:
                        ?>
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-cart-x"></i>
                                <p class="mt-2 mb-0">Your cart is empty</p>
                            </div>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            error_log("Error fetching cart items: " . $e->getMessage());
                        ?>
                            <div class="text-center text-danger py-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                <p class="mt-2 mb-0">Error loading cart items</p>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Proceed to Payment -->
                <div class="card mt-3">
                    <div class="card-body text-center">
                        <h5 class="mb-3">Total: ₹<?php echo number_format($final_amount, 2); ?></h5>
                        
                        <form method="POST" action="payment.php" class="mb-3">
                            <input type="hidden" name="points_used" value="<?php echo $points_to_use; ?>">
                            <input type="hidden" name="final_amount" value="<?php echo $final_amount; ?>">
                            <input type="hidden" name="cart_verified" value="1">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-credit-card"></i> Proceed to Payment
                            </button>
                        </form>
                        
                        <small class="text-muted">
                            <?php if($points_to_use > 0): ?>
                                Using <?php echo $points_to_use; ?> points (₹<?php echo number_format($points_to_use, 2); ?>)
                            <?php else: ?>
                                No points will be used
                            <?php endif; ?>
                        </small>
                    </div>
                </div>

                <!-- Points Statistics -->
                <?php if($points_balance > 0): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-graph-up"></i> Your Points Stats</h6>
                        <?php 
                        try {
                            $stats = $scratchSystem->getCustomerStats($_SESSION["cid"]);
                        ?>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="fw-bold text-primary"><?php echo $stats['current_balance'] ?? 0; ?></div>
                                <small class="text-muted">Available</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-success"><?php echo $stats['total_earned'] ?? 0; ?></div>
                                <small class="text-muted">Total Earned</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-info"><?php echo $stats['total_used'] ?? 0; ?></div>
                                <small class="text-muted">Total Used</small>
                            </div>
                        </div>
                        <?php 
                        } catch (Exception $e) {
                            error_log("Error getting customer stats: " . $e->getMessage());
                        ?>
                        <p class="text-muted mb-0">Statistics temporarily unavailable</p>
                        <?php } ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <?php include('footer.php')?>

    <script>
        // Validate points input
        function validatePointsInput(input) {
            const value = parseInt(input.value) || 0;
            const max = parseInt(input.max) || 0;
            const errorDiv = document.getElementById('points-error');
            const submitBtn = document.getElementById('apply-points-btn');
            
            // Clear previous validation states
            input.classList.remove('validation-error');
            errorDiv.textContent = '';
            submitBtn.classList.remove('btn-disabled');
            submitBtn.disabled = false;
            
            if (value <= 0) {
                input.classList.add('validation-error');
                errorDiv.textContent = 'Please enter a valid amount greater than 0.';
                errorDiv.style.display = 'block';
                submitBtn.classList.add('btn-disabled');
                submitBtn.disabled = true;
                return false;
            }
            
            if (value > max) {
                input.classList.add('validation-error');
                errorDiv.textContent = `Maximum ${max} points can be used.`;
                errorDiv.style.display = 'block';
                submitBtn.classList.add('btn-disabled');
                submitBtn.disabled = true;
                return false;
            }
            
            errorDiv.style.display = 'none';
            return true;
        }
        
        // Set points amount from quick select buttons
        function setPointsAmount(amount) {
            const input = document.getElementById('points_amount');
            input.value = amount;
            validatePointsInput(input);
            
            // Add visual feedback
            const buttons = document.querySelectorAll('.quick-point-btn');
            buttons.forEach(btn => btn.style.background = 'rgba(255,255,255,0.1)');
            
            // Highlight selected button briefly
            event.target.style.background = 'rgba(255,255,255,0.3)';
            setTimeout(() => {
                event.target.style.background = 'rgba(255,255,255,0.1)';
            }, 300);
        }
        
        // Auto-calculate and validate on input
        document.addEventListener('DOMContentLoaded', function() {
            const pointsInput = document.getElementById('points_amount');
            if (pointsInput) {
                // Initial validation
                validatePointsInput(pointsInput);
                
                // Validate on input change
                pointsInput.addEventListener('input', function() {
                    validatePointsInput(this);
                });
                
                // Prevent form submission if validation fails
                document.getElementById('points-form').addEventListener('submit', function(e) {
                    if (!validatePointsInput(pointsInput)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
        
        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const notifications = document.querySelectorAll('.notibar');
            notifications.forEach(function(notification) {
                notification.style.transition = 'opacity 0.5s ease';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            });
        }, 5000);
    </script>
</body>

</html>