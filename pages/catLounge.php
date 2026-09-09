<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cat Lounge | Purr & Pour Café</title>
    <link rel="stylesheet" href="../style/loginHeader.css">
    <link rel="stylesheet" href="../style/catLounge.css">
</head>
<body>
    <?php include '../navigation/header.php'; ?>
<section class="cat-lounge-page">

    <!-- SECTION 1 -->
    <div class="lounge-row">
        <div class="lounge-text">
            <h2>Sip, Relax & Meet Our Cats</h2>
            <p>
                Take a break from the busy day and enjoy a cozy
                space where you can sip your favorite drink, relax,
                and spend time with our friendly cats. Let their
                playful personalities and gentle company bring a
                little extra joy to your day.
            </p>
            <p>
                Whether you're visiting with friends or enjoying
                some quiet time alone, our cat lounge is the perfect
                place to unwind and make memorable moments.
            </p>
        </div>

        <div class="lounge-image-wrapper image-right">
            <div class="image-bg"></div>
            <img
                src="../elements/lounge_1.png"
                alt="Cats inside the Purr and Pour cat lounge"
            >
        </div>
    </div>


    <!-- SECTION 2 -->
    <div class="lounge-row reverse-row">
        <div class="lounge-image-wrapper image-left">
            <div class="image-bg"></div>
            <img
                src="../elements/lounge_2.png"
                alt="Cats playing inside the lounge"
            >
        </div>

        <div class="lounge-text">
            <p>
                Guests also have the chance to feed our cats approved
                treats as a reward for their good behavior. It's a fun
                and heartwarming way to interact with them, build a
                little bond, and get to know their unique personalities.
            </p>
            <p>
                Our friendly cats are well-socialized and used to
                being around guests, creating a comfortable and
                enjoyable experience while you relax and enjoy
                your time at the café.
            </p>
        </div>
    </div>


    <!-- SECTION 3 -->
    <div class="lounge-row">
        <div class="lounge-text">
            <p>
                Our cats are fully vaccinated, regularly cared for,
                and kept healthy to ensure a safe and comfortable
                experience for both our feline friends and our guests.
                Their health and well-being are always a priority,
                so you can relax and enjoy spending time with them
                with peace of mind.
            </p>
        </div>

        <div class="lounge-image-wrapper image-right">
            <div class="image-bg"></div>
            <img
                src="../elements/lounge_3.png"
                alt="Relaxing cat lounge area"
            >
        </div>
    </div>

</section>
</body>
</html>