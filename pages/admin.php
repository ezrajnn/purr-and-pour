<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

// Access Control: Must be logged in as admin
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);
$user_check = mysqli_query($conn, "SELECT role FROM users WHERE id = $user_id");
$user_row = mysqli_fetch_assoc($user_check);
if (!$user_row || $user_row['role'] !== 'admin') {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Access Denied</h2><p>You must be an administrator to access this page.</p><a href='menu.php'>Back to Menu</a></div>");
}

$notice = "";
$error = "";

// -------------------------------------------------------------
// CRUD Operations
// -------------------------------------------------------------

// 1. DELETE PRODUCT (D in CRUD)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);
    $del_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $del_stmt->bind_param("i", $del_id);
    if ($del_stmt->execute()) {
        $notice = "Product #$del_id deleted successfully.";
    } else {
        $error = "Could not delete product. It may be part of previous orders.";
    }
    $del_stmt->close();
}

// 2. APPROVE / UPDATE ORDER STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['status'] ?? 'Approved');
    $status_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $status_stmt->bind_param("si", $new_status, $order_id);
    if ($status_stmt->execute()) {
        $notice = "Order #$order_id status updated to '{$new_status}'.";
    } else {
        $error = "Failed to update order status.";
    }
    $status_stmt->close();
}

// 3. QUICK ADD / DEDUCT STOCKS (Supports both + and -)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    $prod_id = intval($_POST['product_id']);
    $adjust_type = $_POST['adjust_type'] ?? 'add';
    $qty = abs(intval($_POST['qty']));
    
    if ($prod_id > 0 && $qty > 0) {
        $change = ($adjust_type === 'deduct') ? -$qty : $qty;
        $stock_stmt = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock + ?) WHERE id = ?");
        $stock_stmt->bind_param("ii", $change, $prod_id);
        if ($stock_stmt->execute()) {
            $notice = ($adjust_type === 'deduct') ? "Deducted $qty units from product #$prod_id." : "Added $qty units to product #$prod_id.";
        } else {
            $error = "Failed to adjust stock.";
        }
        $stock_stmt->close();
    }
}

// 4. CREATE PRODUCT (C in CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_product') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'Hot Drinks');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image = trim($_POST['image'] ?? 'drink_1.png');
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || $price < 0 || $stock < 0) {
        $error = "Please provide valid product details.";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, category, price, stock, image, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $name, $category, $price, $stock, $image, $description);
        if ($stmt->execute()) {
            $notice = "New product '{$name}' created successfully!";
        } else {
            $error = "Failed to add new product.";
        }
        $stmt->close();
    }
}

// 4. UPDATE PRODUCT (U in CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_product') {
    $edit_id = intval($_POST['product_id']);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'Hot Drinks');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image = trim($_POST['image'] ?? 'drink_1.png');
    $description = trim($_POST['description'] ?? '');

    if ($edit_id > 0 && !empty($name) && $price >= 0 && $stock >= 0) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, stock = ?, image = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssdissi", $name, $category, $price, $stock, $image, $description, $edit_id);
        if ($stmt->execute()) {
            $notice = "Product #$edit_id updated successfully!";
        } else {
            $error = "Failed to update product details.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in valid product data.";
    }
}

// If editing a product, fetch its details
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_product = $edit_stmt->get_result()->fetch_assoc();
    $edit_stmt->close();
}

// READ PRODUCTS (R in CRUD)
$products_query = mysqli_query($conn, "SELECT * FROM products ORDER BY id ASC");
$all_products = [];
$total_inventory_items = 0;
$total_stock_count = 0;
while ($p = mysqli_fetch_assoc($products_query)) {
    $all_products[] = $p;
    $total_inventory_items++;
    $total_stock_count += intval($p['stock']);
}

// Read Orders summary
$orders_count_res = mysqli_query($conn, "SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_sales FROM orders");
$orders_summary = mysqli_fetch_assoc($orders_count_res);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard & Inventory | Purr & Pour</title>
    <link rel="stylesheet" href="../style/loginHeader.css">
    <link rel="stylesheet" href="../style/admin.css">
</head>
<body>

<?php require_once __DIR__ . '/../navigation/header.php'; ?>

<main class="admin-page">
    <div class="admin-header">
        <div>
            <h1>Café Administrator Dashboard</h1>
            <p class="admin-subtitle">Manage stock levels, add products, and update café menu items</p>
        </div>
        <div class="admin-nav-links">
            <a href="menu.php" class="admin-link-live">← View Menu</a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <span>Total Products</span>
            <h3><?php echo $total_inventory_items; ?></h3>
        </div>
        <div class="stat-card">
            <span>Total In-Stock Units</span>
            <h3><?php echo $total_stock_count; ?></h3>
        </div>
        <div class="stat-card">
            <span>Total Placed Orders</span>
            <h3><?php echo $orders_summary['total_orders'] ?? 0; ?></h3>
        </div>
        <div class="stat-card">
            <span>Gross Recorded Sales</span>
            <h3>$<?php echo number_format($orders_summary['total_sales'] ?? 0, 2); ?></h3>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert-box alert-success"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-box alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="admin-grid">
        <!-- Product Create / Edit Form (C & U in CRUD) -->
        <div class="card">
            <h2><?php echo $edit_product ? "✏ Edit Product #{$edit_product['id']}" : "Add New Product"; ?></h2>
            
            <form action="admin.php" method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_product ? 'update_product' : 'create_product'; ?>">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" placeholder="e.g. Caramel Macchiato">
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category">
                        <?php
                        $all_cats = ['Hot Drinks', 'Cold Drinks', 'Pastries & Desserts', 'Rice Meals', 'Pasta & Mains'];
                        $current_cat = $edit_product['category'] ?? 'Hot Drinks';
                        foreach ($all_cats as $cat):
                        ?>
                            <option value="<?php echo $cat; ?>" <?php if ($current_cat === $cat) echo 'selected'; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($) *</label>
                        <input type="number" step="0.25" id="price" name="price" required value="<?php echo htmlspecialchars($edit_product['price'] ?? '5.00'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock Quantity *</label>
                        <input type="number" id="stock" name="stock" required value="<?php echo htmlspecialchars($edit_product['stock'] ?? '20'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Image Asset File *</label>
                    <select id="image" name="image">
                        <?php
                        $images = [
                            'drink_1.png', 'drink_2.png', 'drink_3.png', 'drink_4.png', 'drink_5.png', 'drink_6.png',
                            'drink_7.png', 'drink_8.png', 'drink_9.png', 'drink_10.png', 'drink_11.png', 'drink_12.png',
                            'pastry_1.png', 'pastry_2.png', 'pastry_3.png', 'pastry_4.png', 'pastry_5.png', 'pastry_6.png',
                            'meal_1.png', 'meal_2.png', 'meal_4.png', 'meal_5.png', 'meal_6.png'
                        ];
                        $curr_img = $edit_product['image'] ?? 'drink_1.png';
                        foreach ($images as $img):
                        ?>
                            <option value="<?php echo $img; ?>" <?php if ($curr_img === $img) echo 'selected'; ?>><?php echo $img; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="2" placeholder="Brief flavor profile..."><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit"><?php echo $edit_product ? "Save Changes" : "Create Product"; ?></button>
                <?php if ($edit_product): ?>
                    <a href="admin.php" class="btn-cancel">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Inventory Table !-->
        <div class="card">
            <h2>Live Inventory & Stock Controller</h2>
            <div style="overflow-x: auto;">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Img</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Current Stock</th>
                            <th>Quick Add Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_products as $prod): ?>
                            <?php
                            $stk = intval($prod['stock']);
                            $badge_class = $stk > 10 ? 'stock-good' : ($stk > 0 ? 'stock-low' : 'stock-out');
                            ?>
                            <tr>
                                <td><img src="../elements/<?php echo htmlspecialchars($prod['image']); ?>" class="prod-thumb" alt=""></td>
                                <td><strong>#<?php echo $prod['id']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($prod['category']); ?></td>
                                <td>$<?php echo number_format($prod['price'], 2); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo $badge_class; ?>">
                                        <?php echo $stk; ?> units
                                    </span>
                                </td>
                                <td>
                                    <form action="admin.php" method="POST" class="quick-stock-form" style="display:flex; align-items:center; gap:3px;">
                                        <input type="hidden" name="action" value="adjust_stock">
                                        <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                        <input type="number" name="qty" value="5" min="1" step="1" style="width: 42px; padding: 4px; font-size: 12px; border: 1px solid #dcd4cb; border-radius: 4px; text-align: center;">
                                        <button type="submit" name="adjust_type" value="add" class="btn-stock-add" style="background:#48bb78;" title="Add to stock">+Add</button>
                                        <button type="submit" name="adjust_type" value="deduct" class="btn-stock-add" style="background:#e53e3e;" title="Deduct from stock">Deduct</button>
                                    </form>
                                </td>
                                <td class="action-links">
                                    <a href="admin.php?edit=<?php echo $prod['id']; ?>" class="action-edit">Edit</a>
                                    <a href="admin.php?action=delete&id=<?php echo $prod['id']; ?>" class="action-delete" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars(addslashes($prod['name'])); ?>?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Customer Orders Approval Section -->
    <div class="card" id="orders-section" style="margin-top: 30px;">
        <h2>Customer Orders & Approval Management</h2>
        <?php
        $orders_query = mysqli_query($conn, "SELECT o.*, GROUP_CONCAT(CONCAT(oi.product_name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items_summary FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id GROUP BY o.id ORDER BY o.id DESC");
        $all_orders = [];
        if ($orders_query) {
            while ($ord = mysqli_fetch_assoc($orders_query)) {
                $all_orders[] = $ord;
            }
        }
        ?>

        <?php if (empty($all_orders)): ?>
            <p style="color: #8c7b6d; padding: 15px 0;">No customer orders have been placed yet.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items Ordered</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action / Approval</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_orders as $ord): ?>
                            <?php
                            $st = $ord['status'];
                            $badge_color = 'background:#fef08a; color:#854d0e; border: 1px solid #fde047;';
                            $status_label = 'Pending Approval';
                            if ($st === 'Approved') {
                                $badge_color = 'background:#def7ec; color:#03543f; border: 1px solid #84e1bc;';
                                $status_label = 'Approved';
                            } elseif ($st === 'Cancelled') {
                                $badge_color = 'background:#fee2e2; color:#991b1b; border: 1px solid #fca5a5;';
                                $status_label = 'Cancelled / Rejected';
                            } elseif ($st === 'Completed') {
                                $badge_color = 'background:#e2e8f0; color:#334155; border: 1px solid #cbd5e1;';
                                $status_label = 'Completed';
                            }
                            ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong><br>
                                    <small style="color: #8c7b6d;"><?php echo htmlspecialchars($ord['customer_email']); ?></small>
                                </td>
                                <td>
                                    <div style="max-width: 250px; font-size: 12px; color: #555;">
                                        <?php echo htmlspecialchars($ord['items_summary'] ?? 'None'); ?>
                                    </div>
                                </td>
                                <td><strong>$<?php echo number_format($ord['total_amount'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($ord['payment_method']); ?></td>
                                <td><small><?php echo date('M d, h:i A', strtotime($ord['created_at'])); ?></small></td>
                                <td>
                                    <span class="stock-badge" style="<?php echo $badge_color; ?>">
                                        <?php echo htmlspecialchars($status_label); ?>
                                    </span>
                                </td>
                                <td>
                                    <form action="admin.php#orders-section" method="POST" style="display:flex; gap: 5px; align-items: center; flex-wrap: wrap;">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                        <?php if ($st !== 'Approved' && $st !== 'Completed'): ?>
                                            <button type="submit" name="status" value="Approved" class="btn-stock-add" style="background:#38a169; padding: 6px 10px;" title="Approve this order">Approve</button>
                                        <?php endif; ?>
                                        <?php if ($st !== 'Cancelled'): ?>
                                            <button type="submit" name="status" value="Cancelled" class="btn-stock-add" style="background:#e53e3e; padding: 6px 10px;" onclick="return confirm('Reject / Cancel Order #<?php echo $ord['id']; ?>?');" title="Reject or cancel order">Reject</button>
                                        <?php endif; ?>
                                        <?php if ($st === 'Approved'): ?>
                                            <button type="submit" name="status" value="Completed" class="btn-stock-add" style="background:#4a5568; padding: 6px 10px;" title="Mark as completed/picked up">Done</button>
                                        <?php endif; ?>
                                        <?php if ($st === 'Cancelled'): ?>
                                            <button type="submit" name="status" value="Pending" class="btn-stock-add" style="background:#d97706; padding: 6px 10px;" title="Reopen as pending">Reset</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>

