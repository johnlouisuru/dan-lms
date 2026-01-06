<?php
require "db-config/security.php";
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$type   = $data['type'] ?? '';
$taskId = (int) ($data['task_id'] ?? 0);
$studentId = $_SESSION['user_id'];

if (!$taskId || !in_array($type, ['assignment', 'quiz'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($type === 'assignment') {
    $stmt = $pdo->prepare("
        DELETE FROM assignment_work_attachment
        WHERE assignment_id = ? AND student_id = ?
    ");
} else {
    $stmt = $pdo->prepare("
        DELETE FROM quiz_work_attachment
        WHERE quiz_id = ? AND student_id = ?
    ");
}

$stmt->execute([$taskId, $studentId]);

echo json_encode([
    'success' => true,
    'message' => 'Submission deleted successfully'
]);
