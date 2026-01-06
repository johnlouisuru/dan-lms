<?php
require "db-config/security.php";
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===============================
   UPDATE NEWSFEED
================================ */
if (isset($_POST['action']) && $_POST['action'] === 'update') {

    $id      = (int) $_POST['id'];
    $title   = trim($_POST['feed_title']);
    $content = trim($_POST['feed_content']);

    if ($title === '' || $content === '') {
        echo json_encode(['success'=>false,'message'=>'Title and content required']);
        exit;
    }

    /* 1️⃣ Fetch existing file */
    $stmt = $pdo->prepare("
        SELECT file_attached_path 
        FROM newsfeed 
        WHERE id = ? AND teacher_id = ?
    ");
    $stmt->execute([$id, $user_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['success'=>false,'message'=>'Post not found or unauthorized']);
        exit;
    }

    $oldFile = $existing['file_attached_path'];
    $newFile = null;

    /* 2️⃣ Handle new upload */
    if (!empty($_FILES['file_attached_path']['name'])) {

        $allowed = ['jpg','jpeg','png','gif','pdf'];
        $ext = strtolower(pathinfo($_FILES['file_attached_path']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success'=>false,'message'=>'Invalid file type']);
            exit;
        }

        if (!is_dir('newsfeed')) {
            mkdir('newsfeed', 0777, true);
        }

        $newFile = 'newsfeed/' . uniqid('nf_') . '.' . $ext;

        if (!move_uploaded_file($_FILES['file_attached_path']['tmp_name'], $newFile)) {
            echo json_encode(['success'=>false,'message'=>'Upload failed']);
            exit;
        }

        /* 3️⃣ Delete old file */
        if (!empty($oldFile) && file_exists($oldFile)) {
            unlink($oldFile);
        }
    }

    /* 4️⃣ Update DB */
    $sql = "
        UPDATE newsfeed 
        SET feed_title = ?, feed_content = ?
        ".($newFile ? ", file_attached_path = ?" : "")."
        WHERE id = ? AND teacher_id = ?
    ";

    $params = [$title, $content];
    if ($newFile) $params[] = $newFile;
    $params[] = $id;
    $params[] = $user_id;

    $pdo->prepare($sql)->execute($params);

    echo json_encode(['success'=>true,'message'=>'Post updated successfully']);
    exit;
}

/* ===============================
   DELETE NEWSFEED
================================ */
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['action']) && $data['action'] === 'delete') {

    /* 1️⃣ Fetch file before delete */
    $stmt = $pdo->prepare("
        SELECT file_attached_path 
        FROM newsfeed 
        WHERE id = ? AND teacher_id = ?
    ");
    $stmt->execute([$data['id'], $user_id]);
    $feed = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$feed) {
        echo json_encode(['success'=>false,'message'=>'Post not found or unauthorized']);
        exit;
    }

    /* 2️⃣ Delete file */
    if (!empty($feed['file_attached_path']) && file_exists($feed['file_attached_path'])) {
        unlink($feed['file_attached_path']);
    }

    /* 3️⃣ Delete DB record */
    $pdo->prepare("
        DELETE FROM newsfeed 
        WHERE id = ? AND teacher_id = ?
    ")->execute([$data['id'], $user_id]);

    echo json_encode(['success'=>true,'message'=>'Post deleted successfully']);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid request']);
