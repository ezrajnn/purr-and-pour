<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../functions/cart.php';

// If user is not logged in, redirect to login.php
if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = intval($_SESSION["user_id"]);
    $cart_id = isset($_POST["cart_id"]) ? intval($_POST["cart_id"]) : 0;
    $product_id = isset($_POST["product_id"]) ? intval($_POST["product_id"]) : 0;

    decrease_cart_quantity($user_id, $cart_id, $product_id);
}

// Redirect back to cart page
header("Location: ../pages/cart.php");
exit();
