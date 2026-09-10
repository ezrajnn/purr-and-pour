<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//condition if the user is already logged in redirect ra sa homepage
if (isset($_SESSION["user_id"]) && intval($_SESSION["user_id"]) > 0) {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../database/db.php';

$message = "";
if (isset($_GET["error"]) && $_GET["error"] === "login_required") {
    $message = "Please log in to add items to your cart.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";

    if (empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["name"] = $user["name"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];

                    header("Location: ../index.php");
                    exit();
                } else {
                    $message = "Incorrect password.";
                }
            } else {
                $message = "Email not found.";
            }

            $stmt->close();
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
    <title>Login</title>
    <link rel="stylesheet" href="../style/login.css">
    <link rel="stylesheet" href="../style/loginHeader.css">
</head>
<body>

<?php require_once __DIR__ . '/../navigation/header.php'; ?>

    <div class="login-wrapper">
        <div class="login-container">
            <h1>Log in</h1>
            <p class="login-subtitle">
                Welcome back! Log in to continue to Purr & Pour.
            </p>
            <?php
            if (!empty($message)) {
                echo "<p class='message'>" . htmlspecialchars($message) . "</p>";
            }
            ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
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