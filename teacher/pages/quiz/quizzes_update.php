<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    if (!isset($_POST['id'], $_POST['question'], $_POST['correct_answer'], $_POST['stage_id'])) {
        throw new Exception("Missing parameters.");
    }

    $id       = (int) $_POST['id'];
    $question = trim($_POST['question']);
    $answer   = trim($_POST['correct_answer']);
    $stageId  = (int) $_POST['stage_id'];

    $stmt = $pdo->prepare("UPDATE quizzes 
                           SET question = :question, correct_answer = :answer, stage_id = :stage_id 
                           WHERE id = :id");
    $stmt->execute([
        ':question' => $question,
        ':answer'   => $answer,
        ':stage_id' => $stageId,
        ':id'       => $id
    ]);

    echo json_encode(['success' => true, 'message' => 'Quiz updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
