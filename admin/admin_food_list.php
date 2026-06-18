<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
session_start();
include("../conn_db.php");
include('../head.php');
if ($_SESSION['utype'] != 'ADMIN' && $_SESSION['utype'] != 'SHOP_ADMIN' && $_SESSION['utype'] != 'SUPERADMIN') {
    header("location: ../restricted.php");
    exit(1);
}
?>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../img/Color Icon with background.png" rel="icon">
    <link href="../css/main.css" rel="stylesheet">
    <title>Menu List | NNRG-CÁFE</title>

    <style>
        /* Toggle switch styles */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #28a745;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
        }

        .status-in-stock {
            background-color: #d4edda;
            color: #155724;
        }

        .status-out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body class="d-flex flex-column h-100">

    <?php include('nav_header_admin.php')?>

    <div class="container p-2 pb-0" id="admin-dashboard">
        <div class="mt-4 border-bottom">
            <a class="nav nav-item text-decoration-none text-muted mb-2" href="#" onclick="history.back();">
                <i class="bi bi-arrow-left-square me-2"></i>Go back
            </a>

            <?php
if (isset($_GET["dsb_fdt"])) {
    if ($_GET["dsb_fdt"] == 1) {
?>
            <!-- START SUCCESSFULLY DELETE MENU -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                    <i class="bi bi-check-circle ms-2"></i>
                    <span class="ms-2 mt-2">Successfully removed menu.</span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_food_list.php">X</a></span>
                </div>
            </div>
            <!-- END SUCCESSFULLY DELETE MENU -->
            <?php
    }
    else { ?>
            <!-- START FAILED DELETE MENU -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                    <i class="bi bi-x-circle ms-2"></i><span class="ms-2 mt-2">Failed to remove menu.</span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_food_list.php">X</a></span>
                </div>
            </div>
            <!-- END FAILED DELETE MENU -->
            <?php
    }
}
if (isset($_GET["add_fdt"])) {
    if ($_GET["add_fdt"] == 1) {
?>
            <!-- START SUCCESSFULLY FOOD MENU -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-success text-white rounded text-start">
                    <i class="bi bi-check-circle ms-2"></i>
                    <span class="ms-2 mt-2">Successfully add new menu.</span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_food_list.php">X</a></span>
                </div>
            </div>
            <!-- END SUCCESSFULLY FOOD MENU -->
            <?php
    }
    else { ?>
            <!-- START FAILED FOOD MENU -->
            <div class="row row-cols-1 notibar">
                <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                    <i class="bi bi-x-circle ms-2"></i><span class="ms-2 mt-2">Failed to add new menu.</span>
                    <span class="me-2 float-end"><a class="text-decoration-none link-light"
                            href="admin_food_list.php">X</a></span>
                </div>
            </div>
            <!-- END FAILED FOOD MENU -->
            <?php
    }
}
?>

            <h2 class="pt-3 display-6">Menu List</h2>
            <form class="form-floating mb-3" method="GET" action="admin_food_list.php">
                <div class="row g-2">
                    <div class="col">
                        <input type="text" class="form-control" id="f_name" name="f_name" placeholder="Food name" <?php
if (isset($_GET["search"])) { ?>value="
                        <?php echo $_GET["f_name"]; ?>"
                        <?php
}?>>
                    </div>
                    <?php if (!isset($_SESSION['utype']) || $_SESSION['utype'] === 'ADMIN' || $_SESSION['utype'] === 'SUPERADMIN'): ?>
                    <div class="col">
                        <select class="form-select" id="s_id" name="s_id">
                            <option selected value="">Shop Name</option>
                            <?php
    $option_query = "SELECT s_id,s_name FROM shop;";
    $option_result = $mysqli->query($option_query);
    $opt_row = $option_result->num_rows;
    if ($option_result->num_rows != 0) {
        while ($option_arr = $option_result->fetch_array()) {
?>
                            <option value="<?php echo $option_arr["s_id"]?>">
                                <?php echo $option_arr["s_name"]; ?>
                            </option>
                            <?php
        }
    }
?>
                        </select>
                    </div>
                    <?php
endif; ?>
                    <div class="col-auto">
                        <button type="submit" name="search" value="1" class="btn btn-success">Search</button>
                        <button type="reset" class="btn btn-danger"
                            onclick="javascript: window.location='admin_food_list.php'">Clear</button>
                        <a href="admin_food_add.php" class="btn btn-primary">Add new menu</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="container pt-2" id="cust-table">

        <?php
if (!isset($_GET["search"])) {
    $search_query = "SELECT f.f_id,s.s_id,f.f_name,f.f_price,s.s_name,f.f_stock_status FROM food f INNER JOIN shop s ON f.s_id = s.s_id";
    if (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') {
        $search_query .= " WHERE f.s_id = " . intval($_SESSION['shop_id']);
    }
    $search_query .= " ORDER BY f.f_price DESC,f.s_id ASC;";
}
else {
    $search_sid = $_GET["s_id"];
    // For SHOP_ADMIN, force their shop_id
    if (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') {
        $search_sid = intval($_SESSION['shop_id']);
    }
    if ($search_sid != "") {
        $sid_clause = " AND f.s_id = {$search_sid} ";
    }
    else {
        $sid_clause = " ";
    }
    $search_fn = $_GET["f_name"];
    $search_query = "SELECT f.f_id,s.s_id,f.f_name,f.f_price,s.s_name,f.f_stock_status FROM food f INNER JOIN shop s ON f.s_id = s.s_id
                WHERE f_name LIKE '%{$search_fn}%'" . $sid_clause . " ORDER BY f.f_price DESC,f.s_id ASC;";
}
$search_result = $mysqli->query($search_query);
$search_numrow = $search_result->num_rows;
if ($search_numrow == 0) {
?>
        <div class="row">
            <div class="col mt-2 ms-2 p-2 bg-danger text-white rounded text-start">
                <i class="bi bi-x-circle ms-2"></i><span class="ms-2 mt-2">No shop found!</span>
                <a href="admin_food_list.php" class="text-white">Clear Search Result</a>
            </div>
        </div>
        <?php
}
else { ?>
        <div class="table-responsive">
            <table class="table rounded-5 table-light table-striped table-hover align-middle caption-top mb-5">
                <caption>
                    <?php echo $search_numrow; ?> menu(s)
                    <?php if (isset($_GET["search"])) { ?><br /><a href="admin_food_list.php"
                        class="text-decoration-none text-danger">Clear Search
                        Result</a>
                    <?php
    }?>
                </caption>
                <thead class="bg-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Menu name</th>
                        <th scope="col">Shop name</th>
                        <th scope="col">Price</th>
                        <th scope="col">Stock Status</th>
                        <th scope="col">Toggle Stock</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
    while ($row = $search_result->fetch_array()) {
        $stock_status = isset($row["f_stock_status"]) ? $row["f_stock_status"] : 1;
?>
                    <tr>
                        <th>
                            <?php echo $i++; ?>
                        </th>
                        <td>
                            <?php echo $row["f_name"]; ?>
                        </td>
                        <td>
                            <?php echo $row["s_name"]; ?>
                        </td>
                        <td>
                            <?php echo $row["f_price"] . " INR"; ?>
                        </td>
                        <td>
                            <span
                                class="status-badge <?php echo $stock_status ? 'status-in-stock' : 'status-out-of-stock'; ?>"
                                id="status-text-<?php echo $row["f_id"]; ?>">
                                <?php echo $stock_status ? 'In Stock' : 'Out of Stock'; ?>
                            </span>
                        </td>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" <?php echo $stock_status ? 'checked' : ''; ?>
                                onchange="toggleStock(
                                <?php echo $row['f_id']; ?>, this.checked)"
                                id="toggle-
                                <?php echo $row["f_id"]; ?>">
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td>
                            <a href="admin_food_detail.php?f_id=<?php echo $row["f_id"]?>"
                                class="btn btn-sm btn-primary">View</a>
                            <a href="admin_food_edit.php?s_id=<?php echo $row["s_id"]; ?>&f_id=
                                <?php echo $row["f_id"]?>"
                                class="btn btn-sm btn-outline-success">Edit
                            </a>
                            <a href="admin_food_delete.php?f_id=<?php echo $row["f_id"]?>"
                                class="btn btn-sm btn-outline-danger">Delete</a>
                        </td>
                    </tr>
                    <?php
    }?>
                </tbody>
            </table>
        </div>
        <?php
}
$search_result->free_result();
?>
    </div>

    <?php include('admin_footer.php')?>

    <script>
        function toggleStock(foodId, isChecked) {
            const status = isChecked ? 1 : 0;
            const toggle = document.getElementById('toggle-' + foodId);
            const statusText = document.getElementById('status-text-' + foodId);

            // Disable toggle during request
            toggle.disabled = true;

            // Create form data
            const formData = new FormData();
            formData.append('f_id', foodId);
            formData.append('status', status);

            // Send AJAX request
            fetch('admin_food_toggle.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update status text and class
                        statusText.textContent = data.status_text;
                        statusText.className = 'status-badge ' + (data.new_status ? 'status-in-stock' : 'status-out-of-stock');

                        // Show success message
                        showNotification('Stock status updated successfully!', 'success');
                    } else {
                        // Revert toggle state on error
                        toggle.checked = !isChecked;
                        showNotification('Failed to update stock status: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    // Revert toggle state on error
                    toggle.checked = !isChecked;
                    showNotification('Error updating stock status. Please try again.', 'error');
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Re-enable toggle
                    toggle.disabled = false;
                });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'x-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

            document.body.appendChild(notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
</body>

</html>