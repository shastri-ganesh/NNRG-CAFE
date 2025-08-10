<?php
// Enable error reporting temporarily (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to DB
include('conn_db.php');

$pwd = $_POST["pwd"];
$cfpwd = $_POST["cfpwd"];

// Check if passwords match
if ($pwd !== $cfpwd) {
    echo "<script>alert('Passwords do not match.'); history.back();</script>";
    exit(1);
}

$username     = $_POST["username"];
$firstname    = $_POST["firstname"];
$lastname     = $_POST["lastname"];
$gender       = $_POST["gender"];
$email        = $_POST["email"];
$type         = $_POST["type"];
$phone_number = $_POST["phone_number"];
$department   = $_POST["department"];

// Basic input validations
if ($gender == "-" || $type == "-") {
    echo "<script>alert('Please select gender and role.'); history.back();</script>";
    exit(1);
}

if (($type == "STD" || $type == "STF") && $department == "-") {
    echo "<script>alert('Please select your department/course!'); history.back();</script>";
    exit(1);
}

if (!preg_match("/^[0-9]{10}$/", $phone_number)) {
    echo "<script>alert('Invalid 10-digit phone number!'); history.back();</script>";
    exit(1);
}

// Check for duplicate username
$query = "SELECT c_username FROM customer WHERE c_username = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "<script>alert('Username already taken!'); history.back();</script>";
    exit(1);
}
$stmt->close();

// Check for duplicate email
$query = "SELECT c_email FROM customer WHERE c_email = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "<script>alert('Email already in use!'); history.back();</script>";
    exit(1);
}
$stmt->close();

// Check for duplicate phone number
$query = "SELECT c_phone FROM customer WHERE c_phone = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $phone_number);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "<script>alert('Phone number already in use!'); history.back();</script>";
    exit(1);
}
$stmt->close();

// Insert user
$query = "INSERT INTO customer (c_username, c_pwd, c_firstname, c_lastname, c_email, c_gender, c_type, c_phone, c_department)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("sssssssss", $username, $pwd, $firstname, $lastname, $email, $gender, $type, $phone_number, $department);

if ($stmt->execute()) {
    header("Location: cust_regist_success.php");
    exit(0);
} else {
    header("Location: cust_regist_fail.php?err=" . $stmt->errno);
    exit(1);
}
?>