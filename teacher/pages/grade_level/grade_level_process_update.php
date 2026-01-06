<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    if (!isset($_POST['id'], $_POST['grade_level'])) {
        throw new Exception("Missing parameters.");
    }

    $studentId   = (int) $_POST['id'];
    $gradeLevelId = (int) $_POST['grade_level'];

    $stmt = $pdo->prepare("UPDATE students SET grade_level = :grade_level WHERE id = :id");
    $stmt->execute([
        ':grade_level' => $gradeLevelId,
        ':id'          => $studentId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Grade level updated successfully.',
        'id'      => $studentId,
        'grade_level' => $gradeLevelId
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
