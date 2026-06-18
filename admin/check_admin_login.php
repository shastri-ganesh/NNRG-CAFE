<?php
include('../conn_db.php');
require_once('GoogleAuthenticator.php'); // Make sure this file is in same folder

$username = mysqli_real_escape_string($mysqli, $_POST["username"]);
$pwd = mysqli_real_escape_string($mysqli, $_POST["pwd"]);

$query = "SELECT c_id, c_username, c_firstname, c_lastname, c_type, secret_key FROM customer WHERE
c_username = '$username' AND c_pwd = '$pwd' AND (c_type = 'ADM' OR c_type = 'SUP') LIMIT 0,1";

$result = $mysqli->query($query);

if ($result->num_rows == 1) {
    $row = $result->fetch_array();
    session_start();

    // Store temporary login data
    $_SESSION["temp_aid"] = $row["c_id"];
    $_SESSION["temp_firstname"] = $row["c_firstname"];
    $_SESSION["temp_lastname"] = $row["c_lastname"];
    $_SESSION["temp_username"] = $row["c_username"];
    $_SESSION["temp_utype"] = $row["c_type"]; // Store the DB role (ADM or SUP)

    // Check if 2FA is enabled for this admin
    if (!empty($row["secret_key"])) {
        // 2FA is enabled, redirect to verification page
        header("location: verify_2fa.php");
        exit();
    }
    else {
        // 2FA not setup yet, redirect to setup page
        header("location: setup_2fa.php");
        exit();
    }
}
else {
?>
<script>
    alert("You entered wrong username and/or password!");
    history.back();
</script>
<?php
}
?>