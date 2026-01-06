<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    if (!isset($_POST['id'], $_POST['section_name'])) {
        throw new Exception("Missing parameters.");
    }

    $id   = (int) $_POST['id'];
    $name = trim($_POST['section_name']);

    // teacher_id may be empty
    $teacherId = isset($_POST['teacher_id']) && $_POST['teacher_id'] !== ''
        ? (int) $_POST['teacher_id']
        : null;

    // Base query
    $sql = "UPDATE sections SET section_name = :name";
    $params = [
        ':name' => $name,
        ':id'   => $id
    ];

    // Only update teacher_id if NOT empty
    if ($teacherId !== null) {
        $sql .= ", teacher_id = :teacher_id";
        $params[':teacher_id'] = $teacherId;
    }

    $sql .= " WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Section updated successfully.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
