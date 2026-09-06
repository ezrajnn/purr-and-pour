<?php

session_start();
require '../database/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    if ($password !== $confirmPassword) {
        $message = "Passwords do not match.";

    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password)
                VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sss",
            $name,
            $email,
            $hashedPassword
        );

        if ($stmt->execute()) {
            header("Location: login.php");
            exit;

        } else {
            $message = "Registration failed.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Register</title>
    <link rel="stylesheet" href="../style/login.css">

</head>

<body>

    <div class="login-container">
        <h1>Register</h1>
        <p class="login-subtitle">
            Join Purr & Pour! Create your account to get started.
        </p>

        <?php

        if ($message != "") {
            echo "<p class='message'>$message</p>";
        }

        ?>

        <form method="POST">
            <div class="form-group">
                <label>Name</label>
                <input
                    type="text"
                    name="name"
                    required
                >
            </div>

            <div class="form-group">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    required
                >
            </div>

            <div class="form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    required
                >
            </div>

            <div class="form-group">

                <label>Confirm Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    required
                >
            </div>

            <button
                type="submit"
                class="login-btn"
            >
                Register
            </button>

        </form>

        <p class="register-text">
            Already have an account?
            <a href="login.php">
                Log in
            </a>
        </p>
    </div>
</body>
</html>