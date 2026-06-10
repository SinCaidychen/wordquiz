<?php
// ============================================================
//  includes/auth.php – Kiểm tra session đăng nhập
//  Include vào đầu các trang cần đăng nhập
// ============================================================

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function currentUsername() {
    return $_SESSION['username'] ?? null;
}
?>
