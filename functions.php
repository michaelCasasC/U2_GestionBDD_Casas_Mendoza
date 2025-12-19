<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

/* =========================
   CONEXIÓN A BASE DE DATOS
========================= */
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                DB_DSN,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                die("Error de conexión: " . $e->getMessage());
            }
            die("Error de conexión a la base de datos.");
        }
    }

    return $pdo;
}

/* =========================
   SESIÓN Y AUTENTICACIÓN
========================= */

function require_login() {
    if (!isset($_SESSION['user'])) {
        header('Location: index.php');
        exit;
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_role($role) {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
}

function require_role($role) {
    if (!is_role($role)) {
        die('Acceso denegado');
    }
}

/* =========================
   AUDITORÍA
========================= */
function audit_log(
    $user_id,
    $user_email,
    $user_role,
    $action,
    $target_table = null,
    $target_id = null,
    $details = null
) {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        INSERT INTO audit_logs
        (user_id, user_email, user_role, action, target_table, target_id, details, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME())
    ");

    $stmt->execute([
        $user_id,
        $user_email,
        $user_role,
        $action,
        $target_table,
        $target_id,
        $details
    ]);
}
