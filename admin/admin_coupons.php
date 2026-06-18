<?php
session_start();
include("../conn_db.php");

// Check if user is admin
if ($_SESSION["utype"] != "ADMIN") {
    header("location: ../restricted.php");
    exit(1);
}

// Handle Add Coupon
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['coupon_code']));
    $discount = floatval($_POST['discount']);
    $min_order = floatval($_POST['min_order']);

    if (!empty($code) && $discount > 0) {
        $stmt = $mysqli->prepare("INSERT INTO coupons (coupon_code, discount_amount, min_order_amount) VALUES (?, ?, ?)");
        $stmt->bind_param("sdd", $code, $discount, $min_order);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Coupon code {$code} added successfully!";
        }
        else {
            $_SESSION['error'] = "Failed to add coupon. Code may already exist.";
        }
        $stmt->close();
    }
    else {
        $_SESSION['error'] = "Invalid coupon details provided.";
    }
    header("Location: admin_coupons.php");
    exit();
}

// Handle Delete/Deactivate Coupon
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $mysqli->prepare("DELETE FROM coupons WHERE coupon_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Coupon deleted successfully!";
    }
    $stmt->close();
    header("Location: admin_coupons.php");
    exit();
}

// Fetch all coupons
$result = $mysqli->query("SELECT * FROM coupons ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coupons | NNRG-CAFE Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="../css/main.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 70px;
            margin-bottom: 20px;
        }

        .coupon-card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .coupon-code-badge {
            font-size: 1.1rem;
            letter-spacing: 1px;
            font-weight: bold;
            background: #f8f9fa;
            border: 2px dashed #0d6efd;
            color: #0d6efd;
            padding: 5px 15px;
            border-radius: 5px;
        }
    </style>
</head>

<body class="bg-light">

    <?php include('nav_header_admin.php'); ?>

    <div class="container pt-4">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h2 class="m-0"><i class="bi bi-tags-fill me-2"></i> Manage Coupons</h2>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'];
    unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php
endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'];
    unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php
endif; ?>

        <div class="row">
            <!-- Add New Coupon Form -->
            <div class="col-md-4 mb-4">
                <div class="card coupon-card">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="fw-bold"><i class="bi bi-plus-circle me-2"></i>Create New Coupon</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="admin_coupons.php">
                            <div class="mb-3">
                                <label class="form-label text-muted">Coupon Code</label>
                                <input type="text" name="coupon_code" class="form-control"
                                    placeholder="e.g., FESTIVAL50" required style="text-transform: uppercase;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Discount Amount (₹)</label>
                                <input type="number" step="0.01" name="discount" class="form-control"
                                    placeholder="e.g., 50.00" required min="1">
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted">Minimum Order Amount (₹)</label>
                                <input type="number" step="0.01" name="min_order" class="form-control"
                                    placeholder="e.g., 200.00" required min="0">
                            </div>
                            <button type="submit" name="add_coupon" class="btn btn-primary w-100 fw-bold py-2">Create
                                Coupon</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List of Active Coupons -->
            <div class="col-md-8">
                <div class="card coupon-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Code</th>
                                        <th>Discount</th>
                                        <th>Min. Order</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0):
    while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="coupon-code-badge">
                                                <?= htmlspecialchars($row['coupon_code'])?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-success">-₹
                                            <?= number_format($row['discount_amount'], 2)?>
                                        </td>
                                        <td class="text-muted">₹
                                            <?= number_format($row['min_order_amount'], 2)?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success rounded-pill">Active</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="admin_coupons.php?delete=<?= $row['coupon_id']?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to delete this coupon?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
    endwhile;
else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No coupons found. Create
                                            your first coupon!</td>
                                    </tr>
                                    <?php
endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>