<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception("Missing quiz ID.");
    }

    $id = (int) $_POST['id'];

    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Quiz deleted successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
