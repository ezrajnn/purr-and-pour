<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../database/db.php';
require '../functions/products.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    http_response_code(500);
    exit('Database connection error.');
}

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
    $prod = isset($menu_products[$pid]) ? $menu_products[$pid] : [
        "name" => "Item #$pid",
        "price" => 0.00,
        "image" => "drink_1.png"
    ];
    
    $row['name'] = $prod['name'];
    $row['price'] = $prod['price'];
    $row['image'] = $prod['image'];
    $row['item_total'] = $prod['price'] * $qty;
    $cart_items[] = $row;
    $total_items += $qty;
    $cart_subtotal += $row['item_total'];
}
$stmt->close();

// If cart is empty, redirect back to cart
if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// Fetch user profile info
$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$default_name = $user_data['name'] ?? ($_SESSION['name'] ?? '');
$default_email = $user_data['email'] ?? '';

// Handle Place Order
$error_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash on Pickup');

    if (empty($customer_name)) {
        $error_msg = "Please provide your name for the order.";
    } else {
        $order_stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_email, payment_method, total_amount, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $order_stmt->bind_param("isssd", $user_id, $customer_name, $customer_email, $payment_method, $cart_subtotal);
        
        if ($order_stmt->execute()) {
            $order_id = $conn->insert_id;
            $order_stmt->close();

            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stock_stmt = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
            foreach ($cart_items as $item) {
                $item_stmt->bind_param("iisdid", $order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $item['item_total']);
                $item_stmt->execute();

                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stock_stmt->execute();
            }
            $item_stmt->close();
            $stock_stmt->close();

            // Empty user's cart
            $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();

            header("Location: orderConfirmation.php?order_id=" . $order_id);
            exit();
        } else {
            $error_msg = "Failed to process your order. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Purr & Pour Café</title>
    <link rel="stylesheet" href="../style/loginHeader.css">
    <link rel="stylesheet" href="../style/checkout.css">
</head>
<body>

<?php require '../navigation/header.php'; ?>

<main class="checkout-page">
    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Checkout</h1>
            <a href="cart.php">← Back to Cart</a>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="checkout-error">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <!-- Left Column: Customer Form -->
            <div class="checkout-form-section">
                <form action="checkout.php" method="POST" id="checkout-form">
                    <div class="form-card">
                        <h2 class="section-title">Customer Details</h2>
                        
                        <div class="form-group">
                            <label for="customer_name">Full Name</label>
                            <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($default_name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="customer_email">Email Address</label>
                            <input type="email" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($default_email); ?>" placeholder="your@email.com">
                        </div>
                    </div>

                    <div class="form-card">
                        <h2 class="section-title">Payment Method</h2>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="Cash on Pickup" checked>
                                <span class="payment-label">
                                    <strong>Cash on Pickup</strong>
                                    <small>Pay at the counter when you claim your order</small>
                                </span>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="GCash">
                                <span class="payment-label">
                                    <strong>GCash</strong>
                                    <small>Pay via GCash QR at the café register</small>
                                </span>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="Credit / Debit Card">
                                <span class="payment-label">
                                    <strong>Credit / Debit Card</strong>
                                    <small>Swipe or tap at our store POS</small>
                                </span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-place-order">Place Order • $<?php echo number_format($cart_subtotal, 2); ?></button>
                </form>
            </div>

            <!-- Right Column: Order Summary -->
            <div class="checkout-summary-section">
                <div class="summary-card">
                    <h2 class="section-title">Order Summary (<?php echo $total_items; ?> items)</h2>
                    
                    <div class="summary-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item">
                                <div class="item-img-wrapper">
                                    <img src="../elements/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <span class="item-qty-price">Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></span>
                                </div>
                                <div class="item-total">
                                    $<?php echo number_format($item['item_total'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($cart_subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Pickup Fee</span>
                            <span>$0.00</span>
                        </div>
                        <div class="summary-row total-row">
                            <span>Total</span>
                            <span>$<?php echo number_format($cart_subtotal, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
