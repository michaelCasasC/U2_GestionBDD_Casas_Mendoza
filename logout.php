<?php
require_once __DIR__ . '/functions.php';
$user = current_user();
if ($user) {
    audit_log($user['id'], $user['email'], $user['role'], 'LOGOUT', null, null, json_encode(['ip'=>$_SERVER['REMOTE_ADDR'] ?? '']));
}
session_destroy();
header('Location: index.php');
exit;
