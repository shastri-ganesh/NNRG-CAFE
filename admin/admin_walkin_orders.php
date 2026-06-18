<?php
// admin_walkin_orders.php - CONVERTED TO ORDERS HISTORY PAGE
session_start(); 
include("../conn_db.php"); 

if($_SESSION["utype"]!="ADMIN" && $_SESSION["utype"]!="SUPERADMIN"){
    header("location: ../restricted.php");
    exit(1);
}

// Function to generate short order ID (Pattern 1: WLK-DDMMM-HHMM)
function generateShortOrderId($original_id) {
    if (strpos($original_id, 'WLK_') === 0) {
        // Extract date and time parts from original ID: WLK_YYYYMMDDHHMMSSmmm
        $date_part = substr($original_id, 4, 8);  // YYYYMMDD
        $time_part = substr($original_id, 12, 4); // HHMM (first 4 digits of time)
        
        // Format date: 02NOV (DDMMM)
        $day = substr($date_part, 6, 2); // DD
        $month_num = substr($date_part, 4, 2); // MM
        $month_names = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        $month = $month_names[intval($month_num) - 1];
        
        // Format time: 1642 (HHMM)
        $hours = substr($time_part, 0, 2);
        $minutes = substr($time_part, 2, 2);
        
        return "WLK-" . $day . $month . "-" . $hours . $minutes;
    }
    return $original_id; // Return original if not in expected format
}

// Set default date range (last 7 days)
$start_date = date('Y-m-d', strtotime('-7 days'));
$end_date = date('Y-m-d');

// Get date filters from URL
if(isset($_GET['start_date']) && !empty($_GET['start_date'])){
    $start_date = $_GET['start_date'];
}
if(isset($_GET['end_date']) && !empty($_GET['end_date'])){
    $end_date = $_GET['end_date'];
}

// Get status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
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
    <title>Orders History | NNRG-CÁFE</title>
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 0;
        }
        /* Fix header positioning */
        .navbar {
            position: fixed !important;
            top: 0 !important;
            width: 100% !important;
            z-index: 1030 !important;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            margin-top: 80px; /* Push below fixed header */
        }
        .order-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
            transition: transform 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .order-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-preparing {
            background: #fff3cd;
            color: #856404;
        }
        .status-ready {
            background: #cce7ff;
            color: #004085;
        }
        .item-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #667eea;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .export-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        .export-btn:hover {
            background: #218838;
        }
        .order-id {
            font-size: 1rem;
            color: #2c3e50;
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
        }
        .original-order-id {
            font-size: 0.7rem;
            color: #6c757d;
            font-family: 'Courier New', monospace;
        }
        @media (max-width: 768px) {
            .header-section {
                margin-top: 70px;
                padding: 20px 0;
            }
        }
    </style>
</head>
<body>
    <?php include('nav_header_admin.php')?>

    <!-- Header Section -->
    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold">
                        <i class="fas fa-history me-3"></i>Orders History
                    </h1>
                    <p class="lead mb-0">View all accepted orders</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="admin_menu_page.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus-circle me-2"></i>New Counter Order
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number" id="totalOrders">0</div>
                    <p class="text-muted mb-0">Total Orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">₹<span id="totalRevenue">0</span></div>
                    <p class="text-muted mb-0">Total Revenue</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number" id="avgOrderValue">0</div>
                    <p class="text-muted mb-0">Avg Order Value</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number" id="completedOrders">0</div>
                    <p class="text-muted mb-0">Completed Orders</p>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filter-section">
            <form id="filterForm" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Status</label>
                        <select class="form-select" name="status">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="FNSH" <?php echo $status_filter == 'FNSH' ? 'selected' : ''; ?>>Completed</option>
                            <option value="PREP" <?php echo $status_filter == 'PREP' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="RDPK" <?php echo $status_filter == 'RDPK' ? 'selected' : ''; ?>>Ready</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Order Type</label>
                        <select class="form-select" name="order_type">
                            <option value="all">All Types</option>
                            <option value="walkin">Walk-in</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <button class="btn btn-success" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-2"></i>Export to Excel
                    </button>
                    <button class="btn btn-outline-secondary" onclick="printHistory()">
                        <i class="fas fa-print me-2"></i>Print Report
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <div class="input-group" style="max-width: 300px; margin-left: auto;">
                        <input type="text" class="form-control" placeholder="Search orders..." id="searchOrders">
                        <button class="btn btn-outline-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <div id="ordersList">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading orders history...</p>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        // Function to generate short order ID (Pattern 1: WLK-DDMMM-HHMM)
        function generateShortOrderId(originalId) {
            if (originalId.startsWith('WLK_')) {
                try {
                    // Extract parts from: WLK_YYYYMMDDHHMMSSmmm
                    const datePart = originalId.substring(4, 12);  // YYYYMMDD
                    const timePart = originalId.substring(12, 16); // HHMM
                    
                    // Format date: 02NOV (DDMMM)
                    const day = datePart.substring(6, 8);
                    const monthNum = parseInt(datePart.substring(4, 6));
                    const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
                    const month = monthNames[monthNum - 1];
                    
                    return `WLK-${day}${month}-${timePart}`;
                } catch (error) {
                    console.error('Error generating short ID:', error);
                    return originalId;
                }
            }
            return originalId;
        }

        async function loadOrdersHistory() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            const status = document.querySelector('select[name="status"]').value;
            const orderType = document.querySelector('select[name="order_type"]').value;
            
            try {
                const response = await fetch(`admin_orders_history_ajax.php?action=get_orders&start_date=${startDate}&end_date=${endDate}&status=${status}&order_type=${orderType}`);
                const data = await response.json();

                if(data.success) {
                    updateOrderStats(data.stats);
                    displayOrders(data.orders);
                } else {
                    document.getElementById('ordersList').innerHTML = `
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message || 'No orders found for the selected filters'}
                        </div>
                    `;
                }
            } catch(error) {
                console.error('Error loading orders:', error);
                document.getElementById('ordersList').innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load orders history. Please try again.
                    </div>
                `;
            }
        }

        function updateOrderStats(stats) {
            document.getElementById('totalOrders').textContent = stats.total_orders || 0;
            document.getElementById('totalRevenue').textContent = stats.total_revenue ? parseInt(stats.total_revenue).toLocaleString('en-IN') : 0;
            document.getElementById('avgOrderValue').textContent = stats.avg_order_value ? '₹' + parseInt(stats.avg_order_value).toLocaleString('en-IN') : '₹0';
            document.getElementById('completedOrders').textContent = stats.completed_orders || 0;
        }

        function displayOrders(orders) {
            if(!orders || orders.length === 0) {
                document.getElementById('ordersList').innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No orders found</h4>
                        <p class="text-muted">No orders match your current filters.</p>
                        <button class="btn btn-primary" onclick="clearFilters()">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </button>
                    </div>
                `;
                return;
            }

            let html = '';
            orders.forEach((order) => {
                const shortOrderId = generateShortOrderId(order.tid);
                const statusClass = getStatusClass(order.order_status);
                const statusText = getStatusText(order.order_status);
                const orderDateTime = new Date(order.created_at).toLocaleString('en-US', { 
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <div class="order-card">
                        <div class="order-header">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="fw-bold mb-1">
                                        Order <span class="order-id">${shortOrderId}</span>
                                    </h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-calendar me-1"></i>${orderDateTime}
                                        | <i class="fas fa-user me-1"></i>${order.name || 'Walk-in Customer'}
                                        | <i class="fas fa-tag me-1"></i>${order.order_type || 'walkin'}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <span class="status-badge ${statusClass}">${statusText}</span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <strong class="text-success">₹${parseFloat(order.order_cost || 0).toFixed(2)}</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="item-list">
                            <h6 class="fw-bold mb-3">Order Items:</h6>
                            ${order.items && order.items.map(item => `
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="fw-bold">${item.f_name || item.name}</span>
                                        <small class="text-muted">x${item.quantity}</small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">₹${parseFloat(item.total_price || 0).toFixed(2)}</small>
                                    </div>
                                </div>
                            `).join('') || '<p class="text-muted">No items found</p>'}
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-8">
                                <small class="text-muted">
                                    <i class="fas fa-receipt me-1"></i>Order ID: <span class="order-id">${shortOrderId}</span>
                                    | <i class="fas fa-credit-card me-1"></i>Payment: ${order.payment_method || 'Cash'}
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails('${order.tid}', '${shortOrderId}')">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="reprintReceipt('${order.tid}')">
                                    <i class="fas fa-print me-1"></i>Reprint
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            document.getElementById('ordersList').innerHTML = html;
        }

        function getStatusClass(status) {
            switch(status) {
                case 'PREP': return 'status-preparing';
                case 'RDPK': return 'status-ready';
                case 'FNSH': return 'status-completed';
                default: return 'status-completed';
            }
        }

        function getStatusText(status) {
            switch(status) {
                case 'PREP': return '🔄 Preparing';
                case 'RDPK': return '✅ Ready for Pickup';
                case 'FNSH': return '🎉 Completed';
                default: return 'Completed';
            }
        }

        function clearFilters() {
            document.querySelector('input[name="start_date"]').value = '<?php echo date('Y-m-d', strtotime('-7 days')); ?>';
            document.querySelector('input[name="end_date"]').value = '<?php echo date('Y-m-d'); ?>';
            document.querySelector('select[name="status"]').value = 'all';
            document.querySelector('select[name="order_type"]').value = 'all';
            loadOrdersHistory();
        }

        function viewOrderDetails(orderId, shortOrderId) {
            alert('Order Details:\nShort ID: ' + shortOrderId + '\nOriginal ID: ' + orderId);
            // You can implement a modal here with full details
        }

        function reprintReceipt(orderId) {
            window.open(`print_bluetooth.php?tid=${orderId}`, '_blank');
        }

        async function exportToExcel() {
            try {
                // Show loading
                const originalText = event.target.innerHTML;
                event.target.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting...';
                event.target.disabled = true;

                // Get current filter data
                const startDate = document.querySelector('input[name="start_date"]').value;
                const endDate = document.querySelector('input[name="end_date"]').value;
                const status = document.querySelector('select[name="status"]').value;
                const orderType = document.querySelector('select[name="order_type"]').value;
                
                const response = await fetch(`admin_orders_history_ajax.php?action=get_orders&start_date=${startDate}&end_date=${endDate}&status=${status}&order_type=${orderType}`);
                const data = await response.json();

                if(!data.success || !data.orders) {
                    alert('No data to export');
                    return;
                }

                // Prepare Excel data with short order IDs
                const excelData = data.orders.map((order) => ({
                    'Order ID': generateShortOrderId(order.tid),
                    'Original ID': order.tid,
                    'Date': new Date(order.created_at).toLocaleDateString('en-IN'),
                    'Time': new Date(order.created_at).toLocaleTimeString('en-IN'),
                    'Customer': order.name || 'Walk-in Customer',
                    'Order Type': order.order_type || 'walkin',
                    'Status': getStatusText(order.order_status),
                    'Payment Method': order.payment_method || 'Cash',
                    'Total Amount': parseFloat(order.order_cost || 0).toFixed(2),
                    'Items': order.items ? order.items.map(item => 
                        `${item.f_name || item.name} x${item.quantity} - ₹${parseFloat(item.total_price || 0).toFixed(2)}`
                    ).join('; ') : 'No items'
                }));

                // Create worksheet
                const ws = XLSX.utils.json_to_sheet(excelData);
                
                // Create workbook
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Orders History');
                
                // Generate filename with date range
                const filename = `Orders_History_${startDate}_to_${endDate}.xlsx`;
                
                // Export to Excel
                XLSX.writeFile(wb, filename);
                
            } catch(error) {
                console.error('Export error:', error);
                alert('Error exporting to Excel: ' + error.message);
            } finally {
                // Restore button
                event.target.innerHTML = '<i class="fas fa-file-excel me-2"></i>Export to Excel';
                event.target.disabled = false;
            }
        }

        function printHistory() {
            window.print();
        }

        // Search functionality
        document.getElementById('searchOrders').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const orderCards = document.querySelectorAll('.order-card');
            
            orderCards.forEach(card => {
                const orderText = card.textContent.toLowerCase();
                card.style.display = orderText.includes(searchTerm) ? 'block' : 'none';
            });
        });

        // Load orders when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadOrdersHistory();
        });
    </script>
</body>
</html>