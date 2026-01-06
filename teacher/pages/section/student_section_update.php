<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    if (!isset($_POST['id'], $_POST['section_id'])) {
        throw new Exception("Missing parameters.");
    }

    $studentId  = (int) $_POST['id'];
    $sectionId  = (int) $_POST['section_id'];

    $stmt = $pdo->prepare("UPDATE students SET section_id = :section_id WHERE id = :id");
    $stmt->execute([
        ':section_id' => $sectionId,
        ':id'         => $studentId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Section updated successfully.',
        'id'      => $studentId,
        'section_id' => $sectionId
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
