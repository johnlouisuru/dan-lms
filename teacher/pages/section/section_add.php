<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    if (!isset($_POST['section_name'])) {
        throw new Exception("Section name is required.");
    }

    $name = trim($_POST['section_name']);
    $teacherId = !empty($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : null;

    $stmt = $pdo->prepare("INSERT INTO sections (section_name, teacher_id) VALUES (:name, :teacher_id)");
    $stmt->execute([
        ':name' => $name,
        ':teacher_id' => $teacherId
    ]);

    echo json_encode(['success' => true, 'message' => 'Section added successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
