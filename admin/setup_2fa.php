<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    session_start(); 
    include("../conn_db.php"); 
    include('../head.php');
    require_once('GoogleAuthenticator.php');
    
    // Check if user came from login
    if(!isset($_SESSION["temp_aid"])) {
        header("location: admin_login.php");
        exit();
    }
    ?>
    <meta charset="UTF-8">
    <link href="../css/login.css" rel="stylesheet">
    <link href="../img/Color logo - no background.png" rel="icon">
    <title>Setup 2FA | NNRG-CÁFE</title>
    <style>
        .qr-container {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .qr-code {
            margin: 20px auto;
        }
        .instructions {
            text-align: left;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
    <header class="navbar navbar-light fixed-top bg-light shadow-sm mb-auto">
        <div class="container-fluid mx-4">
            <a href="../index.php">
                <img src="../img/Color logo - no background.png" width="125" class="me-2" alt="FOODCAVE Logo">
            </a>
        </div>
    </header>
    
    <div class="container mt-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="qr-container">
                    <h2><i class="bi bi-shield-check"></i> Setup Two-Factor Authentication</h2>
                    <p class="text-muted">Secure your admin account with 2FA</p>
                    
                    <?php
                    $ga = new PHPGangsta_GoogleAuthenticator();
                    
                    // Generate new secret key
                    $secret = $ga->createSecret();
                    
                    // Save secret to database
                    $aid = $_SESSION["temp_aid"];
                    $update_query = "UPDATE customer SET secret_key = '$secret' WHERE c_id = $aid";
                    $mysqli->query($update_query);
                    
                    // Generate QR code URL
                    $qrCodeUrl = $ga->getQRCodeGoogleUrl('FOODCAVE Admin', $secret, 'FOODCAVE');
                    ?>
                    
                    <div class="instructions">
                        <h5>📱 Instructions:</h5>
                        <ol>
                            <li>Download <strong>Google Authenticator</strong> app from Play Store or App Store</li>
                            <li>Open the app and tap the <strong>"+"</strong> button</li>
                            <li>Select <strong>"Scan a QR code"</strong></li>
                            <li>Scan the QR code below</li>
                            <li>Enter the 6-digit code from the app to verify</li>
                        </ol>
                    </div>
                    
                    <div class="qr-code">
                        <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Secret Key (if you can't scan):</strong><br>
                        <code><?php echo $secret; ?></code><br>
                        <small>You can manually enter this in Google Authenticator</small>
                    </div>
                    
                    <form method="POST" action="verify_2fa.php">
                        <input type="hidden" name="setup_mode" value="1">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="code" name="code" 
                                   placeholder="000000" pattern="[0-9]{6}" maxlength="6" required>
                            <label for="code">Enter 6-digit code from app</label>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-check-circle"></i> Verify & Complete Setup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('admin_footer.php')?>
</body>
</html>