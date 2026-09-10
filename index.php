<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purr & Pour Café</title>
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/loginHeader.css">
</head>

<body>
    <div>
        <?php include "navigation/header.php"; ?>
        
    </div>
    <!-- Homepage Header -->

    <!-- Hero section -->
    <section class="hero" id="home">

    <div class="hero-content">
            <b>
                New Brown Cloud<br>
                Frappuccino<br>
                Available Now!
            </b>
            <h2>
                with more cats coming soon
            </h2>
            <p>
                A creamy, dreamy frappuccino with rich brown sugar and a
                smooth coffee flavor, perfect for a sweet and refreshing
                treat. Plus, more adorable cats are coming soon!
            </p>

            <!-- Hero Buttons -->
            <div class="hero-buttons">
                <a href="pages/menu.php" class="order-btn">
                    Order Now ⟶
                </a>
            </div>
        </div>

        <!-- Hero Images -->
        <div class="image">
            <img
                src="elements/coffee.png"
                alt="Brown Cloud Frappuccino"
                class="frappe">
            <img
                src="elements/iring.png"
                alt="Cats"
                class="cats">
            <img
                src="elements/iring2.png"
                alt="Cats"
                class="iring2">
        </div>
    </section>

    <!-- Bestseller Section -->
    <section class="bestseller-section">

        <!-- Header -->
        <div class="bestseller-header">
            <h2>
                Our Bestsellers
            </h2>
            <a href="pages/menu.php" class="fullmenu-btn">
                View Full Menu ⟶
            </a>
        </div>

        <!-- Cards -->
        <div class="bestseller-cards">

            <!-- DRINK 1 -->
            <div class="drink-card">
                <div class="drink-image">
                    <img
                        src="elements/drink_1.png"
                        alt="Chocolate Frappe">
                </div>
                <a href="pages/menu.php" class="drink-info">
                    <h3>
                        Latte Coffee
                    </h3>
                </a>
            </div>

            <!-- Drink 2 -->
            <div class="drink-card">
                <div class="drink-image">
                    <img
                        src="elements/drink_2.png"
                        alt="Milk Tea">
                </div>
                <a href="pages/menu.php" class="drink-info">
                    <h3>
                        Hot Americano
                    </h3>
                </a>
            </div>

            <!-- Drink 3 -->
            <div class="drink-card">
                <div class="drink-image">
                    <img
                        src="elements/drink_3.png"
                        alt="Caramel Macchiato">
                </div>
                <a href="pages/menu.php" class="drink-info">
                    <h3>
                    Espresso Coffee
                    </h3>
                </a>
            </div>

            <!-- Drink 4 -->
            <div class="drink-card">
                <div class="drink-image">
                    <img
                        src="elements/drink_4.png"
                        alt="Hot Mocha">
                </div>
                <a href="pages/menu.php" class="drink-info">
                    <h3>
                        Hot Mocha
                    </h3>
                </a>
            </div>
        </div>
    </section>

    <!-- Cat Lounge -->
    <section class="lounge-section" id="/pages/catlounge.php">

        <div class="lounge-content">
            <h2>
                Our Cat Lounge
            </h2>
            <div class="lounge-line"></div>
            <p>
                A cozy space where you can relax, play, and spend quality
                time with our friendly cats. Take a break from the day,
                make new furry friends, and enjoy the comforting company
                of our playful companions.
            </p>
            <a href="pages/catLounge.php" class="lounge-btn">
                View More ⟶
            </a>
        </div>

        <!-- Lounge Image -->
        <div class="lounge-image">
            <img
                src="elements/cat_lounge.png"
                alt="Our Cat Lounge">
        </div>
    </section>

    <!-- Cat Profiles -->
    <section class="cat-profile-section" id="cats">

        <!-- Cat Profiles Header -->
        <div class="cat-profile-header">
            <h2>
                Cat Profile
            </h2>
            <div class="cat-profile-line"></div>
            <p>
                Meet the adorable cats of Purr & Pour! Get to know
                their names and sweet little faces.
            </p>
        </div>

        <!-- Cat Polaroids -->
        <div class="cat-profile-grid">

            <!-- Oliver -->
            <div class="cat-profile-card">
                <div class="cat-photo">
                    <img
                        src="elements/oliver.png"
                        alt="Oliver">
                </div>
                <div class="cat-name">
                    <img
                        src="elements/paw.png"
                        alt="Paw"
                        class="paw-image">
                    <h3>
                        Oliver
                    </h3>
                </div>
            </div>

            <!-- Becka -->
            <div class="cat-profile-card">
                <div class="cat-photo">
                    <img
                        src="elements/becka.png"
                        alt="Becka">
                </div>
                <div class="cat-name">
                    <img
                        src="elements/paw.png"
                        alt="Paw"
                        class="paw-image">
                    <h3>
                        Becka
                    </h3>
                </div>
            </div>

            <!-- Billy -->
            <div class="cat-profile-card">
                <div class="cat-photo">
                    <img
                        src="elements/billy.png"
                        alt="Billy">
                </div>
                <div class="cat-name">
                    <img
                        src="elements/paw.png"
                        alt="Paw"
                        class="paw-image">
                    <h3>
                        Billy
                    </h3>
                </div>
            </div>

            <!-- Percy -->
            <div class="cat-profile-card">
                <div class="cat-photo">
                    <img
                        src="elements/percy.png"
                        alt="Percy">
                </div>
                <div class="cat-name">
                    <img
                        src="elements/paw.png"
                        alt="Paw"
                        class="paw-image">
                    <h3>
                        Percy
                    </h3>
                </div>
            </div>

            <!-- Cassy -->
            <div class="cat-profile-card">
                <div class="cat-photo">
                    <img
                        src="elements/cassy.png"
                        alt="Cassy">
                </div>
                <div class="cat-name">
                    <img
                        src="elements/paw.png"
                        alt="Paw"
                        class="paw-image">
                    <h3>
                        Cassy
                    </h3>
                </div>
            </div>

            <!-- Kimmy -->
            <div class="cat-profile-card">
                <div class="cat-photo">
                    <img
                        src="elements/kimmy.png"
                        alt="Kimmy">
                </div>
                <div class="cat-name">
                    <img
                        src="elements/paw.png"
                        alt="Paw"
                        class="paw-image">
                    <h3>
                        Kimmy
                    </h3>
                </div>
            </div>

            <!-- Tom -->
            <div class="cat-profile-card">
                <div class="cat-photo">
                    <img
                        src="elements/tom.png"
                        alt="Tom">
                </div>
                <div class="cat-name">
                    <img
                        src="elements/paw.png"
                        alt="Paw"
                        class="paw-image">
                    <h3>
                        Tom
                    </h3>
                </div>
            </div>

            <!-- Weasley -->
            <div class="cat-profile-card">
                <div class="cat-photo">
                    <img
                        src="elements/weasley.png"
                        alt="Weasley">
                </div>
                <div class="cat-name">
                    <img
                        src="elements/paw.png"
                        alt="Paw"
                        class="paw-image">
                    <h3>
                        Weasley
                    </h3>
                </div>
            </div>
        </div>
    </section>

    <!-- About us Section -->
    <section class="about-section" id="story">

        <!-- Content -->
        <div class="about-content">
            <h3>
                About Us
            </h3>
            <div class="about-line"></div>
            <p>
                Purr & Pour Café started with the owner's
                love for cats and coffee, inspired by the
                comfort of enjoying a warm cup of coffee
                alongside furry companions. This passion
                grew into a cozy café where people can
                relax, enjoy delicious drinks and food,
                and create meaningful memories with cats
                in a warm and welcoming atmosphere.
            </p>
            <a href="pages/ourStory.php" class="about-btn">
                More About us ⟶
            </a>
        </div>

        <!-- Image -->
        <div class="about-image">
            <img
                src="elements/cafe.png"
                alt="About Us">
        </div>
    </section>

    <!-- Reviews Section -->
    <section class="reviews-section" id="reviews">
        <div class="reviews-container">
            <div class="reviews-header">
                <h2>
                    What our Guests say
                </h2>
            
            </div>
            <div class="reviews-grid">


                <!-- Review 1 -->
                <article class="review-card">
                    <div class="card-content">
                        <div class="card-top">
                            <span class="quote-mark">“</span>
                            <div class="rating-stars">
                                ★★★★★
                            </div>
                        </div>
                        <p class="review-text">
                            Such a cozy and relaxing place! The cats were adorable,
                            and I loved enjoying my coffee while spending time with them.
                        </p>
                    </div>
                    <div class="reviewer-info">
                        <img
                            src="elements/gordon.png"
                            alt="Gordon"
                            class="reviewer-avatar">
                        <span class="reviewer-name">
                            Gordon
                        </span>
                    </div>
                </article>

                <!-- Review 2 -->
                <article class="review-card">
                    <div class="card-content">
                        <div class="card-top">
                            <span class="quote-mark">“</span>
                            <div class="rating-stars">
                                ★★★★★
                            </div>
                        </div>
                        <p class="review-text">
                            I had such a fun experience! The coffee was delicious,
                            the staff were friendly, and the cats made my visit even better.
                        </p>
                    </div>
                    <div class="reviewer-info">
                        <img
                            src="elements/michael.png"
                            alt="Michael"
                            class="reviewer-avatar">
                        <span class="reviewer-name">
                            Michael
                        </span>
                    </div>
                </article>

                <!-- Review 3 -->
                <article class="review-card">
                    <div class="card-content">
                        <div class="card-top">
                            <span class="quote-mark">“</span>
                            <div class="rating-stars">
                                ★★★★★
                            </div>
                        </div>
                        <p class="review-text">
                            I really enjoyed my time here. The peaceful atmosphere,
                            tasty drinks, and lovely cats made the experience so memorable.
                        </p>
                    </div>
                    <div class="reviewer-info">
                        <img
                            src="elements/joseph.png"
                            alt="Joseph"
                            class="reviewer-avatar">
                        <span class="reviewer-name">
                            Joseph
                        </span>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">

        <!-- Brand -->
        <div class="footer-brand">
            <img
                src="elements/footer_logo.png"
                alt="Purr & Pour Cafe">
            <p>
                0967 6767 6767
            </p>
            <p>
                purr&pour@gmail.com
            </p>
            <div class="socials">
                <a href="#">
                    f
                </a>
                <a href="#">
                    ◎
                </a>
            </div>
        </div>

        <!-- Menu -->
        <div class="footer-column">
            <h3>
                Menu
            </h3>
            <a href="pages/menu.php">
                Drinks
            </a>
            <a href="pages/menu.php">
                Meals
            </a>
            <a href="pages/menu.php">
                Pastries
            </a>
            <a href="pages/menu.php">
                Bestsellers
            </a>
        </div>

        <!-- Quicklinks -->
        <div class="footer-column">
            <h3>
                Quicklinks
            </h3>
            <a href="pages/ourStory.php">
                About Us
            </a>
            <a href="index.php">
                Home
            </a>
            <a href="pages/menu.php">
                Menu
            </a>
            <a href="#cats">
                Cat Profile
            </a>
            <a href="pages/catLounge.php">
                Cat Lounge
            </a>
        </div>

        <!-- App -->
        <div class="footer-column app-column">
            <h3>
                Get the App
            </h3>
            <img
                src="elements/google_logo.png"
                alt="Google Play">
            <img
                src="elements/apple_logo.png"
                alt="App Store">
        </div>
    </footer>
</body>

</html>