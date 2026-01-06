<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception("Missing teacher ID.");
    }

    $id = (int) $_POST['id'];

    // Soft delete: set is_active = 0
    $stmt = $pdo->prepare("UPDATE teachers SET is_active = 0 WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode([
        'success' => true,
        'message' => 'Teacher deleted successfully (soft delete).',
        'id'      => $id
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
