<?php
require("db-config/security.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$topic_id   = (int)($_POST['topic_id'] ?? 0);
$topic_name = trim($_POST['topic_name'] ?? '');

if ($topic_id <= 0 || empty($topic_name)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE topics
        SET topic_name = :topic_name
        WHERE id = :id
    ");

    $stmt->execute([
        ':topic_name' => $topic_name,
        ':id' => $topic_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Topic updated successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
