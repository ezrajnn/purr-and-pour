<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="login-header">
    <nav class="login-navbar">

        <div class="login-header-logo">
            <a href="/purr-and-pour/index.php">
                <img
                    src="/purr-and-pour/elements/orangePaw.png"
                    alt="Purr & Pour Logo">
            </a>
        </div>

        <ul class="login-header-links">
            <li>
                <a href="/purr-and-pour/index.php">Home</a>
            </li>

            <li>
                <a href="/purr-and-pour/pages/menu.php">Menu</a>
            </li>

            <li>
                <a href="/purr-and-pour/pages/catLounge.php">Cat Lounge</a>
            </li>

            <li>
                <a href="/purr-and-pour/pages/ourStory.php">Our Story</a>
            </li>

            <?php if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin"): ?>
            <li>
                <a href="/purr-and-pour/pages/cart.php" class="header-cart-link">
                    Cart <?php
                    if (isset($_SESSION["user_id"])) {
                        require_once __DIR__ . '/../database/db.php';
                        $header_uid = intval($_SESSION["user_id"]);
                        $header_res = mysqli_query($conn, "SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = $header_uid");
                        if ($header_res && $header_row = mysqli_fetch_assoc($header_res)) {
                            $header_qty = intval($header_row["total_qty"]);
                            if ($header_qty > 0) {
                                echo " <span class='header-cart-badge'>$header_qty</span>";
                            }
                        }
                    }
                    ?></a>
            </li>
            <?php endif; ?>
        </ul>

        <?php if (!isset($_SESSION["user_id"])) : ?>

            <div class="login-header-account">
                <a href="/purr-and-pour/pages/login.php" class="header-register">
                    Order Now ⟶
                </a>
            </div>

        <?php else : ?>

            <div class="header-user-actions">
                <button type="button" class="sidebar-toggle-btn" id="sidebarOpenBtn" aria-label="Open Sidebar Menu">
                    <span class="sidebar-toggle-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                    <span class="sidebar-toggle-text">Menu</span>
                </button>
            </div>

        <?php endif; ?>

    </nav>
</header>

<?php
if (isset($_SESSION["user_id"])) {
    include __DIR__ . '/sidebar.php';
}
?>
