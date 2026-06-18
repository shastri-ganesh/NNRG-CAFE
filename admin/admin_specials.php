<?php
session_start();
include("../conn_db.php");

if ($_SESSION["utype"] != "ADMIN") {
    header("location: ../restricted.php");
    exit(1);
}

// The "Daily Specials" shop s_id in the shop table
$SPECIALS_SHOP_ID = 10;

// Handle Add Special
if (isset($_POST["add_special"])) {
    $ds_name = trim($_POST["ds_name"]);
    $ds_price = floatval($_POST["ds_price"]);
    $ds_description = trim($_POST["ds_description"] ?? '');

    // 1. Insert into daily_specials table
    $stmt = $mysqli->prepare("INSERT INTO daily_specials (ds_name, ds_price, ds_description) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $ds_name, $ds_price, $ds_description);
    $result = $stmt->execute();
    $ds_id = $mysqli->insert_id;

    // 2. Also insert into food table so it can be ordered via normal cart flow
    $food_stmt = $mysqli->prepare("INSERT INTO food (f_name, f_price, s_id, f_todayavail) VALUES (?, ?, ?, 1)");
    $food_stmt->bind_param("sdi", $ds_name, $ds_price, $SPECIALS_SHOP_ID);
    $food_stmt->execute();
    $f_id = $mysqli->insert_id;

    // 3. Link them: store f_id in daily_specials
    $link_stmt = $mysqli->prepare("UPDATE daily_specials SET f_id = ? WHERE ds_id = ?");
    $link_stmt->bind_param("ii", $f_id, $ds_id);
    $link_stmt->execute();

    // 4. Handle image upload for both tables
    $target_filename = '';
    if ($result && !empty($_FILES["ds_pic"]["name"])) {
        $target_dir = '../img/';
        $ext = strtolower(pathinfo($_FILES["ds_pic"]["name"], PATHINFO_EXTENSION));
        $target_filename = "special_" . $ds_id . "." . $ext;
        $target_file = $target_dir . $target_filename;
        if (move_uploaded_file($_FILES["ds_pic"]["tmp_name"], $target_file)) {
            $update_stmt = $mysqli->prepare("UPDATE daily_specials SET ds_pic = ? WHERE ds_id = ?");
            $update_stmt->bind_param("si", $target_filename, $ds_id);
            $update_stmt->execute();
            // Also update food table image
            $food_img_stmt = $mysqli->prepare("UPDATE food SET f_pic = ? WHERE f_id = ?");
            $food_img_stmt->bind_param("si", $target_filename, $f_id);
            $food_img_stmt->execute();
        }
    }

    header("location: admin_specials.php?msg=" . ($result ? "added" : "error"));
    exit();
}

// Handle Delete
if (isset($_GET["delete"])) {
    $ds_id = intval($_GET["delete"]);
    // Get image and linked f_id
    $img_stmt = $mysqli->prepare("SELECT ds_pic, f_id FROM daily_specials WHERE ds_id = ?");
    $img_stmt->bind_param("i", $ds_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    $img_row = $img_result->fetch_assoc();
    if ($img_row) {
        if ($img_row['ds_pic']) {
            $img_path = '../img/' . $img_row['ds_pic'];
            if (file_exists($img_path))
                unlink($img_path);
        }
        // Also delete from food table
        if ($img_row['f_id']) {
            $food_del = $mysqli->prepare("DELETE FROM food WHERE f_id = ?");
            $food_del->bind_param("i", $img_row['f_id']);
            $food_del->execute();
        }
    }
    $del_stmt = $mysqli->prepare("DELETE FROM daily_specials WHERE ds_id = ?");
    $del_stmt->bind_param("i", $ds_id);
    $del_stmt->execute();

    header("location: admin_specials.php?msg=deleted");
    exit();
}

// Handle Toggle Status
if (isset($_GET["toggle"])) {
    $ds_id = intval($_GET["toggle"]);
    // Toggle in daily_specials
    $toggle_stmt = $mysqli->prepare("UPDATE daily_specials SET ds_status = IF(ds_status='active','inactive','active') WHERE ds_id = ?");
    $toggle_stmt->bind_param("i", $ds_id);
    $toggle_stmt->execute();

    // Also toggle f_todayavail in food table
    $get_fid = $mysqli->prepare("SELECT f_id, ds_status FROM daily_specials WHERE ds_id = ?");
    $get_fid->bind_param("i", $ds_id);
    $get_fid->execute();
    $fid_result = $get_fid->get_result()->fetch_assoc();
    if ($fid_result && $fid_result['f_id']) {
        $new_avail = ($fid_result['ds_status'] == 'active') ? 1 : 0;
        $food_toggle = $mysqli->prepare("UPDATE food SET f_todayavail = ? WHERE f_id = ?");
        $food_toggle->bind_param("ii", $new_avail, $fid_result['f_id']);
        $food_toggle->execute();
    }

    header("location: admin_specials.php?msg=toggled");
    exit();
}

include('../head.php');
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../css/main.css" rel="stylesheet">
<link href="../css/login.css" rel="stylesheet">
<link href="../img/Color Icon with background.png" rel="icon">
<title>Manage Daily Specials | NNRG-CÁFE</title>
<style>
    .specials-card {
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s;
    }

    .specials-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .specials-card img {
        height: 160px;
        object-fit: cover;
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .add-form-card {
        background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
        border: 2px dashed #dc3545;
        border-radius: 16px;
    }
</style>
</head>

<body class="d-flex flex-column h-100">
    <?php include('nav_header_admin.php')?>

    <div class="container mt-auto" style="padding-top: 80px; max-width: 1100px;">
        <a class="nav nav-item text-decoration-none text-muted mb-3 d-inline-block" href="#" onclick="history.back();">
            <i class="bi bi-arrow-left-square me-2"></i>Go back
        </a>

        <h2 class="mt-2 mb-4 fw-bold text-danger">
            <i class="bi bi-fire me-2"></i>Manage Daily Specials
        </h2>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?php echo ($_GET['msg'] == 'error') ? 'danger' : 'success'; ?> alert-dismissible fade show"
            role="alert">
            <?php
    switch ($_GET['msg']) {
        case 'added':
            echo '✅ Special item added successfully!';
            break;
        case 'deleted':
            echo '🗑️ Special item deleted.';
            break;
        case 'toggled':
            echo '🔄 Status updated.';
            break;
        case 'error':
            echo '❌ Something went wrong. Please try again.';
            break;
    }
?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php
endif; ?>

        <!-- ADD SPECIAL FORM -->
        <div class="add-form-card p-4 mb-4">
            <h5 class="fw-bold text-danger mb-3"><i class="bi bi-plus-circle me-2"></i>Add New Special</h5>
            <form method="POST" action="admin_specials.php" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="ds_name" name="ds_name" placeholder="Item Name"
                                required>
                            <label for="ds_name">Item Name</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="number" step="0.01" min="0" class="form-control" id="ds_price" name="ds_price"
                                placeholder="Price" required>
                            <label for="ds_price">Price (₹)</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="ds_description" name="ds_description"
                                placeholder="Description">
                            <label for="ds_description">Description (optional)</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Image</label>
                        <input class="form-control form-control-sm" type="file" name="ds_pic" accept="image/*">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_special" class="btn btn-danger px-4">
                            <i class="bi bi-plus-lg me-1"></i>Add Special
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- SPECIALS LIST -->
        <h5 class="fw-bold mb-3"><i class="bi bi-list-stars me-2"></i>Current Specials</h5>

        <?php
$specials = $mysqli->query("SELECT * FROM daily_specials ORDER BY created_at DESC");
if ($specials && $specials->num_rows > 0):
?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
            <?php while ($s = $specials->fetch_assoc()):
        $img = $s['ds_pic'] ? "../img/" . $s['ds_pic'] : "../img/default.png";
?>
            <div class="col">
                <div class="card specials-card shadow-sm h-100">
                    <img src="<?= htmlspecialchars($img)?>" class="card-img-top"
                        alt="<?= htmlspecialchars($s['ds_name'])?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold mb-0">
                                <?= htmlspecialchars($s['ds_name'])?>
                            </h5>
                            <span
                                class="status-badge bg-<?= $s['ds_status'] == 'active' ? 'success' : 'secondary'?> text-white">
                                <?= $s['ds_status'] == 'active' ? '🟢 Active' : '⚪ Inactive'?>
                            </span>
                        </div>
                        <?php if ($s['ds_description']): ?>
                        <p class="text-muted small mb-2">
                            <?= htmlspecialchars($s['ds_description'])?>
                        </p>
                        <?php
        endif; ?>
                        <p class="fs-5 fw-bold text-success mb-3">₹
                            <?= number_format($s['ds_price'], 2)?>
                        </p>
                        <div class="d-flex gap-2">
                            <a href="admin_specials.php?toggle=<?= $s['ds_id']?>"
                                class="btn btn-sm btn-outline-<?= $s['ds_status'] == 'active' ? 'warning' : 'success'?>">
                                <i class="bi bi-<?= $s['ds_status'] == 'active' ? 'pause-circle' : 'play-circle'?>"></i>
                                <?= $s['ds_status'] == 'active' ? 'Deactivate' : 'Activate'?>
                            </a>
                            <a href="admin_specials.php?delete=<?= $s['ds_id']?>" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Delete this special?')">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <small class="text-muted">Added:
                            <?= date('M d, Y h:i A', strtotime($s['created_at']))?>
                        </small>
                    </div>
                </div>
            </div>
            <?php
    endwhile; ?>
        </div>
        <?php
else: ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-stars display-1"></i>
            <p class="mt-3 fs-5">No specials added yet. Add your first special above!</p>
        </div>
        <?php
endif; ?>
    </div>

    <?php include('admin_footer.php')?>
</body>

</html>