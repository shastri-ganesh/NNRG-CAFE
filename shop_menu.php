<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        session_start();
        include("conn_db.php");
        include('head.php');
        if(!isset($_GET["s_id"])){
            header("location: restricted.php");
            exit(1);
        }
    ?>
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/menu.css" rel="stylesheet">
    <title>Shop Menu | FOODCAVE</title>
    <style>
        /* Stock status styles */
        .out-of-stock-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 8px 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: 15px;
            z-index: 2;
            pointer-events: none;
        }
        
        .out-of-stock .card-img-top img {
            filter: grayscale(100%) brightness(0.7);
        }
        
        .out-of-stock .card-body {
            background-color: #f8f9fa;
            opacity: 0.8;
        }
        
        .btn-out-of-stock {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
            cursor: not-allowed !important;
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <?php include('nav_header.php')?>

    <?php
        $s_id = $_GET["s_id"];
        
        // First check if shop exists and is open
        $shop_query = "SELECT s_name, s_location, s_phoneno, s_pic, s_status 
                      FROM shop WHERE s_id = {$s_id} LIMIT 0,1";
        $shop_result = $mysqli->query($shop_query);
        
        if($shop_result->num_rows == 0) {
            // Shop not found
            header("location: index.php?error=shop_not_found");
            exit(1);
        }
        
        $shop_row = $shop_result->fetch_array();
        
        // Check if shop is closed
        if($shop_row["s_status"] == 'CLOSED') {
            $shop_name = urlencode($shop_row["s_name"]);
            header("location: index.php?error=shop_closed&shop_name={$shop_name}");
            exit(1);
        }
    ?>

    <div class="container px-5 py-4" id="shop-body">
        <a class="nav nav-item text-decoration-none text-muted mb-3" href="#" onclick="history.back();">
            <i class="bi bi-arrow-left-square me-2"></i>Go back
        </a>

        <?php
            if(isset($_GET["atc"])){
                if($_GET["atc"]==1){
                    ?>
                    
                    <!-- START SUCCESSFULLY ADD TO CART -->
                    <div class="row row-cols-1 notibar pb-3">
                        <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                            <i class="bi bi-check-circle    "></i>
                            <span class="ms-2 mt-2">Add item to your cart successfully!</span>
                            <span class="me-2 float-end"><a class="text-decoration-none link-light" href="shop_menu.php?s_id=<?php echo $s_id;?>">X</a></span>
                        </div>
                    </div>
                    <!-- END SUCCESSFULLY ADD TO CART -->
            <?php }else{ ?>
                    <!-- START FAILED ADD TO CART -->
                    <div class="row row-cols-1 notibar">
                        <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                            <i class="bi bi-x-circle-fill"></i><span class="ms-2 mt-2">Failed to add item to your cart.</span>
                            <span class="me-2 float-end"><a class="text-decoration-none link-light" href="shop_menu.php?s_id=<?php echo $s_id;?>">X</a></span>
                        </div>
                    </div>
                    <!-- END FAILED ADD TO CART -->
            <?php }
                } ?>
        <div class="mb-3 text-wrap" id="shop-header">
            <div class="rounded-25 mb-4" id="shop-img" style="
                    background: url(
                        <?php
                            if(is_null($shop_row["s_pic"])){echo "'img/default.png'";}
                            else{echo "'img/{$shop_row['s_pic']}'";}
                        ?>
                    ); height: 300px;  width:50%;  background-size:cover; object-fit:fill;
                     background-repeat: no-repeat;
                    background-position: center;">
            </div>
            <h1 class="display-5 strong"><?php echo $shop_row["s_name"];?></h1>
            <ul class="list-unstyled">
                <li class=""><?php echo $shop_row["s_location"];?></li>
                <li class="">Contact number: <?php echo "(+91) ".$shop_row["s_phoneno"];?></li>
            </ul>
        </div>

        <!-- GRID MENU SELECTION -->
        <h3 class="border-top py-3 mt-2">Menu</h3>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 align-items-stretch mb-1">

        <?php
            $shop_result->free_result();
            
            // UPDATED QUERY: Show available items first, then out-of-stock items
            // Check if f_stock_status column exists first
            $check_column = "SHOW COLUMNS FROM food LIKE 'f_stock_status'";
            $column_result = $mysqli->query($check_column);
            
            if($column_result->num_rows > 0) {
                // f_stock_status column exists - order by stock status
                $query = "SELECT * FROM food WHERE s_id = {$s_id} 
                         ORDER BY 
                           CASE WHEN f_stock_status = 1 THEN 0 ELSE 1 END,
                           f_name ASC";
            } else {
                // f_stock_status column doesn't exist - use original query
                $query = "SELECT * FROM food WHERE s_id = {$s_id} ORDER BY f_name ASC";
            }
            
            $result = $mysqli->query($query);
            
            if($result->num_rows > 0){
                while($food_row = $result->fetch_array()){
                    // Check if f_stock_status column exists, if not assume item is in stock
                    $is_out_of_stock = isset($food_row["f_stock_status"]) ? ($food_row["f_stock_status"] == 0) : false;
        ?>
            <!-- GRID EACH MENU -->
            <div class="col">
                <div class="card rounded-25 mb-4 <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?> position-relative">
                    
                    <?php if($is_out_of_stock){ ?>
                        <!-- Out of Stock Overlay -->
                        <div class="out-of-stock-overlay">
                            Out of Stock
                        </div>
                    <?php } ?>
                    
                    <?php if(!$is_out_of_stock){ ?>
                        <a href="food_item.php?<?php echo "s_id=".$food_row["s_id"]."&f_id=".$food_row["f_id"]?>" class="text-decoration-none text-dark">
                    <?php } ?>
                    
                        <div class="card-img-top">
                            <img
                                <?php
                                if(is_null($food_row["f_pic"])){echo "src='img/default.png'";}
                                else{echo "src=\"img/{$food_row['f_pic']}\"";}
                                ?>
                                style="width:100%; height:125px; object-fit:cover;"
                                class="img-fluid" alt="<?php echo $food_row["f_name"]?>">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fs-5"><?php echo $food_row["f_name"]?></h5>
                            <p class="card-text"><?php echo $food_row["f_price"]?> INR</p>
                            
                            <!-- Stock Status Indicator -->
                            <div class="mb-2">
                                <?php if($is_out_of_stock){ ?>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> Out of Stock
                                    </span>
                                <?php } else { ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Available
                                    </span>
                                <?php } ?>
                            </div>
                            
                            <?php if($is_out_of_stock){ ?>
                                <!-- Disabled button for out-of-stock items -->
                                <button class="btn btn-sm mt-3 btn-out-of-stock" disabled>
                                    <i class="bi bi-x-circle"></i> Out of Stock
                                </button>
                            <?php } else { ?>
                                <!-- Normal add to cart button for in-stock items -->
                                <a href="food_item.php?<?php echo "s_id=".$food_row["s_id"]."&f_id=".$food_row["f_id"]?>" class="btn btn-sm mt-3 btn-outline-secondary">
                                    <i class="bi bi-cart-plus"></i>Add to cart
                                </a>
                            <?php } ?>
                        </div>
                    
                    <?php if(!$is_out_of_stock){ ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
            <?php
                    }
                } else {
                    // No items found
                    ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-shop text-muted" style="font-size: 3rem;"></i>
                            <h4 class="text-muted mt-3">No items available</h4>
                            <p class="text-muted">This shop doesn't have any items listed yet.</p>
                        </div>
                    </div>
                    <?php
                }
                $result->free_result();
            ?>
            <!-- END GRID EACH MENU -->

        </div>
        <!-- END GRID MENU SELECTION -->

        <!-- Summary of available items -->
        <?php
        // Only show summary if f_stock_status column exists
        $check_column2 = "SHOW COLUMNS FROM food LIKE 'f_stock_status'";
        $column_result2 = $mysqli->query($check_column2);
        
        if($column_result2->num_rows > 0) {
            // Count in-stock vs out-of-stock items
            $count_query = "SELECT 
                              SUM(CASE WHEN f_stock_status = 1 THEN 1 ELSE 0 END) as in_stock,
                              SUM(CASE WHEN f_stock_status = 0 THEN 1 ELSE 0 END) as out_of_stock,
                              COUNT(*) as total
                            FROM food WHERE s_id = {$s_id}";
            $count_result = $mysqli->query($count_query);
            $count_row = $count_result->fetch_array();
            
            if($count_row['total'] > 0) {
            ?>
            <div class="alert alert-info mt-4">
                <i class="bi bi-info-circle"></i>
                <strong>Menu Summary:</strong> 
                <?php echo $count_row['in_stock']; ?> items available, 
                <?php echo $count_row['out_of_stock']; ?> out of stock
            </div>
            <?php 
                $count_result->free_result();
            }
        }
        ?>

    </div>
    <?php include('footer.php')?>
</body>

</html>