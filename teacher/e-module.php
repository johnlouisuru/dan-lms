<?php
require_once "db-config/security.php";

// Handle success/error messages
$success_message = '';
$error_message = '';

// Check if user is logged in and is a teacher
if (!isLoggedIn()) {
    header('Location: logout/');
    exit;
}

// If logged in but profile incomplete, redirect to complete profile
if (isLoggedIn() && !isProfileComplete()) {
    header('Location: complete-profile');
    exit;
}

// Get teacher ID from session
$teacher_id = $_SESSION['user_id'];

// Handle form submission for adding module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);
    $module_name = trim($_POST['module_name'] ?? '');
    
    // Validate inputs
    $errors = [];
    
    if (!$topic_id) {
        $errors[] = 'Please select a topic';
    }
    
    if (empty($module_name)) {
        $errors[] = 'Please enter a module name';
    }
    
    // Handle file upload
    if (isset($_FILES['module_file']) && $_FILES['module_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['module_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check if it's a PDF
        if ($file_ext !== 'pdf') {
            $errors[] = 'Only PDF files are allowed';
        }
        
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = 'File size must be less than 10MB';
        }
    } else {
        $errors[] = 'Please select a PDF file to upload';
    }
    
    // If no errors, process the upload
    if (empty($errors)) {
        // Create upload directory if it doesn't exist
        $upload_dir = 'uploads/modules/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
        $file_path = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Save to database
            $query = "INSERT INTO topic_modules (topic_id, module_name, module_path, teacher_id) 
                      VALUES (:topic_id, :module_name, :module_path, :teacher_id)";
            
            $params = [
                ':topic_id' => $topic_id,
                ':module_name' => $module_name,
                ':module_path' => $file_path,
                ':teacher_id' => $teacher_id
            ];
            
            try {
                secure_query($pdo, $query, $params);
                $success_message = 'Module uploaded successfully!';
            } catch (Exception $e) {
                $error_message = 'Failed to save module information to database.';
                // Delete uploaded file if database insert fails
                unlink($file_path);
            }
        } else {
            $error_message = 'Failed to upload file. Please try again.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Handle edit module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $module_id = filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
    $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);
    $module_name = trim($_POST['module_name'] ?? '');
    
    $errors = [];
    
    if (!$module_id) {
        $errors[] = 'Invalid module ID';
    }
    
    if (!$topic_id) {
        $errors[] = 'Please select a topic';
    }
    
    if (empty($module_name)) {
        $errors[] = 'Please enter a module name';
    }
    
    // Check if module belongs to this teacher
    $check_query = "SELECT id, module_path FROM topic_modules WHERE id = :id AND teacher_id = :teacher_id";
    $check_params = [':id' => $module_id, ':teacher_id' => $teacher_id];
    $check_result = secure_query($pdo, $check_query, $check_params);
    
    if ($check_result->rowCount() === 0) {
        $errors[] = 'You do not have permission to edit this module';
    }
    
    if (empty($errors)) {
        // Handle file upload if new file is provided
        $file_path = null;
        $old_module = $check_result->fetch(PDO::FETCH_ASSOC);
        
        if (isset($_FILES['module_file']) && $_FILES['module_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['module_file'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($file_ext !== 'pdf') {
                $errors[] = 'Only PDF files are allowed';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $errors[] = 'File size must be less than 10MB';
            } else {
                // Upload new file
                $upload_dir = 'uploads/modules/';
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
                $new_file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $new_file_path)) {
                    $file_path = $new_file_path;
                    // Delete old file
                    if (file_exists($old_module['module_path'])) {
                        unlink($old_module['module_path']);
                    }
                }
            }
        }
        
        if (empty($errors)) {
            // Update database
            if ($file_path) {
                $query = "UPDATE topic_modules 
                         SET topic_id = :topic_id, module_name = :module_name, module_path = :module_path 
                         WHERE id = :id AND teacher_id = :teacher_id";
                $params = [
                    ':topic_id' => $topic_id,
                    ':module_name' => $module_name,
                    ':module_path' => $file_path,
                    ':id' => $module_id,
                    ':teacher_id' => $teacher_id
                ];
            } else {
                $query = "UPDATE topic_modules 
                         SET topic_id = :topic_id, module_name = :module_name 
                         WHERE id = :id AND teacher_id = :teacher_id";
                $params = [
                    ':topic_id' => $topic_id,
                    ':module_name' => $module_name,
                    ':id' => $module_id,
                    ':teacher_id' => $teacher_id
                ];
            }
            
            try {
                secure_query($pdo, $query, $params);
                $success_message = 'Module updated successfully!';
            } catch (Exception $e) {
                $error_message = 'Failed to update module information.';
                // Delete new file if database update fails
                if ($file_path && file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}

// Handle delete module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $module_id = filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
    
    if ($module_id) {
        // Get module info to delete file
        $select_query = "SELECT module_path FROM topic_modules WHERE id = :id AND teacher_id = :teacher_id";
        $select_params = [':id' => $module_id, ':teacher_id' => $teacher_id];
        $select_result = secure_query($pdo, $select_query, $select_params);
        
        if ($select_result->rowCount() > 0) {
            $module = $select_result->fetch(PDO::FETCH_ASSOC);
            
            // Delete from database
            $delete_query = "DELETE FROM topic_modules WHERE id = :id AND teacher_id = :teacher_id";
            $delete_params = [':id' => $module_id, ':teacher_id' => $teacher_id];
            
            try {
                secure_query($pdo, $delete_query, $delete_params);
                
                // Delete file
                if (file_exists($module['module_path'])) {
                    unlink($module['module_path']);
                }
                
                $success_message = 'Module deleted successfully!';
            } catch (Exception $e) {
                $error_message = 'Failed to delete module.';
            }
        } else {
            $error_message = 'You do not have permission to delete this module.';
        }
    } else {
        $error_message = 'Invalid module ID.';
    }
}

// Fetch all topics for dropdown
$topics_query = "SELECT id, topic_name FROM topics WHERE is_active = 1 ORDER BY topic_name";
$topics = secure_query_no_params($pdo, $topics_query);
$topics_array = $topics->fetchAll(PDO::FETCH_ASSOC); // Store as array for reuse

// Fetch uploaded modules for display with teacher name
$modules_query = "
    SELECT tm.*, t.topic_name, 
           CONCAT(tech.lastname, ', ', tech.firstname) AS teacher_name
    FROM topic_modules tm
    JOIN topics t ON tm.topic_id = t.id
    JOIN teachers tech ON tm.teacher_id = tech.id
    ORDER BY tm.id DESC
";

$modules = secure_query_no_params($pdo, $modules_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_ENV['PAGE_HEADER'] ?> - E-Modules</title>
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
            position: relative;
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
            z-index: 10;
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

        /* Module specific styles */
        .topic-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: #e8f0fe;
            color: #1967d2;
            margin-right: 8px;
        }

        .module-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
            font-size: 13px;
            color: #5f6368;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 8px;
            background: #f1f3f4;
            color: #1a73e8;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .download-btn:hover {
            background: #e8eaed;
            color: #1557b0;
        }

        .teacher-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #5f6368;
            font-size: 12px;
        }

        .edit-delete-buttons {
            display: flex;
            gap: 8px;
            margin-top: 10px;
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
        <div class="group-cover mb-4">
            <div class="group-cover-content">
                <h1><?= $_ENV['PAGE_HEADER'] ?></h1>
                <p>E-Modules - Learning Materials</p>
            </div>

            <div class="group-actions">
                <button class="btn btn-primary btn-sm m-3" data-bs-toggle="modal" data-bs-target="#uploadModuleModal">
                    <i class="bi bi-cloud-upload"></i> Upload Module
                </button>
            </div>
        </div>

        <!-- Modules Section -->
        <div id="modules-feed">
            <div class="section-title" style="margin-top: 30px;">Learning Modules</div>
            
            <?php if ($modules && $modules->rowCount() > 0): ?>
                <?php foreach ($modules as $module): ?>
                    <div class="feed-card position-relative" id="module-<?= $module['id'] ?>">
                        
                        <!-- Three dots menu - Only show for the teacher who owns this module -->
                        <?php if ($module['teacher_id'] == $teacher_id): ?>
                            <div class="dropdown feed-menu">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <!-- In the modules loop, update the edit button section -->
<button class="dropdown-item edit-module-btn"
        data-id="<?= $module['id'] ?>"
        data-topic="<?= $module['topic_id'] ?>"
        data-name="<?= htmlspecialchars($module['module_name']) ?>"
        data-file="<?= htmlspecialchars($module['module_path']) ?>">
    <i class="bi bi-pencil me-2"></i> Edit
</button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-danger delete-module-btn"
                                                data-id="<?= $module['id'] ?>"
                                                data-name="<?= htmlspecialchars($module['module_name']) ?>">
                                            <i class="bi bi-trash me-2"></i> Delete
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Header -->
                        <div class="feed-header">
                            <div class="feed-avatar">
                                <i class="bi bi-file-pdf"></i>
                            </div>
                            <div>
                                <div class="feed-author">
                                    <?= htmlspecialchars($module['module_name']) ?>
                                </div>
                                <div class="feed-time">
                                    <span class="topic-badge">
                                        <i class="bi bi-bookmark"></i> <?= htmlspecialchars($module['topic_name']) ?>
                                    </span>
                                    <span class="teacher-badge">
                                        <i class="bi bi-person-circle"></i> 
                                        <?= htmlspecialchars($module['teacher_name']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="feed-content">
                            <!-- PDF Preview -->
                            <div class="feed-media">
                                <div class="feed-pdf-wrapper view-attachment-btn"
                                    data-file="<?= htmlspecialchars($module['module_path']) ?>"
                                    data-title="<?= htmlspecialchars($module['module_name']) ?>"
                                    style="cursor:pointer;">
                                    <iframe src="<?= htmlspecialchars($module['module_path']) ?>#toolbar=0&view=FitH" 
                                            title="<?= htmlspecialchars($module['module_name']) ?>"></iframe>
                                </div>
                            </div>

                            <!-- Module Meta -->
                            <div class="module-meta">
                                <a href="<?= htmlspecialchars($module['module_path']) ?>" 
                                   class="download-btn" 
                                   download>
                                    <i class="bi bi-download"></i> Download PDF
                                </a>
                                <span class="text-muted">
                                    <i class="bi bi-clock"></i> 
                                    Uploaded on <?= date('M d, Y', strtotime($module['id'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-pdf" style="font-size: 48px; color: #ccc;"></i>
                    <p class="mt-3 text-muted">No modules uploaded yet. Click the "Upload Module" button to get started.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Floating Action Button -->
        <button class="fab-button" title="Upload Module" data-bs-toggle="modal" data-bs-target="#uploadModuleModal">
            <i class="bi bi-cloud-upload"></i>
        </button>

        <!-- Upload Module Modal -->
<div class="modal fade" id="uploadModuleModal" tabindex="-1" aria-labelledby="uploadModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModuleModalLabel">Upload E-Module</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="e-module.php" method="POST" enctype="multipart/form-data" id="uploadModuleForm">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <!-- Topic Dropdown -->
                    <div class="mb-3">
                        <label for="topic_id" class="form-label">Select Topic <span class="text-danger">*</span></label>
                        <select class="form-select" id="topic_id" name="topic_id" required>
                            <option value="">Choose a topic...</option>
                            <?php if (!empty($topics_array)): ?>
                                <?php foreach ($topics_array as $topic): ?>
                                    <option value="<?= $topic['id'] ?>">
                                        <?= htmlspecialchars($topic['topic_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No topics available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Module Name -->
                    <div class="mb-3">
                        <label for="module_name" class="form-label">Module Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="module_name" 
                               name="module_name" 
                               required 
                               placeholder="e.g., Module 1: Introduction to ALS">
                    </div>

                    <!-- File Upload -->
                    <div class="mb-3">
                        <label for="module_file" class="form-label">PDF File <span class="text-danger">*</span></label>
                        <input type="file" 
                               class="form-control" 
                               id="module_file" 
                               name="module_file" 
                               accept=".pdf" 
                               required>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> 
                            Only PDF files are allowed. Maximum file size: 10MB.
                        </div>
                    </div>

                    <!-- Preview Area -->
                    <div id="filePreviewArea" class="mt-3 d-none">
                        <div class="alert alert-info">
                            <i class="bi bi-file-pdf"></i> 
                            Selected file: <span id="fileName"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-cloud-upload"></i> Upload Module
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

        <!-- Edit Module Modal -->
<div class="modal fade" id="editModuleModal" tabindex="-1" aria-labelledby="editModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModuleModalLabel">Edit E-Module</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="e-module.php" method="POST" enctype="multipart/form-data" id="editModuleForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="module_id" id="edit_module_id">
                <div class="modal-body">
                    <!-- Topic Dropdown -->
                    <div class="mb-3">
                        <label for="edit_topic_id" class="form-label">Select Topic <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_topic_id" name="topic_id" required>
                            <option value="">Choose a topic...</option>
                            <?php if (!empty($topics_array)): ?>
                                <?php foreach ($topics_array as $topic): ?>
                                    <option value="<?= $topic['id'] ?>">
                                        <?= htmlspecialchars($topic['topic_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No topics available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Module Name -->
                    <div class="mb-3">
                        <label for="edit_module_name" class="form-label">Module Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="edit_module_name" 
                               name="module_name" 
                               required>
                    </div>

                    <!-- Current File -->
                    <div class="mb-3">
                        <label class="form-label">Current File</label>
                        <div id="currentFileDisplay" class="p-2 bg-light rounded">
                            <i class="bi bi-file-pdf"></i> <span id="currentFileName"></span>
                        </div>
                    </div>

                    <!-- File Upload (Optional for edit) -->
                    <div class="mb-3">
                        <label for="edit_module_file" class="form-label">Replace PDF File (Optional)</label>
                        <input type="file" 
                               class="form-control" 
                               id="edit_module_file" 
                               name="module_file" 
                               accept=".pdf">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> 
                            Leave empty to keep current file. Only PDF files allowed. Max size: 10MB.
                        </div>
                    </div>

                    <!-- Edit Preview Area -->
                    <div id="editFilePreviewArea" class="mt-3 d-none">
                        <div class="alert alert-info">
                            <i class="bi bi-file-pdf"></i> 
                            New file: <span id="editFileName"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> 

        <!-- Delete Module Confirmation Modal -->
        <div class="modal fade" id="deleteModuleModal" tabindex="-1" aria-labelledby="deleteModuleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="deleteModuleModalLabel">Delete Module</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="e-module.php" method="POST" id="deleteModuleForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="module_id" id="delete_module_id">
                        <div class="modal-body">
                            <p>Are you sure you want to delete "<span id="deleteModuleName"></span>"?</p>
                            <p class="text-danger"><small>This action cannot be undone and the PDF file will be permanently removed.</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger" id="deleteSubmitBtn">
                                <i class="bi bi-trash"></i> Delete Module
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Attachment Viewer Modal -->
        <div class="modal fade" id="attachmentModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="attachmentModalTitle">Module Preview</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center" id="attachmentModalBody">
                        <!-- Dynamic content -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Modal -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div class="toast" id="appToast">
                <div class="toast-header">
                    <strong class="me-auto" id="toastTitle"></strong>
                    <button class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body" id="toastMessage"></div>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="classroom" class="nav-item">
                <i class="bi bi-chat-left-text"></i>
                <span>Newsfeed</span>
            </a>
            <a href="#" class="nav-item active">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>E-Modules</span>
            </a>
            <a href="classwork" class="nav-item">
                <i class="bi bi-journal-text"></i>
                <span>ClassWork</span>
            </a>
            <a href="class-student" class="nav-item">
                <i class="bi bi-people-fill"></i>
                <span>Students</span>
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File input preview and validation for upload modal
document.getElementById('module_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewArea = document.getElementById('filePreviewArea');
    const fileName = document.getElementById('fileName');
    
    if (file) {
        // Check file type
        if (file.type !== 'application/pdf') {
            alert('Please upload only PDF files');
            e.target.value = '';
            previewArea.classList.add('d-none');
            return;
        }
        
        // Check file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB');
            e.target.value = '';
            previewArea.classList.add('d-none');
            return;
        }
        
        // Show preview
        fileName.textContent = file.name;
        previewArea.classList.remove('d-none');
    } else {
        previewArea.classList.add('d-none');
    }
});

// File input preview for edit modal
document.getElementById('edit_module_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewArea = document.getElementById('editFilePreviewArea');
    const fileName = document.getElementById('editFileName');
    
    if (file) {
        // Check file type
        if (file.type !== 'application/pdf') {
            alert('Please upload only PDF files');
            e.target.value = '';
            previewArea.classList.add('d-none');
            return;
        }
        
        // Check file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB');
            e.target.value = '';
            previewArea.classList.add('d-none');
            return;
        }
        
        // Show preview
        fileName.textContent = file.name;
        previewArea.classList.remove('d-none');
    } else {
        previewArea.classList.add('d-none');
    }
});

// Edit button click handler
document.querySelectorAll('.edit-module-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const topic = this.dataset.topic;
        const name = this.dataset.name;
        const filePath = this.dataset.file;
        
        console.log('Edit button clicked:', { id, topic, name, filePath }); // For debugging
        
        // Set module ID
        document.getElementById('edit_module_id').value = id;
        
        // Set module name
        document.getElementById('edit_module_name').value = name;
        
        // Set topic dropdown value - METHOD 1: Direct value setting
        const topicSelect = document.getElementById('edit_topic_id');
        if (topicSelect) {
            // First, try setting the value directly
            topicSelect.value = topic;
            console.log('Topic dropdown set to (direct):', topicSelect.value);
            
            // If direct setting didn't work, METHOD 2: Iterate through options
            if (topicSelect.value !== topic && topicSelect.value !== String(topic)) {
                console.log('Direct value set failed, trying option iteration');
                let optionFound = false;
                
                for (let i = 0; i < topicSelect.options.length; i++) {
                    // Compare as strings to ensure type matching
                    if (String(topicSelect.options[i].value) === String(topic)) {
                        topicSelect.options[i].selected = true;
                        optionFound = true;
                        console.log('Found and selected option:', topicSelect.options[i].value, topicSelect.options[i].text);
                        break;
                    }
                }
                
                if (!optionFound) {
                    console.error('No matching option found for topic value:', topic);
                    // List all available options for debugging
                    console.log('Available options:');
                    for (let i = 0; i < topicSelect.options.length; i++) {
                        console.log(`Option ${i}: value="${topicSelect.options[i].value}", text="${topicSelect.options[i].text}"`);
                    }
                }
            }
            
            // METHOD 3: Use jQuery-like approach if available
            if (typeof $ !== 'undefined') {
                $('#edit_topic_id').val(topic).trigger('change');
                console.log('jQuery method used');
            }
            
            // Final check
            console.log('Final selected value:', topicSelect.value);
        } else {
            console.error('Topic select element not found!');
        }
        
        // Extract filename from path
        const fileName = filePath.split('/').pop();
        document.getElementById('currentFileName').textContent = fileName;
        
        // Reset file input and preview
        document.getElementById('edit_module_file').value = '';
        document.getElementById('editFilePreviewArea').classList.add('d-none');
        
        // Show edit modal
        try {
            const editModal = new bootstrap.Modal(document.getElementById('editModuleModal'));
            editModal.show();
        } catch (error) {
            console.error('Error showing modal:', error);
        }
    });
});

// Delete button click handler
document.querySelectorAll('.delete-module-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        
        console.log('Delete button clicked:', { id, name }); // For debugging
        
        document.getElementById('delete_module_id').value = id;
        document.getElementById('deleteModuleName').textContent = name;
        
        // Show delete modal
        try {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModuleModal'));
            deleteModal.show();
        } catch (error) {
            console.error('Error showing modal:', error);
        }
    });
});

// Form submission loading states
document.getElementById('uploadModuleForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading...';
});

document.getElementById('editModuleForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
});

document.getElementById('deleteModuleForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('deleteSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
});

// Attachment Viewer
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.view-attachment-btn');
    if (!btn) return;

    const file = btn.dataset.file;
    const title = btn.dataset.title || 'Module Preview';

    const modalBody = document.getElementById('attachmentModalBody');
    const modalTitle = document.getElementById('attachmentModalTitle');

    modalTitle.textContent = title;
    modalBody.innerHTML = `
        <iframe src="${file}" style="width:100%; height:80vh; border:none;"></iframe>
    `;

    new bootstrap.Modal(document.getElementById('attachmentModal')).show();
});

// Toast function
function showToast(title, message, isError = false) {
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMessage').textContent = message;

    const toastEl = document.getElementById('appToast');
    toastEl.classList.toggle('bg-danger', isError);
    toastEl.classList.toggle('text-white', isError);

    new bootstrap.Toast(toastEl).show();
}

// Clear form when modals are closed
document.getElementById('uploadModuleModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('uploadModuleForm').reset();
    document.getElementById('filePreviewArea').classList.add('d-none');
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload Module';
});

document.getElementById('editModuleModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('editModuleForm').reset();
    document.getElementById('editFilePreviewArea').classList.add('d-none');
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-save"></i> Save Changes';
    
    // Clear any selected values
    document.getElementById('edit_topic_id').value = '';
    document.getElementById('edit_module_name').value = '';
    document.getElementById('currentFileName').textContent = '';
});

document.getElementById('deleteModuleModal').addEventListener('hidden.bs.modal', function() {
    const submitBtn = document.getElementById('deleteSubmitBtn');
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-trash"></i> Delete Module';
    document.getElementById('delete_module_id').value = '';
    document.getElementById('deleteModuleName').textContent = '';
});

// Optional: Add this to verify Bootstrap is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Loaded' : 'Not loaded');
    console.log('Topics dropdown exists:', document.getElementById('edit_topic_id') !== null);
});
    </script>
</body>
</html>