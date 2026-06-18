<?php
session_start();
include("../conn_db.php");

if ($_SESSION["utype"] != "ADMIN" && $_SESSION["utype"] != "SHOP_ADMIN") {
    header("location: ../restricted.php");
    exit(1);
}

$image_base_path = "../img/";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link href="../img/Color Icon with background.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <title>Counter Orders | NNRG-CÁFE</title>

    <style>
        body {
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        /* ===== MAIN CONTENT AREA - SCROLLABLE ===== */
        #menu-page {
            margin-top: 70px;
            margin-left: 0;
            margin-right: 0;
            padding: 15px;
            padding-bottom: 350px;
            /* Space for fixed bottom section */
        }

        /* ===== CATEGORY TABS ===== */
        .category-tabs {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 15px;
        }

        .category-tab {
            background: #fff;
            border: 2px solid #dee2e6;
            border-radius: 25px;
            padding: 8px 20px;
            margin: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .category-tab:hover {
            background: #f8f9fa;
        }

        .category-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border-color: #667eea;
        }

        /* ===== FOOD CONTAINER ===== */
        #foodContainer {
            margin-bottom: 20px;
        }

        /* ===== COMPACT ITEM CARD ===== */
        .food-item-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px;
            height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .food-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .food-image,
        .no-image {
            height: 90px;
            border-radius: 8px;
            object-fit: cover;
        }

        .no-image {
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
        }

        .food-name {
            font-size: 13px;
            font-weight: 600;
            line-height: 1.3;
            height: 34px;
            overflow: hidden;
            margin-top: 5px;
        }

        .food-price {
            font-size: 14px;
            font-weight: 700;
            color: #16a34a;
            margin: 5px 0;
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .add-cart-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 600;
            flex: 1;
            min-width: 65px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .add-cart-btn:hover {
            background: #1d4ed8;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .qty-btn {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: none;
            background: #374151;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background: #1f2937;
        }

        .qty-count {
            min-width: 18px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
        }

        /* ===== FIXED BOTTOM SECTION - CART PREVIEW + BUTTONS ===== */
        .bottom-fixed-section {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-top: 3px solid #e5e7eb;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding-bottom: env(safe-area-inset-bottom, 0);
        }

        /* ===== CART PREVIEW SECTION ===== */
        .cart-preview-section {
            max-height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 12px 15px;
        }

        .cart-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            margin-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }

        .cart-preview-title {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
        }

        .cart-item-count-badge {
            background: #2563eb;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .preview-items-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .preview-item {
            background: #fff;
            border-radius: 6px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .preview-item-details {
            flex: 1;
        }

        .preview-item-name {
            font-weight: 600;
            font-size: 13px;
            color: #1f2937;
            margin-bottom: 2px;
        }

        .preview-item-shop {
            font-size: 10px;
            color: #6b7280;
        }

        .preview-item-right {
            text-align: right;
        }

        .preview-qty {
            font-weight: 600;
            color: #2563eb;
            font-size: 12px;
        }

        .preview-amount {
            font-weight: 700;
            color: #16a34a;
            font-size: 14px;
        }

        /* DELETE ITEM BUTTON */
        .delete-item-btn {
            background: #dc2626;
            color: #fff;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .delete-item-btn:hover {
            background: #b91c1c;
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.4);
        }

        .empty-cart-msg {
            text-align: center;
            color: #9ca3af;
            padding: 20px;
            font-size: 13px;
            font-style: italic;
        }

        .cart-total-section {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 16px;
        }

        /* ===== ACTION BUTTONS ===== */
        .action-buttons-section {
            background: #fff;
            padding: 12px 15px;
            display: flex;
            gap: 12px;
        }

        .action-btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-kot {
            background: #ed8936;
            color: #fff;
        }

        .btn-kot:hover {
            background: #dd6b20;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(237, 137, 54, 0.4);
        }

        .btn-bill {
            background: #38a169;
            color: #fff;
        }

        .btn-bill:hover {
            background: #2f855a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(56, 161, 105, 0.4);
        }

        /* Custom Scrollbar */
        .cart-preview-section::-webkit-scrollbar {
            width: 6px;
        }

        .cart-preview-section::-webkit-scrollbar-track {
            background: #e5e7eb;
        }

        .cart-preview-section::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .cart-preview-section::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body>
    <?php include('nav_header_admin.php')?>

    <div class="container-fluid" id="menu-page">
        <h2 class="mb-3"><i class="bi bi-cart-plus"></i> Walk-in Counter Orders</h2>

        <div class="category-tabs">
            <div class="d-flex flex-wrap justify-content-center">
                <div class="category-tab active" data-category="all">All Items</div>
                <div class="category-tab" data-category="breakfast">Breakfast / South Indian</div>
                <div class="category-tab" data-category="chinese">Chinese</div>
                <div class="category-tab" data-category="beverages">Hot Beverages</div>
                <div class="category-tab" data-category="meals">Meals</div>
                <div class="category-tab" data-category="snacks">Snacks</div>
                <div class="category-tab" data-category="specials">🔥 Specials</div>
            </div>
        </div>

        <div class="category-tabs" style="padding: 8px; margin-bottom: 20px;">
            <div class="d-flex flex-wrap justify-content-center">
                <div class="portion-tab active category-tab" data-portion="all"
                    style="font-size: 13px; padding: 5px 15px;">Any Portion</div>
                <div class="portion-tab category-tab" data-portion="single" style="font-size: 13px; padding: 5px 15px;">
                    Single / Half</div>
                <div class="portion-tab category-tab" data-portion="full" style="font-size: 13px; padding: 5px 15px;">
                    Full</div>
            </div>
        </div>

        <div class="row g-3" id="foodContainer">
            <?php
            // CUSTOM SORT TUNING: Precise query sorting constraints mapping out the exact request values sequence
            $custom_order_clause = "ORDER BY 
                CASE 
                    WHEN LOWER(f.f_name) = 'veg meals' THEN 1
                    WHEN LOWER(f.f_name) = 'non-veg meal with chicken' THEN 2
                    WHEN LOWER(f.f_name) = 'chicken biryani' THEN 3
                    WHEN LOWER(f.f_name) = 'chicken curry' THEN 4
                    WHEN LOWER(f.f_name) = 'egg curry' THEN 5
                    WHEN LOWER(f.f_name) = 'chicken fry mandi' THEN 6
                    WHEN LOWER(f.f_name) = 'meal with egg curry' THEN 7
                    WHEN LOWER(f.f_name) = 'idly' THEN 8
                    WHEN LOWER(f.f_name) = 'poori' THEN 9
                    WHEN LOWER(f.f_name) = 'bonda' THEN 10
                    WHEN LOWER(f.f_name) = 'wada' THEN 11
                    WHEN LOWER(f.f_name) = 'any tiffin half' THEN 12
                    WHEN LOWER(f.f_name) = 'tea' THEN 13
                    WHEN LOWER(f.f_name) = 'coffee' THEN 14
                    WHEN LOWER(f.f_name) = 'tea f' THEN 15
                    WHEN LOWER(f.f_name) = 'tea faculty' THEN 16
                    ELSE 999 
                END, f.f_name ASC";

            if (isset($_SESSION['utype']) && $_SESSION['utype'] === 'SHOP_ADMIN') {
                $q = "SELECT f.*,s.s_name FROM food f JOIN shop s ON f.s_id=s.s_id WHERE f.s_id = " . intval($_SESSION['shop_id']) . " " . $custom_order_clause;
            } else {
                $q = "SELECT f.*,s.s_name FROM food f JOIN shop s ON f.s_id=s.s_id " . $custom_order_clause;
            }
            $r = $mysqli->query($q);

            while ($f = $r->fetch_assoc()):
                $name = strtolower($f['f_name']);

                // 1) Determine the Category
                $category = "other";

                // Daily Specials shop items
                if ($f['s_id'] == 10) {
                    $category = "specials";
                }
                // Breakfast / South Indian
                elseif (strpos($name, 'dosa') !== false || strpos($name, 'idly') !== false || strpos($name, 'idli') !==
                false || strpos($name, 'vada') !== false || strpos($name, 'wada') !== false || strpos($name, 'poori') !==
                false || strpos($name, 'uthappam') !== false || strpos($name, 'uttapam') !== false || strpos($name, 'bonda')
                !== false || strpos($name, 'tiffin') !== false || strpos($name, 'bath') !== false || strpos($name, 'lemon
                    rice') !== false) {
                    $category = "breakfast";
                }
                // Chinese
                elseif (strpos($name, 'noodle') !== false || strpos($name, 'manchurian') !== false || strpos($name,
                'manchuria') !== false || strpos($name, 'fried') !== false || strpos($name, 'chicken rice') !== false ||
                strpos($name, 'egg rice') !== false || strpos($name, 'veg rice') !== false || strpos($name, 'chicken 65')
                !== false) {
                    $category = "chinese";
                }
                // Snacks (New Category)
                elseif (strpos($name, 'maggi') !== false || strpos($name, 'fries') !== false || strpos($name, 'frankie') !==
                false || strpos($name, 'pani puri') !== false || strpos($name, 'jamun') !== false || strpos($name, 'omlet')
                !== false || strpos($name, 'omelette') !== false) {
                    $category = "snacks";
                }
                // Hot Beverages
                elseif (strpos($name, 'tea') !== false || strpos($name, 'coffee') !== false || strpos($name, 'milk') !==
                false || strpos($name, 'boost') !== false || strpos($name, 'horlicks') !== false) {
                    $category = "beverages";
                }
                // Meals
                elseif (strpos($name, 'rice') !== false || strpos($name, 'meal') !== false || strpos($name, 'biryani') !==
                false || strpos($name, 'mandi') !== false || (strpos($name, 'curry') !== false && strpos($name, 'poori') ===
                false)) {
                    $category = "meals";
                }

                // 2) Determine the Portion Size
                $portion = "all"; // Default (e.g., Tea, Tiffin, Meals)
                if (strpos($name, 'single') !== false || strpos($name, 'half') !== false || strpos($name, 'piece') !==
                false) {
                    $portion = "single";
                }
                elseif (strpos($name, 'full') !== false) {
                    $portion = "full";
                }

                $img = $f['f_pic'] ? $image_base_path . $f['f_pic'] : '';
            ?>
                <div class="col-6 col-md-4 col-lg-3 food-item" data-category="<?= $category?>"
                    data-portion="<?= $portion?>">

                    <div class="food-item-card" onclick="addToCart(
            <?= $f['f_id']?>,
            '<?= addslashes($f['f_name'])?>',
            <?= $f['f_price']?>,
            '<?= addslashes($f['s_name'])?>',
            '<?= addslashes($img)?>'
            )">
                        <?php if ($img): ?>
                        <img src="<?= $img?>" class="food-image">
                        <?php else: ?>
                        <div class="no-image"><i class="bi bi-image" style="font-size:32px;"></i></div>
                        <?php endif; ?>

                        <div class="food-name">
                            <?= htmlspecialchars($f['f_name'])?>
                        </div>
                        <div class="food-price">₹
                            <?= number_format($f['f_price'], 2)?>
                        </div>

                        <div class="card-actions">
                            <button class="add-cart-btn" onclick="event.stopPropagation();addToCart(
            <?= $f['f_id']?>,
            '<?= addslashes($f['f_name'])?>',
            <?= $f['f_price']?>,
            '<?= addslashes($f['s_name'])?>',
            '<?= addslashes($img)?>'
            )">+ Add</button>

                            <div class="qty-controls">
                                <button class="qty-btn" onclick="event.stopPropagation();decreaseQty(
            <?= $f['f_id']?>,
            '<?= addslashes($f['f_name'])?>',
            <?= $f['f_price']?>,
            '<?= addslashes($f['s_name'])?>',
            '<?= addslashes($img)?>'
            )">−</button>
                                <span class="qty-count" id="qty_<?= $f['f_id']?>">0</span>
                                <button class="qty-btn" onclick="event.stopPropagation();increaseQty(
            <?= $f['f_id']?>,
            '<?= addslashes($f['f_name'])?>',
            <?= $f['f_price']?>,
            '<?= addslashes($f['s_name'])?>',
            '<?= addslashes($img)?>'
            )">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="bottom-fixed-section">
        <div class="cart-preview-section" id="cartPreviewSection">
            <div class="cart-preview-header">
                <div class="cart-preview-title">
                    <i class="bi bi-cart-check-fill"></i> Selected Items
                </div>
                <div class="cart-item-count-badge" id="itemCountBadge">0 items</div>
            </div>

            <div class="preview-items-list" id="previewItemsList">
                <div class="empty-cart-msg">
                    No items selected yet. Add items to see them here.
                </div>
            </div>
        </div>

        <div class="cart-total-section">
            <span>Total Amount</span>
            <span id="cartTotalAmount">₹0.00</span>
        </div>

        <div class="action-buttons-section">
            <button class="action-btn btn-kot" onclick="saveAndHold()">
                🧾 Save & Print KOT
            </button>
            <button class="action-btn btn-bill" onclick="saveAndBill()">
                🖨 Save & Print Bill
            </button>
        </div>
    </div>

    <script>
        /* ===== FILTER STATE ===== */
        let currentCategory = 'all';
        let currentPortion = 'all';

        function applyFilters() {
            document.querySelectorAll('.food-item').forEach(item => {
                const itemCat = item.dataset.category;
                const itemPort = item.dataset.portion;

                const catMatch = (currentCategory === 'all' || itemCat === currentCategory);

                let portMatch = true;
                if (currentPortion !== 'all') {
                    portMatch = (itemPort === currentPortion || itemPort === 'all');
                }

                item.style.display = (catMatch && portMatch) ? "block" : "none";
            });
        }

        /* ===== CATEGORY FILTER LISTENERS ===== */
        document.querySelectorAll('.category-tabs .category-tab:not(.portion-tab)').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.category-tabs .category-tab:not(.portion-tab)').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentCategory = tab.dataset.category;
                applyFilters();
            });
        });

        /* ===== PORTION FILTER LISTENERS ===== */
        document.querySelectorAll('.portion-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.portion-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentPortion = tab.dataset.portion;
                applyFilters();
            });
        });

        /* ===== CART LOGIC ===== */
        let cart = [];
        let isSaving = false;

        function updateCartPreview() {
            const itemsList = document.getElementById('previewItemsList');
            const itemCount = document.getElementById('itemCountBadge');
            const totalAmount = document.getElementById('cartTotalAmount');

            if (cart.length === 0) {
                itemsList.innerHTML = '<div class="empty-cart-msg">No items selected yet. Add items to see them here.</div>';
                itemCount.innerText = '0 items';
                totalAmount.innerText = '₹0.00';
                return;
            }

            itemCount.innerText = `${cart.length} item${cart.length > 1 ? 's' : ''}`;

            let html = '';
            let total = 0;

            cart.forEach(item => {
                const itemTotal = item.price * item.qty;
                total += itemTotal;

                html += `
        <div class="preview-item">
            <div class="preview-item-details">
                <div class="preview-item-name">${item.name}</div>
                <div class="preview-item-shop">${item.shop}</div>
            </div>
            <div class="preview-item-right">
                <div class="preview-qty">Qty: ${item.qty}</div>
                <div class="preview-amount">₹${itemTotal.toFixed(2)}</div>
            </div>
            <button class="delete-item-btn" onclick="removeFromCart(${item.id})" title="Remove item">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        `;
            });

            itemsList.innerHTML = html;
            totalAmount.innerText = `₹${total.toFixed(2)}`;
        }

        /* ===== REMOVE ITEM FROM CART ===== */
        function removeFromCart(id) {
            const idx = cart.findIndex(i => i.id === id);
            if (idx !== -1) {
                cart.splice(idx, 1);
                document.getElementById('qty_' + id).innerText = 0;
                updateCartPreview();
            }
        }

        function addToCart(id, name, price, shop, img) {
            let item = cart.find(i => i.id === id);
            if (item) {
                item.qty++;
            } else {
                cart.push({ id, name, price, shop, img, qty: 1 });
            }
            document.getElementById('qty_' + id).innerText = item ? item.qty : 1;
            updateCartPreview();
        }

        function increaseQty(id, name, price, shop, img) {
            let item = cart.find(i => i.id === id);
            if (item) {
                item.qty++;
            } else {
                cart.push({ id, name, price, shop, img, qty: 1 });
            }
            document.getElementById('qty_' + id).innerText = item ? item.qty : 1;
            updateCartPreview();
        }

        function decreaseQty(id, name, price, shop, img) {
            const idx = cart.findIndex(i => i.id === id);
            if (idx === -1) {
                return;
            }
            cart[idx].qty--;
            if (cart[idx].qty <= 0) {
                cart.splice(idx, 1);
                document.getElementById('qty_' + id).innerText = 0;
            } else {
                document.getElementById('qty_' + id).innerText = cart[idx].qty;
            }
            updateCartPreview();
        }

        /* ===== Save & Print Bill ===== */
        async function saveAndBill() {
            if (isSaving || cart.length === 0) {
                if (cart.length === 0) alert('⚠️ Cart is empty! Add items first.');
                return;
            }
            isSaving = true;
            const data = {
                cart_items: cart.map(i => ({ id: i.id, name: i.name, price: i.price, quantity: i.qty })),
                total_amount: cart.reduce((s, i) => s + i.price * i.qty, 0),
                customer_name: "Walk-in",
                order_type: "takeaway"
            };

            try {
                const r = await fetch('admin_save_walkin_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const res = await r.json();

                if (res.success) {
                    window.open(`print_bluetooth.php?tid=${res.tid}&type=bill`, '_blank');
                    cart = [];
                    document.querySelectorAll('.qty-count').forEach(e => e.innerText = 0);
                    updateCartPreview();
                } else {
                    alert('❌ Error saving order: ' + (res.message || 'Unknown error'));
                }
            } catch (e) {
                alert('❌ Network error: ' + e.message);
            }

            isSaving = false;
        }

        /* ===== Save & Hold (KOT) ===== */
        async function saveAndHold() {
            if (isSaving || cart.length === 0) {
                if (cart.length === 0) alert('⚠️ Cart is empty! Add items first.');
                return;
            }
            isSaving = true;
            const data = {
                cart_items: cart.map(i => ({ id: i.id, name: i.name, price: i.price, quantity: i.qty })),
                total_amount: cart.reduce((s, i) => s + i.price * i.qty, 0),
                customer_name: "Walk-in",
                order_type: "takeaway"
            };

            try {
                const r = await fetch('admin_save_walkin_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const res = await r.json();

                if (res.success) {
                    window.open(`print_bluetooth.php?tid=${res.tid}&type=kot`, '_blank');
                    cart = [];
                    document.querySelectorAll('.qty-count').forEach(e => e.innerText = 0);
                    updateCartPreview();
                } else {
                    alert('❌ Error saving order: ' + (res.message || 'Unknown error'));
                }
            } catch (e) {
                alert('❌ Network error: ' + e.message);
            }

            isSaving = false;
        }
    </script>
</body>

</html>