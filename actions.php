<?php
require_once __DIR__ . '/functions.php';

require_login();

$pdo = getDB();
$user = current_user();

$action = $_POST['action'] ?? null;
$id = intval($_POST['id'] ?? 0);

if (!$action || !$id) {
    header('Location: index.php');
    exit;
}

try {

    // CANCELAR (ESTUDIANTE)
    if ($action === 'cancel' && is_role('student')) {

        $stmt = $pdo->prepare("
            EXEC sp_cancel_lab_request
                @request_id = :request_id,
                @student_id = :student_id,
                @student_email = :student_email
        ");

        $stmt->execute([
            ':request_id'    => $id,
            ':student_id'    => $user['id'],
            ':student_email' => $user['email']
        ]);

        header('Location: student.php');
        exit;
    }

    // ACEPTAR / RECHAZAR (PROFESOR)
    if (($action === 'accept' || $action === 'reject') && is_role('professor')) {

        $newStatus = ($action === 'accept') ? 'ACCEPTED' : 'REJECTED';

        $stmt = $pdo->prepare("
            EXEC sp_process_lab_request
                @request_id = :request_id,
                @new_status = :new_status,
                @professor_id = :professor_id,
                @professor_email = :professor_email
        ");

        $stmt->execute([
            ':request_id'      => $id,
            ':new_status'      => $newStatus,
            ':professor_id'    => $user['id'],
            ':professor_email' => $user['email']
        ]);

        header('Location: professor.php');
        exit;
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Error al procesar la solicitud');
}

header('Location: index.php');
exit;
