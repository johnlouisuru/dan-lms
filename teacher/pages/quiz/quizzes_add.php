<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    if (!isset($_POST['question'], $_POST['correct_answer'], $_POST['stage_id'])) {
        throw new Exception("Missing parameters.");
    }

    $question = trim($_POST['question']);
    $answer   = trim($_POST['correct_answer']);
    $stageId  = (int) $_POST['stage_id'];

    $stmt = $pdo->prepare("INSERT INTO quizzes (question, correct_answer, stage_id) 
                           VALUES (:question, :answer, :stage_id)");
    $stmt->execute([
        ':question' => $question,
        ':answer'   => $answer,
        ':stage_id' => $stageId
    ]);

    echo json_encode(['success' => true, 'message' => 'Quiz added successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
