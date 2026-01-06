<?php 
require_once "db-config/security.php";

$type      = $_POST['type'] ?? '';
$taskId    = (int) ($_POST['task_id'] ?? 0);
$studentId = $_SESSION['user_id'];
$comment   = trim($_POST['comment'] ?? '');
$isUpdate  = (int) ($_POST['is_update'] ?? 0);

if (!in_array($type, ['assignment', 'quiz']) || !$taskId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$allowed = ['pdf','jpg','jpeg','png','gif'];
$filePath = null;

/* Handle file upload if exists */
if (!empty($_FILES['file']['name'])) {

    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }

    $uploadDir = 'uploads/work/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = uniqid() . '.' . $ext;
    $filePath = $uploadDir . $filename;

    move_uploaded_file($_FILES['file']['tmp_name'], $filePath);
}

/* Decide table + column */
$table     = $type === 'assignment'
    ? 'assignment_work_attachment'
    : 'quiz_work_attachment';

$taskCol   = $type === 'assignment'
    ? 'assignment_id'
    : 'quiz_id';

/* UPDATE */
if ($isUpdate) {

    $sql = "
        UPDATE $table
        SET comment = :comment
    ";

    if ($filePath) {
        $sql .= ", file_path = :file_path";
    }

    $sql .= "
        WHERE $taskCol = :task_id
          AND student_id = :student_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':comment', $comment ?: null);
    $stmt->bindValue(':task_id', $taskId);
    $stmt->bindValue(':student_id', $studentId);

    if ($filePath) {
        $stmt->bindValue(':file_path', $filePath);
    }

    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Work updated successfully!'
    ]);
    exit;
}

/* INSERT */
if (!$filePath) {
    echo json_encode(['success' => false, 'message' => 'File is required']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO $table ($taskCol, file_path, student_id, comment)
    VALUES (?, ?, ?, ?)
");

$stmt->execute([$taskId, $filePath, $studentId, $comment ?: null]);

echo json_encode([
    'success' => true,
    'message' => 'Work submitted successfully!'
]);
