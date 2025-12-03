<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Require Login (Middleware)
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header("Location: " . BASE_URL . "login.php");
            exit();
        }
        self::checkSessionTimeout();
    }

    // Require Specific Role
    public static function requireRole($role) {
        self::requireLogin();
        if ($_SESSION['role'] !== $role) {
            // Log unauthorized access attempt
            header("HTTP/1.1 403 Forbidden");
            die("Access Denied: You do not have permission to view this page.");
        }
    }

    // Session Timeout (30 mins)
    public static function checkSessionTimeout() {
        $timeout_duration = 1800; // 30 minutes
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
            session_unset();
            session_destroy();
            header("Location: " . BASE_URL . "login.php?timeout=1");
            exit();
        }
        $_SESSION['last_activity'] = time();
    }
    
    // Get Current User ID
    public static function user() {
        return $_SESSION['user_id'] ?? null;
    }
}
?>