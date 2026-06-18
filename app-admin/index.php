<?php
session_start();

// If admin already logged in → go dashboard
if (isset($_SESSION['utype']) && $_SESSION['utype'] === 'ADMIN') {
    header("Location: /admin/dashboard.php");
    exit;
}

// Otherwise → show login page
header("Location: /admin/admin_login.php");
exit;
