<?php
require_once __DIR__ . '/../database/db.php';

/**
 * Fetch all menu products indexed by their ID.
 *
 * @return array
 */
function get_menu_products() {
    global $conn;
    $products = [];
    
    $sql = "SELECT id, name, category, price, stock, image, description FROM products ORDER BY id ASC";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[intval($row['id'])] = [
                'id'          => intval($row['id']),
                'name'        => $row['name'],
                'category'    => $row['category'],
                'price'       => floatval($row['price']),
                'stock'       => intval($row['stock']),
                'image'       => $row['image'],
                'description' => $row['description']
            ];
        }
    }
    
    return $products;
}

/**
 * Fetch a single product by ID.
 *
 * @param int $product_id
 * @return array|null
 */
function get_product_by_id($product_id) {
    global $conn;
    $product_id = intval($product_id);
    if ($product_id <= 0) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, name, category, price, stock, image, description FROM products WHERE id = ?");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        return [
            'id'          => intval($row['id']),
            'name'        => $row['name'],
            'category'    => $row['category'],
            'price'       => floatval($row['price']),
            'stock'       => intval($row['stock']),
            'image'       => $row['image'],
            'description' => $row['description']
        ];
    }
    return null;
}

/**
 * Fetch bestseller products.
 *
 * @param int $limit
 * @return array
 */
function get_bestseller_products($limit = 4) {
    global $conn;
    $limit = max(1, intval($limit));
    $sql = "SELECT p.id, p.name, p.category, p.price, p.stock, p.image, p.description,
                   COALESCE(SUM(oi.quantity), 0) AS total_sold
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            GROUP BY p.id
            ORDER BY total_sold DESC, p.id ASC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id'          => intval($row['id']),
            'name'        => $row['name'],
            'category'    => $row['category'],
            'price'       => floatval($row['price']),
            'stock'       => intval($row['stock']),
            'image'       => $row['image'],
            'description' => $row['description'],
            'total_sold'  => intval($row['total_sold'])
        ];
    }
    $stmt->close();
    return $products;
}

/**
 * Adjust stock of a product by an offset (positive to add, negative to deduct).
 *
 * @param int $product_id
 * @param int $offset
 * @return bool
 */
function adjust_product_stock($product_id, $offset) {
    global $conn;
    $product_id = intval($product_id);
    $offset = intval($offset);

    if ($product_id <= 0) {
        return false;
    }

    $stmt = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock + ?) WHERE id = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $offset, $product_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
