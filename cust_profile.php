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
    
    // Initialize scratch card system to get points balance
    $scratchSystem = new ScratchCardSystem($mysqli);
    $points_balance = $scratchSystem->getCustomerPointsBalance($_SESSION["cid"]);
    
    // Get scratch cards count
    $scratch_cards = $scratchSystem->getCustomerScratchCards($_SESSION["cid"]);
    $total_cards = count($scratch_cards);
    $unscratched_cards = array_filter($scratch_cards, function($card) { 
        return $card['is_scratched'] == '0' && strtotime($card['expires_at']) > time(); 
    });
    $unscratched_count = count($unscratched_cards);
    ?>
    <meta charset="UTF-8">
     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/menu.css" rel="stylesheet">
    <title>My Profile | NNRG-CÁFE</title>
    
    <style>
        .points-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 15px 0;
            transition: transform 0.3s ease;
        }
        
        .points-card:hover {
            transform: translateY(-3px);
        }
        
        .scratch-stats {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin: 15px 0;
        }
        
        .stat-item {
            display: inline-block;
            margin: 0 15px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
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

            <?php 
            if(isset($_GET["up_pwd"])){
                if($_GET["up_pwd"]==1){
                    ?>
                    <!-- START SUCCESSFULLY UPDATE PASSWORD -->
                    <div class="row row-cols-1 notibar">
                        <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                        <i class="bi bi-check-circle"></i>
                            <span class="ms-2 mt-2">Successfully updated your password!</span>
                            <span class="me-2 float-end"><a class="text-decoration-none link-light" href="cust_profile.php">X</a></span>
                        </div>
                    </div>
                    <!-- END SUCCESSFULLY UPDATE PASSWORD -->
            <?php }else{ ?>
                    <!-- START FAILED UPDATE PASSWORD -->
                    <div class="row row-cols-1 notibar">
                        <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                        <i class="bi bi-x-circle"></i><span class="ms-2 mt-2">Failed to update your password.</span>
                            <span class="me-2 float-end"><a class="text-decoration-none link-light" href="cust_profile.php">X</a></span>
                        </div>
                    </div>
                    <!-- END FAILED UPDATE PASSWORD -->
            <?php }
                }
            if(isset($_GET["up_prf"])){
                if($_GET["up_prf"]==1){
                    ?>
                    <!-- START SUCCESSFULLY UPDATE PASSWORD -->
                    <div class="row row-cols-1 notibar">
                        <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                        <i class="bi bi-check-circle"></i>
                            <span class="ms-2 mt-2">Successfully updated your profile!</span>
                            <span class="me-2 float-end"><a class="text-decoration-none link-light" href="cust_profile.php">X</a></span>
                        </div>
                    </div>
                    <!-- END SUCCESSFULLY UPDATE PASSWORD -->
            <?php }else{ ?>
                    <!-- START FAILED UPDATE PASSWORD -->
                    <div class="row row-cols-1 notibar">
                        <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                        <i class="bi bi-x-circle"></i><span class="ms-2 mt-2">Failed to update your profile.</span>
                            <span class="me-2 float-end"><a class="text-decoration-none link-light" href="cust_profile.php">X</a></span>
                        </div>
                    </div>
                    <!-- END FAILED UPDATE PASSWORD -->
            <?php }
                }
            ?>

            <h2 class="pt-3 display-6"><i class="bi bi-person-circle"></i> My Profile</h2>
        </div>

        <!-- Points and Scratch Cards Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="points-card">
                    <i class="bi bi-coin" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-0"><?php echo $points_balance; ?> Points</h3>
                    <p class="mb-0">Available balance</p>
                    <small class="opacity-75">Use as cash for your next order</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="scratch-stats">
                    <i class="bi bi-gift" style="font-size: 2rem;"></i>
                    <h5 class="mt-2 mb-3">Scratch Cards</h5>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_cards; ?></span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $unscratched_count; ?></span>
                        <span class="stat-label">Unscratched</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mb-4">
            <a class="btn btn-sm btn-outline-secondary me-2" href="cust_update_pwd.php">
            <i class="bi bi-key"></i>
                Change password
            </a>
            <a class="btn btn-sm btn-primary me-2 mt-2 mt-md-0" href="cust_update_profile.php">
            <i class="bi bi-pencil-square"></i>
                Update my profile
            </a>
            <a class="btn btn-sm btn-success me-2 mt-2 mt-md-0" href="scratch_cards.php">
            <i class="bi bi-gift"></i>
                My Scratch Cards
                <?php if($unscratched_count > 0): ?>
                    <span class="badge bg-warning text-dark"><?php echo $unscratched_count; ?></span>
                <?php endif; ?>
            </a>
            <a class="btn btn-sm btn-outline-info mt-2 mt-md-0" href="cust_order_history.php">
            <i class="bi bi-clock-history"></i>
                Order History
            </a>
        </div>

        <!-- Unscratched Cards Alert -->
        <?php if($unscratched_count > 0): ?>
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-gift"></i>
                <strong>You have <?php echo $unscratched_count; ?> unscratched card<?php echo $unscratched_count > 1 ? 's' : ''; ?>!</strong>
                <a href="scratch_cards.php" class="alert-link">Click here to scratch them and earn points.</a>
            </div>
        <?php endif; ?>

        <!-- START CUSTOMER INFORMATION -->
        <?php
            //Select customer record from database
            $query = "SELECT c_username,c_firstname,c_lastname,c_email,c_gender,c_type FROM customer WHERE c_id = {$_SESSION['cid']} LIMIT 0,1";
            $result = $mysqli ->query($query);
            $row = $result -> fetch_array();
        ?>
        <div class="row row-cols-1 mt-4">
            <dl class="row">
                <dt class="col-sm-3">Username</dt>
                <dd class="col-sm-9"><?php echo $row["c_username"];?></dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9"><?php echo $_SESSION["firstname"]." ".$_SESSION["lastname"];?></dd>

                <dt class="col-sm-3">Gender</dt>
                <dd class="col-sm-9"><?php 
                if($row["c_gender"]=="M"){echo "Male";}
                else if($row["c_gender"]=="F"){echo "Female";}
                ?>
                </dd>

                <dt class="col-sm-3">Role</dt>
                <dd class="col-sm-9"><?php 
                if($row["c_type"]=="STD"){echo "Student";}
                else if($row["c_type"]=="STF"){echo "Faculty Staff";}
                else if($row["c_type"]=="GUE"){echo "Guest";}
                else{echo "Others";}
                ?>
                </dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9"><?php echo $row["c_email"];?></dd>

                <dt class="col-sm-3">Points Balance</dt>
                <dd class="col-sm-9">
                    <span class="badge bg-primary"><?php echo $points_balance; ?> Points</span>
                    <small class="text-muted ms-2">Equivalent to ₹<?php echo $points_balance; ?></small>
                </dd>

                <dt class="col-sm-3">Scratch Cards</dt>
                <dd class="col-sm-9">
                    <span class="badge bg-info"><?php echo $total_cards; ?> Total Cards</span>
                    <?php if($unscratched_count > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?php echo $unscratched_count; ?> Unscratched</span>
                    <?php endif; ?>
                </dd>
            </dl>
        </div>
        <!-- END CUSTOMER INFORMATION -->
        
        <!-- Recent Activity Section -->
        <?php if($total_cards > 0): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="border-bottom pb-2">Recent Scratch Cards Activity</h5>
                    <?php 
                    $recent_cards = array_slice($scratch_cards, 0, 3); // Show last 3 cards
                    foreach($recent_cards as $card): 
                        $is_expired = strtotime($card['expires_at']) < time();
                        $is_scratched = $card['is_scratched'] == '1';
                    ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong>Order #<?php echo substr($card['tid'], -6); ?></strong>
                                <small class="text-muted d-block">₹<?php echo number_format($card['order_cost'], 2); ?> order</small>
                            </div>
                            <div class="text-end">
                                <?php if($is_scratched): ?>
                                    <span class="badge bg-success">Won ₹<?php echo $card['scratch_amount']; ?></span>
                                    <small class="text-muted d-block">Scratched</small>
                                <?php elseif($is_expired): ?>
                                    <span class="badge bg-secondary">Expired</span>
                                    <small class="text-muted d-block">₹? lost</small>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Unscratched</span>
                                    <small class="text-muted d-block">₹? waiting</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if($total_cards > 3): ?>
                        <div class="text-center mt-3">
                            <a href="scratch_cards.php" class="btn btn-sm btn-outline-primary">
                                View All <?php echo $total_cards; ?> Scratch Cards
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php include('footer.php')?>
</body>

</html>