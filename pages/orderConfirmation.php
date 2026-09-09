<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../database/db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Database connection unavailable.');
}

// Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header("Location: menu.php");
    exit();
}

// Fetch order details ensuring it belongs to current user
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();
$order_stmt->close();

if (!$order) {
    header("Location: menu.php");
    exit();
}

// Fetch order items
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$order_items = [];
while ($row = $items_result->fetch_assoc()) {
    $order_items[] = $row;
}
$items_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | Purr & Pour Café</title>
    <link rel="stylesheet" href="../style/loginHeader.css">
    <link rel="stylesheet" href="../style/checkout.css">
    <link rel="stylesheet" href="../style/orderConfirmation.css">
</head>
<body>

<?php require '../navigation/header.php'; ?>

<main class="checkout-page">
    <div class="checkout-container receipt-container">
        <div class="receipt-card">
            <div class="receipt-header">
                <div class="success-badge">✓</div>
                <h1>Order Confirmed!</h1>
                <p class="receipt-subtitle">Thank you for your order, <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>! We are preparing your café favorites.</p>
                <div class="order-number-pill">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
            </div>

            <?php
            $current_status = $order['status'] ?? 'Pending';
            $status_class = 'status-pending';
            $status_title = '⏳ Pending Admin Approval';
            $status_msg = 'Your order has been sent to the café! The admin will review and approve your order shortly.';

            if ($current_status === 'Approved' || $current_status === 'Confirmed') {
                $status_class = 'status-approved';
                $status_title = '✓ Approved by Admin';
                $status_msg = 'Great news! The café admin has approved your order. It is currently being prepared.';
            } elseif ($current_status === 'Cancelled') {
                $status_class = 'status-cancelled';
                $status_title = '✕ Not Approved / Cancelled';
                $status_msg = 'This order was declined or cancelled by café management.';
            } elseif ($current_status === 'Completed') {
                $status_class = 'status-completed';
                $status_title = '✓ Order Completed';
                $status_msg = 'This order has been picked up and fulfilled.';
            }
            ?>

            <div class="order-status-banner <?php echo $status_class; ?>">
                <div class="order-status-title">
                    Order Status: <?php echo $status_title; ?>
                </div>
                <div class="order-status-desc">
                    <?php echo $status_msg; ?>
                </div>
            </div>

            <div class="receipt-meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Date Placed</span>
                    <span class="meta-value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Payment Method</span>
                    <span class="meta-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Admin Approval</span>
                    <span class="meta-value receipt-meta-status">
                        <?php echo $status_title; ?>
                    </span>
                </div>
            </div>

            <div class="receipt-items-section">
                <h3>Order Items</h3>
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th class="receipt-table-num">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td class="receipt-table-num">$<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="total-label">Total Amount:</td>
                            <td class="total-amount">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="receipt-actions-group">
                <a href="account.php" class="btn-order-more btn-order-history">📜 My Account & Transaction History</a>
                <a href="menu.php" class="btn-order-more">🐾 Order More Treats</a>
            </div>
        </div>
    </div>
</main>

</body>
</html>
