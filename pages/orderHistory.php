<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Redirect directly to User Account & Transaction History
header("Location: account.php");
exit();
?>
