<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../functions/cart.php';

// If the user is not logged in, redirect to login.php with clear message
if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php?error=login_required");
    exit();
}

// Admins not allowed to place orders
if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
    header("Location: ../pages/admin.php");
    exit();
}

// Read and validate product_id from POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["product_id"])) {
    $product_id = intval($_POST["product_id"]);
    $user_id = intval($_SESSION["user_id"]);
    $quantity = isset($_POST["quantity"]) ? max(1, intval($_POST["quantity"])) : 1;

    if ($product_id > 0 && $user_id > 0) {
        add_to_cart($user_id, $product_id, $quantity);
    }
}

// Redirect back to menu.php with confirmation parameter
header("Location: ../pages/menu.php?added=1");
exit();
