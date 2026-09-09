<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

// Authentication
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);

// Fetch user profile info
$user_stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$user_data) {
    // If user record not found, logout and redirect
    header("Location: ../functions/logout.php");
    exit();
}

// Fetch all transactions/orders placed by the user
$sql = "SELECT o.id, o.payment_method, o.total_amount, o.status, o.created_at,
               GROUP_CONCAT(CONCAT(oi.product_name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items_summary,
               COALESCE(SUM(oi.quantity), 0) as total_units
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

$orders = [];
$total_orders = 0;
$total_spent = 0.00;
$approved_count = 0;
$pending_count = 0;
$cancelled_count = 0;

while ($row = $orders_result->fetch_assoc()) {
    $orders[] = $row;
    $total_orders++;
    $total_spent += floatval($row['total_amount']);
    
    $st = $row['status'];
    if ($st === 'Approved' || $st === 'Confirmed') {
        $approved_count++;
    } elseif ($st === 'Pending') {
        $pending_count++;
    } elseif ($st === 'Cancelled') {
        $cancelled_count++;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account & Transaction History | Purr & Pour Café</title>
    <link rel="stylesheet" href="../style/loginHeader.css">
    <link rel="stylesheet" href="../style/account.css">
</head>
<body>

<?php require_once __DIR__ . '/../navigation/header.php'; ?>

<main class="account-page">

    <!-- User Profile Header -->
    <div class="profile-card">
        <div class="profile-details">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user_data['name']); ?></h1>
                <p>📧 <?php echo htmlspecialchars($user_data['email']); ?></p>
                <div>
                    <span class="role-badge">
                        <?php echo ($user_data['role'] === 'admin') ? '⚙ Administrator' : '🐾 Café Member'; ?>
                    </span>
                    <span style="font-size: 12px; color: #8c7b6d; margin-left: 8px;">User ID #<?php echo $user_data['id']; ?></span>
                </div>
            </div>
        </div>

        <div class="profile-actions">
            <?php if ($user_data['role'] === 'admin'): ?>
                <a href="admin.php" class="btn-order-now" style="background-color: #8c6d48;">⚙ Admin Dashboard</a>
            <?php endif; ?>
            <a href="menu.php" class="btn-order-now">☕ Order from Menu</a>
            <a href="../functions/logout.php" class="receipt-link-btn" style="color:#b91c1c; border-color:#fca5a5;">Log out</a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <span>Total Orders</span>
            <h3><?php echo $total_orders; ?></h3>
        </div>
        <div class="stat-card">
            <span>Total Spent</span>
            <h3>$<?php echo number_format($total_spent, 2); ?></h3>
        </div>
        <div class="stat-card">
            <span style="color: #03543f;">Approved Orders</span>
            <h3 style="color: #03543f;"><?php echo $approved_count; ?></h3>
        </div>
        <div class="stat-card">
            <span style="color: #854d0e;">Pending Approval</span>
            <h3 style="color: #854d0e;"><?php echo $pending_count; ?></h3>
        </div>
    </div>

    <!-- Transaction History Section -->
    <div class="orders-section">
        <div class="orders-header">
            <div>
                <h2>📜 Transaction History & Order Status</h2>
                <p style="color: #8c7b6d; font-size: 13px; margin-top: 3px;">
                    Track every order and check real-time approval status from our café admin
                </p>
            </div>

            <!-- Filter Buttons -->
            <div class="status-filters">
                <button type="button" class="filter-btn active" onclick="filterOrders('all', this)">All (<?php echo $total_orders; ?>)</button>
                <button type="button" class="filter-btn" onclick="filterOrders('pending', this)">Pending (<?php echo $pending_count; ?>)</button>
                <button type="button" class="filter-btn" onclick="filterOrders('approved', this)">Approved (<?php echo $approved_count; ?>)</button>
                <button type="button" class="filter-btn" onclick="filterOrders('cancelled', this)">Cancelled (<?php echo $cancelled_count; ?>)</button>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-history">
                <span class="empty-history-icon">☕</span>
                <h3>No Transactions Found</h3>
                <p>You haven't placed any orders yet. Visit our café menu to order fresh drinks and treats!</p>
                <a href="menu.php" class="btn-order-now">Explore Our Menu →</a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date & Time</th>
                            <th>Items Summary</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Admin Approval Status</th>
                            <th style="text-align: center;">Receipt</th>
                        </tr>
                    </thead>
                    <tbody id="orders-tbody">
                        <?php foreach ($orders as $ord): ?>
                            <?php
                            $st = $ord['status'];
                            $filter_category = 'pending';
                            $pill_class = 'status-pending';
                            $status_text = '⏳ Pending Admin Approval';
                            $status_help = 'Your order is waiting for admin confirmation.';

                            if ($st === 'Approved' || $st === 'Confirmed') {
                                $filter_category = 'approved';
                                $pill_class = 'status-approved';
                                $status_text = '✓ Approved by Admin';
                                $status_help = 'Admin approved this order! It is now being prepared.';
                            } elseif ($st === 'Cancelled') {
                                $filter_category = 'cancelled';
                                $pill_class = 'status-cancelled';
                                $status_text = '✕ Cancelled / Not Approved';
                                $status_help = 'This order was declined or cancelled by admin.';
                            } elseif ($st === 'Completed') {
                                $filter_category = 'approved';
                                $pill_class = 'status-completed';
                                $status_text = '✓ Order Completed';
                                $status_help = 'Order fulfilled and claimed.';
                            }
                            ?>
                            <tr class="order-row" data-status="<?php echo $filter_category; ?>">
                                <td>
                                    <strong>#<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></div>
                                    <small style="color: #8c7b6d;"><?php echo date('h:i A', strtotime($ord['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div style="max-width: 250px; line-height: 1.4; color: #4a3b2c;">
                                        <?php echo htmlspecialchars($ord['items_summary'] ?? 'Items details'); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong style="font-size: 14px;">$<?php echo number_format($ord['total_amount'], 2); ?></strong>
                                </td>
                                <td>
                                    <span style="color: #634b35; font-weight: 600;"><?php echo htmlspecialchars($ord['payment_method']); ?></span>
                                </td>
                                <td>
                                    <span class="status-pill <?php echo $pill_class; ?>">
                                        <span class="status-dot"></span>
                                        <?php echo $status_text; ?>
                                    </span>
                                    <span class="status-help-msg"><?php echo $status_help; ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="orderConfirmation.php?order_id=<?php echo $ord['id']; ?>" class="receipt-link-btn">
                                        View Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="no-filter-results" style="display: none; text-align: center; padding: 30px; color: #8c7b6d;">
                No orders match the selected filter.
            </div>
        <?php endif; ?>
    </div>

</main>

<script>
function filterOrders(category, btn) {
    // Update active button style
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const rows = document.querySelectorAll('.order-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (category === 'all' || rowStatus === category) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const noResults = document.getElementById('no-filter-results');
    if (noResults) {
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}
</script>

</body>
</html>
