<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
        
        session_start();
        
        // Include database connection
        include("conn_db.php");
        
        // Check if required parameters are present
        if(!(isset($_GET["s_id"]) && isset($_GET["f_id"]))){
            header("location: restricted.php");
            exit(1);
        }
        
        // Check if customer is logged in
        if(!isset($_SESSION["cid"])){
            header("location: cust_login.php");
            exit(1);
        }
        
        // Sanitize input parameters
        $s_id = (int)$_GET["s_id"];
        $f_id = (int)$_GET["f_id"];
        
        // Validate parameters
        if($s_id <= 0 || $f_id <= 0) {
            header("location: restricted.php");
            exit(1);
        }
        
        // Include head after all validations
        include('head.php');
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/menu.css" rel="stylesheet">
    <script type="text/javascript" src="js/input_number.js"></script>
    <title>Food Item | NNRG-CÁFE</title>
</head>

<body class="d-flex flex-column h-100">
    <?php 
        include('nav_header.php');
        
        // Use prepared statement for security
        $query = "SELECT f.*, s.s_name FROM food f INNER JOIN shop s ON f.s_id = s.s_id WHERE f.s_id = ? AND f.f_id = ? LIMIT 1";
        $stmt = $mysqli->prepare($query);
        
        if (!$stmt) {
            die("Query preparation failed: " . $mysqli->error);
        }
        
        $stmt->bind_param("ii", $s_id, $f_id);
        
        if (!$stmt->execute()) {
            die("Query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            echo "<div class='container'><div class='alert alert-danger'>Food item not found.</div></div>";
            include('footer.php');
            echo "</body></html>";
            exit;
        }
        
        $food_row = $result->fetch_assoc();
        $stmt->close();
    ?>
    
    <div class="container px-5 py-4" id="shop-body">
        <div class="row my-4">
            <a class="nav nav-item text-decoration-none text-muted mb-2" href="#" onclick="history.back();">
                <i class="bi bi-arrow-left-square me-2"></i>Go back
            </a>
        </div>
        
        <div class="row row-cols-1 row-cols-md-2 mb-5">
            <div class="col mb-3 mb-md-0">
                <img 
                    <?php
                        if(empty($food_row["f_pic"]) || is_null($food_row["f_pic"])){
                            echo "src='img/default.png'";
                        } else {
                            echo "src=\"img/" . htmlspecialchars($food_row['f_pic']) . "\"";
                        }
                    ?> 
                    class="img-fluid rounded-25 float-start" 
                    alt="<?php echo htmlspecialchars($food_row["f_name"]); ?>">
            </div>
            
            <div class="col text-wrap">
                <h1 class="fw-light"><?php echo htmlspecialchars($food_row["f_name"]); ?></h1>
                <h3 class="fw-light">₹<?php echo number_format($food_row["f_price"], 2); ?></h3>
                
                <?php if(!empty($food_row["f_description"])): ?>
                <p class="text-muted"><?php echo htmlspecialchars($food_row["f_description"]); ?></p>
                <?php endif; ?>
                
                <ul class="list-unstyled mb-3 mb-md-0">
                    <?php if(isset($food_row["s_name"])): ?>
                    <li><small class="text-muted">From: <?php echo htmlspecialchars($food_row["s_name"]); ?></small></li>
                    <?php endif; ?>
                </ul>
                
                <div class="form-amount">
                    <form class="mt-3" method="POST" action="add_item.php">
                        <div class="input-group mb-3">
                            <button id="sub_btn" class="btn btn-outline-secondary" type="button" title="subtract amount" onclick="sub_amt('amount')">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                            <input type="number" class="form-control text-center border-secondary" id="amount"
                                name="amount" value="1" min="1" max="99">
                            <button id="add_btn" class="btn btn-outline-secondary" type="button" title="add amount" onclick="add_amt('amount')">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        
                        <input type="hidden" name="s_id" value="<?php echo $s_id; ?>">
                        <input type="hidden" name="f_id" value="<?php echo $f_id; ?>">
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="addrequest" name="request" placeholder=" " maxlength="255">
                            <label for="addrequest" class="d-inline-text">Additional Request (Optional)</label>
                            <div id="addrequest_helptext" class="form-text">
                                Such as less sugar, no ice, etc.
                            </div>
                        </div>
                        
                        <button class="btn btn-success w-100" type="submit" title="add to cart" name="addtocart">
                            <i class="bi bi-cart-plus"></i> Add to cart
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add some basic JavaScript if input_number.js is missing -->
    <script>
        // Fallback functions if external JS file is missing
        function sub_amt(inputId) {
            const input = document.getElementById(inputId);
            const currentVal = parseInt(input.value) || 1;
            if (currentVal > 1) {
                input.value = currentVal - 1;
            }
        }
        
        function add_amt(inputId) {
            const input = document.getElementById(inputId);
            const currentVal = parseInt(input.value) || 1;
            const maxVal = parseInt(input.getAttribute('max')) || 99;
            if (currentVal < maxVal) {
                input.value = currentVal + 1;
            }
        }
        
        // Ensure buttons work even if external JS fails to load
        document.addEventListener('DOMContentLoaded', function() {
            const subBtn = document.getElementById('sub_btn');
            const addBtn = document.getElementById('add_btn');
            
            if (subBtn) {
                subBtn.addEventListener('click', function() {
                    sub_amt('amount');
                });
            }
            
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    add_amt('amount');
                });
            }
        });
    </script>
    
    <?php include('footer.php'); ?>
</body>

</html>