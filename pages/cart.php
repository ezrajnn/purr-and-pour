<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../database/db.php';

require '../functions/products.php';

// If user is not logged in, redirect to login.php
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Admins do not place orders; redirect to admin panel
if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
    header("Location: admin.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);
$menu_products = get_menu_products();

// Fetch only the cart items belonging to the currently logged-in user
$sql = "SELECT id, product_id, quantity FROM cart WHERE user_id = ? ORDER BY id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total_items = 0;
$cart_subtotal = 0.00;

while ($row = $result->fetch_assoc()) {
    $pid = intval($row["product_id"]);
    $qty = intval($row["quantity"]);
    $price = isset($menu_products[$pid]) ? $menu_products[$pid]['price'] : 0.00;
    
    $row['price'] = $price;
    $row['item_total'] = $price * $qty;
    $cart_items[] = $row;
    $total_items += $qty;
    $cart_subtotal += $row['item_total'];
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | Purr & Pour Café</title>
    <link rel="stylesheet" href="../style/loginHeader.css">
    <link rel="stylesheet" href="../style/cart.css">
</head>
<body>

<?php require '../navigation/header.php'; ?>

<main class="cart-page">
    <div class="cart-container">
        <div class="cart-header">
            <h1>Shopping Cart</h1>
            <a href="menu.php">← Back to Menu</a>
        </div>

        <?php if (empty($cart_items)): ?>
            <!-- Empty cart message when there are no items -->
            <div class="empty-cart">
                <span class="empty-cart-icon">🛒</span>
                <h2>Your cart is currently empty</h2>
                <p>Looks like you haven't added any delicious treats or drinks yet.</p>
                <a href="menu.php" class="btn-primary">Explore Our Menu</a>
            </div>
        <?php else: ?>
            <!-- Cart table displaying products, price, and quantity -->
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <?php
                            $pid = intval($item['product_id']);
                            $displayName = isset($menu_products[$pid]) ? $menu_products[$pid]['name'] : "Item #{$pid}";
                        ?>
                        <tr>
                            <td>
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($displayName); ?></h4>
                                    <span class="product-id-badge">ID: <?php echo htmlspecialchars($item['product_id']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></span>
                            </td>
                            <td>
                                <div class="qty-controls">
                                    <!-- Decrease quantity button -->
                                    <form action="../database/decreaseQuantity.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="cart_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product_id']); ?>">
                                        <button type="submit" class="qty-btn" title="Decrease">−</button>
                                    </form>

                                    <!-- Current quantity display -->
                                    <span class="qty-val"><?php echo htmlspecialchars($item['quantity']); ?></span>

                                    <!-- Increase quantity button -->
                                    <?php
                                    $item_stock = isset($menu_products[$pid]) ? intval($menu_products[$pid]['stock']) : 0;
                                    $is_max = ($item['quantity'] >= $item_stock);
                                    ?>
                                    <?php if (!$is_max): ?>
                                        <form action="../database/increaseQuantity.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="cart_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product_id']); ?>">
                                            <button type="submit" class="qty-btn" title="Increase">+</button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="qty-btn" title="Max stock reached" disabled style="opacity: 0.4; cursor: not-allowed;">+</button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($is_max): ?>
                                    <div style="font-size: 11px; color: #c94a4a; font-weight: 700; margin-top: 4px;">Max stock reached</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="cart-item-total"><strong>$<?php echo number_format($item['item_total'], 2); ?></strong></span>
                            </td>
                            <td>
                                <!-- Remove item button -->
                                <form action="../database/removeItem.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="cart_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product_id']); ?>">
                                    <button type="submit" class="remove-btn">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-footer">
                <div class="cart-summary">
                    <div>Total items: <strong><?php echo $total_items; ?></strong></div>
                    <div class="cart-total-amount">Order Total: <strong>$<?php echo number_format($cart_subtotal, 2); ?></strong></div>
                </div>
                <div class="cart-actions">
                    <a href="menu.php" class="btn-secondary">Add More Items</a>
                    <a href="checkout.php" class="btn-primary">Proceed to Checkout →</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
