<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Story | Purr & Pour Café</title>
    <link rel="stylesheet" href="../style/loginHeader.css">

    <link rel="stylesheet" href="../style/ourStory.css">
</head>

<body>

    <?php include '../navigation/header.php'; ?>


    <main class="our-story-page">


        <!-- ABOUT US -->

        <section class="about-section">
            <div class="about-container">
                <div class="about-text">
                    <h1>About Us</h1>

                    <p>
                        At Purr & Pour Café, we believe that a good cup of
                        coffee is even better when shared with a furry friend.
                        Our café was created to bring together delicious
                        drinks, tasty treats, and the comforting company of
                        cats in one cozy and welcoming space.
                    </p>

                    <p>
                        Whether you're here to catch up with friends, take a
                        break from a busy day, or simply enjoy some quiet time,
                        we want every visit to feel warm, relaxing, and
                        memorable. More than just a café, Purr & Pour is a
                        place where coffee lovers and cat lovers can connect,
                        unwind, and create special moments.
                    </p>
                </div>

                <div class="about-image-box">
                    <div class="about-image-background"></div>

                    <img
                        src="../elements/ourStory_4.png"
                        alt="Customer enjoying coffee with a cat"
                        class="about-main-image"
                    >
                </div>
            </div>
        </section>

        <section class="story-gallery">

            <div class="gallery-container">

                <div class="gallery-photo">
                    <img
                        src="../elements/ourStory_1.png"
                        alt="Customers spending time with cats"
                    >
                </div>

                <div class="gallery-photo">
                    <img
                        src="../elements/ourStory_2.png"
                        alt="Customer working inside the café"
                    >
                </div>

                <div class="gallery-photo">
                    <img
                        src="../elements/ourStory_3.png"
                        alt="Cat beside a cup of coffee"
                    >
                </div>
            </div>
        </section>

        <section class="story-section">
            <div class="story-content">

                <h2>Our Story</h2>

                <p>
                    Purr & Pour began with the owner's genuine love for cats
                    and the happiness they bring into everyday life. Being
                    surrounded by cats has always brought a sense of comfort,
                    warmth, and companionship, which inspired the dream of
                    creating a place where others could experience that same
                    feeling.
                </p>
                <p>
                    That dream grew into Purr & Pour Café, a cozy space where
                    people can enjoy delicious coffee and treats while
                    spending time with friendly cats. Every part of the café
                    was created to feel comfortable and welcoming, giving
                    guests a place to relax, connect, and take a break from
                    their busy day.
                </p>
                <p>
                    Today, Purr & Pour continues to share the owner's love for
                    cats with every person who walks through its doors. We hope
                    every visit is filled with good coffee, peaceful moments,
                    and wonderful memories with our feline friends.
                </p>
            </div>
        </section>
    </main>
</body>
</html>