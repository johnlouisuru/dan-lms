<?php
require_once "db-config/security.php";

// If logged in but profile incomplete, redirect to complete profile
if (isLoggedIn() && !isProfileComplete()) {
    header('Location: complete-profile');
    exit;
}

// If not logged in, redirect to logout
if (!isLoggedIn()) {
    header('Location: logout/');
    exit;
}

// Get user info to display appropriate greeting
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'] ?? '';

// Get teacher information
$teacher_query = "SELECT id, firstname, lastname FROM teachers WHERE email = :email";
$teacher_params = [':email' => $user_email];
$teacher_result = secure_query($pdo, $teacher_query, $teacher_params);

if ($teacher_result->rowCount() === 0) {
    // Not a teacher, redirect
    header('Location: logout/');
    exit;
}

$teacher = $teacher_result->fetch(PDO::FETCH_ASSOC);
$teacher_id = $teacher['id'];
$user_name = $teacher['firstname'] . ' ' . $teacher['lastname'];

// Handle success/error messages
$success_message = '';
$error_message = '';

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
$topics_result = secure_query_no_params($pdo, $topics_query);
$topics = $topics_result->fetchAll(PDO::FETCH_ASSOC);

// Fetch all modules with teacher and topic information
$modules_query = "
    SELECT 
        tm.*,
        t.topic_name,
        CONCAT(tech.lastname, ', ', tech.firstname) AS teacher_name,
        tech.id AS teacher_id,
        tech.email AS teacher_email
    FROM topic_modules tm
    JOIN topics t ON tm.topic_id = t.id
    JOIN teachers tech ON tm.teacher_id = tech.id
    WHERE t.is_active = 1
    ORDER BY tm.id DESC
";

$modules_result = secure_query_no_params($pdo, $modules_query);
$modules = $modules_result->fetchAll(PDO::FETCH_ASSOC);

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

    <!-- This is the Google Classroom Style -->
    <link rel="stylesheet" href="css/classroom.css">
    
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background: #f0f2f5;
        }
        
        .content-area {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            padding-bottom: 80px;
        }
        
        .group-cover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 40px 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .group-cover h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .group-cover p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .upload-btn {
            position: absolute;
            bottom: 30px;
            right: 30px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .module-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        
        .module-menu {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
        }
        
        .module-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .module-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .module-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
            line-height: 1.3;
            flex: 1;
            padding-right: 30px;
        }
        
        .module-topic {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            background: #e8f0fe;
            color: #1967d2;
            margin-bottom: 12px;
        }
        
        .module-teacher {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 0.9rem;
            color: #5f6368;
        }
        
        .teacher-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            color: #333;
        }
        
        .module-preview {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 ratio */
            border-radius: 12px;
            overflow: hidden;
            background: #f1f3f4;
            margin: 12px 0;
            cursor: pointer;
        }
        
        .module-preview iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            pointer-events: none; /* Prevent interaction with iframe */
        }
        
        .preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .module-preview:hover .preview-overlay {
            opacity: 1;
        }
        
        .preview-btn {
            background: rgba(255,255,255,0.9);
            color: #667eea;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .module-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }
        
        .module-date {
            font-size: 0.8rem;
            color: #9aa0a6;
        }
        
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            background: #f1f3f4;
            color: #1a73e8;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .download-btn:hover {
            background: #e8eaed;
            color: #1557b0;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 12px 20px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #666;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .nav-item i {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        .nav-item.active {
            color: #667eea;
        }
        
        .nav-item:hover {
            color: #764ba2;
        }
        
        .search-box {
            position: relative;
            max-width: 300px;
            width: 100%;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa0a6;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            background: white;
            color: #5f6368;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-btn:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }
        
        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #777;
        }
        
        .toast-container {
            z-index: 1100;
        }
        
        /* Floating Action Button */
        .fab-button {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 999;
        }
        
        .fab-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(102,126,234,0.5);
        }
        
        @media (max-width: 768px) {
            .group-cover h1 {
                font-size: 1.8rem;
            }
            
            .group-cover p {
                font-size: 1rem;
            }
            
            .upload-btn {
                position: static;
                margin-top: 20px;
                text-align: right;
            }
            
            .section-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .module-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-buttons {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 5px;
            }
            
            .filter-btn {
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <?php require_once "bars/topbar.php"; ?>

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
        <!-- Cover Header -->
        <div class="group-cover mb-4">
            <div class="group-cover-content">
                <h1>Welcome, <?= htmlspecialchars($user_name) ?>!</h1>
                <p>E-Modules Library - Manage and Share Learning Materials</p>
            </div>
            <div class="upload-btn">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#uploadModuleModal">
                    <i class="bi bi-cloud-upload"></i> Upload Module
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-filter="all">All Modules</button>
                        <?php foreach ($topics as $topic): ?>
                            <button class="filter-btn" data-filter="topic-<?= $topic['id'] ?>">
                                <?= htmlspecialchars($topic['topic_name']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search modules...">
                        <i class="bi bi-search"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modules Grid -->
        <div class="section-title">
            <span>Available Modules (<?= count($modules) ?>)</span>
        </div>

        <?php if (empty($modules)): ?>
            <div class="empty-state">
                <i class="bi bi-file-earmark-pdf"></i>
                <h3>No Modules Available</h3>
                <p>Click the "Upload Module" button to get started!</p>
            </div>
        <?php else: ?>
            <div class="module-grid" id="moduleGrid">
                <?php foreach ($modules as $module): ?>
                    <div class="module-card" 
                         data-topic="<?= $module['topic_id'] ?>"
                         data-title="<?= strtolower(htmlspecialchars($module['module_name'])) ?>"
                         data-teacher="<?= strtolower(htmlspecialchars($module['teacher_name'])) ?>">
                        
                        <!-- Three dots menu - Only show for the teacher who owns this module -->
                        <?php if ($module['teacher_id'] == $teacher_id): ?>
                            <div class="dropdown module-menu">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
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

                        <div class="module-header">
                            <div class="module-icon">
                                <i class="bi bi-file-pdf"></i>
                            </div>
                            <h3 class="module-title"><?= htmlspecialchars($module['module_name']) ?></h3>
                        </div>

                        <div class="module-topic">
                            <i class="bi bi-bookmark"></i> <?= htmlspecialchars($module['topic_name']) ?>
                        </div>

                        <div class="module-teacher">
                            <div class="teacher-avatar">
                                <?= strtoupper(substr($module['teacher_name'], 0, 1)) ?>
                            </div>
                            <span><?= htmlspecialchars($module['teacher_name']) ?></span>
                        </div>

                        <!-- PDF Preview -->
                        <div class="module-preview view-attachment-btn"
                             data-file="<?= htmlspecialchars($module['module_path']) ?>"
                             data-title="<?= htmlspecialchars($module['module_name']) ?>">
                            <iframe src="<?= htmlspecialchars($module['module_path']) ?>#toolbar=0&view=FitH&page=1" 
                                    title="<?= htmlspecialchars($module['module_name']) ?>"></iframe>
                            <div class="preview-overlay">
                                <span class="preview-btn">
                                    <i class="bi bi-eye"></i> Click to Preview
                                </span>
                            </div>
                        </div>

                        <div class="module-footer">
                            <span class="module-date">
                                <i class="bi bi-calendar3"></i> 
                                <?= date('M d, Y', strtotime($module['id'])) ?>
                            </span>
                            <a href="<?= htmlspecialchars($module['module_path']) ?>" 
                               class="download-btn" 
                               download>
                                <i class="bi bi-download"></i> Download
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upload Module Modal -->
    <div class="modal fade" id="uploadModuleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload E-Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="e-module.php" method="POST" enctype="multipart/form-data" id="uploadModuleForm">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <!-- Topic Dropdown -->
                        <div class="mb-3">
                            <label for="topic_id" class="form-label">Select Topic <span class="text-danger">*</span></label>
                            <select class="form-select" id="topic_id" name="topic_id" required>
                                <option value="">Choose a topic...</option>
                                <?php foreach ($topics as $topic): ?>
                                    <option value="<?= $topic['id'] ?>">
                                        <?= htmlspecialchars($topic['topic_name']) ?>
                                    </option>
                                <?php endforeach; ?>
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
                                   placeholder="e.g., Module 1: Introduction">
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
    <div class="modal fade" id="editModuleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit E-Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                <?php foreach ($topics as $topic): ?>
                                    <option value="<?= $topic['id'] ?>">
                                        <?= htmlspecialchars($topic['topic_name']) ?>
                                    </option>
                                <?php endforeach; ?>
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
    <div class="modal fade" id="deleteModuleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                <div class="modal-footer">
                    <a href="#" id="modalDownloadBtn" class="btn btn-primary" download>
                        <i class="bi bi-download"></i> Download PDF
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast" id="appToast">
            <div class="toast-header">
                <strong class="me-auto" id="toastTitle"></strong>
                <button class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastMessage"></div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab-button" title="Upload Module" data-bs-toggle="modal" data-bs-target="#uploadModuleModal">
        <i class="bi bi-cloud-upload"></i>
    </button>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="classroom" class="nav-item">
            <i class="bi bi-chat-left-text"></i>
            <span>Newsfeed</span>
        </a><a href="e-module" class="nav-item active">
            <i class="bi bi-journal-bookmark-fill"></i>
            <span>E-Modules</span>
        </a>
        <a href="ane" class="nav-item">
            <i class="bi bi-pencil-square"></i>
            <span>Assessment</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toast function
        function showToast(title, message, isError = false) {
            document.getElementById('toastTitle').textContent = title;
            document.getElementById('toastMessage').textContent = message;
            
            const toastEl = document.getElementById('appToast');
            toastEl.classList.toggle('bg-danger', isError);
            toastEl.classList.toggle('text-white', isError);
            
            new bootstrap.Toast(toastEl).show();
        }

        // Attachment Viewer
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.view-attachment-btn');
            if (!btn) return;

            const file = btn.dataset.file;
            const title = btn.dataset.title || 'Module Preview';

            const modalBody = document.getElementById('attachmentModalBody');
            const modalTitle = document.getElementById('attachmentModalTitle');
            const downloadBtn = document.getElementById('modalDownloadBtn');

            modalTitle.textContent = title;
            downloadBtn.href = file;
            
            modalBody.innerHTML = `
                <iframe src="${file}" style="width:100%; height:80vh; border:none;"></iframe>
            `;

            new bootstrap.Modal(document.getElementById('attachmentModal')).show();
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all filter buttons
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                filterModules();
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            filterModules();
        });

        function filterModules() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const activeFilter = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';
            
            const modules = document.querySelectorAll('.module-card');
            let visibleCount = 0;
            
            modules.forEach(module => {
                const moduleTopic = module.dataset.topic;
                const moduleTitle = module.dataset.title;
                const moduleTeacher = module.dataset.teacher;
                
                // Check topic filter
                let topicMatch = activeFilter === 'all' || activeFilter === `topic-${moduleTopic}`;
                
                // Check search filter
                let searchMatch = searchTerm === '' || 
                                 moduleTitle.includes(searchTerm) || 
                                 moduleTeacher.includes(searchTerm);
                
                if (topicMatch && searchMatch) {
                    module.style.display = 'flex';
                    visibleCount++;
                } else {
                    module.style.display = 'none';
                }
            });
            
            // Show/hide empty state message
            const emptyState = document.querySelector('.empty-state');
            const moduleGrid = document.getElementById('moduleGrid');
            
            if (visibleCount === 0 && !emptyState) {
                // Create and show no results message
                let noResults = document.querySelector('.no-results');
                if (!noResults) {
                    noResults = document.createElement('div');
                    noResults.className = 'empty-state no-results';
                    noResults.innerHTML = `
                        <i class="bi bi-search"></i>
                        <h3>No Matching Modules</h3>
                        <p>Try adjusting your filters or search terms.</p>
                    `;
                    moduleGrid.parentNode.insertBefore(noResults, moduleGrid.nextSibling);
                }
            } else {
                const noResults = document.querySelector('.no-results');
                if (noResults) noResults.remove();
            }
        }

        // File input preview and validation for upload modal
        document.getElementById('module_file')?.addEventListener('change', function(e) {
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
        document.getElementById('edit_module_file')?.addEventListener('change', function(e) {
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
                
                // Set module ID
                document.getElementById('edit_module_id').value = id;
                
                // Set module name
                document.getElementById('edit_module_name').value = name;
                
                // Set topic dropdown value
                const topicSelect = document.getElementById('edit_topic_id');
                if (topicSelect) {
                    topicSelect.value = topic;
                }
                
                // Extract filename from path
                const fileName = filePath.split('/').pop();
                document.getElementById('currentFileName').textContent = fileName;
                
                // Reset file input and preview
                document.getElementById('edit_module_file').value = '';
                document.getElementById('editFilePreviewArea').classList.add('d-none');
                
                // Show edit modal
                new bootstrap.Modal(document.getElementById('editModuleModal')).show();
            });
        });

        // Delete button click handler
        document.querySelectorAll('.delete-module-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                
                document.getElementById('delete_module_id').value = id;
                document.getElementById('deleteModuleName').textContent = name;
                
                // Show delete modal
                new bootstrap.Modal(document.getElementById('deleteModuleModal')).show();
            });
        });

        // Form submission loading states
        document.getElementById('uploadModuleForm')?.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading...';
        });

        document.getElementById('editModuleForm')?.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('editSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        });

        document.getElementById('deleteModuleForm')?.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('deleteSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
        });

        // Clear form when modals are closed
        document.getElementById('uploadModuleModal')?.addEventListener('hidden.bs.modal', function() {
            document.getElementById('uploadModuleForm')?.reset();
            document.getElementById('filePreviewArea')?.classList.add('d-none');
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload Module';
            }
        });

        document.getElementById('editModuleModal')?.addEventListener('hidden.bs.modal', function() {
            document.getElementById('editModuleForm')?.reset();
            document.getElementById('editFilePreviewArea')?.classList.add('d-none');
            const submitBtn = document.getElementById('editSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-save"></i> Save Changes';
            }
            
            // Clear any selected values
            document.getElementById('edit_topic_id').value = '';
            document.getElementById('edit_module_name').value = '';
            document.getElementById('currentFileName').textContent = '';
        });

        document.getElementById('deleteModuleModal')?.addEventListener('hidden.bs.modal', function() {
            const submitBtn = document.getElementById('deleteSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-trash"></i> Delete Module';
            }
            document.getElementById('delete_module_id').value = '';
            document.getElementById('deleteModuleName').textContent = '';
        });

        // Initialize filter on page load
        filterModules();
    </script>
</body>
</html>