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

$topic_name = trim($_POST['topic_name'] ?? '');
$is_active  = $_POST['is_active'] ?? 1;

if ($topic_name === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Topic name cannot be empty.'
    ]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        INSERT INTO topics (topic_name, is_active)
        VALUES (:topic_name, :is_active)
    ");

    $stmt->execute([
        ':topic_name' => $topic_name,
        ':is_active'  => $is_active
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Topic created successfully.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error.'
    ]);
}
