<?php

require 'database/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];

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
        $message = "Registration successful!";
    } else {
        $message = "Registration failed.";
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
    <title>Register</title>
</head>

<body>

    <h1>Register</h1>

    <?php
    if ($message != "") {
        echo "<p>$message</p>";
    }
    ?>

    <form method="POST">

        <label>Name</label><br>
        <input
            type="text"
            name="name"
            required
        >

        <br><br>

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
            Register
        </button>

    </form>

</body>

</html>