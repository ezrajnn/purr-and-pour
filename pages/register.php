<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = isset($_POST["name"]) ? trim($_POST["name"]) : "";
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    $confirmPassword = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $message = "Please fill in all fields.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        // Check if email already exists
        $checkSql = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $conn->prepare($checkSql);

        if ($checkStmt) {
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                $message = "Email is already registered.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param("sss", $name, $email, $hashedPassword);

                    if ($stmt->execute()) {
                        header("Location: login.php");
                        exit();
                    } else {
                        $message = "Registration failed. Please try again.";
                    }

                    $stmt->close();
                } else {
                    $message = "Database error. Please try again later.";
                }
            }

            $checkStmt->close();
        } else {
            $message = "Database error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../style/register.css">
    <link rel="stylesheet" href="../style/loginHeader.css">
</head>
<body>

    <?php require_once __DIR__ . '/../navigation/header.php'; ?>

    <main class="register-wrapper">
        <div class="register-container">
            <h1>Register</h1>
            <p class="register-subtitle">
                Join Purr & Pour! Create your account to get started.
            </p>
            <?php
            if (!empty($message)) {
                echo "<p class='message'>" . htmlspecialchars($message) . "</p>";
            }
            ?>

            <form method="POST">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="register-btn">
                    Register
                </button>
            </form>

            <p class="login-text">
                Already have an account?
                <a href="login.php">Log in</a>
            </p>
        </div>
    </main>
</body>
</html>