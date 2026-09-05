<?php

session_start();

require 'database/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "s",
        $email
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["email"] = $user["email"];

            header("Location: index.php");
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login</title>

</head>

<body>

    <h1>Login</h1>

    <?php

    if ($message != "") {
        echo "<p>$message</p>";
    }

    ?>

    <form method="POST">

        <label>Email</label><br>

        <input
            type="email"
            name="email"
            required
        >

        <br><br>

        <label>Password</label><br>

        <input
            type="password"
            name="password"
            required
        >

        <br><br>

        <button type="submit">
            Login
        </button>

    </form>

    <br>

    <p>
        Don't have an account?
        <a href="register.php">Register</a>
    </p>

</body>

</html>