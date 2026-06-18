<!--    NAV HEADER FOR ADMIN SIDE PAGE   -->

<header class="navbar navbar-expand-md navbar-light fixed-top bg-light shadow-sm mb-auto">
    <div class="container-fluid mx-2 mx-md-4">
        <a href="admin_home.php">
            <img src="../img/Color logo - no background.png" width="100" class="me-2" alt="FOODCAVE Logo">
        </a>
        <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-collapse collapse" id="navbarCollapse">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <li class="nav-item">
                    <a class="nav-link px-2 text-dark" href="admin_home.php">Home</a>
                </li>
                <?php if (!isset($_SESSION['utype']) || $_SESSION['utype'] === 'ADMIN' || $_SESSION['utype'] === 'SUPERADMIN'): ?>
                <li class="nav-item">
                    <a href="admin_customer_list.php" class="nav-link px-2 text-dark">Customer</a>
                </li>
                <li class="nav-item">
                    <a href="admin_shop_list.php" class="nav-link px-2 text-dark">Shop</a>
                </li>
                <?php
endif; ?>
                <li class="nav-item">
                    <a href="admin_food_list.php" class="nav-link px-2 text-dark">Menu</a>
                </li>
                <li class="nav-item">
                    <a href="admin_order_list.php" class="nav-link px-2 text-dark">Order</a>
                </li>
                <?php if (!isset($_SESSION['utype']) || $_SESSION['utype'] === 'ADMIN' || $_SESSION['utype'] === 'SUPERADMIN'): ?>
                <li class="nav-item">
                    <a href="admin_coupons.php" class="nav-link px-2 text-dark">Coupons</a>
                </li>
                <li class="nav-item">
                    <a href="admin_specials.php" class="nav-link px-2 text-dark">Specials</a>
                </li>
                <li class="nav-item">
                    <a href="admin_bulk_mail.php" class="nav-link px-2 text-dark">Bulk Mail</a>
                </li>
                <?php
endif; ?>
            </ul>
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                <!-- ANALYTICS DASHBOARD BUTTON -->
                <a href="admin_analytics_dashboard.php" class="btn btn-primary analytics-nav-btn">
                    <i class="bi bi-graph-up"></i> <span class="btn-text">Analytics</span>
                </a>

                <!-- INSTANT COUNTER ORDER BUTTON IN NAVBAR -->
                <a href="admin_menu_page.php" class="btn btn-success instant-order-nav-btn">
                    <i class="bi bi-lightning-fill"></i> <span class="btn-text">Counter Order</span>
                </a>

                <?php if (!isset($_SESSION['aid']) && !isset($_SESSION['shop_id'])) { ?>
                <a class="btn btn-outline-secondary me-2" href="../cust_regist.php">Sign Up</a>
                <a class="btn btn-success" href="../cust_login.php">Log In</a>
                <?php
}
else { ?>
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                    <?php if (isset($_SESSION['shop_id'])): ?>
                    <span class="nav-link px-2 text-dark">
                        <span class="welcome-text">Welcome,
                            <?= htmlspecialchars($_SESSION['shop_name'])?>
                        </span>
                        <i class="bi bi-shop"></i>
                    </span>
                    <?php
    else: ?>
                    <a href="admin_customer_detail.php?c_id=<?php echo $_SESSION['aid'] ?? $_SESSION['cid']; ?>"
                        class="nav-link px-2 text-dark">
                        <span class="welcome-text">Welcome,
                            <?= $_SESSION['firstname']?>
                        </span>
                        <i class="bi bi-person-circle"></i>
                    </a>
                    <?php
    endif; ?>
                    <a class="btn btn-outline-danger btn-sm" href="../logout.php">Log Out</a>
                </div>
                <?php
}?>
            </div>
        </div>
    </div>
</header>

<style>
    /* Navbar Adjustments for Tablets */
    .navbar {
        padding: 8px 0;
    }

    /* Analytics Dashboard Button */
    .analytics-nav-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        white-space: nowrap;
    }

    .analytics-nav-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    /* Instant Counter Order Button */
    .instant-order-nav-btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        white-space: nowrap;
    }

    .instant-order-nav-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        color: white;
    }

    /* Tablet Specific Adjustments */
    @media (min-width: 768px) and (max-width: 1024px) {
        .navbar-brand img {
            width: 90px !important;
        }

        .nav-link {
            padding: 0.3rem 0.5rem !important;
            font-size: 0.85rem;
        }

        .analytics-nav-btn,
        .instant-order-nav-btn {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .btn-text {
            display: none;
        }

        .analytics-nav-btn i,
        .instant-order-nav-btn i {
            font-size: 1.1rem;
        }

        .welcome-text {
            font-size: 0.85rem;
        }

        .btn-outline-danger {
            padding: 4px 12px;
            font-size: 0.85rem;
        }
    }

    /* Mobile Responsiveness */
    @media (max-width: 767px) {

        .analytics-nav-btn,
        .instant-order-nav-btn {
            width: 100%;
            text-align: center;
            margin-bottom: 8px;
        }
    }
</style>