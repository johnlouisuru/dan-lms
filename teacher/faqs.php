<?php
require_once "db-config/security.php";

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
$user_name = $teacher['firstname'] . ' ' . $teacher['lastname'];

// Handle success/error messages
$success_message = '';
$error_message = '';

// Handle Add FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    
    $errors = [];
    
    if (empty($question)) {
        $errors[] = 'Question is required';
    }
    
    if (empty($answer)) {
        $errors[] = 'Answer is required';
    }
    
    if (empty($errors)) {
        $query = "INSERT INTO faqs (frequently_asked_question, answer, is_active) VALUES (:question, :answer, 1)";
        $params = [
            ':question' => $question,
            ':answer' => $answer
        ];
        
        try {
            secure_query($pdo, $query, $params);
            $success_message = 'FAQ added successfully!';
        } catch (Exception $e) {
            $error_message = 'Failed to add FAQ. Please try again.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Handle Edit FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $faq_id = filter_input(INPUT_POST, 'faq_id', FILTER_VALIDATE_INT);
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    
    $errors = [];
    
    if (!$faq_id) {
        $errors[] = 'Invalid FAQ ID';
    }
    
    if (empty($question)) {
        $errors[] = 'Question is required';
    }
    
    if (empty($answer)) {
        $errors[] = 'Answer is required';
    }
    
    if (empty($errors)) {
        $query = "UPDATE faqs SET frequently_asked_question = :question, answer = :answer WHERE id = :id";
        $params = [
            ':question' => $question,
            ':answer' => $answer,
            ':id' => $faq_id
        ];
        
        try {
            secure_query($pdo, $query, $params);
            $success_message = 'FAQ updated successfully!';
        } catch (Exception $e) {
            $error_message = 'Failed to update FAQ. Please try again.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Handle Toggle FAQ Status (Active/Inactive)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $faq_id = filter_input(INPUT_POST, 'faq_id', FILTER_VALIDATE_INT);
    $current_status = filter_input(INPUT_POST, 'current_status', FILTER_VALIDATE_INT);
    
    if ($faq_id) {
        $new_status = $current_status ? 0 : 1;
        $query = "UPDATE faqs SET is_active = :status WHERE id = :id";
        $params = [
            ':status' => $new_status,
            ':id' => $faq_id
        ];
        
        try {
            secure_query($pdo, $query, $params);
            $success_message = 'FAQ status updated successfully!';
        } catch (Exception $e) {
            $error_message = 'Failed to update FAQ status.';
        }
    }
}

// Handle Delete FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $faq_id = filter_input(INPUT_POST, 'faq_id', FILTER_VALIDATE_INT);
    
    if ($faq_id) {
        $query = "DELETE FROM faqs WHERE id = :id";
        $params = [':id' => $faq_id];
        
        try {
            secure_query($pdo, $query, $params);
            $success_message = 'FAQ deleted successfully!';
        } catch (Exception $e) {
            $error_message = 'Failed to delete FAQ.';
        }
    }
}

// Fetch all FAQs
$faqs_query = "SELECT * FROM faqs ORDER BY id DESC";
$faqs_result = secure_query_no_params($pdo, $faqs_query);
$faqs = $faqs_result->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_ENV['PAGE_HEADER'] ?> - FAQ Management</title>
    <link rel="apple-touch-icon" sizes="76x76" href="<?=$_ENV['PAGE_ICON']?>">
    <link rel="icon" type="image/png" href="<?=$_ENV['PAGE_ICON']?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
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
            background: linear-gradient(135deg, #34a853 0%, #0f9d58 100%);
            border-radius: 16px;
            padding: 40px 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            box-shadow: 0 10px 30px rgba(52,168,83,0.3);
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
        
        .group-actions {
            position: absolute;
            top: 30px;
            right: 30px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #34a853;
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
        
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
            gap: 20px;
        }
        
        .faq-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
        }
        
        .faq-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        
        .faq-card.inactive {
            opacity: 0.7;
            background: #f8f9fa;
        }
        
        .faq-menu {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
        }
        
        .faq-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-right: 30px;
        }
        
        .faq-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #34a853 0%, #0f9d58 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .faq-question {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
            line-height: 1.3;
            flex: 1;
        }
        
        .faq-answer {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin: 12px 0;
            color: #333;
            font-size: 0.95rem;
            line-height: 1.5;
            border-left: 4px solid #34a853;
        }
        
        .faq-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }
        
        .faq-date {
            font-size: 0.8rem;
            color: #9aa0a6;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-inactive {
            background: #ffebee;
            color: #c62828;
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
            color: #34a853;
        }
        
        .nav-item:hover {
            color: #0f9d58;
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
            border-color: #34a853;
            box-shadow: 0 0 0 3px rgba(52,168,83,0.1);
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
            border-color: #34a853;
        }
        
        .filter-btn.active {
            background: #34a853;
            color: white;
            border-color: #34a853;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            grid-column: 1 / -1;
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
        
        .fab-container {
            position: fixed;
            bottom: 80px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 999;
        }
        
        .fab-button {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #34a853 0%, #0f9d58 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(52,168,83,0.4);
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .fab-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(52,168,83,0.5);
        }
        
        .toast-container {
            z-index: 1100;
        }
        
        @media (max-width: 768px) {
            .group-cover h1 {
                font-size: 1.8rem;
            }
            
            .group-cover p {
                font-size: 1rem;
            }
            
            .group-actions {
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
            
            .faq-grid {
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
                <p>FAQ Management - Create and Manage Frequently Asked Questions</p>
            </div>
            <div class="group-actions">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addFaqModal">
                    <i class="bi bi-plus-lg"></i> Add New FAQ
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-filter="all">All FAQs</button>
                        <button class="filter-btn" data-filter="active">Active</button>
                        <button class="filter-btn" data-filter="inactive">Inactive</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search FAQs...">
                        <i class="bi bi-search"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQs Grid -->
        <div class="section-title">
            <span>FAQs (<?= count($faqs) ?>)</span>
        </div>

        <?php if (empty($faqs)): ?>
            <div class="empty-state">
                <i class="bi bi-question-circle"></i>
                <h3>No FAQs Yet</h3>
                <p>Click the "Add New FAQ" button to create your first FAQ.</p>
            </div>
        <?php else: ?>
            <div class="faq-grid" id="faqGrid">
                <?php foreach ($faqs as $faq): ?>
                    <div class="faq-card <?= $faq['is_active'] ? '' : 'inactive' ?>" 
                         data-status="<?= $faq['is_active'] ? 'active' : 'inactive' ?>"
                         data-question="<?= strtolower(htmlspecialchars($faq['frequently_asked_question'])) ?>"
                         data-answer="<?= strtolower(htmlspecialchars($faq['answer'])) ?>">
                        
                        <!-- Three dots menu -->
                        <div class="dropdown faq-menu">
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item edit-faq-btn"
                                            data-id="<?= $faq['id'] ?>"
                                            data-question="<?= htmlspecialchars($faq['frequently_asked_question']) ?>"
                                            data-answer="<?= htmlspecialchars($faq['answer']) ?>">
                                        <i class="bi bi-pencil me-2 text-primary"></i> Edit
                                    </button>
                                </li>
                                <li>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="faq_id" value="<?= $faq['id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $faq['is_active'] ?>">
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi <?= $faq['is_active'] ? 'bi-eye-slash' : 'bi-eye' ?> me-2 text-warning"></i>
                                            <?= $faq['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </li>
                                <li>
                                    <button class="dropdown-item text-danger delete-faq-btn"
                                            data-id="<?= $faq['id'] ?>"
                                            data-question="<?= htmlspecialchars($faq['frequently_asked_question']) ?>">
                                        <i class="bi bi-trash me-2"></i> Delete
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="faq-header">
                            <div class="faq-icon">
                                <i class="bi bi-question-lg"></i>
                            </div>
                            <h3 class="faq-question"><?= htmlspecialchars($faq['frequently_asked_question']) ?></h3>
                        </div>

                        <div class="faq-answer">
                            <?= nl2br(htmlspecialchars($faq['answer'])) ?>
                        </div>

                        <div class="faq-footer">
                            <span class="faq-date">
                                <i class="bi bi-clock"></i> 
                                Added: <?= date('M d, Y', strtotime($faq['timestamp'])) ?>
                            </span>
                            <span class="status-badge <?= $faq['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $faq['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add FAQ Modal -->
    <div class="modal fade" id="addFaqModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle text-success me-2"></i>
                        Add New FAQ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="faqs.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="question" class="form-label">Question <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="question" 
                                   name="question" 
                                   required 
                                   placeholder="e.g., How do I upload a module?">
                        </div>

                        <div class="mb-3">
                            <label for="answer" class="form-label">Answer <span class="text-danger">*</span></label>
                            <textarea class="form-control" 
                                      id="answer" 
                                      name="answer" 
                                      rows="5" 
                                      required 
                                      placeholder="Provide a detailed answer..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Add FAQ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit FAQ Modal -->
    <div class="modal fade" id="editFaqModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square text-primary me-2"></i>
                        Edit FAQ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="faqs.php" method="POST" id="editFaqForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="faq_id" id="edit_faq_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_question" class="form-label">Question <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="edit_question" 
                                   name="question" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_answer" class="form-label">Answer <span class="text-danger">*</span></label>
                            <textarea class="form-control" 
                                      id="edit_answer" 
                                      name="answer" 
                                      rows="5" 
                                      required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete FAQ Confirmation Modal -->
    <div class="modal fade" id="deleteFaqModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Delete FAQ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="faqs.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="faq_id" id="delete_faq_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this FAQ?</p>
                        <p class="fw-bold" id="deleteFaqQuestion"></p>
                        <p class="text-danger"><small>This action cannot be undone.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete FAQ
                        </button>
                    </div>
                </form>
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
    <div class="fab-container">
        <button class="fab-button" title="Add New FAQ" data-bs-toggle="modal" data-bs-target="#addFaqModal">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="classroom" class="nav-item">
            <i class="bi bi-chat-left-text"></i>
            <span>Newsfeed</span>
        </a>
        <a href="classwork" class="nav-item">
            <i class="bi bi-journal-text"></i>
            <span>ClassWork</span>
        </a>
        <a href="quiz_area" class="nav-item">
            <i class="bi bi-pencil-square"></i>
            <span>Assessment</span>
        </a>
        <a href="e-module" class="nav-item">
            <i class="bi bi-journal-bookmark-fill"></i>
            <span>E-Modules</span>
        </a>
        <a href="faqs" class="nav-item active">
            <i class="bi bi-question-circle"></i>
            <span>FAQs</span>
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

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all filter buttons
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                filterFaqs();
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            filterFaqs();
        });

        function filterFaqs() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const activeFilter = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';
            
            const faqs = document.querySelectorAll('.faq-card');
            let visibleCount = 0;
            
            faqs.forEach(faq => {
                const status = faq.dataset.status;
                const question = faq.dataset.question;
                const answer = faq.dataset.answer;
                
                // Check status filter
                let statusMatch = activeFilter === 'all' || status === activeFilter;
                
                // Check search filter
                let searchMatch = searchTerm === '' || 
                                 question.includes(searchTerm) || 
                                 answer.includes(searchTerm);
                
                if (statusMatch && searchMatch) {
                    faq.style.display = 'block';
                    visibleCount++;
                } else {
                    faq.style.display = 'none';
                }
            });
            
            // Show/hide empty state message
            const faqGrid = document.getElementById('faqGrid');
            const emptyState = document.querySelector('.empty-state');
            
            if (visibleCount === 0 && !emptyState) {
                // Create and show no results message
                let noResults = document.querySelector('.no-results');
                if (!noResults) {
                    noResults = document.createElement('div');
                    noResults.className = 'empty-state no-results';
                    noResults.innerHTML = `
                        <i class="bi bi-search"></i>
                        <h3>No Matching FAQs</h3>
                        <p>Try adjusting your filters or search terms.</p>
                    `;
                    faqGrid.appendChild(noResults);
                }
            } else {
                const noResults = document.querySelector('.no-results');
                if (noResults) noResults.remove();
            }
        }

        // Edit button click handler
        document.querySelectorAll('.edit-faq-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const question = this.dataset.question;
                const answer = this.dataset.answer;
                
                document.getElementById('edit_faq_id').value = id;
                document.getElementById('edit_question').value = question;
                document.getElementById('edit_answer').value = answer;
                
                new bootstrap.Modal(document.getElementById('editFaqModal')).show();
            });
        });

        // Delete button click handler
        document.querySelectorAll('.delete-faq-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const question = this.dataset.question;
                
                document.getElementById('delete_faq_id').value = id;
                document.getElementById('deleteFaqQuestion').textContent = `"${question}"`;
                
                new bootstrap.Modal(document.getElementById('deleteFaqModal')).show();
            });
        });

        // Initialize filter on page load
        filterFaqs();

        // Clear form when modals are closed
        document.getElementById('addFaqModal')?.addEventListener('hidden.bs.modal', function() {
            document.getElementById('question').value = '';
            document.getElementById('answer').value = '';
        });

        document.getElementById('editFaqModal')?.addEventListener('hidden.bs.modal', function() {
            document.getElementById('edit_question').value = '';
            document.getElementById('edit_answer').value = '';
        });
    </script>
</body>
</html>