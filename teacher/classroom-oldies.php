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

    <style>
        /* Newsfeed */
.feed-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    transition: box-shadow .2s ease, transform .2s ease;
}

.feed-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

/* Header */
.feed-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.feed-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg,#667eea,#764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    font-size: 14px;
    flex-shrink: 0;
}

.feed-author {
    font-weight: 600;
    font-size: 14px;
}

.feed-time {
    font-size: 12px;
    color: #6c757d;
}

/* Body */
.feed-title {
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 6px;
}

.feed-content {
    font-size: 14px;
    color: #333;
    margin-bottom: 10px;
    line-height: 1.5;
}

/* Attachment */
.feed-attachment {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 8px;
    background: #f1f3f4;
    font-size: 13px;
    border: none;
}

/* 3 dots */
.feed-menu {
    position: absolute;
    top: 12px;
    right: 12px;
}
.feed-media {
    margin-top: 12px;
}

/* Image preview */
.feed-image {
    width: 100%;
    max-height: 420px;
    object-fit: cover;
    border-radius: 12px;
    cursor: zoom-in;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

/* PDF iframe wrapper */
.feed-pdf-wrapper {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 ratio */
    border-radius: 12px;
    overflow: hidden;
    background: #f1f3f4;
}

.feed-pdf-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

.feed-pdf-wrapper::after {
    content: "🔍 View";
    position: absolute;
    bottom: 10px;
    right: 12px;
    background: rgba(0,0,0,.6);
    color: #fff;
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 6px;
}


    </style>



</head>
<body>
    <!-- Top Bar -->
    <?php 
        require_once "bars/topbar.php";
    ?>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Content Area -->
    <div class="content-area" id="contentArea">

    <!-- Facebook Group–Style Cover Header -->
        <div class="group-cover mb-4"
            > 
            <!-- style="background-image: url('assets/img/cover.jpg');" -->

            <div class="group-cover-content">
                <h1><?= $_ENV['PAGE_HEADER'] ?></h1>
                <p>Alternative Learning System (ALS)</p>
                <!-- <div class="group-meta">
                    <i class="bi bi-people-fill"></i> 128 members · Private Group
                </div> -->
            </div>

            <div class="group-actions">
                <!-- <button class="btn btn-light btn-sm me-2">
                    <i class="bi bi-person-plus"></i> Invite
                </button> -->
                <button class="btn btn-primary btn-sm m-3" data-bs-toggle="modal" data-bs-target="#newsfeedModal">
                    <i class="bi bi-pencil-square"></i> Feed
                </button>
            </div>
        </div>

        <!-- Newsfeed Section -->
        <div id="newsfeed">
           
            <div class="section-title" style="margin-top: 30px;">Recent Updates</div>
            <?php
                // Fetch all Newsfeed
                $query_newsfeed = "SELECT newsfeed.created_at AS time_display,
                                          newsfeed.id AS feed_id,
                                          newsfeed.feed_title, newsfeed.feed_content, newsfeed.teacher_id, newsfeed.file_attached_path,
                                          teachers.*
                                    FROM newsfeed
                                    JOIN teachers ON newsfeed.teacher_id = teachers.id
                                    ORDER BY newsfeed.created_at DESC
                                    ";
                $newsfeed = secure_query_no_params($pdo, $query_newsfeed);
            ?>
                <?php if ($newsfeed && $newsfeed->rowCount() > 0): ?>
                    <?php foreach ($newsfeed as $feed): ?>
                        <div class="feed-card position-relative">

                            <?php if ($feed['teacher_id'] == $_SESSION['user_id']): ?>
                                <div class="dropdown feed-menu">
                                    <button class="btn btn-sm btn-light"
                                            data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button type="button"
                                                    class="dropdown-item edit-feed-btn"
                                                    data-id="<?= $feed['feed_id'] ?>"
                                                    data-title="<?= htmlspecialchars($feed['feed_title']) ?>"
                                                    data-content="<?= htmlspecialchars($feed['feed_content']) ?>"
                                                    data-file="<?= htmlspecialchars($feed['file_attached_path'] ?? '') ?>">
                                                <i class="bi bi-pencil me-2"></i> Edit
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button"
                                                    class="dropdown-item text-danger delete-feed-btn"
                                                    data-id="<?= $feed['feed_id'] ?>">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </button>
                                        </li>
                                    </ul>

                                </div>
                            <?php endif; ?>

                            <!-- Header -->
                            <div class="feed-header">
                                <div class="feed-avatar">
                                    <?= strtoupper($feed['firstname'][0] . $feed['lastname'][0]) ?>
                                </div>
                                <div>
                                    <div class="feed-author">
                                        <?= htmlspecialchars($feed['firstname'] . ' ' . $feed['lastname']) ?>
                                    </div>
                                    <div class="feed-time">
                                        <?= date('M d, Y h:i A', strtotime($feed['time_display'])) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="feed-title">
                                <?= htmlspecialchars($feed['feed_title']) ?>
                            </div>

                            <div class="feed-content">
                                <?= nl2br(htmlspecialchars($feed['feed_content'])) ?>
                            </div>

                            <!-- Attachment -->
                            <?php if (!empty($feed['file_attached_path'])): ?>
                                <?php
                                    $ext = strtolower(pathinfo($feed['file_attached_path'], PATHINFO_EXTENSION));
                                ?>
                                <div class="feed-media">

                                    <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                                        <!-- Image -->
                                        <img src="<?= htmlspecialchars($feed['file_attached_path']) ?>"
                                            class="feed-image view-attachment-btn"
                                            data-file="<?= htmlspecialchars($feed['file_attached_path']) ?>"
                                            data-title="Image Preview"
                                            alt="Attachment">

                                    <?php elseif ($ext === 'pdf'): ?>
                                        <div class="feed-pdf-wrapper view-attachment-btn"
                                            data-file="<?= htmlspecialchars($feed['file_attached_path']) ?>"
                                            data-title="PDF Preview"
                                            style="cursor: pointer;">
                                            <iframe src="<?= htmlspecialchars($feed['file_attached_path']) ?>#toolbar=0"></iframe>
                                        </div>
                                    <?php endif; ?>


                                </div>
                            <?php endif; ?>



                        </div>

                        <?php endforeach; ?>

                <?php else: ?>
                    <option value="" disabled>No teacher/s available</option>
                <?php endif; ?>

        </div>

        

   <!-- Floating Action Button -->
    <button class="fab-button" title="Add new" data-bs-toggle="modal" data-bs-target="#newsfeedModal">
        <i class="bi bi-plus-lg"></i>
    </button>

    <!-- Add Newsfeed Modal -->
    <div class="modal fade" id="newsfeedModal" tabindex="-1" aria-labelledby="newsfeedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newsfeedModalLabel">Add New Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_newsfeed.php" method="POST" enctype="multipart/form-data" id="newsfeedForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="feedTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="feedTitle" name="feed_title" required placeholder="Enter post title">
                        </div>
                        <div class="mb-3">
                            <label for="feedContent" class="form-label">Content</label>
                            <textarea class="form-control" id="feedContent" name="feed_content" rows="4" required placeholder="Write your post content here..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="feedFile" class="form-label">Attach File (Optional)</label>
                            <input type="file" class="form-control" id="feedFile" name="file_attached" accept=".pdf,.jpg,.jpeg,.png,.gif">
                            <div class="form-text">Allowed: PDF, JPG, PNG, GIF</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

 <!-- Edit Feed Modal -->
    <div class="modal fade" id="editFeedModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Post</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="editFeedId">

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" id="editFeedTitle" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea id="editFeedContent" class="form-control"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Replace Attachment (optional)</label>
                        <input type="file" id="editFeedFile"
                            class="form-control"
                            accept=".pdf,image/*">
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="saveFeedChangesBtn">
                        Save Changes
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Delete Feed Confirmation Modal -->
    <div class="modal fade" id="deleteFeedModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Post</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Are you sure you want to delete this post?
                    <input type="hidden" id="deleteFeedId">
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" id="confirmDeleteFeedBtn">
                        Delete
                    </button>
                </div>

            </div>
        </div>
    </div>
                    <!-- Toast Modal, Reusable -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast" id="appToast">
            <div class="toast-header">
                <strong class="me-auto" id="toastTitle"></strong>
                <button class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastMessage"></div>
        </div>
    </div>

    <!-- Attachment Viewer Modal -->
    <div class="modal fade" id="attachmentModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="attachmentModalTitle">Attachment</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center" id="attachmentModalBody">
                <!-- Dynamic content -->
            </div>

            </div>
        </div>
    </div>


    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="#" class="nav-item active" >
            <i class="bi bi-chat-left-text"></i>
            <span>Newsfeed</span>
        </a>
        <a href="classwork" class="nav-item" >
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

        /* ==========================
            ATTACHMENT VIEWER
            ========================== */
            document.addEventListener('click', function (e) {

                const btn = e.target.closest('.view-attachment-btn');
                if (!btn) return;

                const file  = btn.dataset.file;
                const title = btn.dataset.title || 'Attachment';

                const modalBody  = document.getElementById('attachmentModalBody');
                const modalTitle = document.getElementById('attachmentModalTitle');

                modalTitle.textContent = title;
                modalBody.innerHTML = '';

                const ext = file.split('.').pop().toLowerCase();

                if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                    modalBody.innerHTML = `
                        <img src="${file}" class="img-fluid rounded">
                    `;
                }
                else if (ext === 'pdf') {
                    modalBody.innerHTML = `
                        <iframe src="${file}" style="width:100%; height:80vh; border:none;"></iframe>
                    `;
                }
                else {
                    modalBody.innerHTML = `<p class="text-danger">Unsupported file</p>`;
                }

                new bootstrap.Modal(document.getElementById('attachmentModal')).show();
            });

        // File input validation
        document.getElementById('feedFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload only PDF or image files (JPG, PNG, GIF)');
                    e.target.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB');
                    e.target.value = '';
                    return;
                }
            }
        });

        function showToast(title, message, isError = false) {
            document.getElementById('toastTitle').textContent = title;
            document.getElementById('toastMessage').textContent = message;

            const toastEl = document.getElementById('appToast');
            toastEl.classList.toggle('bg-danger', isError);
            toastEl.classList.toggle('text-white', isError);

            new bootstrap.Toast(toastEl).show();
        }

        const editFeedModal = new bootstrap.Modal('#editFeedModal');
        const deleteFeedModal = new bootstrap.Modal('#deleteFeedModal');

        /* OPEN EDIT */
        document.addEventListener('click', e => {
            const btn = e.target.closest('.edit-feed-btn');
            if (!btn) return;

            editFeedId.value = btn.dataset.id;
            editFeedTitle.value = btn.dataset.title;
            editFeedContent.value = btn.dataset.content;

            editFeedModal.show();
        });

        /* SAVE EDIT */
        saveFeedChangesBtn.addEventListener('click', () => {
            const fd = new FormData();
            fd.append('action', 'update');
            fd.append('id', editFeedId.value);
            fd.append('feed_title', editFeedTitle.value);
            fd.append('feed_content', editFeedContent.value);

            if (editFeedFile.files.length) {
                fd.append('file_attached_path', editFeedFile.files[0]);
            }

            fetch('process_update_newsfeed.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(r => {
                if (!r.success) {
                    showToast('Error', r.message, true);
                    return;
                }

                showToast('Success', r.message);
                setTimeout(() => location.reload(), 1000);
            });
        });

        /* OPEN DELETE */
        document.addEventListener('click', e => {
            const btn = e.target.closest('.delete-feed-btn');
            if (!btn) return;

            deleteFeedId.value = btn.dataset.id;
            deleteFeedModal.show();
        });

        /* CONFIRM DELETE */
        confirmDeleteFeedBtn.addEventListener('click', () => {
            fetch('process_update_newsfeed.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({
                    action: 'delete',
                    id: deleteFeedId.value
                })
            })
            .then(r => r.json())
            .then(r => {
                if (!r.success) {
                    showToast('Error', r.message, true);
                    return;
                }

                showToast('Deleted', r.message);
                setTimeout(() => location.reload(), 800);
            });
        });


    </script>

</body>
</html>