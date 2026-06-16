<?php
date_default_timezone_set('Asia/Bangkok');
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        header('Location: /ias/index.php');
        exit;
    }
}

function require_role($roles) {
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array(current_user()['role'], $roles, true)) {
        header('Location: /ias/index.php');
        exit;
    }
}
