<?php
require("db-config/security.php");
header('Content-Type: application/json');

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$due_date = $_POST['due_date'] ?: null;
$points = $_POST['points'] ?: null;
$teacher_id = $_SESSION['user_id'];
$file_path = null;
$status = $_POST['status'] ?? 'published';

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

if (!empty($_FILES['file_path']['name'])) {
    $allowed = ['image/jpeg','image/png','image/jpg','application/pdf'];

    if (!in_array($_FILES['file_path']['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }

    $uploadDir = 'uploads/quizzes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time().'_'.basename($_FILES['file_path']['name']);
    $targetPath = $uploadDir.$fileName;

    move_uploaded_file($_FILES['file_path']['tmp_name'], $targetPath);
    $file_path = $targetPath;
}

$stmt = $pdo->prepare("
    INSERT INTO quizzes (title, description, due_date, points, file_path, teacher_id, status)
                VALUES(:title, :description, :due_date, :points, :file_path, :teacher_id, :status)
");

$stmt->execute([
    ':title' => $title,
    ':description' => $description,
    ':due_date' => $due_date,
    ':points' => $points,
    ':file_path' => $file_path,
    ':teacher_id' => $teacher_id,
    ':status' => $status
]);

echo json_encode([
    'success' => true,
    'message' => 'Quiz created successfully!'
]);

