<?php

require_once 'db-config/security.php';

// If logged in but profile incomplete, redirect to complete profile
if (!isLoggedIn()) {
    header('Location: logout/');
    exit;
}
$type = $_GET['type'] ?? '';
$taskId = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['assignment','quiz']) || !$taskId) {
    die('Invalid request');
}

/* ==============================
   FETCH TASK
================================ */
if ($type === 'assignment') {
    $taskStmt = $pdo->prepare("SELECT * FROM assignments WHERE id=?");
} else {
    $taskStmt = $pdo->prepare("SELECT * FROM quizzes WHERE id=?");
}
$taskStmt->execute([$taskId]);
$task = $taskStmt->fetch(PDO::FETCH_ASSOC);
if (!$task) die('Task not found');

/* ==============================
   FETCH SUBMISSIONS
================================ */
if ($type === 'assignment') {
    $workStmt = $pdo->prepare("
        SELECT awa.*, s.firstname, s.lastname, s.email
        FROM assignment_work_attachment awa
        JOIN students s ON s.id = awa.student_id
        WHERE awa.assignment_id = ?
        ORDER BY awa.created_at DESC
    ");
} else {
    $workStmt = $pdo->prepare("
        SELECT qwa.*, s.firstname, s.lastname, s.email
        FROM quiz_work_attachment qwa
        JOIN students s ON s.id = qwa.student_id
        WHERE qwa.quiz_id = ?
        ORDER BY qwa.created_at DESC
    ");
}

$workStmt->execute([$taskId]);
$submissions = $workStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_ENV['PAGE_HEADER'] ?></title>
    <link rel="apple-touch-icon" sizes="76x76" href="<?=$_ENV['PAGE_ICON']?>">
    <link rel="icon" type="image/png" href="<?=$_ENV['PAGE_ICON']?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- This is the Google Classroom Style  -->
     <link rel="stylesheet" href="css/classroom.css">
    <!-- This is the Google Classroom Style  -->
</head>

<body class="bg-light">

    <!-- Top Bar -->
    <?php 
        require_once "bars/topbar.php";
    ?>

<div class="container py-4">
<a href="classwork" class="btn btn-secondary w-100" type="button">Back to ClassWork</a>
<hr />
<?= !empty($_GET['topic']) ? '<h5> '.$_GET["topic"].' </h5>' : '' ?>
    <h3><?= htmlspecialchars($task['title']) ?></h3>
    <p class="text-muted"><?= nl2br(htmlspecialchars($task['description'])) ?></p>

    <?php if (!empty($task['file_path'])): ?>
        <button class="btn btn-outline-primary btn-sm view-attachment-btn"
                data-file="<?= htmlspecialchars($task['file_path']) ?>"
                data-title="Task Attachment">
            <i class="bi bi-paperclip"></i> View Attachment
        </button>
    <?php endif; ?>

    <hr>

    <h5>Student Submissions</h5>

    <?php if ($submissions): ?>
        <?php foreach ($submissions as $sub): ?>
            <a href="work_submission_checking?type=<?= $type ?>&task_id=<?= $taskId ?>&work_id=<?= $sub['id'] ?>&topic=<?= isset($_GET['topic']) ? $_GET['topic'] : '' ?>"
               class="text-decoration-none">

                <div class="card mb-3 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">

                        <div>
                            <strong><?= htmlspecialchars($sub['lastname'].' '.$sub['firstname']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($sub['email']) ?></small>
                        </div>

                        <span class="badge <?= $sub['gained_score'] !== null ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= $sub['gained_score'] !== null ? 'Scored ['.$sub['gained_score'].']' : 'Pending' ?>
                        </span>

                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">No submissions yet.</p>
    <?php endif; ?>

</div>

<!-- ===========================
     ATTACHMENT MODAL
=========================== -->
<div class="modal fade" id="attachmentModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="attachmentModalTitle" class="modal-title">Attachment</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="attachmentModalBody"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// setTimeout(() => location.reload(), 10);
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-attachment-btn');
    if (!btn) return;

    const file = btn.dataset.file;
    const title = btn.dataset.title || 'Attachment';

    const modalBody = document.getElementById('attachmentModalBody');
    const modalTitle = document.getElementById('attachmentModalTitle');

    modalTitle.textContent = title;
    modalBody.innerHTML = '';

    const ext = file.split('.').pop().toLowerCase();

    if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
        modalBody.innerHTML = `<img src="${file}" class="img-fluid rounded">`;
    } else if (ext === 'pdf') {
        modalBody.innerHTML = `<iframe src="${file}" style="width:100%;height:80vh;border:none;"></iframe>`;
    } else {
        modalBody.innerHTML = `<p class="text-danger">Unsupported file type</p>`;
    }

    new bootstrap.Modal(document.getElementById('attachmentModal')).show();
});

</script>

</body>
</html>
