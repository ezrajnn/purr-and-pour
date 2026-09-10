<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if a user is logged in.
 
function is_logged_in() {
    return isset($_SESSION["user_id"]) && intval($_SESSION["user_id"]) > 0;
}

// Check if the logged in account is an admin.
 
function is_admin() {
    return is_logged_in() && isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

// Get current user id if not logged in.
 
function get_current_user_id() {
    return is_logged_in() ? intval($_SESSION["user_id"]) : 0;
}
// Get current user display name.
function get_current_user_name() {
    return isset($_SESSION["name"]) ? $_SESSION["name"] : "Guest";
}

// Require user to be logged in, otherwise redirect.
 
function require_login($redirect = '../pages/login.php') {
    if (!is_logged_in()) {
        header("Location: " . $redirect);
        exit();
    }
}

// Require user to be an admin, otherwise redirect or deny.
 
function require_admin($redirect = '../pages/login.php') {
    if (!is_admin()) {
        header("Location: " . $redirect);
        exit();
    }
}
