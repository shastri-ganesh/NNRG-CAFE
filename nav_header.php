<?php
// Get unscratched cards count for badge (only if customer is logged in)
$unscratched_count = 0;
if (isset($_SESSION["cid"]) && file_exists("scratch_card_functions.php")) {
    try {
        include_once("scratch_card_functions.php");
        $scratchSystem = new ScratchCardSystem($mysqli);
        $scratch_cards = $scratchSystem->getCustomerScratchCards($_SESSION["cid"]);
        $unscratched = array_filter($scratch_cards, function ($card) {
            return $card['is_scratched'] == '0' && strtotime($card['expires_at']) > time();
        });
        $unscratched_count = count($unscratched);
    }
    catch (Exception $e) {
        // Silently handle errors to avoid breaking navigation
        error_log("Navigation scratch card count error: " . $e->getMessage());
    }
}
?>

<header class="navbar navbar-expand-md navbar-light bg-light fixed-top shadow-sm mb-auto">
    <div class="container-fluid mx-4">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <!-- NNRG College Logo -->
            <img src="img/nnrg-logo.jpeg" alt="NNRG College Logo" width="35" height="35" class="me-2">
            <!-- FOODCAFE Logo (smaller) -->
            <img src="img/Color logo - no background.png" alt="FOODCAVE LOGO" width="90" class="me-1">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
            aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <li class="nav-item">
                    <a class="nav-link px-2 text-dark" aria-current="page" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2 text-dark" href="shop_list.php">Shop List</a>
                </li>
                <?php if (isset($_SESSION['cid'])) { ?>
                <li class="nav-item">
                    <a href="cust_order_history.php" class="nav-link px-2 text-dark">Order History</a>
                </li>
                <li class="nav-item">
                    <a href="scratch_cards.php" class="nav-link px-2 text-dark">
                        <i class="bi bi-gift"></i> Scratch Cards
                        <?php if ($unscratched_count > 0): ?>
                        <span class="badge bg-warning text-dark ms-1">
                            <?php echo $unscratched_count; ?>
                        </span>
                        <?php
    endif; ?>
                    </a>
                </li>
                <?php
}?>
            </ul>
            <div class="d-flex">
                <?php if (!isset($_SESSION['cid'])) { ?>
                <a href="cust_regist.php" class="btn btn-outline-secondary me-2">Sign Up</a>
                <a href="cust_login.php" class="btn btn-success">Log In</a>
                <?php
}
else { ?>
                <ul class="navbar nav me-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <a href="cust_cart.php" class="btn btn-light" type="button">My Cart
                            <?php
    // FIXED: Better cart count query with proper NULL handling
    $incart_query = "SELECT COALESCE(SUM(ct_amount), 0) AS incart_amt FROM cart WHERE c_id = {$_SESSION['cid']}";
    $incart_result = $mysqli->query($incart_query);

    if ($incart_result && $incart_result->num_rows > 0) {
        $incart_row = $incart_result->fetch_array();
        $incart_amt = (int)$incart_row["incart_amt"]; // Ensure it's an integer
    }
    else {
        $incart_amt = 0; // Default to 0 if query fails
    }

    if ($incart_amt > 0) { ?>
                            <span class="ms-1 badge bg-success">
                                <?php echo $incart_amt; ?>
                            </span>
                            <?php
    }
    else { ?>
                            <span class="ms-1 badge bg-secondary">0</span>
                            <?php
    }?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="cust_profile.php" class="nav-link px-2 text-dark">
                            Welcome,
                            <?= $_SESSION['firstname']?>
                            <i class="bi bi-person-circle"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="mx-2 mt-1 mt-md-0 btn btn-outline-danger">Log Out</a>
                    </li>
                </ul>
                <?php
}?>
            </div>
        </div>
    </div>
</header>

<!-- Optional: Add notification for new scratch cards -->
<?php if (isset($_SESSION["cid"]) && $unscratched_count > 0): ?>
<div class="alert alert-warning alert-dismissible fade show mb-0" role="alert"
    style="margin-top: 56px; position: relative; z-index: 1020;">
    <i class="bi bi-gift"></i>
    <strong>You have
        <?php echo $unscratched_count; ?> unscratched card
        <?php echo $unscratched_count > 1 ? 's' : ''; ?>!
    </strong>
    <a href="scratch_cards.php" class="alert-link">Scratch them now to earn points.</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
endif; ?>