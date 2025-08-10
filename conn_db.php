<?php
$host = "localhost";
$user = "u568372288_Alpha_DB";
$password = "Alpha1@ganesh";
$dbname = "u568372288_Alpha";

$mysqli = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>