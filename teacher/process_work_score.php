<?php
require "db-config/security.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$type = $data['type'];
$workId = $data['work_id'];
$score = $data['score'];
$comment = trim($data['comment']);
$teacherId = $_SESSION['user_id'];

if ($type === 'assignment') {
    $stmt = $pdo->prepare("
        UPDATE assignment_work_attachment
        SET gained_score = ?,
            teacher_comment = ?,
            teacher_id = ?
        WHERE id = ?
    ");
} else {
    $stmt = $pdo->prepare("
        UPDATE quiz_work_attachment
        SET gained_score = ?,
            teacher_comment = ?,
            teacher_id = ?
        WHERE id = ?
    ");
}

$stmt->execute([$score, $comment, $teacherId, $workId]);

echo json_encode([
    'success' => true,
    'message' => 'Work returned successfully'
]);
