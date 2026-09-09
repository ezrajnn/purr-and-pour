<header class="login-header">
    <nav class="login-navbar">

        <div class="login-header-logo">
            <img
                src="/purr-and-pour/elements/orangePaw.png"
                alt="Purr & Pour Logo">
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
            <?php if (isset($_SESSION["user_id"])): ?>
            <li>
                <a href="/purr-and-pour/pages/account.php">My Account & History</a>
            </li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>

        <?php if (!isset($_SESSION["user_id"])) : ?>

            <div class="login-header-account">
                <a href="/purr-and-pour/pages/login.php" class="header-register">
                    Order Now ⟶
                </a>
            </div>

        <?php else : ?>

            <div class="hero-buttons">

                <a href="/purr-and-pour/pages/account.php" class="header-login" title="View My Account & Transaction History">

                    <svg
                        class="user-icon"
                        viewBox="0 0 32 32"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg">

                        <circle cx="16" cy="16" r="14" />
                        <circle cx="16" cy="11" r="5" />

                        <path d="M7.5 27C8.5 21.5 11.5 18.5 16 18.5C20.5 18.5 23.5 21.5 24.5 27" />

                    </svg>

                    <span class="username">
                        <?php echo htmlspecialchars($_SESSION["name"]); ?>
                    </span>

                </a>

                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") : ?>
                    <a href="/purr-and-pour/pages/admin.php" class="header-register header-admin">
                        ⚙ Admin
                    </a>
                <?php endif; ?>

                <a href="/purr-and-pour/functions/logout.php" class="header-register">
                    Log out
                </a>

            </div>

        <?php endif; ?>

    </nav>
</header>