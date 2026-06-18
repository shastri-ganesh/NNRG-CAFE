<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    // Add error reporting to debug issues
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    session_start();
    if(!isset($_SESSION["cid"])){
        header("location: restricted.php");
        exit(1);
    }
    
    include("conn_db.php");
    include("scratch_card_functions.php");
    include('head.php');
    
    $customer_id = $_SESSION["cid"];
    
    // Initialize scratch card system
    $scratchSystem = new ScratchCardSystem($mysqli);
    
    // Debug output - remove this in production
    echo "<!-- DEBUG: Customer ID = $customer_id -->";
    
    // Handle form submission for scratching cards
    if(isset($_POST['scratch_card']) && isset($_POST['sc_id'])) {
        echo "<!-- DEBUG: Form submitted with sc_id = " . $_POST['sc_id'] . " -->";
        
        $sc_id = intval($_POST['sc_id']);
        
        if($sc_id > 0) {
            echo "<!-- DEBUG: Attempting to scratch card ID $sc_id -->";
            $result = $scratchSystem->scratchCard($sc_id, $customer_id);
            
            echo "<!-- DEBUG: Scratch result = " . print_r($result, true) . " -->";
            
            if($result['success']) {
                $success_message = "🎉 Congratulations! You won ₹" . $result['amount'] . " points!";
                $points_earned = $result['amount'];
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = "Invalid scratch card ID.";
        }
    }
    
    // Get customer's scratch cards
    $scratch_cards = $scratchSystem->getCustomerScratchCards($customer_id);
    $points_balance = $scratchSystem->getCustomerPointsBalance($customer_id);
    
    // Debug output
    echo "<!-- DEBUG: Found " . count($scratch_cards) . " scratch cards -->";
    echo "<!-- DEBUG: Points balance = $points_balance -->";
    ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Scratch Cards - College Canteen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .scratch-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            margin: 10px 0;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: white;
            position: relative;
            overflow: hidden;
            min-height: 280px;
        }
        
        .scratch-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1.5" fill="rgba(255,255,255,0.08)"/><circle cx="50" cy="10" r="1" fill="rgba(255,255,255,0.12)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            opacity: 0.3;
        }
        
        .scratch-amount {
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .scratch-btn {
            background: linear-gradient(45deg, #FFA726, #FF7043);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(255, 167, 38, 0.4);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .scratch-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 167, 38, 0.6);
            background: linear-gradient(45deg, #FF7043, #FFA726);
            color: white;
        }
        
        .scratch-btn:disabled {
            background: linear-gradient(45deg, #6c757d, #495057);
            cursor: not-allowed;
            opacity: 0.7;
            transform: none;
            box-shadow: none;
        }
        
        .scratch-area {
            position: relative;
            width: 200px;
            height: 120px;
            margin: 20px auto;
            border-radius: 10px;
            overflow: hidden;
            background: linear-gradient(45deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .scratch-surface {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #c0c0c0, #808080);
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.8) 2px, transparent 2px),
                radial-gradient(circle at 40% 20%, rgba(255,255,255,0.6) 1px, transparent 1px),
                radial-gradient(circle at 90% 80%, rgba(255,255,255,0.5) 1.5px, transparent 1.5px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            user-select: none;
            border-radius: 10px;
        }
        
        .scratch-surface:hover {
            background: linear-gradient(45deg, #d0d0d0, #909090);
        }
        
        .reveal-amount {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .scratched-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            opacity: 0.8;
        }
        
        .expired-card {
            background: linear-gradient(135deg, #6c757d, #495057);
            opacity: 0.7;
        }
        
        .pending-approval-card {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            opacity: 0.8;
        }
        
        .points-balance {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 15px;
            padding: 25px;
            color: white;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 123, 255, 0.3);
        }
        
        .alert-custom {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: bold;
        }
        
        .status-approved {
            background-color: #28a745;
            color: white;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        
        /* Scratch animation */
        @keyframes scratch {
            0% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.05) rotate(-2deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
        
        .scratching {
            animation: scratch 0.2s ease-in-out;
        }
        
        /* Reveal animation */
        @keyframes reveal {
            0% { 
                opacity: 0; 
                transform: scale(0.8) rotateY(90deg); 
            }
            50% { 
                opacity: 0.7; 
                transform: scale(1.1) rotateY(45deg); 
            }
            100% { 
                opacity: 1; 
                transform: scale(1) rotateY(0deg); 
            }
        }
        
        .revealed {
            animation: reveal 0.8s ease-out;
        }
        
        /* Confetti effect */
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotateZ(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotateZ(720deg);
                opacity: 0;
            }
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f39c12;
            animation: confetti-fall 3s ease-out forwards;
            z-index: 9999;
            pointer-events: none;
        }
        
        .confetti:nth-child(odd) {
            background: #e74c3c;
            width: 8px;
            height: 8px;
            animation-duration: 2.5s;
        }
        
        .confetti:nth-child(even) {
            background: #3498db;
            width: 6px;
            height: 6px;
            animation-duration: 3.5s;
        }
        
        /* Pulse effect for scratchable cards */
        @keyframes pulse {
            0% { box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37); }
            50% { box-shadow: 0 12px 40px rgba(31, 38, 135, 0.6); }
            100% { box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37); }
        }
        
        .can-scratch {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <?php include('nav_header.php'); ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="text-center mb-4">
                    <i class="bi bi-gift"></i> My Scratch Cards
                </h2>
                
                <!-- Points Balance Display -->
                <div class="points-balance">
                    <h3><i class="bi bi-coin"></i> Your Points Balance</h3>
                    <div class="display-4 fw-bold"><?php echo number_format($points_balance); ?> Points</div>
                    <small>1 Point = ₹1 Discount on Orders</small>
                </div>
                
                <!-- Success Message -->
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success alert-custom" role="alert">
                        <i class="bi bi-check-circle"></i>
                        <?php echo $success_message; ?>
                        <br><small>Your points balance has been updated!</small>
                    </div>
                <?php endif; ?>
                
                <!-- Error Message -->
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger alert-custom" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                
                    <?php
                    // Check for approved orders
                    $approved_orders = $mysqli->query("SELECT COUNT(*) as count FROM transaction WHERE c_id = $customer_id AND order_status = 'ACPT'")->fetch_assoc()['count'];
                    echo "";
                    ?>
                </div>
                
                <!-- Scratch Cards Display -->
                <?php if(count($scratch_cards) > 0): ?>
                    <div class="row">
                        <?php foreach($scratch_cards as $card): ?>
                            <?php
                            $is_scratched = ($card['is_scratched'] == '1');
                            $is_expired = (strtotime($card['expires_at']) < time());
                            $can_scratch = $card['can_scratch'];
                            $scratch_reason = $scratchSystem->getScratchBlockReason($card);
                            
                            // Determine card styling
                            if($is_scratched) {
                                $card_class = 'scratched-card';
                            } elseif($is_expired) {
                                $card_class = 'expired-card';
                            } elseif(!$can_scratch) {
                                $card_class = 'pending-approval-card';
                            } else {
                                $card_class = 'can-scratch';
                            }
                            
                            // Status badge
                            $status_text = '';
                            $status_class = '';
                            switch($card['order_status']) {
                                case 'VRFY':
                                    $status_text = 'Verifying';
                                    $status_class = 'status-pending';
                                    break;
                                case 'ACPT':
                                    $status_text = 'Approved';
                                    $status_class = 'status-approved';
                                    break;
                                case 'PREP':
                                    $status_text = 'Preparing';
                                    $status_class = 'status-approved';
                                    break;
                                case 'RDPK':
                                    $status_text = 'Ready';
                                    $status_class = 'status-approved';
                                    break;
                                case 'FNSH':
                                    $status_text = 'Finished';
                                    $status_class = 'status-approved';
                                    break;
                                case 'CNCL':
                                    $status_text = 'Cancelled';
                                    $status_class = 'status-cancelled';
                                    break;
                                default:
                                    $status_text = 'Unknown';
                                    $status_class = 'status-pending';
                            }
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="scratch-card <?php echo $card_class; ?>">
                                    <div class="text-center position-relative" style="z-index: 1;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5><i class="bi bi-gift-fill"></i> Scratch Card</h5>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </div>
                                        
                                        <?php if($is_scratched): ?>
                                            <!-- Show amount for scratched cards -->
                                            <div class="scratch-amount">₹<?php echo $card['scratch_amount']; ?></div>
                                        <?php elseif($can_scratch && !$is_expired): ?>
                                            <!-- Interactive scratch area -->
                                            <div class="scratch-area" id="scratch-<?php echo $card['sc_id']; ?>">
                                                <div class="scratch-surface" onclick="scratchCard(<?php echo $card['sc_id']; ?>, <?php echo $card['scratch_amount']; ?>)">
                                                    <div>
                                                        <i class="bi bi-hand-index-thumb" style="font-size: 1.5rem;"></i><br>
                                                        Scratch Here!
                                                    </div>
                                                </div>
                                                <div class="reveal-amount" style="display: none;">
                                                    ₹<?php echo $card['scratch_amount']; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Show placeholder for non-scratchable cards -->
                                            <div class="scratch-area" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white; font-size: 1.5rem;">
                                                    <i class="bi bi-question-circle"></i>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <p class="mb-1">
                                            <small>
                                                <i class="bi bi-calendar3"></i> 
                                                Created: <?php echo date('M j, Y', strtotime($card['created_at'])); ?>
                                            </small>
                                        </p>
                                        
                                        <p class="mb-3">
                                            <small>
                                                <i class="bi bi-receipt"></i>
                                                Order: #<?php echo $card['tid']; ?>
                                                <?php if($card['order_cost']): ?>
                                                    (₹<?php echo number_format($card['order_cost'], 2); ?>)
                                                <?php endif; ?>
                                            </small>
                                        </p>
                                        
                                        <?php if($is_scratched): ?>
                                            <div class="text-center">
                                                <i class="bi bi-check-circle-fill" style="font-size: 2rem;"></i>
                                                <p class="mt-2 mb-0">Already Scratched!</p>
                                                <small>Scratched on <?php echo date('M j, Y g:i A', strtotime($card['scratched_at'])); ?></small>
                                            </div>
                                        <?php elseif($is_expired): ?>
                                            <div class="text-center">
                                                <i class="bi bi-x-circle-fill" style="font-size: 2rem;"></i>
                                                <p class="mt-2 mb-0">Expired</p>
                                                <small>Expired on <?php echo date('M j, Y', strtotime($card['expires_at'])); ?></small>
                                            </div>
                                        <?php elseif(!$can_scratch): ?>
                                            <div class="text-center">
                                                <i class="bi bi-clock-fill" style="font-size: 2rem; color: #ffc107;"></i>
                                                <p class="mt-2 mb-1">Cannot Scratch Yet</p>
                                                <small class="text-warning fw-bold"><?php echo $scratch_reason; ?></small>
                                                
                                                <?php if($card['order_status'] == 'VRFY'): ?>
                                                    <div class="mt-2">
                                                        <small class="text-light">
                                                            <i class="bi bi-info-circle"></i> 
                                                            Your order is being verified by admin. You'll be able to scratch this card once approved!
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <!-- Card can be scratched -->
                                            <div class="text-center">
                                                <div class="mt-2">
                                                    <small class="text-success">
                                                        <i class="bi bi-check-circle"></i> Ready to scratch!
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-2">
                                            <small class="text-light">Card ID: <?php echo $card['sc_id']; ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-gift" style="font-size: 4rem; color: #ccc;"></i>
                        <h4 class="text-muted mt-3">No Scratch Cards Yet</h4>
                        <p class="text-muted">Complete orders to earn scratch cards and win points!</p>
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-shop"></i> Start Ordering
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- How it Works Section -->
                <div class="card mt-5">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-question-circle"></i> How Scratch Cards Work</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="bi bi-1-circle-fill text-primary"></i> Place Your Order</h6>
                                <p class="small text-muted">Order food from our canteen through the website.</p>
                                
                                <h6><i class="bi bi-2-circle-fill text-warning"></i> Wait for Admin Approval</h6>
                                <p class="small text-muted">Admin will verify and approve your order. You'll get a scratch card when approved.</p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-3-circle-fill text-success"></i> Scratch & Win</h6>
                                <p class="small text-muted">Once approved, scratch your card to win points worth ₹1-₹20!</p>
                                
                                <h6><i class="bi bi-4-circle-fill text-info"></i> Use Points</h6>
                                <p class="small text-muted">Use your points as discount on future orders (1 Point = ₹1).</p>
                            </div>
                        </div>
                        
                        <hr>
                        <div class="text-center">
                            <small class="text-muted">
                                
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function scratchCard(cardId, amount) {
            const scratchArea = document.getElementById('scratch-' + cardId);
            const surface = scratchArea.querySelector('.scratch-surface');
            const revealAmount = scratchArea.querySelector('.reveal-amount');
            
            // Add scratching animation
            surface.classList.add('scratching');
            
            // Play scratching effect
            setTimeout(() => {
                // Hide the scratch surface
                surface.style.opacity = '0';
                surface.style.transition = 'opacity 0.5s ease-out';
                
                // Show the revealed amount
                setTimeout(() => {
                    surface.style.display = 'none';
                    revealAmount.style.display = 'flex';
                    revealAmount.classList.add('revealed');
                    
                    // Create confetti effect
                    createConfetti();
                    
                    // Submit the form after animation
                    setTimeout(() => {
                        submitScratchForm(cardId);
                    }, 1500);
                    
                }, 500);
                
            }, 300);
        }
        
        function createConfetti() {
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                document.body.appendChild(confetti);
                
                // Remove confetti after animation
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }
        
        function submitScratchForm(cardId) {
            // Create and submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'scratch_cards.php';
            
            const scIdInput = document.createElement('input');
            scIdInput.type = 'hidden';
            scIdInput.name = 'sc_id';
            scIdInput.value = cardId;
            
            const scratchInput = document.createElement('input');
            scratchInput.type = 'hidden';
            scratchInput.name = 'scratch_card';
            scratchInput.value = '1';
            
            form.appendChild(scIdInput);
            form.appendChild(scratchInput);
            document.body.appendChild(form);
            
            form.submit();
        }
        
        // Debug Console Logging
        console.log('Scratch Cards Page Loaded');
        console.log('Customer ID: <?php echo $customer_id; ?>');
        console.log('Scratch Cards Count: <?php echo count($scratch_cards); ?>');
        console.log('Points Balance: <?php echo $points_balance; ?>');
    </script>
</body>
</html>