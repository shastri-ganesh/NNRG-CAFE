<!DOCTYPE html>
<html lang="en">

<head>
    <?php
session_start();
include("../conn_db.php");

if ($_SESSION["utype"] != "ADMIN" && $_SESSION["utype"] != "SUPERADMIN") {
    header("location: ../restricted.php");
    exit(1);
}

// Prevent ordinary admins from promoting anyone to ADM or SUP
if (isset($_POST["type"]) && ($_POST["type"] == "ADM" || $_POST["type"] == "SUP") && $_SESSION["utype"] != "SUPERADMIN") {
    header("location: ../restricted.php");
    exit(1);
}

// Handle OTP-verified profile update
if (isset($_POST["upd_confirm"]) && isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    $c_id = $_POST["c_id"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    $gender = $_POST["gender"];
    $type = $_POST["type"];

    $query = "UPDATE customer SET c_firstname = '{$firstname}', c_lastname = '{$lastname}', c_email = '{$email}', c_gender = '{$gender}', c_type = '{$type}' WHERE c_id = {$c_id}";
    $result = $mysqli->query($query);

    if ($result) {
        // Clear OTP verification
        unset($_SESSION['otp_verified']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['redirect_after_otp']);
        unset($_SESSION['pending_admin_profile_update']);

        header("location: admin_customer_list.php?up_prf=1");
    }
    else {
        header("location: admin_customer_list.php?up_prf=0");
    }
    exit(1);
}

// Handle initial form submission - Send OTP
if (isset($_POST["send_otp"])) {
    $new_email = $_POST["email"];
    $firstname = $_POST["firstname"];

    // Validate email format
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
?>
    <script>
        alert('Invalid email format. Please enter a valid email address.');
        history.back();
    </script>
    <?php
        exit(1);
    }

    // Include OTP sending function
    require_once('../includes/send_otp.php');
    $otp_result = sendOTP($new_email, $firstname);

    if ($otp_result['success']) {
        // Store form data in session for later use
        $_SESSION['pending_admin_profile_update'] = array(
            'c_id' => $_POST['c_id'],
            'firstname' => $_POST['firstname'],
            'lastname' => $_POST['lastname'],
            'email' => $_POST['email'],
            'gender' => $_POST['gender'],
            'type' => $_POST['type']
        );

        // Set redirect URL after OTP verification
        $_SESSION['redirect_after_otp'] = 'admin/admin_customer_edit.php';

        // Redirect to OTP verification page
        header("location: ../includes/verify_otp.php");
        exit(1);
    }
    else {
?>
    <script>
        alert('Failed to send OTP: <?php echo addslashes($otp_result["message"]); ?>');
    </script>
    <?php
    }
}

// If returning from OTP verification with success
if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true && isset($_SESSION['pending_admin_profile_update'])) {
    // Auto-submit the form with stored data
    $pending_data = $_SESSION['pending_admin_profile_update'];
    $_POST['c_id'] = $pending_data['c_id'];
    $_POST['firstname'] = $pending_data['firstname'];
    $_POST['lastname'] = $pending_data['lastname'];
    $_POST['email'] = $pending_data['email'];
    $_POST['gender'] = $pending_data['gender'];
    $_POST['type'] = $pending_data['type'];
    $_POST['upd_confirm'] = true;

    // Process the profile update
    $c_id = $_POST["c_id"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    $gender = $_POST["gender"];
    $type = $_POST["type"];

    $query = "UPDATE customer SET c_firstname = '{$firstname}', c_lastname = '{$lastname}', c_email = '{$email}', c_gender = '{$gender}', c_type = '{$type}' WHERE c_id = {$c_id}";
    $result = $mysqli->query($query);

    if ($result) {
        // Clear OTP verification
        unset($_SESSION['otp_verified']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['redirect_after_otp']);
        unset($_SESSION['pending_admin_profile_update']);

        header("location: admin_customer_list.php?up_prf=1");
        exit(1);
    }
    else {
        header("location: admin_customer_list.php?up_prf=0");
        exit(1);
    }
}

include('../head.php');
?>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/main.css" rel="stylesheet">
    <link href="../css/login.css" rel="stylesheet">
    <link href="../img/Color Icon with background.png" rel="icon">
    <title>Update profile | NNRG-CÁFE</title>
</head>

<body class="d-flex flex-column h-100">
    <?php include('nav_header_admin.php')?>

    <div class="container form-signin mt-auto w-50">
        <a class="nav nav-item text-decoration-none text-muted" href="#" onclick="history.back();">
            <i class="bi bi-arrow-left-square me-2"></i>Go back
        </a>
        <?php
//Select customer record from database
$c_id = $_GET["c_id"];
$query = "SELECT c_firstname,c_lastname,c_email,c_gender,c_type FROM customer WHERE c_id = {$c_id} LIMIT 0,1";
$result = $mysqli->query($query);
$row = $result->fetch_array();

// Prevent ordinary admin from even viewing the edit page for another admin
if ($_SESSION["utype"] != "SUPERADMIN" && ($row["c_type"] == "ADM" || $row["c_type"] == "SUP")) {
    echo "<script>alert('Access Denied: Only Super Admins can edit admin accounts.'); window.location.href = 'admin_customer_list.php';</script>";
    exit(1);
}
?>
        <form method="POST" action="admin_customer_edit.php" class="form-floating">
            <h2 class="mt-4 mb-3 fw-normal text-bold"><i class="bi bi-pencil-square me-2"></i>Update Customer Profile
            </h2>

            <div class="alert alert-info" role="alert">
                <i class="bi bi-shield-lock me-2"></i>
                An OTP will be sent to the customer's NEW email address for verification before updating.
            </div>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="firstname" placeholder="First Name" name="firstname"
                    value="<?php echo $row["c_firstname"]; ?>" required>
                <label for="firstname">First Name</label>
            </div>
            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="lastname" placeholder="Last Name" value="<?php echo $row["c_lastname"]; ?>" name="lastname" required>
                <label for="lastname">Last Name</label>
            </div>
            <div class="form-floating mb-2">
                <input type="email" class="form-control" id="email" placeholder="E-mail" name="email"
                    value="<?php echo $row["c_email"]; ?>" required>
                <label for="email">E-mail</label>
            </div>
            <div class="form-floating">
                <select class="form-select mb-2" id="gender" name="gender">
                    <option value="M" <?php if ($row["c_gender"] == "M" ) {
    echo "selected" ;
}?>>Male</option>
                    <option value="F" <?php if ($row["c_gender"] == "F" ) {
    echo "selected" ;
}?>>Female</option>

                </select>
                <label for="gender">Gender</label>
            </div>
            <div class="form-floating">
                <select class="form-select mb-2" id="type" name="type">
                    <option value="STD" <?php if ($row["c_type"] == "STD" ) {
    echo "selected" ;
}?>>Student</option>

                    <option value="GUE" <?php if ($row["c_type"] == "GUE" ) {
    echo "selected" ;
}?>>Visitor</option>
                    <option value="OTH" <?php if ($row["c_type"] == "OTH" ) {
    echo "selected" ;
}?>>Other</option>
                    <?php if ($_SESSION["utype"] == "SUPERADMIN") { ?>
                    <option value="ADM" <?php if ($row["c_type"] == "ADM" ) {
        echo "selected" ;
    }?>>Admin</option>
                    <option value="SUP" <?php if ($row["c_type"] == "SUP" ) {
        echo "selected" ;
    }?>>Super Admin</option>
                    <?php
}?>
                </select>
                <label for="type">User Role</label>
            </div>
            <input type="hidden" name="c_id" value="<?php echo $c_id; ?>">
            <button class="w-100 btn btn-success mb-3" name="send_otp" type="submit">
                <i class="bi bi-envelope-fill me-2"></i>Send OTP & Update Profile
            </button>
        </form>
    </div>

    <?php include('admin_footer.php')?>
</body>

</html>