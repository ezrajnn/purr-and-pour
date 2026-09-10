<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

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

// Delete User Account
if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && isset($_GET['user_id'])) {
    $target_user_id = intval($_GET['user_id']);
    if ($target_user_id === $user_id) {
        $error = "You cannot delete your own logged-in admin account.";
    } else {
        $del_user_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del_user_stmt->bind_param("i", $target_user_id);
        if ($del_user_stmt->execute()) {
            $notice = "Account #$target_user_id deleted successfully.";
        } else {
            $error = "Failed to delete account. Error: " . $conn->error;
        }
        $del_user_stmt->close();
    }
}

// Update User Account (Edit Name, Email, Role, and Optional Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $edit_uid = intval($_POST['user_id'] ?? 0);
    $edit_name = trim($_POST['name'] ?? '');
    $edit_email = trim($_POST['email'] ?? '');
    $edit_role = trim($_POST['role'] ?? 'customer');
    $new_password = $_POST['new_password'] ?? '';

    // Validate role
    if (!in_array($edit_role, ['admin', 'customer'])) {
        $edit_role = 'customer';
    }

    if ($edit_uid <= 0 || empty($edit_name) || empty($edit_email)) {
        $error = "Please provide valid user name and email.";
    } else {
        // Check if email belongs to another user
        $email_chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $email_chk->bind_param("si", $edit_email, $edit_uid);
        $email_chk->execute();
        $email_chk_res = $email_chk->get_result();

        if ($email_chk_res->num_rows > 0) {
            $error = "The email '{$edit_email}' is already used by another account.";
        } else {
            if (!empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_u_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $update_u_stmt->bind_param("ssssi", $edit_name, $edit_email, $edit_role, $hash, $edit_uid);
            } else {
                $update_u_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $update_u_stmt->bind_param("sssi", $edit_name, $edit_email, $edit_role, $edit_uid);
            }

            if ($update_u_stmt->execute()) {
                $notice = "User account #$edit_uid updated successfully!";
                if ($edit_uid === $user_id) {
                    $_SESSION['name'] = $edit_name;
                    $_SESSION['email'] = $edit_email;
                    $_SESSION['role'] = $edit_role;
                }
            } else {
                $error = "Failed to update user account: " . $conn->error;
            }
            $update_u_stmt->close();
        }
        $email_chk->close();
    }
}

// Quick Role Change from Table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_change_role') {
    $target_uid = intval($_POST['user_id'] ?? 0);
    $new_role = trim($_POST['new_role'] ?? 'customer');

    if (!in_array($new_role, ['admin', 'customer'])) {
        $new_role = 'customer';
    }

    if ($target_uid <= 0) {
        $error = "Invalid user specified.";
    } elseif ($target_uid === $user_id && $new_role !== 'admin') {
        $error = "You cannot demote your own account from admin.";
    } else {
        $role_stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $role_stmt->bind_param("si", $new_role, $target_uid);
        if ($role_stmt->execute()) {
            $notice = "Account #$target_uid role changed to '{$new_role}' successfully!";
        } else {
            $error = "Failed to update role: " . $conn->error;
        }
        $role_stmt->close();
    }
}

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

$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_product = $edit_stmt->get_result()->fetch_assoc();
    $edit_stmt->close();
}

$products_query = mysqli_query($conn, "SELECT * FROM products ORDER BY id ASC");
$all_products = [];
$total_inventory_items = 0;
$total_stock_count = 0;
$total_out_of_stock = 0;
while ($p = mysqli_fetch_assoc($products_query)) {
    $all_products[] = $p;
    $total_inventory_items++;
    $p_stk = intval($p['stock']);
    $total_stock_count += $p_stk;
    if ($p_stk <= 0) {
        $total_out_of_stock++;
    }
}

// Read Orders summary
$orders_count_res = mysqli_query($conn, "SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_sales FROM orders");
$orders_summary = mysqli_fetch_assoc($orders_count_res);

// Edit User check
$edit_user = null;
if (isset($_GET['edit_user'])) {
    $euid = intval($_GET['edit_user']);
    $eu_stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $eu_stmt->bind_param("i", $euid);
    $eu_stmt->execute();
    $edit_user = $eu_stmt->get_result()->fetch_assoc();
    $eu_stmt->close();
}

// Fetch all registered accounts
$users_query = mysqli_query($conn, "SELECT u.id, u.name, u.email, u.role, COUNT(DISTINCT o.id) AS order_count FROM users u LEFT JOIN orders o ON u.id = o.user_id GROUP BY u.id ORDER BY u.id ASC");
$all_users = [];
$total_users_count = 0;
$admin_count = 0;
$customer_count = 0;
if ($users_query) {
    while ($urow = mysqli_fetch_assoc($users_query)) {
        $all_users[] = $urow;
        $total_users_count++;
        if ($urow['role'] === 'admin') {
            $admin_count++;
        } else {
            $customer_count++;
        }
    }
}
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
            <a href="#accounts-section" class="admin-link-live" style="background:#e4d7c7; padding:6px 12px; border-radius:6px;">Registered Accounts</a>
            <a href="#orders-section" class="admin-link-live" style="background:#e4d7c7; padding:6px 12px; border-radius:6px;">Orders</a>
            <a href="menu.php" class="admin-link-live">← View Menu</a>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="stat-card">
            <span>Total Products</span>
            <h3><?php echo $total_inventory_items; ?></h3>
        </div>
        <div class="stat-card">
            <span>Total In-Stock Units</span>
            <h3><?php echo $total_stock_count; ?></h3>
        </div>
        <div class="stat-card">
            <span style="<?php echo $total_out_of_stock > 0 ? 'color:#c53030; font-weight:bold;' : ''; ?>">Out of Stock Items</span>
            <h3 style="<?php echo $total_out_of_stock > 0 ? 'color:#c53030;' : ''; ?>"><?php echo $total_out_of_stock; ?></h3>
        </div>
        <div class="stat-card">
            <span>Total Placed Orders</span>
            <h3><?php echo $orders_summary['total_orders'] ?? 0; ?></h3>
        </div>
        <div class="stat-card">
            <span>Gross Recorded Sales</span>
            <h3>$<?php echo number_format($orders_summary['total_sales'] ?? 0, 2); ?></h3>
        </div>
        <div class="stat-card">
            <span>Registered Accounts</span>
            <h3><?php echo $total_users_count; ?> <small style="font-size:12px; font-weight:normal; color:#8c7b6d;">(<?php echo $admin_count; ?> Admin, <?php echo $customer_count; ?> Customer)</small></h3>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert-box alert-success"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-box alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="admin-grid">

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
                            $is_out = ($stk <= 0);
                            ?>
                            <tr style="<?php echo $is_out ? 'background-color: #fff5f5;' : ''; ?>">
                                <td><img src="../elements/<?php echo htmlspecialchars($prod['image']); ?>" class="prod-thumb" alt=""></td>
                                <td><strong>#<?php echo $prod['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prod['name']); ?></strong>
                                    <?php if ($is_out): ?>
                                        <span style="display:inline-block; margin-left: 6px; font-size: 10px; font-weight: 800; background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; border: 1px solid #fca5a5;">OUT OF STOCK</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($prod['category']); ?></td>
                                <td>$<?php echo number_format($prod['price'], 2); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo $badge_class; ?>">
                                        <?php echo $is_out ? '0 units (Out of Stock)' : ($stk . ' units'); ?>
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
                                    <?php if (!empty($ord['address'])): ?>
                                        <div style="font-size: 11px; color: #6b553e; background: #faf4ed; padding: 4px 6px; border-radius: 4px; margin-top: 4px; max-width: 220px; line-height: 1.3; border: 1px solid #eee1d3;">
                                             <?php echo nl2br(htmlspecialchars($ord['address'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="max-width: 250px; font-size: 12px; color: #555;">
                                        <?php echo htmlspecialchars($ord['items_summary'] ?? 'None'); ?>
                                    </div>
                                </td>
                                <td><strong>$<?php echo number_format($ord['total_amount'], 2); ?></strong></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($ord['payment_method']); ?></div>
                                    <?php if (!empty($ord['reference_number'])): ?>
                                        <div style="font-size: 11px; color: #0369a1; background: #e0f2fe; padding: 2px 6px; border-radius: 4px; margin-top: 3px; font-family: monospace; display: inline-block;">
                                            Ref: <?php echo htmlspecialchars($ord['reference_number']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
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

    <!-- Registered Accounts Management Section -->
    <div class="card" id="accounts-section" style="margin-top: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
            <div>
                <h2>Registered Accounts</h2>
                <p style="color: #8c7b6d; font-size: 13px; margin-top: 3px;">View and manage users, change account roles, edit details, or remove accounts.</p>
            </div>
            <?php if ($edit_user): ?>
                <a href="admin.php#accounts-section" style="font-size: 13px; color: #785338; font-weight: bold; text-decoration: underline;">+ Cancel Editing User</a>
            <?php endif; ?>
        </div>

        <?php if ($edit_user): ?>
            <!-- Edit User Form Box -->
            <div style="background: #faf6f0; border: 1px solid #e2d3c2; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <h3 style="font-size: 16px; color: #5b4530; margin-bottom: 12px;">✏ Edit Account: <?php echo htmlspecialchars($edit_user['name']); ?> (ID #<?php echo $edit_user['id']; ?>)</h3>
                <form action="admin.php#accounts-section" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="edit_user_name" style="font-size: 12px; font-weight: bold; color: #6b553e;">Full Name *</label>
                        <input type="text" id="edit_user_name" name="name" required value="<?php echo htmlspecialchars($edit_user['name']); ?>" style="width: 100%; padding: 8px 10px; border: 1px solid #dcd4cb; border-radius: 6px; font-size: 13px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="edit_user_email" style="font-size: 12px; font-weight: bold; color: #6b553e;">Email Address *</label>
                        <input type="email" id="edit_user_email" name="email" required value="<?php echo htmlspecialchars($edit_user['email']); ?>" style="width: 100%; padding: 8px 10px; border: 1px solid #dcd4cb; border-radius: 6px; font-size: 13px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="edit_user_role" style="font-size: 12px; font-weight: bold; color: #6b553e;">Role *</label>
                        <select id="edit_user_role" name="role" style="width: 100%; padding: 8px 10px; border: 1px solid #dcd4cb; border-radius: 6px; font-size: 13px;">
                            <option value="customer" <?php if ($edit_user['role'] === 'customer') echo 'selected'; ?>>Customer</option>
                            <option value="admin" <?php if ($edit_user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="edit_user_pass" style="font-size: 12px; font-weight: bold; color: #6b553e;">New Password <small style="font-weight: normal; color:#999;">(optional)</small></label>
                        <input type="password" id="edit_user_pass" name="new_password" placeholder="Leave blank to keep current" style="width: 100%; padding: 8px 10px; border: 1px solid #dcd4cb; border-radius: 6px; font-size: 13px;">
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn-stock-add" style="background: #634b35; padding: 9px 18px; font-size: 13px; border-radius: 6px;">Save Changes</button>
                        <a href="admin.php#accounts-section" class="btn-stock-add" style="background: #a89f91; padding: 9px 14px; font-size: 13px; text-decoration: none; border-radius: 6px; display: inline-block;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if (empty($all_users)): ?>
            <p style="color: #8c7b6d; padding: 15px 0;">No accounts found.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Total Orders</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $usr): ?>
                            <?php
                            $is_self = ($usr['id'] == $user_id);
                            $is_admin = ($usr['role'] === 'admin');
                            $role_badge = $is_admin
                                ? 'background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe;'
                                : 'background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0;';
                            ?>
                            <tr style="<?php echo $is_self ? 'background-color: #fdfaf6;' : ''; ?>">
                                <td>
                                    <strong>#<?php echo $usr['id']; ?></strong>
                                    <?php if ($is_self): ?>
                                        <span style="font-size:10px; background:#634b35; color:#fff; padding:2px 5px; border-radius:4px; margin-left:4px;">You</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($usr['name']); ?></strong>
                                </td>
                                <td>
                                    <span style="color: #4a5568;"><?php echo htmlspecialchars($usr['email']); ?></span>
                                </td>
                                <td>
                                    <?php if ($is_self): ?>
                                        <span class="stock-badge" style="<?php echo $role_badge; ?> text-transform: uppercase; font-size: 11px;">
                                            <?php echo htmlspecialchars($usr['role']); ?>
                                        </span>
                                    <?php else: ?>
                                        <form action="admin.php#accounts-section" method="POST" style="display:inline-flex; align-items:center; gap: 4px;">
                                            <input type="hidden" name="action" value="quick_change_role">
                                            <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                            <select name="new_role" onchange="this.form.submit()" style="padding: 3px 6px; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid <?php echo $is_admin ? '#c7d2fe' : '#a7f3d0'; ?>; <?php echo $role_badge; ?> cursor: pointer;">
                                                <option value="customer" <?php if ($usr['role'] === 'customer') echo 'selected'; ?>>CUSTOMER</option>
                                                <option value="admin" <?php if ($usr['role'] === 'admin') echo 'selected'; ?>>ADMIN</option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 13px; color: #555;">
                                        <?php echo $usr['order_count']; ?> orders
                                    </span>
                                </td>
                                <td class="action-links">
                                    <a href="admin.php?edit_user=<?php echo $usr['id']; ?>#accounts-section" class="action-edit" title="Edit this user">Edit</a>
                                    <?php if ($is_self): ?>
                                        <span style="font-size: 12px; color: #a0aec0; font-style: italic; margin-right: 8px;">(Current Account)</span>
                                    <?php else: ?>
                                        <a href="admin.php?action=delete_user&user_id=<?php echo $usr['id']; ?>#accounts-section"
                                           class="action-delete"
                                           onclick="return confirm('Are you sure you want to delete account \'<?php echo htmlspecialchars(addslashes($usr['name'])); ?>\' (<?php echo htmlspecialchars(addslashes($usr['email'])); ?>)? This will also delete their cart items and orders.');"
                                           title="Delete this user">Delete</a>
                                    <?php endif; ?>
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

