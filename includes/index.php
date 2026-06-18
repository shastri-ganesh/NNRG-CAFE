<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("conn_db.php");
include("head.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO OPTIMIZED META TAGS -->
    <title>NNRG Cafeteria - Online Food Ordering | NNRG Canteen | Fresh Meals & Lunch Delivery</title>
    <meta name="description" content="Order fresh meals online from NNRG Cafeteria. Best canteen food delivery service for NNRG employees. Quick lunch orders, snacks, beverages and more from NNRG cafe.">
    <meta name="keywords" content="NNRG cafeteria, NNRG canteen, NNRG lunch, NNRG cafe, NNRG food ordering, NNRG meal delivery, office canteen, employee cafeteria, online food order">
    
    <!-- ADDITIONAL SEO META TAGS -->
    <meta name="author" content="NNRG Cafeteria">
    <meta name="robots" content="index, follow">
    <meta name="language" content="English">
    <meta name="revisit-after" content="1 day">
    
    <!-- OPEN GRAPH FOR SOCIAL SHARING -->
    <meta property="og:title" content="NNRG Cafeteria - Online Food Ordering System">
    <meta property="og:description" content="Order delicious meals from NNRG Cafeteria. Fresh food delivery from your favorite office canteen.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://nnrgcafeteria.in">
    <meta property="og:site_name" content="NNRG Cafeteria">
    
    <!-- TWITTER CARD -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="NNRG Cafeteria - Online Food Ordering">
    <meta name="twitter:description" content="Order fresh meals online from NNRG Cafeteria. Best canteen food delivery service.">
    
    <!-- STRUCTURED DATA - JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Restaurant",
        "name": "NNRG Cafeteria",
        "alternateName": ["NNRG Canteen", "NNRG Cafe"],
        "url": "https://nnrgcafeteria.in",
        "description": "Online food ordering system for NNRG employees. Fresh meals, lunch, snacks and beverages delivered quickly.",
        "servesCuisine": ["Indian", "Continental", "Snacks", "Beverages"],
        "priceRange": "₹",
        "serviceType": "Food Delivery",
        "areaServed": "NNRG Office Campus"
    }
    </script>
    
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/main1.css">
    <style>
        .shop-image-container {
            position: relative;
            overflow: hidden;
        }
        
        .coming-soon-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(220, 53, 69, 0.95);
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 2;
            pointer-events: none;
            backdrop-filter: blur(2px);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .shop-closed .card-img-top {
            filter: grayscale(70%) brightness(0.7);
        }
        
        .shop-closed .card-body {
            background-color: #f8f9fa;
        }
        
        .btn-disabled-custom {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
            cursor: not-allowed !important;
        }
        
        .btn-disabled-custom:hover {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
        }

        /* Instagram Section Styles */
        .instagram-section {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin: 1rem 0;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .instagram-btn:hover {
            background: #d63384;
            color: white;
            text-decoration: none;
        }

        /* Clickable card styles */
        .clickable-card {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .clickable-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .shop-closed {
            cursor: not-allowed;
        }

        .shop-closed:hover {
            transform: none;
            box-shadow: none;
        }

        /* SEO Content Section - REMOVED */
    </style>
</head>
<body class="d-flex flex-column h-100">
    <?php include('nav_header.php'); ?>
    
    <!-- Error Messages for Closed Shops -->
    <?php if(isset($_GET['error'])){ ?>
        <div class="container mt-3">
            <?php if($_GET['error'] == 'shop_closed'){ ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Shop Closed!</strong> 
                    <?php if(isset($_GET['shop_name'])){ ?>
                        "<?php echo htmlspecialchars(urldecode($_GET['shop_name'])); ?>" is currently closed and not accepting orders.
                    <?php } else { ?>
                        The shop you're trying to access is currently closed and not accepting orders.
                    <?php } ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } elseif($_GET['error'] == 'shop_not_found'){ ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-x-circle-fill"></i>
                    <strong>Shop Not Found!</strong> The shop you're looking for doesn't exist.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
    
    <!-- HERO SECTION WITH SEO OPTIMIZED CONTENT -->
    <div class="d-flex text-center text-white position-relative promo-banner-bg py-3">
        <div class="p-lg-2 mx-auto my-5">
            <h1 class="display-5 fw-normal">Welcome to NNRG Cafeteria</h1>
            
            <p class="lead fw-light">Food ordering system of NNRG</p>
            <span class="xsmall-font text-muted"></span>
        </div>
    </div>
    
    <div class="container p-5" id="recommended-shop">
        <h2 class="border-bottom pb-2"><i class="bi bi-shop align-top"></i> Available NNRG Canteen Shops</h2>

        <!-- GRID SHOP SELECTION -->
        <div class="row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-3">

            <?php
            // Show open shops first, then closed shops - ONLY CHANGE: Added ORDER BY
            $query = "SELECT s_id,s_name,s_pic,s_status FROM shop ORDER BY 
                      CASE WHEN s_status = 'OPEN' THEN 0 ELSE 1 END, s_name ASC";
            $result = $mysqli -> query($query);
            if($result -> num_rows > 0){
            while($row = $result -> fetch_array()){
                // Check if shop is closed
                $is_closed = ($row["s_status"] == 'CLOSED');
        ?>
            <!-- GRID EACH SHOP -->
            <div class="col">
                <div class="card rounded-25 position-relative <?php echo $is_closed ? 'shop-closed' : 'clickable-card'; ?>"
                     <?php if(!$is_closed){ ?>
                        onclick="window.location.href='<?php echo "shop_menu.php?s_id=".$row["s_id"]?>'"
                     <?php } ?>>
                    
                    <!-- Shop Status Badge -->
                    <div class="position-absolute top-0 end-0 m-2" style="z-index: 3;">
                        <?php if($is_closed){ ?>
                            <span class="badge bg-danger">
                                <i class="bi bi-shop-window"></i> CLOSED
                            </span>
                        <?php } else { ?>
                            <span class="badge bg-success">
                                <i class="bi bi-shop"></i> OPEN
                            </span>
                        <?php } ?>
                    </div>
                    
                    <!-- Shop Image Container -->
                    <div class="shop-image-container">
                        <?php if($is_closed){ ?>
                            <!-- Coming Soon Watermark -->
                            <div class="coming-soon-watermark">
                                Shop Closed
                            </div>
                        <?php } ?>
                        
                        <img <?php
                            if(is_null($row["s_pic"])){echo "src='img/default.png'";}
                            else{echo "src=\"img/{$row['s_pic']}\"";}
                        ?> style="width:100%; height:175px; object-fit:cover;"
                            class="card-img-top rounded-25 img-fluid" 
                            alt="<?php echo $row["s_name"]?> - NNRG Cafeteria Shop">
                    </div>
                    
                    <div class="card-body">
                        <h3 name="shop-name" class="card-title"><?php echo $row["s_name"]?></h3>
                        <p class="card-subtitle <?php echo $is_closed ? 'text-danger' : 'text-success'; ?>">
                            <?php if($is_closed){ ?>
                                <i class="bi bi-circle-fill" style="font-size: 8px;"></i> Currently unavailable
                            <?php } else { ?>
                                <i class="bi bi-circle-fill" style="font-size: 8px;"></i> Available for orders
                            <?php } ?>
                        </p>
                        
                        <div class="text-end">
                            <?php if($is_closed){ ?>
                                <!-- Disabled button for closed shops -->
                                <button class="btn btn-sm btn-disabled-custom" disabled>
                                    <i class="bi bi-lock-fill"></i> Shop Closed
                                </button>
                            <?php } else { ?>
                                <!-- Normal button for open shops (now just for visual, card handles click) -->
                                <span class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-arrow-right"></i> Order Now
                                </span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END GRID EACH SHOP -->
            <?php }
        }else{
            ?>
            <div class="row row-cols-1 w-100">
                <div class="col mt-4 pt-3 px-3 bg-danger text-white rounded text-center">
                    <i class="bi bi-x-circle-fill"></i>
                    <p class="ms-2 mt-2">No NNRG canteen shops currently available.</p>
                </div>
            </div>
            <?php
        }
        $result -> free_result();
        ?>
        </div>
        <!-- END GRID SHOP SELECTION -->

        <!-- Instagram Section -->
        <div class="instagram-section">
            <i class="bi bi-instagram instagram-icon"></i>
            Follow NNRG Cafeteria updates: 
            <a href="https://www.instagram.com/nnrg_group/" 
               target="_blank" 
               rel="noopener noreferrer"
               class="instagram-btn ms-2">
                @nnrg_group
            </a>
        </div>
        <!-- End Instagram Section -->

    </div>
    <?php include('footer.php')?>
    
    <!-- JavaScript to auto-refresh cart badge when page becomes visible -->
    <script>
        // Function to update cart badge
        function updateCartBadge() {
            <?php if(isset($_SESSION['cid'])){ ?>
                fetch('get_cart_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const cartBadge = document.querySelector('.badge');
                        if (cartBadge) {
                            if (data.count > 0) {
                                cartBadge.className = 'ms-1 badge bg-success';
                                cartBadge.textContent = data.count;
                            } else {
                                cartBadge.className = 'ms-1 badge bg-secondary';
                                cartBadge.textContent = '0';
                            }
                        }
                    })
                    .catch(error => console.log('Cart update failed:', error));
            <?php } ?>
        }

        // Update cart badge when page becomes visible (user returns to tab/page)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                updateCartBadge();
            }
        });

        // Update cart badge when page loads
        window.addEventListener('load', function() {
            setTimeout(updateCartBadge, 500); // Small delay to ensure everything is loaded
        });

        // Update cart badge when user focuses on window (alternative to visibility change)
        window.addEventListener('focus', function() {
            updateCartBadge();
        });
    </script>
        
</body>
</html>