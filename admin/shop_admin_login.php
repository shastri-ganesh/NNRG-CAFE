<!DOCTYPE html>
<html lang="en">

<head>
    <?php session_start();
include("../conn_db.php");
include('../head.php'); ?>
    <meta charset="UTF-8">
    <link href="../css/login.css" rel="stylesheet">
    <link href="../img/Color logo - no background.png" rel="icon">
    <title>Shop Admin Login | FOODCAVE</title>
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
        <form method="POST" action="check_shop_login.php" class="form-floating">
            <h2 class="mt-5 mb-3 fw-normal text-bold"><i class="bi bi-shop me-2"></i>Shop Admin Login</h2>
            <p class="text-muted small mb-3">Login with your shop credentials to manage your items and orders.</p>
            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="floatingInput" placeholder="Shop Username" name="username"
                    required>
                <label for="floatingInput">Shop Username</label>
            </div>
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="floatingPassword" placeholder="Password" name="pwd"
                    required>
                <label for="floatingPassword">Password</label>
            </div>
            <button class="w-100 btn btn-primary mb-3" type="submit"><i class="bi bi-box-arrow-in-right me-1"></i>Shop
                Login</button>
        </form>
        <div class="text-center mb-4">
            <a href="admin_login.php" class="text-muted"><i class="bi bi-shield-lock me-1"></i>Main Admin Login</a>
        </div>
    </div>
    <?php include('admin_footer.php')?>
</body>

</html>