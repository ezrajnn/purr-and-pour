<?php

session_start();
require '../database/db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["email"] = $user["email"];
            header("Location: ../index.php");
            exit;

        } else {
            $message = "Incorrect password.";
        }

    } else {
        $message = "Email not found.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../style/login.css">
</head>

<body>

<?php require '../navigation/loginHeader.php'; ?>

    <div class="login-wrapper">
        <div class="login-container">
            <h1>Log in</h1>
            <p class="login-subtitle">
                Welcome back! Log in to continue to Purr & Pour.
            </p>
            <?php
            if ($message != "") {
                echo "<p class='message'>$message</p>";
            }
            ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" class="login-btn">
                    Log in
                </button>
            </form>

            <p class="register-text">
                Don't have an account yet?
                <a href="register.php">Register</a>
            </p>
        </div>
    </div>

</body>
</html>