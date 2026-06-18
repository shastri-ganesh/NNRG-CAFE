<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
        session_start(); 
        include("../conn_db.php"); 
        
        if($_SESSION["utype"]!="ADMIN" && $_SESSION["utype"]!="SUPERADMIN"){
            header("location: ../restricted.php");
            exit(1);
        }
        
        // Handle OTP-verified password update
        if(isset($_POST["rst_confirm"]) && isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true){
            $c_id = $_POST["c_id"];
            $oldpwd = $_POST["old_pwd"];
            $newpwd = $_POST["new_pwd"];
            $newcfpwd = $_POST["new_cfpwd"];
            
            if($newpwd != $newcfpwd){
                ?>
                    <script>
                        alert('New password does not match.\nPlease re-enter again.');
                        history.back();
                    </script>
                <?php
                exit(1);
            }else{
                // Verify old password
                $query_check = "SELECT c_pwd FROM customer WHERE c_id = {$c_id} LIMIT 0,1";
                $result_check = $mysqli -> query($query_check);
                $row_check = $result_check -> fetch_array();
                
                if($oldpwd == $row_check["c_pwd"]){
                    $query = "UPDATE customer SET c_pwd = '{$newpwd}' WHERE c_id = {$c_id}";
                    $result = $mysqli -> query($query);
                    
                    if($result){
                        // Clear OTP verification
                        unset($_SESSION['otp_verified']);
                        unset($_SESSION['otp_email']);
                        unset($_SESSION['redirect_after_otp']);
                        unset($_SESSION['pending_admin_pwd_change']);
                        
                        header("location: admin_customer_detail.php?c_id={$c_id}&up_pwd=1");
                    }else{
                        header("location: admin_customer_detail.php?c_id={$c_id}&up_pwd=0");
                    }
                }else{
                    unset($_SESSION['pending_admin_pwd_change']);
                    unset($_SESSION['otp_verified']);
                    ?>
                    <script>
                        alert('Old password does not match.\nPlease re-enter again.');
                        window.location.href = 'admin_customer_pwd.php?c_id=<?php echo $c_id; ?>';
                    </script>
                    <?php
                    exit(1);
                }
            }
        }
        
        // Handle initial form submission - Send OTP
        if(isset($_POST["send_otp"])){
            $c_id = $_POST["c_id"];
            
            // Get customer email from database
            $query = "SELECT c_email, c_firstname FROM customer WHERE c_id = {$c_id} LIMIT 0,1";
            $result = $mysqli -> query($query);
            $row = $result -> fetch_array();
            
            if($row){
                $customer_email = $row['c_email'];
                $customer_name = $row['c_firstname'];
                
                // Include OTP sending function
                require_once('../includes/send_otp.php');
                $otp_result = sendOTP($customer_email, $customer_name);
                
                if($otp_result['success']){
                    // Store form data in session for later use
                    $_SESSION['pending_admin_pwd_change'] = array(
                        'c_id' => $_POST['c_id'],
                        'old_pwd' => $_POST['old_pwd'],
                        'new_pwd' => $_POST['new_pwd'],
                        'new_cfpwd' => $_POST['new_cfpwd']
                    );
                    
                    // Set redirect URL after OTP verification
                    $_SESSION['redirect_after_otp'] = 'admin/admin_customer_pwd.php';
                    
                    // Redirect to OTP verification page
                    header("location: ../includes/verify_otp.php");
                    exit(1);
                }else{
                    ?>
                    <script>
                        alert('Failed to send OTP: <?php echo addslashes($otp_result["message"]); ?>');
                    </script>
                    <?php
                }
            }
        }
        
        // If returning from OTP verification with success
        if(isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true && isset($_SESSION['pending_admin_pwd_change'])){
            // Auto-submit the form with stored data
            $pending_data = $_SESSION['pending_admin_pwd_change'];
            $_POST['c_id'] = $pending_data['c_id'];
            $_POST['old_pwd'] = $pending_data['old_pwd'];
            $_POST['new_pwd'] = $pending_data['new_pwd'];
            $_POST['new_cfpwd'] = $pending_data['new_cfpwd'];
            $_POST['rst_confirm'] = true;
            
            // Process the password update
            $c_id = $_POST["c_id"];
            $oldpwd = $_POST["old_pwd"];
            $newpwd = $_POST["new_pwd"];
            $newcfpwd = $_POST["new_cfpwd"];
            
            if($newpwd != $newcfpwd){
                unset($_SESSION['pending_admin_pwd_change']);
                unset($_SESSION['otp_verified']);
                ?>
                    <script>
                        alert('New password does not match.\nPlease re-enter again.');
                        window.location.href = 'admin_customer_pwd.php?c_id=<?php echo $c_id; ?>';
                    </script>
                <?php
                exit(1);
            }else{
                // Verify old password
                $query_check = "SELECT c_pwd FROM customer WHERE c_id = {$c_id} LIMIT 0,1";
                $result_check = $mysqli -> query($query_check);
                $row_check = $result_check -> fetch_array();
                
                if($oldpwd == $row_check["c_pwd"]){
                    $query = "UPDATE customer SET c_pwd = '{$newpwd}' WHERE c_id = {$c_id}";
                    $result = $mysqli -> query($query);
                    
                    if($result){
                        // Clear OTP verification
                        unset($_SESSION['otp_verified']);
                        unset($_SESSION['otp_email']);
                        unset($_SESSION['redirect_after_otp']);
                        unset($_SESSION['pending_admin_pwd_change']);
                        
                        header("location: admin_customer_detail.php?c_id={$c_id}&up_pwd=1");
                        exit(1);
                    }else{
                        header("location: admin_customer_detail.php?c_id={$c_id}&up_pwd=0");
                        exit(1);
                    }
                }else{
                    unset($_SESSION['pending_admin_pwd_change']);
                    unset($_SESSION['otp_verified']);
                    ?>
                    <script>
                        alert('Old password does not match.\nPlease re-enter again.');
                        window.location.href = 'admin_customer_pwd.php?c_id=<?php echo $c_id; ?>';
                    </script>
                    <?php
                    exit(1);
                }
            }
        }

        include('../head.php');
    ?>
    <meta charset="UTF-8">
     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/main.css" rel="stylesheet">
    <link href="../css/login.css" rel="stylesheet">
    <link href="../img/Color Icon with background.png" rel="icon">
    <title>Update customer password | NNRG-CÁFE</title>
</head>

<body class="d-flex flex-column h-100">
    <?php include('nav_header_admin.php')?>

    <div class="container form-signin mt-auto w-50">
        <a class="nav nav-item text-decoration-none text-muted" href="#" onclick="history.back();">
            <i class="bi bi-arrow-left-square me-2"></i>Go back
        </a>
        <form method="POST" action="admin_customer_pwd.php" class="form-floating">
            <h2 class="mt-4 mb-3 fw-normal text-bold"><i class="bi bi-key me-2"></i>Update Customer Password</h2>
            
            <div class="alert alert-info" role="alert">
                <i class="bi bi-shield-lock me-2"></i>
                For security, an OTP will be sent to the customer's registered email before updating their password.
            </div>
            
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="old_pwd" minlength="8" maxlength="45" placeholder="Old Password" name="old_pwd"
                    required>
                <label for="old_pwd">Customer's Old Password</label>
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
                    New password must be at least 8 characters long.
                </div>
            </div>
            <input type="hidden" name="c_id" value="<?php echo $_GET["c_id"]?>">
            <button class="w-100 btn btn-success my-3" name="send_otp" type="submit" onclick="return confirm('An OTP will be sent to the customer\'s email. Continue?');">
                <i class="bi bi-envelope-fill me-2"></i>Send OTP & Update Password
            </button>
        </form>
    </div>
    <?php include('admin_footer.php')?>
</body>

</html>