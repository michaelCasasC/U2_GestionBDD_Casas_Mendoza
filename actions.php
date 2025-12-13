<?php
require_once __DIR__ . '/functions.php';
require_login();

$pdo = getDB();
$user = current_user();

$action = $_POST['action'] ?? null;
$id = $_POST['id'] ?? null;

if (!$action || !$id) {
    header('Location: index.php');
    exit;
}

if ($action === 'cancel' && is_role('student')) {
    // student cancels own request
    $stmt = $pdo->prepare("SELECT * FROM lab_requests WHERE id = ? AND student_id = ?");
    $stmt->execute([$id, $user['id']]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        $stmt = $pdo->prepare("UPDATE lab_requests SET status='CANCELLED', updated_at=SYSUTCDATETIME() WHERE id = ?");
        $stmt->execute([$id]);
        audit_log($user['id'], $user['email'], $user['role'], 'DELETE', 'lab_requests', $id, json_encode(['reason'=>'student_cancel']));
    }
    header('Location: student.php');
    exit;
}

if ($action === 'accept' && is_role('professor')) {
    $stmt = $pdo->prepare("UPDATE lab_requests SET status='ACCEPTED', processed_by = ?, updated_at=SYSUTCDATETIME() WHERE id = ?");
    $stmt->execute([$user['id'], $id]);
    audit_log($user['id'], $user['email'], $user['role'], 'ACCEPT', 'lab_requests', $id, json_encode(['professor_id'=>$user['id']]));
    header('Location: professor.php');
    exit;
}

if ($action === 'reject' && is_role('professor')) {
    $stmt = $pdo->prepare("UPDATE lab_requests SET status='REJECTED', processed_by = ?, updated_at=SYSUTCDATETIME() WHERE id = ?");
    $stmt->execute([$user['id'], $id]);
    audit_log($user['id'], $user['email'], $user['role'], 'REJECT', 'lab_requests', $id, json_encode(['professor_id'=>$user['id']]));
    header('Location: professor.php');
    exit;
}

header('Location: index.php');
exit;
