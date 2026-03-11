<?php
require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

function isLoggedIn(): bool {
    return !empty($_SESSION['certgen_auth']) && $_SESSION['certgen_auth'] === true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function attemptLogin(string $user, string $pass): bool {
    if ($user === AUTH_USERNAME && hash_equals(AUTH_PASSWORD, $pass)) {
        session_regenerate_id(true);
        $_SESSION['certgen_auth'] = true;
        return true;
    }
    return false;
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
