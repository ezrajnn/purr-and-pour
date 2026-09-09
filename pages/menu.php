<?php
session_start();
require_once __DIR__ . '/../functions/products.php';
$menu_products = get_menu_products();

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
            <span>✓ Item successfully added to your cart!</span>
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
                    <div class="menu-card">
                        <img src="../elements/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">

                        <div class="menu-info">
                            <div class="menu-title-row">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <span>$<?php echo number_format($product['price'], 2); ?></span>
                            </div>

                            <p class="<?php echo $product['stock'] > 0 ? 'menu-stock-available' : 'menu-stock-out'; ?>">
                                <?php if ($product['stock'] > 0): ?>
                                    Available Stock: <?php echo $product['stock']; ?>
                                <?php else: ?>
                                    Out of Stock
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                            <a href="admin.php?edit=<?php echo $product['id']; ?>" class="add-cart admin-edit-btn">⚙ Edit in Admin</a>
                        <?php elseif ($product['stock'] > 0): ?>
                            <form action="../database/addCart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="add-cart">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="add-cart sold-out-btn" disabled>Sold Out</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

</main>

</body>

</html>