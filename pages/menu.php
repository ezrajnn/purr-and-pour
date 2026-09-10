<?php
session_start();
require_once __DIR__ . '/../functions/products.php';
require_once __DIR__ . '/../database/db.php';

$menu_products = get_menu_products();

// Track items and quantities already in the current user's cart
$user_cart_quantities = [];
if (isset($_SESSION["user_id"])) {
    $uid = intval($_SESSION["user_id"]);
    $cart_q = $conn->prepare("SELECT product_id, quantity FROM cart WHERE user_id = ?");
    if ($cart_q) {
        $cart_q->bind_param("i", $uid);
        $cart_q->execute();
        $cart_res = $cart_q->get_result();
        while ($c_row = $cart_res->fetch_assoc()) {
            $user_cart_quantities[intval($c_row['product_id'])] = intval($c_row['quantity']);
        }
        $cart_q->close();
    }
}

$categories = [];
foreach ($menu_products as $prod) {
    $cat = !empty($prod['category']) ? $prod['category'] : 'Specialties';
    $categories[$cat][] = $prod;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Menu | Purr & Pour Café</title>

    <link rel="stylesheet" href="../style/menu.css">
    <link rel="stylesheet" href="../style/loginHeader.css">
</head>

<body>

<?php include '../navigation/header.php'; ?>

<main class="menu-page">

    <?php if (isset($_GET['added'])): ?>
        <div class="cart-notification">
            <span>Item successfully added to your cart!</span>
            <a href="cart.php" class="view-cart-link">View Cart</a>
        </div>
    <?php endif; ?>

    <?php foreach ($categories as $category_name => $items): ?>
        <div class="menu-category-section">
            <h2 class="category-heading">
                <?php echo htmlspecialchars($category_name); ?>
            </h2>

            <div class="menu-container">
                <?php foreach ($items as $product): ?>
                    <?php
                    $pid = intval($product['id']);
                    $in_cart = $user_cart_quantities[$pid] ?? 0;
                    $actual_stock = intval($product['stock']);
                    $remaining_stock = max(0, $actual_stock - $in_cart);
                    $is_out_of_stock = ($actual_stock <= 0) || ($in_cart >= $actual_stock);
                    ?>
                    <div class="menu-card">
                        <img src="../elements/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">

                        <div class="menu-info">
                            <div class="menu-title-row">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <span>$<?php echo number_format($product['price'], 2); ?></span>
                            </div>

                            <p class="<?php echo !$is_out_of_stock ? 'menu-stock-available' : 'menu-stock-out'; ?>">
                                <?php if ($actual_stock <= 0): ?>
                                    Out of Stock
                                <?php elseif ($in_cart >= $actual_stock): ?>
                                    Out of Stock (All in your cart)
                                <?php else: ?>
                                    Available Stock: <?php echo $remaining_stock; ?>
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                            <a href="admin.php?edit=<?php echo $product['id']; ?>" class="add-cart admin-edit-btn">⚙ Edit in Admin</a>
                        <?php elseif (!$is_out_of_stock): ?>
                            <form action="../database/addCart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="add-cart">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="add-cart sold-out-btn" disabled>
                                <?php echo ($actual_stock > 0 && $in_cart >= $actual_stock) ? 'Max in Cart' : 'Sold Out'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

</main>

</body>

</html>