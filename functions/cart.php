<?php
require_once __DIR__ . '/../database/db.php';

/**
 * Get all cart items for a given user with product details.
 *
 * @param int $user_id
 * @return array
 */
function get_cart_items($user_id) {
    global $conn;
    $user_id = intval($user_id);
    if ($user_id <= 0) {
        return [];
    }

    $sql = "SELECT c.id AS cart_id, c.product_id, c.quantity,
                   p.name, p.category, p.price, p.stock, p.image, p.description
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.id ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $price = floatval($row['price']);
        $qty = intval($row['quantity']);
        $row['price'] = $price;
        $row['quantity'] = $qty;
        $row['item_total'] = $price * $qty;
        $items[] = $row;
    }
    $stmt->close();

    return $items;
}

/**
 * Get the total quantity of items in the user's cart.
 *
 * @param int $user_id
 * @return int
 */
function get_cart_count($user_id) {
    global $conn;
    $user_id = intval($user_id);
    if ($user_id <= 0) {
        return 0;
    }

    $sql = "SELECT COALESCE(SUM(quantity), 0) AS total_count FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? intval($row['total_count']) : 0;
}

/**
 * Get the subtotal amount for the user's cart.
 *
 * @param int $user_id
 * @return float
 */
function get_cart_subtotal($user_id) {
    $items = get_cart_items($user_id);
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += $item['item_total'];
    }
    return $subtotal;
}

/**
 * Add an item to the user's cart or increase its quantity.
 *
 * @param int $user_id
 * @param int $product_id
 * @param int $quantity
 * @return bool
 */
function add_to_cart($user_id, $product_id, $quantity = 1) {
    global $conn;
    $user_id = intval($user_id);
    $product_id = intval($product_id);
    $quantity = max(1, intval($quantity));

    if ($user_id <= 0 || $product_id <= 0) {
        return false;
    }

    // Verify product exists and has stock
    $check_prod = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $check_prod->bind_param("i", $product_id);
    $check_prod->execute();
    $prod_res = $check_prod->get_result()->fetch_assoc();
    $check_prod->close();

    if (!$prod_res || intval($prod_res['stock']) <= 0) {
        return false;
    }

    // Check if item already exists in cart
    $check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $cart_id = intval($row['id']);
        $update_sql = "UPDATE cart SET quantity = quantity + ? WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iii", $quantity, $cart_id, $user_id);
        $success = $update_stmt->execute();
        $update_stmt->close();
    } else {
        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
        $success = $insert_stmt->execute();
        $insert_stmt->close();
    }
    $stmt->close();

    return $success;
}

/**
 * Increase quantity of an item in the user's cart by 1.
 *
 * @param int $user_id
 * @param int $cart_id
 * @param int $product_id
 * @return bool
 */
function increase_cart_quantity($user_id, $cart_id = 0, $product_id = 0) {
    global $conn;
    $user_id = intval($user_id);
    $cart_id = intval($cart_id);
    $product_id = intval($product_id);

    if ($user_id <= 0) {
        return false;
    }

    if ($cart_id > 0) {
        $sql = "UPDATE cart SET quantity = quantity + 1 WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cart_id, $user_id);
    } elseif ($product_id > 0) {
        $sql = "UPDATE cart SET quantity = quantity + 1 WHERE product_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $product_id, $user_id);
    } else {
        return false;
    }

    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Decrease quantity of an item in the user's cart by 1, removing it if 0.
 *
 * @param int $user_id
 * @param int $cart_id
 * @param int $product_id
 * @return bool
 */
function decrease_cart_quantity($user_id, $cart_id = 0, $product_id = 0) {
    global $conn;
    $user_id = intval($user_id);
    $cart_id = intval($cart_id);
    $product_id = intval($product_id);

    if ($user_id <= 0) {
        return false;
    }

    if ($cart_id > 0) {
        $sql = "SELECT id, quantity FROM cart WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cart_id, $user_id);
    } elseif ($product_id > 0) {
        $sql = "SELECT id, quantity FROM cart WHERE product_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $product_id, $user_id);
    } else {
        return false;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return false;
    }

    $item_id = intval($row['id']);
    $qty = intval($row['quantity']);

    if ($qty > 1) {
        $update = $conn->prepare("UPDATE cart SET quantity = quantity - 1 WHERE id = ? AND user_id = ?");
        $update->bind_param("ii", $item_id, $user_id);
        $success = $update->execute();
        $update->close();
    } else {
        $delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $delete->bind_param("ii", $item_id, $user_id);
        $success = $delete->execute();
        $delete->close();
    }

    return $success;
}

/**
 * Remove an item completely from the user's cart.
 *
 * @param int $user_id
 * @param int $cart_id
 * @param int $product_id
 * @return bool
 */
function remove_from_cart($user_id, $cart_id = 0, $product_id = 0) {
    global $conn;
    $user_id = intval($user_id);
    $cart_id = intval($cart_id);
    $product_id = intval($product_id);

    if ($user_id <= 0) {
        return false;
    }

    if ($cart_id > 0) {
        $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cart_id, $user_id);
    } elseif ($product_id > 0) {
        $sql = "DELETE FROM cart WHERE product_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $product_id, $user_id);
    } else {
        return false;
    }

    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Clear all items in the user's cart.
 *
 * @param int $user_id
 * @return bool
 */
function clear_user_cart($user_id) {
    global $conn;
    $user_id = intval($user_id);
    if ($user_id <= 0) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
