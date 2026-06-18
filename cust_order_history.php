<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    session_start();
    if(!isset($_SESSION["cid"])){
        header("location: restricted.php");
        exit(1);
    }
    include("conn_db.php");
    include('head.php');
    ?>
    <meta charset="UTF-8">
     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/menu.css" rel="stylesheet">
    <title>Order History | FOODCAVE</title>
</head>

<body class="d-flex flex-column h-100">
    <?php include('nav_header.php')?>

    <div class="container px-5 py-4" id="shop-body">
        <a class="nav nav-item text-decoration-none text-muted mb-3" href="#" onclick="history.back();">
            <i class="bi bi-arrow-left-square me-2"></i>Go back
        </a>
        <div class="mb-3 text-wrap" id="shop-header">
            <h2 class="display-6 strong fw-normal">Order History</h2>
        </div>

        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <button class="nav-link active px-4" id="ongoing-tab" data-bs-toggle="tab" data-bs-target="#nav-ongoing"
                    type="button" role="tab" aria-controls="nav-ongoing"
                    aria-selected="true">&nbsp;Ongoing&nbsp;</button>
                <button class="nav-link px-4" id="completed-tab" data-bs-toggle="tab" data-bs-target="#nav-completed"
                    type="button" role="tab" aria-controls="nav-completed" aria-selected="false">Completed</button>
            </div>
        </nav>

        <div class="tab-content" id="nav-tabContent">
            <!-- ONGOING ORDER TAB -->
            <div class="tab-pane fade show active p-3" id="nav-ongoing" role="tabpanel" aria-labelledby="ongoing-tab">
                <?php
                // Get ongoing orders (not finished or cancelled)
                $ongoing_query = "SELECT t.*, c.c_firstname, c.c_lastname 
                                 FROM transaction t 
                                 LEFT JOIN customer c ON t.c_id = c.c_id 
                                 WHERE t.c_id = {$_SESSION['cid']} 
                                 AND t.order_status NOT IN ('FNSH', 'CNCL')
                                 ORDER BY t.created_at DESC";
                $ongoing_result = $mysqli->query($ongoing_query);
                $ongoing_num = $ongoing_result->num_rows;
                if($ongoing_num > 0){
            ?>
                <div class="row row-cols-1 row-cols-md-3">
                    <!-- START EACH ORDER DETAIL -->
                    <?php while($og_row = $ongoing_result->fetch_array()){ ?>
                    <div class="col">
                        <a href="cust_order_detail.php?t_id=<?php echo $og_row["id"]?>"
                            class="text-dark text-decoration-none">
                            <div class="card mb-3">
                                <?php if($og_row["order_status"]=="VRFY"){ ?>
                                <div class="card-header bg-info text-dark justify-content-between">
                                  <small class="me-auto d-flex" style="font-weight: 500;">Verifying your order</small>
                                </div>
                                <?php  } else if($og_row["order_status"]=="ACPT"){ ?>
                                <div class="card-header bg-secondary text-white justify-content-between">
                                    <small class="me-auto d-flex" style="font-weight: 500;">Accepted your order</small>
                                </div>
                                <?php }else if($og_row["order_status"]=="PREP"){?>
                                <div class="card-header bg-warning justify-content-between">
                                    <small class="me-auto d-flex" style="font-weight: 500;">Preparing your order</small>
                                </div>
                                <?php }else if($og_row["order_status"]=="RDPK"){?>
                                <div class="card-header bg-primary text-white justify-content-between">
                                    <small class="me-auto d-flex" style="font-weight: 500;">Your order is ready for
                                        pick-up</small>
                                </div>
                                <?php }else if($og_row["order_status"]=="CNCL"){?>
                                <div class="card-header bg-danger text-white justify-content-between">
                                    <small class="me-auto d-flex" style="font-weight: 500;">Order Cancelled</small>
                                </div>
                                <?php }else{?>
                                <div class="card-header bg-success text-white justify-content-between">
                                    <small class="me-auto d-flex" style="font-weight: 500;">Order Finished</small>
                                </div><?php } ?>
                                <div class="card-body">
                                    <div class="card-text row row-cols-1 small">
                                        <div class="col">Order #<?php echo $og_row["tid"];?></div>
                                        <div class="col mb-2">Customer: <?php echo $og_row["name"];?></div>
                                        <div class="col mb-2">Roll No: <?php echo $og_row["rollno"];?></div>
                                        <div class="col mb-2">Year: <?php echo $og_row["year"];?></div>
                                        <div class="col mb-2">Branch: <?php echo $og_row["branch_section"];?></div>
                                        <?php if($og_row["pickup_time"]){ ?>
                                        <div class="col mb-2">Pickup: <?php echo $og_row["pickup_time"];?></div>
                                        <?php } ?>
                                        <div class="col pt-2 border-top">1 order</div>
                                        <div class="col mt-1 mb-2"><strong class="h5">₹<?php echo number_format($og_row["order_cost"], 2);?></strong></div>
                                        <div class="col text-end">
                                            <a href="cust_order_detail.php?t_id=<?php echo $og_row["id"]?>"
                                                class="text-dark text-decoration-none">
                                                <i class="bi bi-arrow-right-square"></i> More Detail
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php } ?>
                    <!-- END EACH ORDER DETAIL -->
                </div>
                <?php }else{ ?>
                <!-- IN CASE NO ORDER -->
                <div class="row row-cols-1">
                    <div class="col pt-3 px-3 bg-danger text-white rounded text-center">
                        <i class="bi bi-x-circle-fill"></i>
                        <p class="ms-2 mt-2">You don't have any ongoing orders.</p>
                    </div>
                </div>
                <!-- END CASE NO ORDER -->
                <?php } ?>
            </div>

            <!-- COMPLETED ORDER TAB -->
            <div class="tab-pane fade p-3" id="nav-completed" role="tabpanel" aria-labelledby="completed-tab">
            <?php
                // Get completed orders (finished or cancelled)
                $completed_query = "SELECT t.*, c.c_firstname, c.c_lastname 
                                   FROM transaction t 
                                   LEFT JOIN customer c ON t.c_id = c.c_id 
                                   WHERE t.c_id = {$_SESSION['cid']} 
                                   AND t.order_status IN ('FNSH', 'CNCL')
                                   ORDER BY t.created_at DESC";
                $completed_result = $mysqli->query($completed_query);
                $completed_num = $completed_result->num_rows;
                if($completed_num > 0){
            ?>
                <div class="row row-cols-1 row-cols-md-3">
                    <!-- START EACH ORDER DETAIL -->
                    <?php while($comp_row = $completed_result->fetch_array()){ ?>
                    <div class="col">
                        <a href="cust_order_detail.php?t_id=<?php echo $comp_row["id"]?>"
                            class="text-dark text-decoration-none">
                            <div class="card mb-3">
                                <?php if($comp_row["order_status"]=="FNSH"){?>
                                <div class="card-header bg-success text-white justify-content-between">
                                    <small class="me-auto d-flex" style="font-weight: 500;">Order Finished</small>
                                </div>
                                <?php }else if($comp_row["order_status"]=="CNCL"){?>
                                <div class="card-header bg-danger text-white justify-content-between">
                                    <small class="me-auto d-flex" style="font-weight: 500;">Order Cancelled</small>
                                </div>
                                <?php } ?>
                                <div class="card-body">
                                    <div class="card-text row row-cols-1 small">
                                        <div class="col">Order #<?php echo $comp_row["tid"];?></div>
                                        <div class="col mb-2">Customer: <?php echo $comp_row["name"];?></div>
                                        <div class="col mb-2">Roll No: <?php echo $comp_row["rollno"];?></div>
                                        <div class="col mb-2">Year: <?php echo $comp_row["year"];?></div>
                                        <div class="col mb-2">Branch: <?php echo $comp_row["branch_section"];?></div>
                                        <?php if($comp_row["pickup_time"]){ ?>
                                        <div class="col mb-2">Pickup: <?php echo $comp_row["pickup_time"];?></div>
                                        <?php } ?>
                                        <?php if($comp_row["pickup_notes"]){ ?>
                                        <div class="col mb-2">Notes: <?php echo substr($comp_row["pickup_notes"], 0, 30) . (strlen($comp_row["pickup_notes"]) > 30 ? '...' : '');?></div>
                                        <?php } ?>
                                        <div class="col pt-2 border-top">1 order</div>
                                        <div class="col mt-1 mb-2"><strong class="h5">₹<?php echo number_format($comp_row["order_cost"], 2);?></strong></div>
                                        <div class="col text-end">
                                            <a href="cust_order_detail.php?t_id=<?php echo $comp_row["id"]?>"
                                                class="text-dark text-decoration-none">
                                                <i class="bi bi-arrow-right-square"></i>More Detail
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php } ?>
                    <!-- END EACH ORDER DETAIL -->
                </div>
                <?php }else{ ?>
                <!-- IN CASE NO ORDER -->
                <div class="row row-cols-1">
                    <div class="col pt-3 px-3 bg-danger text-white rounded text-center">
                        <i class="bi bi-x-circle-fill"></i>
                        <p class="ms-2 mt-2">You don't have any completed orders yet.</p>
                    </div>
                </div>
                <!-- END CASE NO ORDER -->
                <?php } ?>
            </div>
        </div>
    </div>
    <footer
        class="footer d-flex flex-wrap justify-content-between align-items-center px-5 py-3 mt-auto bg-secondary text-light">
        <span class="smaller-font">&copy; The Alpha Achievers <br /><span class="xsmall-font">Designed and developed by Ganesh Shastri and Vamshi</span></span>
        <ul class="nav justify-content-end list-unstyled d-flex">
            <li class="ms-3">
                <a class="text-light" target="_blank" href="https://mail.google.com/mail/u/0/?tab=rm&ogbl#inbox">
                    <i class="bi bi-telephone"></i>
                </a>
            </li>
        </ul>
    </footer>
    
</body>

</html>
