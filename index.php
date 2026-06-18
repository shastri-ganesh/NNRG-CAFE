<?php
// ✅ Production-safe settings
error_reporting(0);
ini_set('display_errors', 0);

session_start();
include("conn_db.php");
include("head.php");

// Cache homepage
header("Cache-Control: public, max-age=3600");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NNRG Cafeteria - Online Food Ordering</title>

    <!-- 🔥 PRELOAD HERO BACKGROUND IMAGE (LCP FIX) -->
    <link rel="preload" as="image" href="img/promo-banner.jpg">

    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/main1.css">

    <style>
        /* ===== RESTORE ORIGINAL CARD LOOK ===== */
        .card.rounded-25 {
            border-radius: 20px !important;
            overflow: hidden;
        }

        .card.rounded-25 .card-img-top {
            border-top-left-radius: 20px !important;
            border-top-right-radius: 20px !important;
            height: 175px;
            object-fit: cover;
        }

        .card.rounded-25 .card-body {
            padding: 12px 14px;
        }

        .card-title {
            font-size: 1.1rem;
            margin-bottom: 4px;
        }

        /* Clickable cards */
        .clickable-card {
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .clickable-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, .15);
        }

        .shop-closed {
            cursor: not-allowed;
        }

        /* Instagram Section */
        .instagram-section {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin: 1rem 0;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
        }

        .instagram-icon {
            color: #e4405f;
            font-size: 1.2rem;
            margin-right: 8px;
        }

        .instagram-btn {
            background: #e4405f;
            border: none;
            color: white;
            padding: 6px 15px;
            border-radius: 6px;
            font-size: .9rem;
            font-weight: 500;
            text-decoration: none;
        }

        .instagram-btn:hover {
            background: #d63384;
            color: white;
        }
    </style>
</head>

<body class="d-flex flex-column h-100">

    <?php include('nav_header.php'); ?>

    <!-- ✅ HERO SECTION (UNCHANGED DESIGN) -->
    <div class="d-flex text-center text-white position-relative promo-banner-bg py-3">
        <div class="p-lg-2 mx-auto my-5">
            <h1 class="display-5 fw-normal">Welcome to NNRG Cafeteria</h1>
            <p class="lead fw-light">Food ordering system of NNRG</p>
        </div>
    </div>

    <?php
// Fetch active coupon for the banner
$coupon_res = $mysqli->query("SELECT * FROM coupons WHERE status='active' ORDER BY created_at DESC LIMIT 1");
if ($coupon_res && $coupon_res->num_rows > 0) {
    $active_coupon = $coupon_res->fetch_assoc();
?>
    <!-- 🚀 DYNAMIC COUPON BANNER -->
    <div style="background: linear-gradient(90deg, #ff416c 0%, #ff4b2b 100%); color: white; padding: 12px; text-align: center; font-weight: 600; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3); z-index: 10; position: relative; cursor: pointer;"
        onclick="navigator.clipboard.writeText('<?= $active_coupon['coupon_code']?>'); alert('Coupon code copied!');">
        🎉 USE CODE <span
            style="background: white; color: #ff416c; padding: 3px 10px; border-radius: 4px; margin: 0 5px; font-weight: 800; cursor: copy;"
            title="Click to copy">
            <?= $active_coupon['coupon_code']?>
        </span> FOR ₹
        <?= number_format($active_coupon['discount_amount'], 0)?> OFF ON ORDERS ABOVE ₹
        <?= number_format($active_coupon['min_order_amount'], 0)?>! ✨
    </div>
    <?php
}?>

    <div class="container p-5" id="recommended-shop">

        <!-- 🔥 DAILY SPECIALS SECTION -->
        <h2 class="border-bottom pb-2 text-danger fw-bold mb-4">
            <i class="bi bi-fire align-top"></i> Today's Specials
        </h2>
        <div class="row row-cols-1 row-cols-lg-3 g-4 mb-5 pb-4 border-bottom">
            <?php
// Fetch active Daily Specials from the dedicated table
$specials_query = "SELECT * FROM daily_specials WHERE ds_status = 'active' ORDER BY created_at DESC LIMIT 6";
$specials_result = $mysqli->query($specials_query);
if ($specials_result && $specials_result->num_rows > 0) {
    while ($sp = $specials_result->fetch_assoc()) {
        $img = $sp['ds_pic'] ? "img/" . $sp['ds_pic'] : "img/default.png";
?>
            <div class="col">
                <div class="card rounded-25 position-relative">
                    <div class="position-absolute top-0 start-0 m-2" style="z-index:3;">
                        <span class="badge bg-danger fs-6 heartbeat"
                            style="box-shadow: 0 2px 10px rgba(220,53,69,0.5);">🔥 HOT</span>
                    </div>
                    <img src="<?= $img?>" loading="lazy" class="card-img-top img-fluid"
                        style="height: 150px; object-fit: cover;" alt="<?= htmlspecialchars($sp['ds_name'])?>">
                    <div class="card-body bg-light p-3">
                        <h5 class="card-title fw-bold text-dark mb-1">
                            <?= htmlspecialchars($sp['ds_name'])?>
                        </h5>
                        <?php if ($sp['ds_description']): ?>
                        <p class="text-muted small mb-1" style="font-size: 0.85rem; line-height: 1.2;">
                            <?= htmlspecialchars($sp['ds_description'])?>
                        </p>
                        <?php
        endif; ?>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="fs-5 fw-bold text-success">₹
                                <?= number_format($sp['ds_price'], 2)?>
                            </span>
                            <?php if (isset($_SESSION['cid']) && $sp['f_id']): ?>
                            <form method="POST" action="add_item.php" style="display:inline;">
                                <input type="hidden" name="f_id" value="<?= $sp['f_id']?>">
                                <input type="hidden" name="s_id" value="10">
                                <input type="hidden" name="amount" value="1">
                                <input type="hidden" name="request" value="">
                                <button type="submit"
                                    class="btn btn-sm btn-danger rounded-pill px-3 py-2 fw-bold shadow-sm"><i
                                        class="bi bi-cart-plus me-1"></i>Add to Cart</button>
                            </form>
                            <?php
        elseif (!isset($_SESSION['cid'])): ?>
                            <a href="cust_login.php"
                                class="btn btn-sm btn-danger rounded-pill px-3 py-2 fw-bold shadow-sm text-white text-decoration-none"><i
                                    class="bi bi-cart-plus me-1"></i>Add to Cart</a>
                            <?php
        endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
    }
}
else { ?>
            <div class="col-12 text-muted fst-italic">No specials available today. Check out our shops below!</div>
            <?php
}?>
        </div>

        <h2 class="border-bottom pb-2 mt-4">
            <i class="bi bi-shop align-top"></i> Available NNRG Canteen Shops
        </h2>

        <div class="row row-cols-1 row-cols-lg-3 g-4 py-3">

            <?php
$query = "SELECT s_id, s_name, s_pic, s_status
          FROM shop
          WHERE s_id != 10
          ORDER BY CASE WHEN s_status='OPEN' THEN 0 ELSE 1 END, s_name ASC";
$result = $mysqli->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $is_closed = ($row['s_status'] === 'CLOSED');
        $img = $row['s_pic'] ? "img/" . $row['s_pic'] : "img/default.png";
?>

            <div class="col">
                <?php
        $card_class = $is_closed ? 'shop-closed' : 'clickable-card';
        $onclick = $is_closed ? '' : " onclick=\"window.location.href='shop_menu.php?s_id=" . $row['s_id'] . "'\"";
        echo "<div class=\"card rounded-25 position-relative {$card_class}\"{$onclick}>";
?>

                <div class="position-absolute top-0 end-0 m-2" style="z-index:3;">
                    <span class="badge <?php echo $is_closed ? 'bg-danger' : 'bg-success'; ?>">
                        <?php echo $is_closed ? 'CLOSED' : 'OPEN'; ?>
                    </span>
                </div>

                <img src="<?php echo $img; ?>" loading="lazy" width="300" height="175" class="card-img-top img-fluid"
                    alt="<?php echo htmlspecialchars($row['s_name']); ?>">

                <div class="card-body">
                    <h3 class="card-title">
                        <?php echo htmlspecialchars($row['s_name']); ?>
                    </h3>
                    <p class="<?php echo $is_closed ? 'text-danger' : 'text-success'; ?>">
                        <?php echo $is_closed ? 'Currently unavailable' : 'Available for orders'; ?>
                    </p>

                    <div class="text-end">
                        <?php if ($is_closed) { ?>
                        <button class="btn btn-sm btn-secondary" disabled>Shop Closed</button>
                        <?php
        }
        else { ?>
                        <span class="btn btn-sm btn-outline-dark">Order Now</span>
                        <?php
        }?>
                    </div>
                </div>

            </div>
        </div>

        <?php
    }
}?>

    </div>

    <!-- ✅ Instagram Section RESTORED -->
    <div class="instagram-section">
        <i class="bi bi-instagram instagram-icon"></i>
        Follow NNRG Cafeteria updates:
        <a href="https://www.instagram.com/nnrg_group/" target="_blank" rel="noopener noreferrer"
            class="instagram-btn ms-2">
            @nnrg_group
        </a>
    </div>

    </div>

    <?php include('footer.php'); ?>

    <script defer>
        function updateCartBadge() { 
<? php f(isset($_SESSION['cid']  { ?>
            fetch('get_cart_count.php')
                .then(r => r.json())
                .then(d => {
                    const badge = document.querySelector('.badge');
                    if (badge) badge.textContent = d.count;
                });
<? php
}?>
}
        window.addEventListener('focus', updat
    </script>

</body>

</html>