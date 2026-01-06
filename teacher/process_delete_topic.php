<?php
require("db-config/security.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
    exit;
}

$topic_id = $_POST['topic_id'] ?? null;

if (!$topic_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing topic ID.'
    ]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE topics
        SET is_active = 0
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $topic_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Topic deleted.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error.'
    ]);
}
