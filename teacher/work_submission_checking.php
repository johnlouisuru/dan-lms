<?php
require "db-config/security.php";

$type    = $_GET['type'];
$taskId  = (int) $_GET['task_id'];
$workId  = (int) $_GET['work_id'];
$topicName  = htmlspecialchars($_GET['topic']);
$teacherId = $_SESSION['user_id'];

if ($type === 'assignment') {
    $stmt = $pdo->prepare("
        SELECT awa.*, s.lastname, s.firstname
        FROM assignment_work_attachment awa
        JOIN students s ON awa.student_id = s.id
        WHERE awa.id = ?
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT qwa.*, s.lastname, s.firstname
        FROM quiz_work_attachment qwa
        JOIN students s ON qwa.student_id = s.id
        WHERE qwa.id = ?
    ");
}

$stmt->execute([$workId]);
$work = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$work) die('Submission not found');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_ENV['PAGE_HEADER'] ?></title>
    <link rel="apple-touch-icon" sizes="76x76" href="<?= $_ENV['PAGE_ICON'] ?>">
    <link rel="icon" type="image/png" href="<?= $_ENV['PAGE_ICON'] ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- This is the Google Classroom Style  -->
    <link rel="stylesheet" href="css/classroom.css">
    <!-- This is the Google Classroom Style  -->
</head>

<body>
    <!-- Top Bar -->
    <?php
    require_once "bars/topbar.php";
    ?>
    <hr />

    <!-- Content Area -->
    <div class="content-area" id="contentArea">
        <a href="task-list?type=<?= $type ?>&id=<?= $taskId ?>&topic=<?= $topicName ?>" class="btn btn-secondary w-100" type="button">Back to ClassWork</a>
        <!-- Newsfeed Section -->
        <div class="container mt-4">
            <h5><?= htmlspecialchars($_GET['topic']) ?></h5>
            <h4><?= htmlspecialchars($work['lastname'] . ' ' . $work['firstname']) ?></h4>

            <?php if ($work['file_path']): ?>
                <button class="btn btn-outline-primary btn-sm view-attachment-btn"
                    data-file="<?= htmlspecialchars('../final/'.$work['file_path']) ?>"
                    data-title="Student Work">
                    <i class="bi bi-eye"></i> View Attachment
                </button>
            <?php endif; ?>

            <?php if ($work['comment']): ?>
                <p class="mt-3">
                    <strong>Student Comment:</strong><br>
                    <?= nl2br(htmlspecialchars($work['comment'])) ?>
                </p>
            <?php endif; ?>

            <hr>

            <div class="mb-3">
                <label class="form-label">Points</label>
                <input type="number"
                    class="form-control"
                    id="scoreInput"
                    value="<?= htmlspecialchars($work['gained_score']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Teacher Comment</label>
                <textarea class="form-control"
                    id="teacherComment"><?= htmlspecialchars($work['teacher_comment']) ?></textarea>
            </div>

            <button class="btn btn-success" id="returnWorkBtn">
                <i class="bi bi-arrow-return-left"></i> Return Work
            </button>

            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div id="appToast" class="toast">
                    <div class="toast-body"></div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        document.getElementById('returnWorkBtn').addEventListener('click', (event) => {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Saving...';

            fetch('process_work_score.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: '<?= $type ?>',
                        work_id: '<?= $workId ?>',
                        score: document.getElementById('scoreInput').value,
                        comment: document.getElementById('teacherComment').value
                    })
                })
                .then(res => res.json())
                .then(r => {
                    showToast(r.success ? 'success' : 'danger', r.message);
                    btn.disabled = false;
                    btn.textContent = 'Return Work';
                });
        });

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.view-attachment-btn');
            if (!btn) return;

            e.preventDefault();

            const file  = btn.dataset.file;
            const title = btn.dataset.title || 'Attachment';

            const modalBody  = document.getElementById('attachmentModalBody');
            const modalTitle = document.getElementById('attachmentModalTitle');

            modalTitle.textContent = title;
            modalBody.innerHTML = '';

            const ext = file.split('.').pop().toLowerCase();

            if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                modalBody.innerHTML = `
                    <img src="${file}" class="img-fluid rounded" style="max-height:80vh">
                `;
            } else if (ext === 'pdf') {
                modalBody.innerHTML = `
                    <iframe src="${file}#toolbar=0"
                            style="width:100%;height:80vh;border:none;"></iframe>
                `;
            } else {
                modalBody.innerHTML = `
                    <p class="text-danger">Unsupported file type</p>
                `;
            }

            new bootstrap.Modal(document.getElementById('attachmentModal')).show();
        });

        function showToast(type, message) {
            const toastEl = document.getElementById('appToast');
            toastEl.className = `toast align-items-center text-bg-${type}`;
            toastEl.querySelector('.toast-body').textContent = message;
            new bootstrap.Toast(toastEl).show();
        }
    </script>

        <!-- ATTACHMENT MODAL -->
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

</body>