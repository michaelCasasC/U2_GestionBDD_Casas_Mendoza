<?php
require_once __DIR__ . '/config.php';
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            if (defined('DEBUG') && DEBUG) {
                echo "DB Connection error: " . $e->getMessage();
            }
            throw $e;
        }
    }
    return $pdo;
}
