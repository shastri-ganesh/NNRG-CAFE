<?php
session_start();
// Mock everything payment.php needs so it doesn't crash or redirect
$_SESSION["cid"] = "1"; // Just mock something
$_SESSION["firstname"] = "Test";
$_SESSION["lastname"] = "User";
$_SESSION["utype"] = "customer";

// create a dummy db connection proxy
class DummyDB {
    public function query($q) {
        return new DummyResult();
    }
}
class DummyResult {
    public $num_rows = 1;
    public function fetch_array() {
        return ["grandtotal" => "180.00", "c_firstname" => "Test", "c_lastname" => "User", "c_email" => "test@test.com", "c_username" => "test"];
    }
}

// Intercept include safely
$mysqli = new DummyDB();
$points_balance = 11;
$base_cost = 180.00;

ob_start();
// Include raw HTML directly if possible, or bypass the DB check.
// Better: just run a sed to extract the script block.
