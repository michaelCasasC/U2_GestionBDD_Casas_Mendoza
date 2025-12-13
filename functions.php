<?php
require_once __DIR__ . '/db.php';

session_start();

function audit_log($user_id, $user_email, $user_role, $action, $target_table = null, $target_id = null, $details = null) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, user_email, user_role, action, target_table, target_id, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_email, $user_role, $action, $target_table, $target_id, $details]);
}

// simple auth helpers
function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        header('Location: index.php');
        exit;
    }
}

function is_role($role_name) {
    $u = current_user();
    return $u && isset($u['role']) && strtolower($u['role']) === strtolower($role_name);
}

function require_role($role_name) {
    if (!is_role($role_name)) {
        die('Access denied.');
    }
}
