<?php
// admin_analytics_dashboard.php
session_start();
include("../conn_db.php");

if ($_SESSION['utype'] != 'ADMIN' && $_SESSION['utype'] != 'SHOP_ADMIN' && $_SESSION['utype'] != 'SUPERADMIN') {
    header("location: ../restricted.php");
    exit(1);
}

// Get shop_id for SHOP_ADMIN filtering
$shop_id = (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') ? intval($_SESSION['shop_id']) : null;

// Set default date range (last 7 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

// Get date filters from URL
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
}
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
}

// FUNCTION 1: Get Basic Stats for Both Order Types (UPDATED WITH TOTAL)
function getOrderStats($mysqli, $start_date, $end_date, $shop_id = null)
{
    $stats = [
        'walkin' => ['orders' => 0, 'revenue' => 0, 'avg_order_value' => 0],
        'online' => ['orders' => 0, 'revenue' => 0, 'avg_order_value' => 0],
        'total' => ['orders' => 0, 'revenue' => 0, 'avg_order_value' => 0]
    ];

    $shop_filter = $shop_id ? " AND t.tid IN (SELECT DISTINCT ti.tid FROM transaction_items ti JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = $shop_id)" : " AND t.tid NOT IN (SELECT DISTINCT ti.tid FROM transaction_items ti JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = 11)";
    $query = "SELECT 
                t.order_source,
                COUNT(*) as order_count,
                SUM(t.order_cost) as total_revenue,
                AVG(t.order_cost) as avg_order_value
              FROM transaction t
              WHERE DATE(t.created_at) BETWEEN ? AND ? $shop_filter
              GROUP BY t.order_source";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $source = $row['order_source'];
        $stats[$source] = [
            'orders' => $row['order_count'] ?? 0,
            'revenue' => $row['total_revenue'] ?? 0,
            'avg_order_value' => $row['avg_order_value'] ?? 0
        ];
    }

    // Calculate combined totals
    $stats['total']['orders'] = $stats['walkin']['orders'] + $stats['online']['orders'];
    $stats['total']['revenue'] = $stats['walkin']['revenue'] + $stats['online']['revenue'];
    $stats['total']['avg_order_value'] = $stats['total']['orders'] > 0
        ? $stats['total']['revenue'] / $stats['total']['orders']
        : 0;

    $stmt->close();
    return $stats;
}

// FUNCTION 2: Get Daily Trend Data
function getDailyTrend($mysqli, $start_date, $end_date, $shop_id = null)
{
    $trend_data = [];

    $shop_filter = $shop_id ? " AND t.tid IN (SELECT DISTINCT ti.tid FROM transaction_items ti JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = $shop_id)" : " AND t.tid NOT IN (SELECT DISTINCT ti.tid FROM transaction_items ti JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = 11)";
    $query = "SELECT 
                DATE(t.created_at) as order_date,
                t.order_source,
                COUNT(*) as order_count,
                SUM(t.order_cost) as daily_revenue
              FROM transaction t
              WHERE DATE(t.created_at) BETWEEN ? AND ? $shop_filter
              GROUP BY DATE(t.created_at), t.order_source
              ORDER BY order_date";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trend_data[] = $row;
    }

    $stmt->close();
    return $trend_data;
}

// FUNCTION 3: Get Peak Hours Distribution
function getPeakHours($mysqli, $start_date, $end_date, $shop_id = null)
{
    $hours_data = [
        'walkin' => array_fill(0, 24, 0),
        'online' => array_fill(0, 24, 0)
    ];

    $shop_filter = $shop_id ? " AND t.tid IN (SELECT DISTINCT ti.tid FROM transaction_items ti JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = $shop_id)" : " AND t.tid NOT IN (SELECT DISTINCT ti.tid FROM transaction_items ti JOIN food f ON ti.f_id = f.f_id WHERE f.s_id = 11)";
    $query = "SELECT 
                HOUR(t.created_at) as order_hour,
                t.order_source,
                COUNT(*) as order_count
              FROM transaction t
              WHERE DATE(t.created_at) BETWEEN ? AND ? $shop_filter
              GROUP BY HOUR(t.created_at), t.order_source
              ORDER BY order_hour";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $hour = (int)$row['order_hour'];
        $source = $row['order_source'];
        $hours_data[$source][$hour] = (int)$row['order_count'];
    }

    $stmt->close();
    return $hours_data;
}

// FUNCTION 4: Get Popular Items
function getPopularItems($mysqli, $start_date, $end_date, $limit = 5, $shop_id = null)
{
    $popular_items = [
        'walkin' => [],
        'online' => []
    ];

    $shop_filter = $shop_id ? " AND f.s_id = $shop_id" : " AND f.s_id != 11";
    $query = "SELECT 
                t.order_source,
                f.f_name,
                COUNT(*) as order_count,
                SUM(ti.quantity) as total_quantity
              FROM transaction t
              JOIN transaction_items ti ON t.tid = ti.tid
              JOIN food f ON ti.f_id = f.f_id
              WHERE DATE(t.created_at) BETWEEN ? AND ? $shop_filter
              GROUP BY t.order_source, f.f_name
              ORDER BY t.order_source, order_count DESC
              LIMIT 20";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $source = $row['order_source'];
        if (count($popular_items[$source]) < $limit) {
            $popular_items[$source][] = [
                'name' => $row['f_name'],
                'order_count' => $row['order_count'],
                'total_quantity' => $row['total_quantity']
            ];
        }
    }

    $stmt->close();
    return $popular_items;
}

// NEW FUNCTION 5: Get Comprehensive Item-Wise Sales Breakdown for Vendors
function getItemWiseSalesBreakdown($mysqli, $start_date, $end_date, $shop_id = null)
{
    $sales_data = [];
    $shop_filter = $shop_id ? " AND f.s_id = $shop_id" : " AND f.s_id != 11";
    
    $query = "SELECT 
                f.f_name,
                SUM(CASE WHEN t.order_source = 'walkin' THEN ti.quantity ELSE 0 END) as walkin_qty,
                SUM(CASE WHEN t.order_source = 'online' THEN ti.quantity ELSE 0 END) as online_qty,
                SUM(ti.quantity) as total_qty,
                SUM(ti.quantity * f.f_price) as total_revenue
              FROM transaction t
              JOIN transaction_items ti ON t.tid = ti.tid
              JOIN food f ON ti.f_id = f.f_id
              WHERE DATE(t.created_at) BETWEEN ? AND ? $shop_filter
              GROUP BY f.f_name
              ORDER BY total_qty DESC";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sales_data[] = [
            'name' => $row['f_name'],
            'walkin_qty' => intval($row['walkin_qty']),
            'online_qty' => intval($row['online_qty']),
            'total_qty' => intval($row['total_qty']),
            'revenue' => doubleval($row['total_revenue'])
        ];
    }
    
    $stmt->close();
    return $sales_data;
}

// Check if it's an AJAX request for data
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');

    $stats = getOrderStats($mysqli, $start_date, $end_date, $shop_id);
    $trend_data = getDailyTrend($mysqli, $start_date, $end_date, $shop_id);
    $peak_hours = getPeakHours($mysqli, $start_date, $end_date, $shop_id);
    $popular_items = getPopularItems($mysqli, $start_date, $end_date, 5, $shop_id);
    $itemwise_sales = getItemWiseSalesBreakdown($mysqli, $start_date, $end_date, $shop_id);

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'trend_data' => $trend_data,
        'peak_hours' => $peak_hours,
        'popular_items' => $popular_items,
        'itemwise_sales' => $itemwise_sales,
        'date_range' => [
            'start_date' => $start_date,
            'end_date' => $end_date
        ]
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../img/Color Icon with background.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <title>Analytics Dashboard | NNRG-CÁFE</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .walkin-stat {
            border-left: 5px solid #667eea;
        }

        .online-stat {
            border-left: 5px solid #48bb78;
        }

        .walkin-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .online-badge {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }

        .total-sales-card {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border: none;
            color: black;
        }

        .total-sales-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(245, 87, 108, 0.4);
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .date-filter {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .popular-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .popular-item:last-child {
            border-bottom: none;
        }

        .item-rank {
            width: 30px;
            height: 30px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }

        .online .item-rank {
            background: #48bb78;
        }

        /* NEW CUSTOM STYLE SPECIFICATION FOR ITEMIZED ANALYSIS ROWS */
        .itemwise-table th {
            background-color: #f8f9fa;
            color: #4a5568;
            font-weight: 600;
        }
        .itemwise-table tr:hover {
            background-color: #fcfcfc;
        }
    </style>
</head>

<body>
    <?php include('nav_header_admin.php')?>

    <div class="container-fluid dashboard-container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold text-dark">
                    <i class="fas fa-chart-line me-3"></i>
                    <?php echo $shop_id ? htmlspecialchars($_SESSION['shop_name']) . ' Analytics' : 'Analytics Dashboard'; ?>
                </h1>
                <p class="text-muted">
                    <?php echo $shop_id ? 'Real-time insights into your shop performance' : 'Real-time insights into your cafeteria performance'; ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group">
                    <button class="btn btn-outline-primary active" onclick="setDateRange('today')">Today</button>
                    <button class="btn btn-outline-primary" onclick="setDateRange('week')">This Week</button>
                    <button class="btn btn-outline-primary" onclick="setDateRange('month')">This Month</button>
                </div>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="date-filter">
            <form id="dateFilterForm" method="GET">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">From Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">To Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Apply Filter
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="resetDateFilter()">
                            <i class="fas fa-refresh me-2"></i>Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats Overview -->
        <div class="row" id="statsOverview">
            <div class="col-md-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading analytics data...</p>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-chart-bar me-2"></i>Daily Orders Trend
                    </h5>
                    <canvas id="ordersTrendChart" height="250"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-clock me-2"></i>Peak Hours Distribution
                    </h5>
                    <canvas id="peakHoursChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Popular Items Summary Cards -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-utensils me-2"></i>Popular Walk-in Items
                    </h5>
                    <div id="walkinPopularItems"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-mobile-alt me-2"></i>Popular Online Items
                    </h5>
                    <div id="onlinePopularItems"></div>
                </div>
            </div>
        </div>

        <!-- NEW COMPREHENSIVE ITEM-WISE QUANTITY SALES SECTION FOR VENDORS -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="chart-container">
                    <h5 class="fw-bold mb-4 text-dark">
                        <i class="fas fa-list-ol me-2 text-primary"></i>Detailed Item-Wise Sales Count
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped itemwise-table align-middle">
                            <thead>
                                <tr class="text-center">
                                    <th class="text-start" style="width: 40%;">Item Name</th>
                                    <th>Walk-In Qty</th>
                                    <th>Online Qty</th>
                                    <th class="table-primary text-primary">Total Sold Qty</th>
                                    <th class="table-success text-success">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="itemWiseSalesTableBody">
                                <tr>
                                    <td colspan="5" class="text-center text-muted italic py-4">No item sales records found for this period.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let ordersTrendChart = null;
        let peakHoursChart = null;

        async function loadAnalyticsData() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;

            try {
                const response = await fetch(`?ajax=1&start_date=${startDate}&end_date=${endDate}`);
                const data = await response.json();

                if (data.success) {
                    updateStatsOverview(data.stats);
                    updatePopularItems(data.popular_items);
                    updateItemWiseSalesTable(data.itemwise_sales); // Triggers real-time item count render loop
                    createTrendChart(data.trend_data);
                    createPeakHoursChart(data.peak_hours);
                }
            } catch (error) {
                console.error('Error loading analytics:', error);
                document.getElementById('statsOverview').innerHTML = `
                    <div class="col-md-12 text-center py-5">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Failed to load analytics data
                        </div>
                    </div>
                `;
            }
        }

        function updateStatsOverview(stats) {
            const walkin = stats.walkin || { orders: 0, revenue: 0, avg_order_value: 0 };
            const online = stats.online || { orders: 0, revenue: 0, avg_order_value: 0 };
            const total = stats.total || { orders: 0, revenue: 0, avg_order_value: 0 };

            document.getElementById('statsOverview').innerHTML = `
                <div class="col-md-12 mb-3">
                    <div class="stat-card total-sales-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-dark mb-2" style="opacity: 0.8;">
                                    <i class="fas fa-chart-line me-2"></i>TOTAL SALES (Walk-in + Online)
                                </h6>
                                <div style="font-size: 3rem; font-weight: 700; line-height: 1;">
                                    ₹${Number(total.revenue).toLocaleString('en-IN')}
                                </div>
                                <div class="mt-2" style="opacity: 0.9;">
                                    <i class="fas fa-shopping-cart me-2"></i>${total.orders} Total Orders
                                    <span class="mx-3">•</span>
                                    <i class="fas fa-calculator me-2"></i>Avg: ₹${Math.round(total.avg_order_value)}
                                </div>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-coins fa-4x" style="opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="stat-card walkin-stat">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge walkin-badge text-white px-3 py-2">
                                <i class="fas fa-store me-2"></i>WALK-IN ORDERS
                            </span>
                            <i class="fas fa-users text-primary fa-2x"></i>
                        </div>
                        <div class="stat-number text-primary">${walkin.orders}</div>
                        <p class="text-muted mb-2">Total Orders</p>
                        <div class="d-flex justify-content-between border-top pt-3">
                            <div>
                                <div class="fw-bold text-dark">₹${Number(walkin.revenue).toLocaleString('en-IN')}</div>
                                <small class="text-muted">Total Revenue</small>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">₹${Math.round(walkin.avg_order_value)}</div>
                                <small class="text-muted">Avg. Order</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="stat-card online-stat">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge online-badge text-white px-3 py-2">
                                <i class="fas fa-mobile-alt me-2"></i>ONLINE ORDERS
                            </span>
                            <i class="fas fa-qrcode text-success fa-2x"></i>
                        </div>
                        <div class="stat-number text-success">${online.orders}</div>
                        <p class="text-muted mb-2">Total Orders</p>
                        <div class="d-flex justify-content-between border-top pt-3">
                            <div>
                                <div class="fw-bold text-dark">₹${Number(online.revenue).toLocaleString('en-IN')}</div>
                                <small class="text-muted">Total Revenue</small>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">₹${Math.round(online.avg_order_value)}</div>
                                <small class="text-muted">Avg. Order</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function updatePopularItems(popular_items) {
            let walkinHtml = '';
            if (popular_items.walkin && popular_items.walkin.length > 0) {
                popular_items.walkin.forEach((item, index) => {
                    walkinHtml += `
                        <div class="popular-item">
                            <div class="item-rank">${index + 1}</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">${item.name}</div>
                                <small class="text-muted">${item.order_count} orders • ${item.total_quantity} items</small>
                            </div>
                        </div>
                    `;
                });
            } else {
                walkinHtml = '<p class="text-muted text-center py-3">No walk-in orders in this period</p>';
            }
            document.getElementById('walkinPopularItems').innerHTML = walkinHtml;

            let onlineHtml = '';
            if (popular_items.online && popular_items.online.length > 0) {
                popular_items.online.forEach((item, index) => {
                    onlineHtml += `
                        <div class="popular-item online">
                            <div class="item-rank">${index + 1}</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">${item.name}</div>
                                <small class="text-muted">${item.order_count} orders • ${item.total_quantity} items</small>
                            </div>
                        </div>
                    `;
                });
            } else {
                onlineHtml = '<p class="text-muted text-center py-3">No online orders in this period</p>';
            }
            document.getElementById('onlinePopularItems').innerHTML = onlineHtml;
        }

        // NEW JAVASCRIPT INJECTION LOGIC: Builds out the real-time items loop table rows dynamically
        function updateItemWiseSalesTable(itemwise_sales) {
            const tbody = document.getElementById('itemWiseSalesTableBody');
            
            if (!itemwise_sales || itemwise_sales.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4 italic">No item sales records found for this period.</td>
                    </tr>
                `;
                return;
            }

            let tableHtml = '';
            itemwise_sales.forEach(item => {
                tableHtml += `
                    <tr class="text-center">
                        <td class="text-start fw-bold text-dark" style="padding-left: 15px;">${item.name}</td>
                        <td class="text-muted">${item.walkin_qty}</td>
                        <td class="text-muted">${item.online_qty}</td>
                        <td class="fw-bold text-primary table-primary">${item.total_qty}</td>
                        <td class="fw-bold text-success table-success">₹${Number(item.revenue).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = tableHtml;
        }

        function createTrendChart(trend_data) {
            const ctx = document.getElementById('ordersTrendChart').getContext('2d');
            const dates = [...new Set(trend_data.map(item => item.order_date))].sort();
            const walkinData = dates.map(date => {
                const item = trend_data.find(d => d.order_date === date && d.order_source === 'walkin');
                return item ? item.order_count : 0;
            });
            const onlineData = dates.map(date => {
                const item = trend_data.find(d => d.order_date === date && d.order_source === 'online');
                return item ? item.order_count : 0;
            });

            if (ordersTrendChart) { ordersTrendChart.destroy(); }

            ordersTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Walk-in Orders',
                            data: walkinData,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Online Orders',
                            data: onlineData,
                            borderColor: '#48bb78',
                            backgroundColor: 'rgba(72, 187, 120, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of Orders' } } }
                }
            });
        }

        function createPeakHoursChart(peak_hours) {
            const ctx = document.getElementById('peakHoursChart').getContext('2d');
            const hours = Array.from({ length: 24 }, (_, i) => `${i}:00`);

            if (peakHoursChart) { peakHoursChart.destroy(); }

            peakHoursChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hours,
                    datasets: [
                        {
                            label: 'Walk-in',
                            data: peak_hours.walkin,
                            backgroundColor: 'rgba(102, 126, 234, 0.8)',
                            borderColor: '#667eea',
                            borderWidth: 1
                        },
                        {
                            label: 'Online',
                            data: peak_hours.online,
                            backgroundColor: 'rgba(72, 187, 120, 0.8)',
                            borderColor: '#48bb78',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of Orders' } } }
                }
            });
        }

        function setDateRange(range) {
            let startDate, endDate = new Date().toISOString().split('T')[0];

            switch (range) {
                case 'today':
                    startDate = endDate;
                    break;
                case 'week':
                    startDate = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    break;
                case 'month':
                    startDate = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    break;
            }

            document.querySelector('input[name="start_date"]').value = startDate;
            document.querySelector('input[name="end_date"]').value = endDate;

            document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
            if(event) event.target.classList.add('active');

            loadAnalyticsData();
        }

        function resetDateFilter() {
            const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            const today = new Date().toISOString().split('T')[0];

            document.querySelector('input[name="start_date"]').value = weekAgo;
            document.querySelector('input[name="end_date"]').value = today;

            loadAnalyticsData();
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadAnalyticsData();
            setInterval(loadAnalyticsData, 120000); // Triggers real-time context reload optimization sweep every 2 minutes
        });
    </script>
</body>

</html>