<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /empanadas/index.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['rol'] !== 'admin') {
        header('Location: /empanadas/dashboard.php?error=sin_permiso');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}
