<?php 
session_start(); 
include("conn_db.php"); 

if(!isset($_SESSION["cid"])){
    header("location: restricted.php");
    exit(1);
}

// Handle OTP-verified password update
if(isset($_POST["rst_confirm"]) && isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true){
    $oldpwd = $_POST["old_pwd"];
    $newpwd = $_POST["new_pwd"];
    $newcfpwd = $_POST["new_cfpwd"];
    
    if($newpwd != $newcfpwd){
        ?>
            <script>
                alert('Your new password does not match.\nPlease re-enter again.');
                history.back();
            </script>
        <?php
        exit(1);
    }else{
        $query = "SELECT c_pwd FROM customer WHERE c_id = {$_SESSION['cid']} LIMIT 0,1";
        $result = $mysqli -> query($query);
        $row = $result -> fetch_array();
        
        if($oldpwd == $row["c_pwd"]){
            $query = "UPDATE customer SET c_pwd = '{$newpwd}' WHERE c_id = {$_SESSION['cid']}";
            $result = $mysqli -> query($query);
            
            if($result){
                // Clear OTP verification
                unset($_SESSION['otp_verified']);
                unset($_SESSION['otp_email']);
                unset($_SESSION['redirect_after_otp']);
                unset($_SESSION['pending_pwd_change']);
                
                header("location: cust_profile.php?up_pwd=1");
                exit(1);
            }else{
                header("location: cust_profile.php?up_pwd=0");
                exit(1);
            }
        }else{
            ?>
            <script>
                alert('Your old password does not match.\nPlease re-enter again.');
                history.back();
            </script>
            <?php
            exit(1);
        }
    }
}

// Handle initial form submission - Send OTP
if(isset($_POST["send_otp"])){
    // Get customer email from database
    $query = "SELECT c_email, c_firstname FROM customer WHERE c_id = {$_SESSION['cid']} LIMIT 0,1";
    $result = $mysqli -> query($query);
    $row = $result -> fetch_array();
    
    if($row){
        $customer_email = $row['c_email'];
        $customer_name = $row['c_firstname'];
        
        // Include OTP sending function
        require_once('includes/send_otp.php');
        $otp_result = sendOTP($customer_email, $customer_name);
        
        if($otp_result['success']){
            // Store form data in session for later use
            $_SESSION['pending_pwd_change'] = array(
                'old_pwd' => $_POST['old_pwd'],
                'new_pwd' => $_POST['new_pwd'],
                'new_cfpwd' => $_POST['new_cfpwd']
            );
            
            // Set redirect URL after OTP verification
            $_SESSION['redirect_after_otp'] = 'cust_update_pwd.php';
            $_SESSION['firstname'] = $customer_name;
            
            // Redirect to OTP verification page
            header("location: includes/verify_otp.php");
            exit(1);
        }else{
            $otp_error = addslashes($otp_result['message']);
        }
    }
}

// If returning from OTP verification with success
if(isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true && isset($_SESSION['pending_pwd_change'])){
    // Auto-submit the form with stored data
    $pending_data = $_SESSION['pending_pwd_change'];
    $oldpwd = $pending_data['old_pwd'];
    $newpwd = $pending_data['new_pwd'];
    $newcfpwd = $pending_data['new_cfpwd'];
    
    if($newpwd != $newcfpwd){
        unset($_SESSION['pending_pwd_change']);
        unset($_SESSION['otp_verified']);
        ?>
            <script>
                alert('Your new password does not match.\nPlease re-enter again.');
                window.location.href = 'cust_update_pwd.php';
            </script>
        <?php
        exit(1);
    }else{
        $query = "SELECT c_pwd FROM customer WHERE c_id = {$_SESSION['cid']} LIMIT 0,1";
        $result = $mysqli -> query($query);
        $row = $result -> fetch_array();
        
        if($oldpwd == $row["c_pwd"]){
            $query = "UPDATE customer SET c_pwd = '{$newpwd}' WHERE c_id = {$_SESSION['cid']}";
            $result = $mysqli -> query($query);
            
            if($result){
                // Clear OTP verification
                unset($_SESSION['otp_verified']);
                unset($_SESSION['otp_email']);
                unset($_SESSION['redirect_after_otp']);
                unset($_SESSION['pending_pwd_change']);
                
                header("location: cust_profile.php?up_pwd=1");
                exit(1);
            }else{
                header("location: cust_profile.php?up_pwd=0");
                exit(1);
            }
        }else{
            unset($_SESSION['pending_pwd_change']);
            unset($_SESSION['otp_verified']);
            ?>
            <script>
                alert('Your old password does not match.\nPlease re-enter again.');
                window.location.href = 'cust_update_pwd.php';
            </script>
            <?php
            exit(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('head.php'); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
    <title>Update password | FOODCAVE</title>
</head>

<body class="d-flex flex-column h-100">
    <?php include('nav_header.php')?>

    <div class="container form-signin mt-auto w-50">
        <a class="nav nav-item text-decoration-none text-muted" href="#" onclick="history.back();">
            <i class="bi bi-arrow-left-square me-2"></i>Go back
        </a>
        <form method="POST" action="cust_update_pwd.php" class="form-floating">
            <h2 class="mt-4 mb-3 fw-normal text-bold"><i class="bi bi-key me-2"></i>Update Password</h2>
            
            <?php if(isset($otp_error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Failed to send OTP: <?php echo $otp_error; ?>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info" role="alert">
                <i class="bi bi-shield-lock me-2"></i>
                For your security, we'll send an OTP to your registered email before updating your password.
            </div>
            
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="old_pwd" minlength="8" maxlength="45" placeholder="Old Password" name="old_pwd"
                    required>
                <label for="old_pwd">Old Password</label>
            </div>
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="rst_pwd" minlength="8" maxlength="45" placeholder="New Password" name="new_pwd"
                    required>
                <label for="rst_pwd">New Password</label>
            </div>
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="rst_cfpwd" minlength="8" maxlength="45" placeholder="Confirm New Password"
                    name="new_cfpwd" required>
                <label for="rst_cfpwd">Confirm New Password</label>
                <div id="passwordHelpBlock" class="form-text smaller-font">
                    Your password must be at least 8 characters long.
                </div>
            </div>
            <button class="w-100 btn btn-success my-3" name="send_otp" type="submit" onclick="return confirm('We will send an OTP to your registered email. Continue?');">
                <i class="bi bi-envelope-fill me-2"></i>Send OTP & Update Password
            </button>
        </form>
    </div>
    <?php include('footer.php')?>
</body>

</html>