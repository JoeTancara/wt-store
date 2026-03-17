<?php
// config/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user']['rol'] === 'admin';
}

function isVendedor() {
    return isLoggedIn() && $_SESSION['user']['rol'] === 'vendedor';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        if (!isLoggedIn()) {
            redirect(BASE_URL . '/login.php');
        } else {
            redirect(BASE_URL . '/admin/dashboard.php');
        }
    }
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function sanitize($value) {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function formatMoney($amount) {
    return 'Bs. ' . number_format(floatval($amount), 2);
}

function formatDate($dateStr) {
    return date('d/m/Y H:i', strtotime($dateStr));
}
