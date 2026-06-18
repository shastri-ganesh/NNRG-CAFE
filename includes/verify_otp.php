<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// includes/verify_otp.php - OTP Verification Page
session_start();

// ✅ CRITICAL FIX: Include database connection BEFORE checking sessions
require_once __DIR__ . '/../conn_db.php';

// Check if user is logged in
if (!isset($_SESSION["cid"]) && !isset($_SESSION["utype"])) {
    header("location: ../restricted.php");
    exit(1);
}

// Check if OTP exists in session
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_email'])) {
    if(isset($_SESSION["cid"])){
        header("location: ../cust_profile.php");
    }else{
        header("location: ../admin/admin_dashboard.php");
    }
    exit(1);
}

// Handle OTP verification
if (isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];
    $current_time = time();
    
    // Check if OTP expired
    if ($current_time > $_SESSION['otp_expiry']) {
        $error_message = "OTP has expired. Please request a new one.";
        // Clear expired OTP
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiry']);
        unset($_SESSION['otp_email']);
    } 
    // Check if OTP matches
    elseif ($entered_otp == $_SESSION['otp']) {
        // OTP is correct
        $_SESSION['otp_verified'] = true;
        
        // Clear OTP from session
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiry']);
        
        // Redirect to original page based on redirect URL stored in session
        $redirect_url = isset($_SESSION['redirect_after_otp']) ? $_SESSION['redirect_after_otp'] : '';
        
        if($redirect_url){
            // Check if it's an admin page or customer page
            if(strpos($redirect_url, 'admin/') !== false){
                // Admin page - redirect with ../ prefix
                header("location: ../" . $redirect_url);
            }else{
                // Customer page - redirect to root
                header("location: ../{$redirect_url}");
            }
        }else{
            // Default redirect
            if(isset($_SESSION["cid"])){
                header("location: ../cust_profile.php");
            }else{
                header("location: ../admin/admin_customer_list.php");
            }
        }
        exit(1);
    } 
    else {
        $error_message = "Invalid OTP. Please try again.";
    }
}

// Handle resend OTP
if (isset($_POST['resend_otp'])) {
    require_once __DIR__ . '/send_otp.php';
    
    $result = sendOTP($_SESSION['otp_email'], $_SESSION['firstname'] ?? 'User');
    
    if ($result['success']) {
        $success_message = "New OTP sent successfully!";
    } else {
        $error_message = $result['message'];
    }
}

// Calculate remaining time
$remaining_seconds = max(0, $_SESSION['otp_expiry'] - time());
$remaining_minutes = floor($remaining_seconds / 60);
$remaining_seconds = $remaining_seconds % 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/main.css" rel="stylesheet">
    <link href="../css/login.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="../img/Color Icon with background.png" rel="icon">
    <title>OTP Verification | FOODCAVE</title>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .otp-input {
            font-size: 24px;
            letter-spacing: 15px;
            text-align: center;
            font-weight: bold;
        }
        .timer {
            font-size: 18px;
            color: #198754;
            font-weight: bold;
        }
        .expired {
            color: #dc3545;
        }
        .form-signin {
            max-width: 600px;
            margin: auto;
        }
        
        /* Fix NNRG College logo path when in includes folder */
        img[src="img/nnrg-logo.jpeg"] {
            content: url('../img/nnrg-logo.jpeg') !important;
        }
        
        /* Fix FOODCAVE logo path when in includes folder */
        img[src="img/Color logo - no background.png"] {
            content: url('../img/Color logo - no background.png') !important;
        }
        
        /* Ensure logos display properly */
        .navbar-brand img {
            max-height: 50px;
            width: auto;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
    <?php 
    // Include navigation based on user type
    if (isset($_SESSION["cid"])) {
        // Customer navigation - database connection already loaded above
        if(file_exists('../nav_header.php')){
            include('../nav_header.php');
        }
    } else {
        // Admin navigation
        if(file_exists('../admin/nav_header_admin.php')){
            include('../admin/nav_header_admin.php');
        }
    }
    ?>

    <div class="container form-signin mt-5 mb-5">
        <a class="nav nav-item text-decoration-none text-muted mb-3 d-inline-block" href="#" onclick="history.back();">
            <i class="bi bi-arrow-left-square me-2"></i>Go back
        </a>
        
        <form method="POST" action="verify_otp.php">
            <h2 class="mt-4 mb-3 fw-normal text-bold">
                <i class="bi bi-shield-lock me-2"></i>OTP Verification
            </h2>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info" role="alert">
                <i class="bi bi-envelope-fill me-2"></i>
                A 6-digit OTP has been sent to:<br>
                <strong><?php echo htmlspecialchars($_SESSION['otp_email']); ?></strong>
            </div>
            
            <div class="form-floating mb-3">
                <input type="text" class="form-control otp-input" id="otp" placeholder="000000" 
                       name="otp" maxlength="6" pattern="[0-9]{6}" required autofocus>
                <label for="otp">Enter 6-Digit OTP</label>
            </div>
            
            <div class="text-center mb-3">
                <p class="timer" id="timer">
                    Time remaining: <span id="minutes"><?php echo $remaining_minutes; ?></span>:<span id="seconds"><?php echo str_pad($remaining_seconds, 2, '0', STR_PAD_LEFT); ?></span>
                </p>
            </div>
            
            <button class="w-100 btn btn-success mb-2" name="verify_otp" type="submit">
                <i class="bi bi-check-circle me-2"></i>Verify OTP
            </button>
            
            <button class="w-100 btn btn-outline-secondary mb-3" name="resend_otp" type="submit">
                <i class="bi bi-arrow-clockwise me-2"></i>Resend OTP
            </button>
            
            <p class="text-muted text-center smaller-font">
                <i class="bi bi-info-circle me-1"></i>
                Didn't receive the code? Check your spam folder or click resend.
            </p>
        </form>
    </div>
    
    <?php 
    // Include footer based on user type
    if (isset($_SESSION["cid"])) {
        if(file_exists('../footer.php')){
            include('../footer.php');
        }
    } else {
        if(file_exists('../admin/admin_footer.php')){
            include('../admin/admin_footer.php');
        }
    }
    ?>
    
    <script>
        // Countdown timer
        let totalSeconds = <?php echo $remaining_seconds + ($remaining_minutes * 60); ?>;
        
        function updateTimer() {
            if (totalSeconds <= 0) {
                document.getElementById('timer').innerHTML = '<span class="expired">⏰ OTP Expired! Please request a new one.</span>';
                return;
            }
            
            let minutes = Math.floor(totalSeconds / 60);
            let seconds = totalSeconds % 60;
            
            document.getElementById('minutes').textContent = minutes;
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            
            totalSeconds--;
            setTimeout(updateTimer, 1000);
        }
        
        updateTimer();
        
        // Auto-format OTP input (only numbers)
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>