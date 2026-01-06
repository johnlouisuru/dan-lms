<?php
require "db-config/security.php";
header('Content-Type: application/json');

$id    = $_POST['id'];
$type  = $_POST['type'];
$table = $type === 'quiz' ? 'quizzes' : 'assignments';
$teacher_id = $_SESSION['user_id'];

$file_path = null;

if (!empty($_FILES['file']['name'])) {
    $dir = "uploads/$table/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $name = time().'_'.$_FILES['file']['name'];
    move_uploaded_file($_FILES['file']['tmp_name'], $dir.$name);
    $file_path = $dir.$name;
}

$sql = "
UPDATE $table SET
 title = :title,
 description = :description,
 topic_id = :topic,
 points = :points,
 due_date = :due,
 status = :status,
 teacher_id = :teacher_id
 ".($file_path ? ", file_path = :file" : "")."
 WHERE id = :id
";

$stmt = $pdo->prepare($sql);

$params = [
 ':title' => $_POST['title'],
 ':description' => $_POST['description'],
 ':topic' => $_POST['topic_id'] ?: null,
 ':points' => $_POST['points'] ?: null,
 ':due' => $_POST['due_date'] ?: null,
 ':status' => $_POST['status'],
 ':teacher_id' => $teacher_id,
 ':id' => $id
];

if ($file_path) $params[':file'] = $file_path;

$stmt->execute($params);

echo json_encode(['success' => true, 'message' => 'Updated successfully']);
