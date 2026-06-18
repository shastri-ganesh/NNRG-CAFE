<!DOCTYPE html>
<html lang="en">

<head>
    <?php
session_start();
include("../conn_db.php");
include('../head.php');
require_once('GoogleAuthenticator.php');

// Check if user came from login
if (!isset($_SESSION["temp_aid"])) {
    header("location: admin_login.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST["code"];
    $aid = $_SESSION["temp_aid"];

    // Get secret key from database
    $query = "SELECT secret_key FROM customer WHERE c_id = $aid";
    $result = $mysqli->query($query);

    if ($result && $row = $result->fetch_array()) {
        $secret = $row["secret_key"];

        // Verify code using Google Authenticator
        $ga = new PHPGangsta_GoogleAuthenticator();
        $checkResult = $ga->verifyCode($secret, $code, 2); // 2 = 2*30sec clock tolerance

        if ($checkResult) {
            // ✅ Code is correct — complete login
            $_SESSION["aid"] = $_SESSION["temp_aid"];
            $_SESSION["firstname"] = $_SESSION["temp_firstname"];
            $_SESSION["lastname"] = $_SESSION["temp_lastname"];

            // Assign specific admin role
            if (isset($_SESSION["temp_utype"]) && $_SESSION["temp_utype"] == "SUP") {
                $_SESSION["utype"] = "SUPERADMIN";
            }
            else {
                $_SESSION["utype"] = "ADMIN";
            }

            // Clear temporary session data
            unset($_SESSION["temp_aid"]);
            unset($_SESSION["temp_firstname"]);
            unset($_SESSION["temp_lastname"]);
            unset($_SESSION["temp_username"]);
            unset($_SESSION["temp_utype"]);

            // 🌐 Log successful login
            $ip = $mysqli->real_escape_string($_SERVER['REMOTE_ADDR']);
            $user_agent = $mysqli->real_escape_string($_SERVER['HTTP_USER_AGENT']);
            $log_query = "INSERT INTO admin_status_log (changed_by, old_status, new_status, ip_address, user_agent) 
                          VALUES ('$aid', 'LOGIN', 'SUCCESS', '$ip', '$user_agent')";
            $mysqli->query($log_query);

            header("location: admin_home.php");
            exit();
        }
        else {
            // ❌ Invalid 2FA code
            $error = "Invalid code! Please try again.";

            // Log failed attempt
            $ip = $mysqli->real_escape_string($_SERVER['REMOTE_ADDR']);
            $user_agent = $mysqli->real_escape_string($_SERVER['HTTP_USER_AGENT']);
            $log_query = "INSERT INTO admin_status_log (changed_by, old_status, new_status, ip_address, user_agent, notes)
                          VALUES ('$aid', 'LOGIN', 'FAILED_2FA', '$ip', '$user_agent', 'Incorrect 2FA code')";
            $mysqli->query($log_query);
        }
    }
    else {
        $error = "Error fetching secret key. Please contact support.";
    }
}
?>
    <meta charset="UTF-8">
    <link href="../css/login.css" rel="stylesheet">
    <link href="../img/Color logo - no background.png" rel="icon">
    <title>2FA Verification | NNRG-CÁFE</title>
    <style>
        .code-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 10px;
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

    <div class="container form-signin mt-auto">
        <form method="POST" action="" class="form-floating">
            <h2 class="mt-5 mb-3 fw-normal text-bold">
                <i class="bi bi-shield-lock me-2"></i>Two-Factor Authentication
            </h2>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
            <?php
endif; ?>

            <div class="alert alert-info">
                <i class="bi bi-phone"></i> Open <strong>Google Authenticator</strong> app and enter the 6-digit code
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control code-input" id="code" name="code" placeholder="000000"
                    pattern="[0-9]{6}" maxlength="6" required autofocus>
                <label for="code">6-Digit Code</label>
            </div>

            <button class="w-100 btn btn-success mb-2" type="submit">
                <i class="bi bi-check-circle"></i> Verify Code
            </button>

            <a href="admin_login.php" class="btn btn-outline-secondary w-100 mb-3">
                <i class="bi bi-arrow-left"></i> Back to Login
            </a>
        </form>
    </div>

    <?php include('admin_footer.php')?>
</body>

</html>