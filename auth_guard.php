<?php
// auth_guard.php - Central Authentication Middleware

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

// Strict Security: Kick out unlogged users globally
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>