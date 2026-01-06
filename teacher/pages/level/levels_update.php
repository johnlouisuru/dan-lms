<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    if (!isset($_POST['id'], $_POST['level_number'], $_POST['level_name'], $_POST['stage_id'])) {
        throw new Exception("Missing parameters.");
    }

    $id          = (int) $_POST['id'];
    $levelNumber = trim($_POST['level_number']);
    $levelName   = trim($_POST['level_name']);
    $stageId     = (int) $_POST['stage_id'];

    $stmt = $pdo->prepare("UPDATE levels 
                           SET level_number = :number, level_name = :name, stage_id = :stage_id 
                           WHERE id = :id");
    $stmt->execute([
        ':number'   => $levelNumber,
        ':name'     => $levelName,
        ':stage_id' => $stageId,
        ':id'       => $id
    ]);

    echo json_encode(['success' => true, 'message' => 'Level updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
