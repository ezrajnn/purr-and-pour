<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/cart.php';

/**
 * Place a new order using the items currently in the user's cart.
 *
 * @param int $user_id
 * @param string $customer_name
 * @param string $customer_email
 * @param string $payment_method
 * @param string $order_notes
 * @return int|false Returns order ID on success, or false on failure
 */
function create_order($user_id, $customer_name, $customer_email = '', $payment_method = 'Cash on Pickup', $order_notes = '') {
    global $conn;
    $user_id = intval($user_id);
    $customer_name = trim($customer_name);
    $customer_email = trim($customer_email);
    $payment_method = trim($payment_method);
    $order_notes = trim($order_notes);

    if ($user_id <= 0 || empty($customer_name)) {
        return false;
    }

    $cart_items = get_cart_items($user_id);
    if (empty($cart_items)) {
        return false;
    }

    $total_amount = 0.0;
    foreach ($cart_items as $item) {
        $total_amount += $item['item_total'];
    }

    $order_stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_email, payment_method, order_notes, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    if (!$order_stmt) {
        return false;
    }

    $order_stmt->bind_param("issssd", $user_id, $customer_name, $customer_email, $payment_method, $order_notes, $total_amount);
    if (!$order_stmt->execute()) {
        $order_stmt->close();
        return false;
    }

    $order_id = $conn->insert_id;
    $order_stmt->close();

    // Insert order items and deduct stock
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $stock_stmt = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");

    foreach ($cart_items as $item) {
        $pid = intval($item['product_id']);
        $pname = $item['name'];
        $price = floatval($item['price']);
        $qty = intval($item['quantity']);
        $subtotal = floatval($item['item_total']);

        if ($item_stmt) {
            $item_stmt->bind_param("iisdid", $order_id, $pid, $pname, $price, $qty, $subtotal);
            $item_stmt->execute();
        }

        if ($stock_stmt) {
            $stock_stmt->bind_param("ii", $qty, $pid);
            $stock_stmt->execute();
        }
    }

    if ($item_stmt) $item_stmt->close();
    if ($stock_stmt) $stock_stmt->close();

    // Clear cart
    clear_user_cart($user_id);

    return $order_id;
}

/**
 * Get all orders for a specific user.
 *
 * @param int $user_id
 * @return array
 */
function get_user_orders($user_id) {
    global $conn;
    $user_id = intval($user_id);
    if ($user_id <= 0) {
        return [];
    }

    $sql = "SELECT o.id, o.payment_method, o.total_amount, o.status, o.created_at,
                   GROUP_CONCAT(CONCAT(oi.product_name, ' (x', oi.quantity, ')') SEPARATOR ', ') AS items_summary,
                   COALESCE(SUM(oi.quantity), 0) AS total_units
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.id DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();

    return $orders;
}

/**
 * Get a single order by ID (optionally constrained to a specific user_id).
 *
 * @param int $order_id
 * @param int|null $user_id
 * @return array|null
 */
function get_order_by_id($order_id, $user_id = null) {
    global $conn;
    $order_id = intval($order_id);
    if ($order_id <= 0) {
        return null;
    }

    if ($user_id !== null) {
        $user_id = intval($user_id);
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
    }

    if (!$stmt) {
        return null;
    }

    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $order;
}

/**
 * Get all line items for an order.
 *
 * @param int $order_id
 * @return array
 */
function get_order_items($order_id) {
    global $conn;
    $order_id = intval($order_id);
    if ($order_id <= 0) {
        return [];
    }

    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    return $items;
}

/**
 * Update the status of an order (e.g. Approved, Completed, Cancelled).
 *
 * @param int $order_id
 * @param string $status
 * @return bool
 */
function update_order_status($order_id, $status) {
    global $conn;
    $order_id = intval($order_id);
    $status = trim($status);

    if ($order_id <= 0 || empty($status)) {
        return false;
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("si", $status, $order_id);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}
