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
$entered_address = "";
$entered_ref = "";
$selected_payment = "Cash on Pickup";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash on Pickup');
    $reference_number = trim($_POST['reference_number'] ?? '');

    $entered_address = $address;
    $entered_ref = $reference_number;
    $selected_payment = $payment_method;

    if (empty($customer_name)) {
        $error_msg = "Please provide your name for the order.";
    } elseif (empty($address)) {
        $error_msg = "Please provide your delivery/customer address.";
    } elseif ($payment_method === 'GCash' && empty($reference_number)) {
        $error_msg = "Please enter your GCash reference number to verify payment.";
    } elseif ($payment_method === 'GCash' && !preg_match('/^\d{12}$/', $reference_number)) {
        $error_msg = "The GCash reference number must be exactly 12 numeric digits (numbers only).";
    } else {
        // If not GCash, clear reference number
        if ($payment_method !== 'GCash') {
            $reference_number = null;
        }

        $order_stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_email, address, payment_method, reference_number, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $order_stmt->bind_param("isssssd", $user_id, $customer_name, $customer_email, $address, $payment_method, $reference_number, $cart_subtotal);
        
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
                        <h2 class="section-title">Customer Details & Delivery Address</h2>
                        
                        <div class="form-group">
                            <label for="customer_name">Full Name *</label>
                            <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($default_name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="customer_email">Email Address</label>
                            <input type="email" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($default_email); ?>" placeholder="your@email.com">
                        </div>

                        <div class="form-group">
                            <label for="address">Complete Address *</label>
                            <textarea id="address" name="address" rows="3" required placeholder=><?php echo htmlspecialchars($entered_address); ?></textarea>
                            <small style="color: #8c7b6d; font-size: 12px;">Please enter your full address where you wish to receive your café package.</small>
                        </div>
                    </div>

                    <div class="form-card">
                        <h2 class="section-title">Payment Method</h2>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="Cash on Pickup" <?php if ($selected_payment === 'Cash on Pickup') echo 'checked'; ?> onchange="toggleGcashField()">
                                <span class="payment-label">
                                    <strong>Cash on Pickup / Delivery</strong>
                                    <small>Pay with cash upon claiming or delivery</small>
                                </span>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="GCash" id="pm_gcash" <?php if ($selected_payment === 'GCash') echo 'checked'; ?> onchange="toggleGcashField()">
                                <span class="payment-label">
                                    <strong>GCash (Mobile Wallet)</strong>
                                    <small>Send payment via GCash and enter your transaction reference number</small>
                                </span>
                            </label>
                        </div>

                        <!-- GCash Reference Number Field (shown when GCash is selected) -->
                        <div id="gcash-reference-group" class="form-group" style="margin-top: 18px; padding: 14px; background: #faf7f2; border: 1.5px dashed #c9baa9; border-radius: 10px; display: <?php echo ($selected_payment === 'GCash') ? 'block' : 'none'; ?>;">
                            <label for="reference_number" style="display: flex; align-items: center; justify-content: space-between;">
                                <span>GCash Reference Number *</span>
                            </label>
                            <input type="text" id="reference_number" name="reference_number" value="<?php echo htmlspecialchars($entered_ref); ?>" placeholder="12-digit reference number (e.g. 100298451234)" maxlength="12" pattern="\d{12}" inputmode="numeric" autocomplete="off" style="background: #ffffff;">
                            <small style="color: #6e5c4e; font-size: 12px; margin-top: 6px; display: block;">
                                Café GCash: <strong>0967 6767 6767</strong> (Purr & Pour Café). Must be exactly 12 numeric digits from your GCash receipt.
                            </small>
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

<script>
function toggleGcashField() {
    const gcashRadio = document.getElementById('pm_gcash');
    const gcashGroup = document.getElementById('gcash-reference-group');
    const refInput = document.getElementById('reference_number');

    if (gcashRadio && gcashRadio.checked) {
        gcashGroup.style.display = 'block';
        refInput.setAttribute('required', 'required');
        refInput.focus();
    } else {
        gcashGroup.style.display = 'none';
        refInput.removeAttribute('required');
    }
}

// Run on page load in case GCash was previously selected
document.addEventListener('DOMContentLoaded', function() {
    toggleGcashField();

    const refInput = document.getElementById('reference_number');
    if (refInput) {
        // Prevent typing non-numeric characters
        refInput.addEventListener('keypress', function(e) {
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Strip non-digits and cap at 12 on paste/input
        refInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 12);
        });
    }
});
</script>

</body>
</html>
