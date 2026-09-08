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
        </ul>

        <?php if (!isset($_SESSION["user_id"])) : ?>

            <div class="login-header-account">
                <a href="/purr-and-pour/pages/login.php" class="header-register">
                    Order Now ⟶
                </a>
            </div>

        <?php else : ?>

            <div class="hero-buttons">

                <div class="header-login">

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

                </div>

                <a href="/purr-and-pour/functions/logout.php" class="header-register">
                    Log out
                </a>

            </div>

        <?php endif; ?>

    </nav>
</header>