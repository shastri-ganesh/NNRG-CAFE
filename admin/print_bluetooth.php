<?php
session_start();
include("../conn_db.php");

if ($_SESSION["utype"] != "ADMIN") {
    header("location: ../restricted.php");
    exit;
}

$tid = $_GET['tid'] ?? '';
$print_type = $_GET['type'] ?? 'bill';

if (!$tid) die("Order ID required");

$is_walkin = (strpos($tid, 'WLK_') === 0);

/* ---------- FETCH ORDER ---------- */
$q = "SELECT t.*, c.c_phone
      FROM transaction t
      LEFT JOIN customer c ON t.c_id = c.c_id
      WHERE t.tid = ?";
$s = $mysqli->prepare($q);
$s->bind_param("s", $tid);
$s->execute();
$r = $s->get_result();
if ($r->num_rows == 0) die("Order not found");
$order = $r->fetch_assoc();

/* ---------- ITEMS — exact 3 fields the printer app expects ---------- */
$q2 = "SELECT ti.quantity, ti.total_price, f.f_name
       FROM transaction_items ti
       JOIN food f ON ti.f_id = f.f_id
       WHERE ti.tid = ?";
$s2 = $mysqli->prepare($q2);
$s2->bind_param("s", $tid);
$s2->execute();
$r2 = $s2->get_result();

$items = [];
while ($row = $r2->fetch_assoc()) $items[] = $row;

/* ---------- KOT NUMBER ---------- */
$kot_q = "SELECT COUNT(*) AS kot_no FROM transaction
          WHERE DATE(created_at) = DATE(?) AND created_at <= ?";
$ks = $mysqli->prepare($kot_q);
$ks->bind_param("ss", $order['created_at'], $order['created_at']);
$ks->execute();
$kot = $ks->get_result()->fetch_assoc()['kot_no'];

/* ---------- INVOICE NUMBER (continuous, never resets) ---------- */
$invoice_q = "SELECT COUNT(*) AS invoice_no FROM transaction WHERE created_at <= ?";
$is = $mysqli->prepare($invoice_q);
$is->bind_param("s", $order['created_at']);
$is->execute();
$invoice_count = $is->get_result()->fetch_assoc()['invoice_no'];
$invoice_number = 23000 + $invoice_count;

$s->close(); $s2->close(); $ks->close(); $is->close();
$mysqli->close();

/* ---------- COMMON ---------- */
$date   = date('d/m/Y', strtotime($order['created_at']));
$time   = date('h:i A', strtotime($order['created_at']));
$pickup = $order['pickup_time'] ?? 'ASAP';
$roll   = $order['rollno'] ?? '';
$name   = $order['name'];
$total  = number_format($order['order_cost'], 2);

$is_kot     = ($print_type === 'kot');
$page_title = $is_kot ? 'KOT Print' : 'Bill Print';
$badge_class = $is_kot ? 'kot-badge' : 'bill-badge';
$badge_text  = $is_kot ? '🧾 KITCHEN ORDER TICKET' : '🧾 BILL / INVOICE';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?></title>
<style>
body{font-family:Arial;background:#f4f6fb;padding:20px}
.container{max-width:600px;background:#fff;padding:20px;border-radius:10px;margin:0 auto}
.btn{padding:12px 24px;font-size:16px;border:none;border-radius:6px;cursor:pointer;margin-right:10px}
.print{background:#28a745;color:#fff}
.back{background:#6c757d;color:#fff}
.items-table{width:100%;border-collapse:collapse;margin:20px 0}
.items-table th,.items-table td{padding:10px;text-align:left;border-bottom:1px solid #dee2e6}
.items-table th{background:#f8f9fa;font-weight:600}
.items-section{background:#f8f9fa;padding:15px;border-radius:8px;margin:15px 0}
.kot-badge{display:inline-block;background:#ed8936;color:#fff;padding:8px 16px;border-radius:6px;font-weight:700;font-size:14px;margin:10px 0}
.bill-badge{display:inline-block;background:#38a169;color:#fff;padding:8px 16px;border-radius:6px;font-weight:700;font-size:14px;margin:10px 0}
.print-type-header{text-align:center;border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:15px}
</style>
</head>
<body>

<div class="container">
<div class="print-type-header">
<h2 style="margin:5px 0">🖨 <?= $is_kot ? 'Kitchen Order' : 'Customer Bill' ?></h2>
<span class="<?= $badge_class ?>"><?= $badge_text ?></span>
</div>

<?php if($is_kot): ?>
<p><b>🧾 KOT Number:</b> KOT-<?= date('Ymd', strtotime($order['created_at'])) ?>-<?= $kot ?></p>
<?php else: ?>
<p><b>🧾 Invoice Number:</b> <?= $invoice_number ?></p>
<?php endif; ?>

<p><b>📋 Transaction ID:</b> <?= $tid ?></p>
<p><b>📅 Date:</b> <?= $date ?></p>
<p><b>🕒 Time:</b> <?= $time ?></p>

<?php if(!$is_walkin): ?>
<p><b>🎓 Roll / Username:</b> <?= htmlspecialchars($roll) ?></p>
<p><b>⏰ Pickup:</b> <?= htmlspecialchars($pickup) ?></p>
<?php endif; ?>

<p><b>👤 Customer:</b> <?= htmlspecialchars($name) ?></p>

<div class="items-section">
<h4 style="margin-top:0"><?= $is_kot ? '🍽 Items to Prepare' : '📋 Ordered Items' ?></h4>
<?php if(!empty($items)): ?>
<table class="items-table">
<thead>
<tr>
<th>Item Name</th>
<th style="text-align:center">Qty</th>
<?php if(!$is_kot): ?>
<th style="text-align:right">Price</th>
<th style="text-align:right">Total</th>
<?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach($items as $item): ?>
<tr>
<td><?= htmlspecialchars($item['f_name']) ?></td>
<td style="text-align:center"><b><?= $item['quantity'] ?></b></td>
<?php if(!$is_kot): ?>
<td style="text-align:right">Rs.<?= number_format($item['total_price'] / $item['quantity'], 2) ?></td>
<td style="text-align:right">Rs.<?= number_format($item['total_price'], 2) ?></td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p style="color:#dc3545">⚠️ No items found for this order</p>
<?php endif; ?>
</div>

<?php if(!$is_kot): ?>
<div style="background:#f8f9fa;padding:15px;border-radius:8px;margin:15px 0">
<table style="width:100%;border-collapse:collapse">
<thead>
<tr>
<th style="text-align:left;padding:8px 6px;border-bottom:2px solid #dee2e6">Item Name</th>
<th style="text-align:center;padding:8px 6px;border-bottom:2px solid #dee2e6">Qty</th>
<th style="text-align:right;padding:8px 6px;border-bottom:2px solid #dee2e6">Total</th>
</tr>
</thead>
<tbody>
<?php foreach($items as $item): ?>
<tr>
<td style="padding:7px 6px;border-bottom:1px solid #dee2e6"><?= htmlspecialchars($item['f_name']) ?></td>
<td style="text-align:center;padding:7px 6px;border-bottom:1px solid #dee2e6"><b><?= $item['quantity'] ?></b></td>
<td style="text-align:right;padding:7px 6px;border-bottom:1px solid #dee2e6">Rs.<?= number_format($item['total_price'], 2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p style="font-size:18px;font-weight:700;margin:12px 0 0;text-align:right">
💰 Total Amount: <span style="color:#38a169">Rs.<?= $total ?></span>
</p>
</div>
<?php else: ?>
<div style="background:#fff3cd;border:2px dashed #ed8936;padding:12px;border-radius:8px;margin:15px 0">
<p style="margin:0;font-weight:600;color:#856404">
⚠️ <b>KITCHEN COPY</b> - Please prepare the above items
</p>
</div>
<?php endif; ?>

<button class="btn print" onclick="printNow()">🖨 Print <?= $is_kot ? 'KOT' : 'Bill' ?></button>
<button class="btn back" onclick="location.href='admin_menu_page.php'">⬅ Back to Counter</button>
</div>

<script>
// Grab raw elements from PHP safely
const rawItems = <?= json_encode($items) ?>;

// Clean array structure mappings to protect external app layout parser columns
const formattedItems = rawItems.map(item => {
    return {
        f_name: String(item.f_name).trim(), 
        quantity: parseInt(item.quantity),
        total_price: parseFloat(item.total_price)
    };
});

const orderData = {
    printType: "<?= $print_type ?>",
    tid: "<?= $tid ?>",
    kot: <?= $kot ?>,
    invoiceNo: <?= $invoice_number ?>,
    isWalkin: <?= $is_walkin ? 'true':'false' ?>,
    date: "<?= $date ?>",
    time: "<?= $time ?>",
    roll: "<?= addslashes($roll) ?>",
    name: "<?= addslashes($name) ?>",
    pickup: "<?= addslashes($pickup) ?>",
    total: "<?= $total ?>",
    items: formattedItems
};

function printNow(){
    const payload = encodeURIComponent(JSON.stringify(orderData));
    window.location.href = "nnrgprint://print?data=" + payload;
    setTimeout(() => {
        window.location.href = "admin_menu_page.php";
    }, 1500);
}
</script>

</body>
</html>