<?php
require_once "db-config/security.php";

// Handle success/error messages
$success_message = '';
$error_message = '';

// $is_active_button = 'active';

if (isset($_GET['success'])) {
    $success_message = 'Post created successfully!';
}

if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

// If logged in but profile incomplete, redirect to complete profile
if (isLoggedIn() && !isProfileComplete()) {
    header('Location: complete-profile');
    exit;
}

// If logged in but profile incomplete, redirect to complete profile
if (!isLoggedIn()) {
    header('Location: logout/');
    exit;
}

    $query_topics = "SELECT * FROM topics WHERE is_active = 1";
    $topics = secure_query_no_params($pdo, $query_topics);
    
    require_once "reusable-query/get-assignments-topic-teacher.php";
    require_once "reusable-query/get-quizzes-topic-teacher.php";

    $classwork = [];

    /* Normalize assignments */
    foreach ($assignments as $a) {
        $a['type'] = 'assignment';
        $classwork[] = $a;
    }

    /* Normalize quizzes */
    foreach ($quizzes as $q) {
        $q['type'] = 'quiz';
        $classwork[] = $q;
    }

    /* Sort by newest */
    usort($classwork, function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });


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
<body>
    <!-- Top Bar -->
    <?php 
        require_once "bars/topbar.php";
    ?>

    <!-- Content Area -->
    <div class="content-area" id="contentArea">
        <!-- Facebook Group–Style Cover Header -->
        <div class="group-cover mb-4"> 
            <!-- style="background-image: url('assets/img/cover.jpg');" -->
             <!-- <img src="<?= $_SESSION["profile_picture"] ?>"/> -->

            <div class="group-cover-content ">
                <h1><?= $_ENV['PAGE_HEADER'] ?></h1>
        <hr />
        <h5>TOPICS:</h5>
             <?php if ($topics && $topics->rowCount() > 0): ?>
                    <?php foreach ($topics as $topic): ?>

                        <!-- <h5>Topics:</h5> -->
                <div class="d-flex align-items-center">
                    <!-- Topic name -->
                    <p class="mb-0 fw-semibold flex-grow-1 text-truncate"
                    id="topicText-<?= $topic['id'] ?>">
                        <?= htmlspecialchars($topic['topic_name']) ?>
                    </p>

                    <!-- 3-dots dropdown -->
                        <div class="dropdown ms-auto">
                            <button class="btn btn-sm btn-dark p-1"
                                    type="button"
                                    data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item edit-topic-btn"
                                    href="#"
                                    data-topic-id="<?= $topic['id'] ?>"
                                    data-topic-name="<?= htmlspecialchars($topic['topic_name']) ?>">
                                        <i class="bi bi-pencil me-2"></i> Edit Topic
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item text-danger delete-topic-btn"
                                    href="#"
                                    data-topic-id="<?= $topic['id'] ?>"
                                    data-topic-name="<?= htmlspecialchars($topic['topic_name']) ?>">
                                        <i class="bi bi-trash me-2"></i> Delete Topic
                                    </a>
                                </li>
                            </ul>
                        </div>

                </div>

                <!-- END of Topics -->

                    <?php endforeach; ?>
                <?php else: ?>
                    
                <?php endif; ?>

            </div>

            <!-- <div class="group-actions">
                <button class="btn btn-primary btn-sm m-3" data-bs-toggle="modal" data-bs-target="#optionsModal">
                    <i class="bi bi-pencil-square"></i> Classwork
                </button>
            </div> -->
        </div>

        <!-- Newsfeed Section -->

        <!-- ClassWork Section -->
        <div id="classwork">
            <div class="section-title">To Do</div>

            <?php if (!empty($classwork)): ?>
                <?php foreach ($classwork as $item): ?>

                    <?php
                        $title       = htmlspecialchars($item['title']);
                        $topic       = $item['topic_name'] ?? '';
                        $teacher     = htmlspecialchars($item['teacher_name']);
                        $points      = $item['points'] ?? 0;
                        $file        = $item['file_path'] ?? '';
                        $dueDate     = $item['due_date'];
                        $postedDays  = floor((time() - strtotime($item['created_at'])) / 86400);

                        $icon = $item['type'] === 'quiz'
                            ? 'bi-patch-question'
                            : 'bi-file-earmark-text';

                        $dueBadgeClass = '';
                        if ($dueDate) {
                            $daysLeft = (strtotime($dueDate) - time()) / 86400;
                            $dueBadgeClass = $daysLeft <= 1 ? 'urgent' : 'upcoming';
                        }
                    ?>
            
                <div class="assignment-card todo position-relative">

                <!-- 3 DOTS MENU -->
                    <div class="dropdown position-absolute top-0 end-0 m-2">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item edit-btn"
                                    data-id="<?= $item['id'] ?>"
                                    data-type="<?= $item['type'] ?>"
                                    data-title="<?= htmlspecialchars($item['title']) ?>"
                                    data-description="<?= htmlspecialchars($item['description']) ?>"
                                    data-topic="<?= isset($item['topic_id']) ? $item['topic_id'] : 0 ?>"
                                    data-points="<?= $item['points'] ? $item['points'] : 0 ?>"
                                    data-due="<?= $item['due_date'] ? $item['due_date'] : '' ?>"
                                    data-status="<?= $item['status'] ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </li>

                            <li>
                                <button class="dropdown-item text-danger delete-btn"
                                    data-id="<?= $item['id'] ?>"
                                    data-type="<?= $item['type'] ?>">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </li>
                        </ul>
                    </div>
                    <a href="task-list?type=<?= $item['type'] ?>&id=<?= $item['id'] ?>&topic=<?= $topic ?>" class="text-decoration-none text-dark">
                        <div class="assignment-header">

                            <div class="assignment-icon">
                                <i class="bi <?= $icon ?>"></i>
                            </div>

                            <div class="assignment-info">
                                <div class="assignment-title">
                                    <?= $title ?>
                                </div>

                                <div class="assignment-meta">
                                    <?= $topic ?>
                                    <?= $topic ? '•' : '' ?>
                                    <?= ucfirst($item['type']) ?>
                                    • Posted <?= $postedDays ?> day<?= $postedDays != 1 ? 's' : '' ?> ago
                                </div>

                                <?php if ($dueDate): ?>
                                    <div class="due-badge <?= $dueBadgeClass ?>">
                                        Due: <?= date('M d Y', strtotime($dueDate)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </a>
                </div>
                

                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No assignments or quizzes available.</p>
            <?php endif; ?>
        </div>


   <!-- Floating Action Button -->
    <button class="fab-button" title="Add new" data-bs-toggle="modal" data-bs-target="#optionsModal">
        <i class="bi bi-plus-lg"></i>
    </button>

    <!-- Add Options Modal -->
   <div class="modal fade" id="optionsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Create</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body d-grid gap-2">
                    <button class="btn btn-outline-primary option-btn" data-type="assignment">
                        <i class="bi bi-file-text me-2"></i> Assignment
                    </button>
                    <button class="btn btn-outline-success option-btn" data-type="quiz">
                        <i class="bi bi-ui-checks me-2"></i> Quiz
                    </button>
                    <button class="btn btn-outline-warning">
                        <i class="bi bi-question-circle me-2"></i> Question
                    </button>
                    <button class="btn btn-outline-info">
                        <i class="bi bi-folder me-2"></i> Materials
                    </button>
                    <button class="btn btn-outline-primary w-100 mb-2"
                            data-bs-toggle="modal"
                            data-bs-target="#topicModal">
                        Topic
                    </button>

                </div>

            </div>
        </div>
    </div>
    

    <!-- Assignment Modal -->
     <div class="modal fade" id="assignmentModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">New Assignment</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox"
                                id="assignmentPublish"
                                checked>
                            <label class="form-check-label">
                                Publish immediately
                            </label>
                        </div>

                        <div class="alert alert-danger d-none" id="assignmentError"></div>
                        <div class="alert alert-success d-none" id="assignmentSuccess"></div>

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" id="assignmentTitle" placeholder="Assignment Title" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="assignmentDescription" placeholder="Assignment Description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attach File (optional)</label>
                            <input type="file"
                                id="assignmentFile"
                                class="form-control"
                                accept=".pdf,image/*">
                            <small class="text-muted">
                                Allowed: PDF, JPG, PNG
                            </small>
                        </div>

                        <div class="progress d-none" id="assignmentProgressWrapper">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                id="assignmentProgress"
                                style="width: 0%">
                                0%
                            </div>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">Due Date (optional)</label>
                            <input type="date" id="assignmentDueDate" placeholder="Assignment Due Date" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Points (optional)</label>
                            <input type="number" id="assignmentPoints" placeholder="Assignment Points" class="form-control">
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" id="saveAssignmentBtn">
                            Create Assignment
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Quiz Modal -->
        <div class="modal fade" id="quizModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">New Quiz</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox"
                                id="quizPublish"
                                checked>
                            <label class="form-check-label">
                                Publish immediately
                            </label>
                        </div>
                        <div class="alert alert-danger d-none" id="quizError"></div>
                        <div class="alert alert-success d-none" id="quizSuccess"></div>

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" id="quizTitle" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="quizDescription" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attach File (optional)</label>
                            <input type="file"
                                id="quizFile"
                                class="form-control"
                                accept=".pdf,image/*">
                            <small class="text-muted">
                                Allowed: PDF, JPG, PNG
                            </small>
                        </div>

                        <div class="progress d-none" id="quizProgressWrapper">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                id="quizProgress"
                                style="width: 0%">
                                0%
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Due Date (optional)</label>
                            <input type="date" id="quizDueDate" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Points (optional)</label>
                            <input type="number" id="quizPoints" class="form-control">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success" id="saveQuizBtn">
                            Create Quiz
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Topic Modal -->

        <div class="modal fade" id="topicModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Add Topic</h5>
                        <button type="button" class="btn-close"
                                data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <!-- Topic Name -->
                        <div class="mb-3">
                            <label class="form-label">Topic Name</label>
                            <input type="text"
                                id="topicName"
                                class="form-control"
                                placeholder="Enter topic name">
                        </div>

                        <!-- Active / Inactive -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                type="checkbox"
                                id="topicActive"
                                checked>
                            <label class="form-check-label">
                                Active
                            </label>
                        </div>

                        <!-- Alerts -->
                        <div class="alert alert-danger d-none" id="topicError"></div>
                        <div class="alert alert-success d-none" id="topicSuccess"></div>

                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary"
                                id="saveTopicBtn">Save Topic</button>
                    </div>

                </div>
            </div>
        </div>

        <!-- EDIT CLASSWORK MODAL -->
            <div class="modal fade" id="editTaskModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form id="editForm" class="modal-content" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Classwork</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="edit_id">
                    <input type="hidden" id="edit_type">

                    <div class="mb-2">
                    <label>Title</label>
                    <input type="text" class="form-control" id="edit_title" required>
                    </div>

                    <div class="mb-2">
                    <label>Description</label>
                    <textarea class="form-control" id="edit_description"></textarea>
                    </div>

                    <div class="mb-2">
                    <label>Topic</label>
                    <select class="form-select" id="edit_topic">
                        <?php
                        $topics = $pdo->query("SELECT id, topic_name FROM topics WHERE is_active = 1");
                        foreach ($topics as $t) { ?>
                            <option value='<?= $t['id'] ?>'><?= $t['topic_name'] ?></option>
                        <?php 
                        }
                        ?>
                    </select>
                    </div>

                    <div class="mb-2">
                    <label>Points (optional)</label>
                    <input type="number" class="form-control" id="edit_points">
                    </div>

                    <div class="mb-2">
                    <label>Due Date (optional)</label>
                    <input type="date" class="form-control" id="edit_due">
                    </div>

                    <div class="mb-2">
                    <label>Attachment (optional)</label>
                    <input type="file" class="form-control" id="edit_file">
                    </div>

                    <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" id="edit_status">
                    <label class="form-check-label">Published</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-success">Save Changes</button>
                </div>
                </form>
            </div>
            </div>




    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="classroom" class="nav-item" >
            <i class="bi bi-chat-left-text"></i>
            <span>Newsfeed</span>
        </a>
        <a href="#" class="nav-item active" >
            <i class="bi bi-journal-text"></i>
            <span>ClassWork</span>
        </a>
        <a href="class-student" class="nav-item" >
            <i class="bi bi-people-fill"></i>
            <span>Students</span>
        </a>
    </div>
    <?php 
        // require_once "bars/bottom-bar.php";
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const optionsModal = new bootstrap.Modal('#optionsModal');
            const assignmentModal = new bootstrap.Modal('#assignmentModal');
            const quizModal = new bootstrap.Modal('#quizModal');

            const editModal = new bootstrap.Modal(
                document.getElementById('editTopicModal')
            );

            const editTaskModal = new bootstrap.Modal(document.getElementById('editTaskModal'));

                // OPEN EDIT MODAL
                document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', () => {

                    edit_id.value     = btn.dataset.id;
                    edit_type.value   = btn.dataset.type;
                    edit_title.value  = btn.dataset.title;
                    edit_description.value = btn.dataset.description;
                    edit_topic.value  = btn.dataset.topic;
                    edit_points.value = btn.dataset.points;
                    edit_due.value    = btn.dataset.due;
                    edit_status.checked = btn.dataset.status === 'published';

                    editTaskModal.show();
                });
                });

                // SUBMIT EDIT
                document.getElementById('editForm').addEventListener('submit', e => {
                e.preventDefault();

                const fd = new FormData();
                fd.append('id', edit_id.value);
                fd.append('type', edit_type.value);
                fd.append('title', edit_title.value);
                fd.append('description', edit_description.value);
                fd.append('topic_id', edit_topic.value);
                fd.append('points', edit_points.value);
                fd.append('due_date', edit_due.value);
                fd.append('status', edit_status.checked ? 'published' : 'draft');
                fd.append('file', edit_file.files[0] ?? '');

                fetch('process_update_quiz_or_assignment.php', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(r => {
                    showToast(r.success ? 'success' : 'danger', r.message);
                    if (r.success) location.reload();
                });
                });

                // TOAST
                function showToast(type, msg) {
                const toast = document.getElementById('appToast');
                toast.className = `toast text-bg-${type}`;
                toast.querySelector('.toast-body').textContent = msg;
                new bootstrap.Toast(toast).show();
                }
        
        
    // Open modal
    document.querySelectorAll('.edit-topic-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();

            document.getElementById('editTopicId').value =
                btn.dataset.topicId;

            document.getElementById('editTopicName').value =
                btn.dataset.topicName;

            document.getElementById('editTopicError').classList.add('d-none');
            document.getElementById('editTopicSuccess').classList.add('d-none');

            editModal.show();
        });
    });

    // Save topic (AJAX)
    document.getElementById('editTopicBtn').addEventListener('click', () => {
        const topicId   = document.getElementById('editTopicId').value;
        const topicName = document.getElementById('editTopicName').value.trim();

        if (!topicName) {
            showError("Topic name cannot be empty.");
            return;
        }

        fetch('process_change_topic.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                topic_id: topicId,
                topic_name: topicName
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showError(data.message);
                return;
            }

            // Update UI instantly
            document.getElementById(`topicText-${topicId}`).textContent = topicName;

            document.getElementById('editTopicSuccess').textContent = data.message;
            document.getElementById('editTopicSuccess').classList.remove('d-none');

            setTimeout(() => editModal.hide(), 800);
        })
        .catch(() => showError("Something went wrong."));
    });

    function showError(msg) {
        const el = document.getElementById('editTopicError');
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    let deleteTopicId = null;

    const deleteTopicModal = new bootstrap.Modal(
        document.getElementById('deleteTopicModal')
    );

    // Open delete modal
    document.querySelectorAll('.delete-topic-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();

            deleteTopicId = btn.dataset.topicId;
            document.getElementById('deleteTopicName').textContent =
                btn.dataset.topicName;

            document.getElementById('deleteTopicError').classList.add('d-none');

            deleteTopicModal.show();
        });
    });

    // Confirm delete
    document.getElementById('confirmDeleteTopicBtn').addEventListener('click', () => {

        const errorEl = document.getElementById('deleteTopicError');

        const fd = new FormData();
        fd.append('topic_id', deleteTopicId);

        fetch('process_delete_topic.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(r => {

            if (!r.success) {
                errorEl.textContent = r.message;
                errorEl.classList.remove('d-none');
                return;
            }

            showToast('Topic deleted successfully!', 'success');

            setTimeout(() => {
                deleteTopicModal.hide();
                location.reload();
            }, 800);
        })
        .catch(() => {
            errorEl.textContent = 'Something went wrong.';
            errorEl.classList.remove('d-none');
        });
    });

    // Handle option click
    document.querySelectorAll('.option-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            optionsModal.hide();

            setTimeout(() => {
                if (btn.dataset.type === 'assignment') {
                    assignmentModal.show();
                }
                if (btn.dataset.type === 'quiz') {
                    quizModal.show();
                }
            }, 300);
        });
    });

    // Save Assignment
    document.getElementById('saveAssignmentBtn').addEventListener('click', () => {

        const titleEl       = document.getElementById('assignmentTitle');
        const descEl        = document.getElementById('assignmentDescription');
        const dueEl         = document.getElementById('assignmentDueDate');
        const pointsEl      = document.getElementById('assignmentPoints');
        const fileEl        = document.getElementById('assignmentFile');
        const publishEl     = document.getElementById('assignmentPublish');

        const errorEl       = document.getElementById('assignmentError');
        const successEl     = document.getElementById('assignmentSuccess');
        const progressWrap  = document.getElementById('assignmentProgressWrapper');
        const progressBar   = document.getElementById('assignmentProgress');

        errorEl.classList.add('d-none');
        successEl.classList.add('d-none');

        if (!titleEl.value.trim()) {
            errorEl.textContent = 'Title is required.';
            errorEl.classList.remove('d-none');
            return;
        }

        const fd = new FormData();
        fd.append('title', titleEl.value);
        fd.append('description', descEl.value);
        fd.append('due_date', dueEl.value);
        fd.append('points', pointsEl.value);
        fd.append('status', publishEl.checked ? 'published' : 'draft');

        if (fileEl.files.length > 0) {
            fd.append('file', fileEl.files[0]); // ✅ CORRECT FIELD NAME
        }

        sendFormDataXHR({
            url: 'process_add_assignment.php',
            formData: fd,
            modal: assignmentModal,
            errorEl,
            successEl,
            progressWrap,
            progressBar,
            successMessage: 'Assignment saved successfully!'
        });
    });





    // Save Quiz
    document.getElementById('saveQuizBtn').addEventListener('click', () => {

        const fd = new FormData();
        fd.append('title', quizTitle.value);
        fd.append('description', quizDescription.value);
        fd.append('due_date', quizDueDate.value);
        fd.append('points', quizPoints.value);
        fd.append(
            'status',
            quizPublish.checked ? 'published' : 'draft'
        );

        if (quizFile.files.length > 0) {
            fd.append('file_path', quizFile.files[0]);
        }

        sendFormDataXHR({
            url: 'process_add_quiz.php',
            formData: fd,
            modal: quizModal,
            errorEl: quizError,
            successEl: quizSuccess,
            progressWrap: quizProgressWrapper,
            progressBar: quizProgress,
            successMessage: 'Quiz saved successfully!'
        });
    });

    const topicModal = new bootstrap.Modal(
        document.getElementById('topicModal')
    );

    document.getElementById('saveTopicBtn').addEventListener('click', () => {

        const topicName = document.getElementById('topicName').value.trim();
        const isActive  = document.getElementById('topicActive').checked ? 1 : 0;

        const errorEl   = document.getElementById('topicError');
        const successEl = document.getElementById('topicSuccess');

        errorEl.classList.add('d-none');
        successEl.classList.add('d-none');

        if (topicName === '') {
            errorEl.textContent = 'Topic name is required.';
            errorEl.classList.remove('d-none');
            return;
        }

        const fd = new FormData();
        fd.append('topic_name', topicName);
        fd.append('is_active', isActive);

        fetch('process_add_topic.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(r => {

            if (!r.success) {
                errorEl.textContent = r.message;
                errorEl.classList.remove('d-none');
                return;
            }

            showToast('Topic added successfully!', 'success');

            setTimeout(() => {
                topicModal.hide();
                location.reload();
            }, 1000);
        })
        .catch(() => {
            errorEl.textContent = 'Something went wrong.';
            errorEl.classList.remove('d-none');
        });
    });



    function sendFormDataXHR(config) {

        const {
            url, formData, modal,
            errorEl, successEl,
            progressWrap, progressBar,
            successMessage
        } = config;

        errorEl.classList.add('d-none');
        successEl.classList.add('d-none');

        progressWrap.classList.remove('d-none');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
            }
        };

        xhr.onload = function () {
            progressWrap.classList.add('d-none');

            try {
                const res = JSON.parse(xhr.responseText);

                if (!res.success) {
                    errorEl.textContent = res.message;
                    errorEl.classList.remove('d-none');
                    return;
                }

                showToast(successMessage, 'success');

                setTimeout(() => {
                    modal.hide();
                    location.reload();
                }, 1200);

            } catch {
                errorEl.textContent = 'Server error.';
                errorEl.classList.remove('d-none');
            }
        };

        xhr.send(formData);
    }

    /* Toast helper */
    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('globalToast');
        const toastMsg = document.getElementById('toastMessage');

        toastEl.className = `toast align-items-center text-bg-${type} border-0`;
        toastMsg.textContent = message;

        new bootstrap.Toast(toastEl).show();
    }
});

    </script>

        <div class="modal fade" id="editTopicModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Topic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="editTopicId">

                    <div class="mb-3">
                        <label class="form-label">New Topic Name</label>
                        <input type="text"
                            class="form-control"
                            id="editTopicName"
                            placeholder="Enter new topic name"
                            required>
                    </div>

                    <div class="alert alert-danger d-none" id="editTopicError"></div>
                    <div class="alert alert-success d-none" id="editTopicSuccess"></div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button class="btn btn-primary"
                            id="editTopicBtn">
                        Save Changes
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteTopicModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Topic</h5>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>
                        Are you sure you want to delete
                        <strong id="deleteTopicName"></strong>?
                    </p>

                    <div class="alert alert-danger d-none"
                        id="deleteTopicError"></div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button class="btn btn-danger"
                            id="confirmDeleteTopicBtn">
                        Yes, Delete
                    </button>
                </div>

            </div>
        </div>
    </div>


    <div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="globalToast" class="toast align-items-center text-bg-success border-0">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">
                Success
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

</body>
</html>