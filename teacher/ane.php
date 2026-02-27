<?php
define('DEVELOPMENT_MODE', true); // Add this line at the very top, before requiring security.php
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

// Handle success/error messages
$success_message = '';
$error_message = '';

// Fetch all topics for dropdown
$topics_query = "SELECT id, topic_name FROM topics WHERE is_active = 1 ORDER BY topic_name";
$topics_result = secure_query_no_params($pdo, $topics_query);
$topics_array = $topics_result->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX requests for quiz creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['ajax_action'] === 'save_quiz') {
            // Save quiz header
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $topic_id = !empty($_POST['topic_id']) ? (int)$_POST['topic_id'] : null;
            $time_limit = !empty($_POST['time_limit']) ? (int)$_POST['time_limit'] : null;
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            
            if (empty($title)) {
                throw new Exception('Quiz title is required');
            }
            
            // Insert quiz into final_quizzes table
            $query = "INSERT INTO final_quizzes (title, description, topic_id, teacher_id, time_limit, due_date, status) 
                      VALUES (:title, :description, :topic_id, :teacher_id, :time_limit, :due_date, 'draft')";
            
            $params = [
                ':title' => $title,
                ':description' => $description,
                ':topic_id' => $topic_id,
                ':teacher_id' => $teacher_id,
                ':time_limit' => $time_limit,
                ':due_date' => $due_date
            ];
            
            secure_query($pdo, $query, $params);
            $quiz_id = $pdo->lastInsertId();
            
            echo json_encode(['success' => true, 'quiz_id' => $quiz_id]);
            exit;
            
        } elseif ($_POST['ajax_action'] === 'save_question') {
            // Save a question with its options
            $quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
            $question_text = trim($_POST['question_text'] ?? '');
            $points = filter_input(INPUT_POST, 'points', FILTER_VALIDATE_INT) ?: 1;
            $options = json_decode($_POST['options'] ?? '[]', true);
            $correct_option = filter_input(INPUT_POST, 'correct_option', FILTER_VALIDATE_INT);
            
            if (!$quiz_id) {
                throw new Exception('Invalid quiz ID');
            }
            
            if (empty($question_text)) {
                throw new Exception('Question text is required');
            }
            
            if (count($options) < 2) {
                throw new Exception('At least 2 options are required');
            }
            
            if ($correct_option === null || $correct_option < 0 || $correct_option >= count($options)) {
                throw new Exception('Please select a correct answer');
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Get the next order number
                $order_query = "SELECT COUNT(*) as count FROM quiz_questions WHERE quiz_id = :quiz_id";
                $order_params = [':quiz_id' => $quiz_id];
                $order_result = secure_query($pdo, $order_query, $order_params);
                $order_row = $order_result->fetch(PDO::FETCH_ASSOC);
                $order_number = (int)$order_row['count'] + 1;
                
                // Insert question
                $question_query = "INSERT INTO quiz_questions (quiz_id, question_text, points, order_number) 
                                   VALUES (:quiz_id, :question_text, :points, :order_number)";
                $question_params = [
                    ':quiz_id' => $quiz_id,
                    ':question_text' => $question_text,
                    ':points' => $points,
                    ':order_number' => $order_number
                ];
                
                secure_query($pdo, $question_query, $question_params);
                $question_id = $pdo->lastInsertId();
                
                // Insert options
                foreach ($options as $index => $option_text) {
                    $is_correct = ($index == $correct_option) ? 1 : 0;
                    
                    $option_query = "INSERT INTO quiz_options (question_id, option_text, is_correct, order_number) 
                                     VALUES (:question_id, :option_text, :is_correct, :order_number)";
                    $option_params = [
                        ':question_id' => $question_id,
                        ':option_text' => $option_text,
                        ':is_correct' => $is_correct,
                        ':order_number' => $index + 1
                    ];
                    
                    secure_query($pdo, $option_query, $option_params);
                }
                
                // Update total points in final_quizzes
                $update_points_query = "UPDATE final_quizzes SET total_points = (
                                            SELECT COALESCE(SUM(points), 0) FROM quiz_questions WHERE quiz_id = :quiz_id1
                                        ) WHERE id = :quiz_id2";
                $update_points_params = [
                    ':quiz_id1' => $quiz_id,
                    ':quiz_id2' => $quiz_id
                ];
                
                secure_query($pdo, $update_points_query, $update_points_params);
                
                // Commit transaction
                $pdo->commit();
                
                echo json_encode(['success' => true, 'question_id' => $question_id]);
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
        } elseif ($_POST['ajax_action'] === 'get_questions') {
            $quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
            
            if (!$quiz_id) {
                throw new Exception('Invalid quiz ID');
            }
            
            // Get quiz info from final_quizzes
            $quiz_query = "SELECT fq.*, t.topic_name 
                          FROM final_quizzes fq
                          LEFT JOIN topics t ON fq.topic_id = t.id
                          WHERE fq.id = :quiz_id AND fq.teacher_id = :teacher_id";
            $quiz_params = [
                ':quiz_id' => $quiz_id,
                ':teacher_id' => $teacher_id
            ];
            $quiz_result = secure_query($pdo, $quiz_query, $quiz_params);
            $quiz = $quiz_result->fetch(PDO::FETCH_ASSOC);
            
            if (!$quiz) {
                throw new Exception('Quiz not found');
            }
            
            // Get questions with options
            $questions_query = "
                SELECT q.*, 
                       (SELECT COUNT(*) FROM quiz_options WHERE question_id = q.id) as options_count
                FROM quiz_questions q
                WHERE q.quiz_id = :quiz_id
                ORDER BY q.order_number
            ";
            $questions_params = [':quiz_id' => $quiz_id];
            $questions_result = secure_query($pdo, $questions_query, $questions_params);
            $questions = $questions_result->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($questions as &$question) {
                $options_query = "SELECT * FROM quiz_options WHERE question_id = :question_id ORDER BY order_number";
                $options_params = [':question_id' => $question['id']];
                $options_result = secure_query($pdo, $options_query, $options_params);
                $question['options'] = $options_result->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode(['success' => true, 'quiz' => $quiz, 'questions' => $questions]);
            exit;
            
        } elseif ($_POST['ajax_action'] === 'update_question') {
            $question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
            $question_text = trim($_POST['question_text'] ?? '');
            $points = filter_input(INPUT_POST, 'points', FILTER_VALIDATE_INT) ?: 1;
            $options = json_decode($_POST['options'] ?? '[]', true);
            $correct_option = filter_input(INPUT_POST, 'correct_option', FILTER_VALIDATE_INT);
            
            if (!$question_id) {
                throw new Exception('Invalid question ID');
            }
            
            if (empty($question_text)) {
                throw new Exception('Question text is required');
            }
            
            if (count($options) < 2) {
                throw new Exception('At least 2 options are required');
            }
            
            if ($correct_option === null || $correct_option < 0 || $correct_option >= count($options)) {
                throw new Exception('Please select a correct answer');
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Verify question belongs to teacher's quiz
                $check_query = "
                    SELECT qq.*, fq.id as quiz_id FROM quiz_questions qq
                    JOIN final_quizzes fq ON qq.quiz_id = fq.id
                    WHERE qq.id = :question_id AND fq.teacher_id = :teacher_id
                ";
                $check_params = [
                    ':question_id' => $question_id,
                    ':teacher_id' => $teacher_id
                ];
                
                $check_result = secure_query($pdo, $check_query, $check_params);
                
                if ($check_result->rowCount() === 0) {
                    throw new Exception('You do not have permission to edit this question');
                }
                
                $question = $check_result->fetch(PDO::FETCH_ASSOC);
                $quiz_id = $question['quiz_id'];
                
                // Update question
                $update_query = "UPDATE quiz_questions SET question_text = :question_text, points = :points WHERE id = :id";
                $update_params = [
                    ':question_text' => $question_text,
                    ':points' => $points,
                    ':id' => $question_id
                ];
                
                secure_query($pdo, $update_query, $update_params);
                
                // Delete old options
                $delete_options_query = "DELETE FROM quiz_options WHERE question_id = :question_id";
                $delete_options_params = [':question_id' => $question_id];
                
                secure_query($pdo, $delete_options_query, $delete_options_params);
                
                // Insert new options
                foreach ($options as $index => $option_text) {
                    $is_correct = ($index == $correct_option) ? 1 : 0;
                    
                    $option_query = "INSERT INTO quiz_options (question_id, option_text, is_correct, order_number) 
                                     VALUES (:question_id, :option_text, :is_correct, :order_number)";
                    $option_params = [
                        ':question_id' => $question_id,
                        ':option_text' => $option_text,
                        ':is_correct' => $is_correct,
                        ':order_number' => $index + 1
                    ];
                    
                    secure_query($pdo, $option_query, $option_params);
                }
                
                // Update total points in final_quizzes
                $update_points_query = "UPDATE final_quizzes SET total_points = (
                                            SELECT COALESCE(SUM(points), 0) FROM quiz_questions WHERE quiz_id = :quiz_id1
                                        ) WHERE id = :quiz_id2";
                $update_points_params = [
                    ':quiz_id1' => $quiz_id,
                    ':quiz_id2' => $quiz_id
                ];
                
                secure_query($pdo, $update_points_query, $update_points_params);
                
                // Commit transaction
                $pdo->commit();
                
                echo json_encode(['success' => true]);
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
        } elseif ($_POST['ajax_action'] === 'delete_question') {
            $question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
            
            if (!$question_id) {
                throw new Exception('Invalid question ID');
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Get quiz_id before deletion and verify ownership
                $check_query = "
                    SELECT qq.quiz_id FROM quiz_questions qq
                    JOIN final_quizzes fq ON qq.quiz_id = fq.id
                    WHERE qq.id = :question_id AND fq.teacher_id = :teacher_id
                ";
                $check_params = [
                    ':question_id' => $question_id,
                    ':teacher_id' => $teacher_id
                ];
                $check_result = secure_query($pdo, $check_query, $check_params);
                
                if ($check_result->rowCount() === 0) {
                    throw new Exception('You do not have permission to delete this question');
                }
                
                $question = $check_result->fetch(PDO::FETCH_ASSOC);
                $quiz_id = $question['quiz_id'];
                
                // Delete question (options will cascade)
                $delete_query = "DELETE FROM quiz_questions WHERE id = :id";
                $delete_params = [':id' => $question_id];
                secure_query($pdo, $delete_query, $delete_params);
                
                // Update total points in final_quizzes
                $update_points_query = "UPDATE final_quizzes SET total_points = (
                                            SELECT COALESCE(SUM(points), 0) FROM quiz_questions WHERE quiz_id = :quiz_id1
                                        ) WHERE id = :quiz_id2";
                $update_points_params = [
                    ':quiz_id1' => $quiz_id,
                    ':quiz_id2' => $quiz_id
                ];
                secure_query($pdo, $update_points_query, $update_points_params);
                
                // Reorder remaining questions
                $reorder_query = "
                    UPDATE quiz_questions 
                    SET order_number = (@rownum := @rownum + 1) 
                    WHERE quiz_id = :quiz_id 
                    ORDER BY order_number
                ";
                $pdo->exec("SET @rownum = 0");
                $reorder_params = [':quiz_id' => $quiz_id];
                secure_query($pdo, $reorder_query, $reorder_params);
                
                // Commit transaction
                $pdo->commit();
                
                echo json_encode(['success' => true]);
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
        } elseif ($_POST['ajax_action'] === 'publish_quiz') {
            $quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
            
            if (!$quiz_id) {
                throw new Exception('Invalid quiz ID');
            }
            
            // Check if quiz has at least one question
            $check_query = "SELECT COUNT(*) as count FROM quiz_questions WHERE quiz_id = :quiz_id";
            $check_params = [':quiz_id' => $quiz_id];
            $check_result = secure_query($pdo, $check_query, $check_params);
            $check_row = $check_result->fetch(PDO::FETCH_ASSOC);
            
            if ($check_row['count'] == 0) {
                throw new Exception('Quiz must have at least one question before publishing');
            }
            
            $update_query = "UPDATE final_quizzes SET status = 'published' WHERE id = :id AND teacher_id = :teacher_id";
            $update_params = [
                ':id' => $quiz_id,
                ':teacher_id' => $teacher_id
            ];
            secure_query($pdo, $update_query, $update_params);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log('Quiz error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Fetch teacher's quizzes from final_quizzes table
$quizzes_query = "
    SELECT fq.*, t.topic_name,
           (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = fq.id) as question_count
    FROM final_quizzes fq
    LEFT JOIN topics t ON fq.topic_id = t.id
    WHERE fq.teacher_id = :teacher_id
    ORDER BY fq.created_at DESC
";
$quizzes_params = [':teacher_id' => $teacher_id];
$quizzes_result = secure_query($pdo, $quizzes_query, $quizzes_params);
$quizzes = $quizzes_result->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_ENV['PAGE_HEADER'] ?> - Assessment & Evaluation</title>
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
        
        .group-actions {
            position: absolute;
            top: 30px;
            right: 30px;
        }
        
        .quiz-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .quiz-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .quiz-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }
        
        .quiz-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .quiz-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-draft {
            background: #fff3e0;
            color: #e65100;
        }
        
        .status-published {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .fab-button {
            position: fixed;
            bottom: 90px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .fab-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
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
        
        .question-builder {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .option-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 8px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .option-row .form-check {
            margin: 0;
            padding: 0;
            min-width: 30px;
        }
        
        .option-row .form-check-input {
            margin: 0;
            cursor: pointer;
        }
        
        .option-row .form-check-input:checked {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .remove-option {
            color: #dc3545;
            cursor: pointer;
            font-size: 18px;
            opacity: 0.6;
            transition: opacity 0.3s;
        }
        
        .remove-option:hover {
            opacity: 1;
        }
        
        .add-option-btn {
            border: 2px dashed #667eea;
            background: transparent;
            color: #667eea;
            padding: 10px;
            border-radius: 8px;
            width: 100%;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .add-option-btn:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .question-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            position: relative;
        }
        
        .question-number {
            position: absolute;
            top: -10px;
            left: 20px;
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .question-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
        }
        
        .question-actions i {
            cursor: pointer;
            font-size: 18px;
            opacity: 0.6;
            transition: opacity 0.3s;
        }
        
        .question-actions i:hover {
            opacity: 1;
        }
        
        .edit-icon {
            color: #667eea;
        }
        
        .delete-icon {
            color: #dc3545;
        }
        
        .toast-container {
            z-index: 1100;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        /* Add these to your existing styles */
.quiz-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
    width: 100%;
    overflow: hidden;
}

.quiz-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.1);
}

.quiz-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    gap: 10px;
}

.quiz-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
    word-break: break-word;
    flex: 1;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    white-space: nowrap;
    flex-shrink: 0;
}

.quiz-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 16px;
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.quiz-meta span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f8f9fa;
    padding: 6px 12px;
    border-radius: 20px;
    white-space: nowrap;
}

.quiz-meta span i {
    font-size: 0.9rem;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .quiz-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .quiz-title {
        font-size: 1.1rem;
        width: 100%;
    }
    
    .status-badge {
        align-self: flex-start;
    }
    
    .quiz-meta {
        gap: 8px;
    }
    
    .quiz-meta span {
        width: calc(50% - 4px);
        white-space: normal;
        word-break: break-word;
        font-size: 0.8rem;
        padding: 6px 8px;
    }
    
    .quiz-meta span i {
        flex-shrink: 0;
    }
    
    .mt-3 {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .mt-3 .btn {
        width: 100%;
        margin: 0 !important;
    }
}

/* Small mobile devices */
@media (max-width: 480px) {
    .quiz-meta span {
        width: 100%;
    }
    
    .quiz-card {
        padding: 15px;
    }
}

/* For the due date specifically - ensure it doesn't overflow */
.quiz-meta span:last-child {
    flex: 0 1 auto;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Better handling for long dates on mobile */
@media (max-width: 768px) {
    .quiz-meta span:has(.bi-calendar) {
        width: 100% !important;
        justify-content: flex-start;
    }
}
    </style>
</head>
<body>
    <!-- Top Bar -->
    <?php require_once "bars/topbar.php"; ?>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Content Area -->
    <div class="content-area" id="contentArea">
        <!-- Cover Header -->
        <div class="group-cover mb-4">
            <div class="group-cover-content">
                <h1><?= $_ENV['PAGE_HEADER'] ?></h1>
                <p>Assessment & Evaluation - Create and Manage Final Quizzes</p>
            </div>
            <div class="group-actions">
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createQuizModal">
                    <i class="bi bi-plus-lg"></i> New Final Quiz
                </button>
            </div>
        </div>

        <!-- Quizzes List -->
        <div class="section-title">My Final Quizzes</div>
        
        <?php if (empty($quizzes)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-text" style="font-size: 48px; color: #ccc;"></i>
                <p class="mt-3 text-muted">You haven't created any final quizzes yet. Click "New Final Quiz" to get started.</p>
            </div>
        <?php else: ?>
            <?php foreach ($quizzes as $quiz): ?>
                <div class="quiz-card" id="quiz-<?= $quiz['id'] ?>">
                    <div class="quiz-header">
                        <h3 class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></h3>
                        <span class="status-badge <?= $quiz['status'] === 'published' ? 'status-published' : 'status-draft' ?>">
                            <?= ucfirst($quiz['status']) ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($quiz['description'])): ?>
                        <p class="text-muted mb-3"><?= htmlspecialchars($quiz['description']) ?></p>
                    <?php endif; ?>
                    
                    <div class="quiz-meta">
                        <span><i class="bi bi-bookmark"></i> <?= htmlspecialchars($quiz['topic_name'] ?? 'No Topic') ?></span>
                        <span><i class="bi bi-question-circle"></i> <?= $quiz['question_count'] ?> Questions</span>
                        <span><i class="bi bi-star"></i> <?= $quiz['total_points'] ?? 0 ?> Points</span>
                        <?php if ($quiz['time_limit']): ?>
                            <span><i class="bi bi-clock"></i> <?= $quiz['time_limit'] ?> mins</span>
                        <?php endif; ?>
                        <?php if ($quiz['due_date']): ?>
                            <span><i class="bi bi-calendar"></i> Due: <?= date('M d, Y', strtotime($quiz['due_date'])) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary edit-quiz-btn" 
                                data-quiz-id="<?= $quiz['id'] ?>">
                            <i class="bi bi-pencil"></i> Edit Quiz
                        </button>
                        <?php if ($quiz['status'] === 'draft' && $quiz['question_count'] > 0): ?>
                            <button class="btn btn-sm btn-success publish-quiz-btn" 
                                    data-quiz-id="<?= $quiz['id'] ?>">
                                <i class="bi bi-check2-circle"></i> Publish
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Floating Action Button -->
        <button class="fab-button" title="Create New Final Quiz" data-bs-toggle="modal" data-bs-target="#createQuizModal">
            <i class="bi bi-plus-lg"></i>
        </button>

        <!-- Create Quiz Modal -->
        <div class="modal fade" id="createQuizModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Final Quiz</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createQuizForm">
                            <div class="mb-3">
                                <label class="form-label">Quiz Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="quizTitle" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="quizDescription" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Topic</label>
                                <select class="form-select" id="quizTopic">
                                    <option value="">No Topic</option>
                                    <?php foreach ($topics_array as $topic): ?>
                                        <option value="<?= $topic['id'] ?>"><?= htmlspecialchars($topic['topic_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Time Limit (minutes)</label>
                                    <input type="number" class="form-control" id="quizTimeLimit" min="0" value="0">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="quizDueDate">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveQuizBtn">Create Quiz</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz Builder Modal -->
        <div class="modal fade" id="quizBuilderModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="builderModalTitle">Build Final Quiz</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Quiz Info Summary -->
                        <div class="alert alert-info" id="quizSummary">
                            Loading...
                        </div>
                        
                        <!-- Questions List -->
                        <div id="questionsContainer" class="mb-4">
                            <!-- Questions will be loaded here -->
                        </div>
                        
                        <!-- Question Builder -->
                        <div class="question-builder">
                            <h6 class="mb-3">Add New Question</h6>
                            <form id="questionForm">
                                <input type="hidden" id="currentQuizId">
                                <input type="hidden" id="editingQuestionId" value="">
                                
                                <div class="mb-3">
                                    <label class="form-label">Question <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="questionText" rows="2" required></textarea>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Points</label>
                                        <input type="number" class="form-control" id="questionPoints" min="1" value="1">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Options <span class="text-danger">*</span></label>
                                    <div id="optionsContainer">
                                        <!-- Options will be added here dynamically -->
                                    </div>
                                    <button type="button" class="add-option-btn" id="addOptionBtn">
                                        <i class="bi bi-plus"></i> Add Option
                                    </button>
                                </div>
                                
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" id="cancelEditBtn" style="display: none;">Cancel Edit</button>
                                    <button type="button" class="btn btn-primary" id="saveQuestionBtn">Add Question</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <span class="text-muted me-auto" id="totalPointsDisplay">Total Points: 0</span>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success" id="finishQuizBtn">Finish & Submit</button>
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

        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="classroom" class="nav-item">
                <i class="bi bi-chat-left-text"></i>
                <span>Newsfeed</span>
            </a>
            <a href="e-module" class="nav-item">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>E-Modules</span>
            </a>
            <a href="ane" class="nav-item active">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentQuizId = null;
        let questions = [];
        
        // Toast function
        function showToast(title, message, isError = false) {
            document.getElementById('toastTitle').textContent = title;
            document.getElementById('toastMessage').textContent = message;
            
            const toastEl = document.getElementById('appToast');
            toastEl.classList.toggle('bg-danger', isError);
            toastEl.classList.toggle('text-white', isError);
            
            new bootstrap.Toast(toastEl).show();
        }
        
        // Initialize with 2 options
        function initializeOptions() {
            const container = document.getElementById('optionsContainer');
            container.innerHTML = '';
            addOption();
            addOption();
        }
        
        // Add new option
        function addOption() {
            const container = document.getElementById('optionsContainer');
            const optionCount = container.children.length + 1;
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'option-row';
            optionDiv.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="correctOption" value="${optionCount - 1}">
                </div>
                <input type="text" class="form-control" placeholder="Option ${optionCount}" required>
                <i class="bi bi-x-circle remove-option"></i>
            `;
            
            container.appendChild(optionDiv);
            
            // Add remove functionality
            optionDiv.querySelector('.remove-option').addEventListener('click', function() {
                if (container.children.length > 2) {
                    optionDiv.remove();
                    updateOptionNumbers();
                } else {
                    showToast('Warning', 'At least 2 options are required', true);
                }
            });
            
            updateOptionNumbers();
        }
        
        // Update option numbers after removal
        function updateOptionNumbers() {
            const container = document.getElementById('optionsContainer');
            const options = container.querySelectorAll('.option-row');
            
            options.forEach((option, index) => {
                const radio = option.querySelector('input[type="radio"]');
                radio.value = index;
                
                const textInput = option.querySelector('input[type="text"]');
                textInput.placeholder = `Option ${index + 1}`;
            });
        }
        
        // Save new quiz
        document.getElementById('saveQuizBtn').addEventListener('click', function() {
            const title = document.getElementById('quizTitle').value.trim();
            const description = document.getElementById('quizDescription').value.trim();
            const topicId = document.getElementById('quizTopic').value;
            const timeLimit = document.getElementById('quizTimeLimit').value;
            const dueDate = document.getElementById('quizDueDate').value;
            
            if (!title) {
                showToast('Error', 'Quiz title is required', true);
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'save_quiz');
            formData.append('title', title);
            formData.append('description', description);
            formData.append('topic_id', topicId);
            formData.append('time_limit', timeLimit);
            formData.append('due_date', dueDate);
            
            fetch('ane.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('createQuizModal')).hide();
                    showToast('Success', 'Final quiz created successfully!');
                    
                    // Reset form
                    document.getElementById('createQuizForm').reset();
                    
                    // Open quiz builder
                    currentQuizId = data.quiz_id;
                    document.getElementById('currentQuizId').value = currentQuizId;
                    document.getElementById('builderModalTitle').textContent = 'Build Final Quiz';
                    initializeOptions();
                    loadQuestions();
                    
                    new bootstrap.Modal(document.getElementById('quizBuilderModal')).show();
                } else {
                    showToast('Error', data.error, true);
                }
            })
            .catch(error => {
                showToast('Error', 'An error occurred', true);
                console.error('Error:', error);
            });
        });
        
        // Add option button
        document.getElementById('addOptionBtn').addEventListener('click', function() {
            addOption();
        });
        
        // Save question
        document.getElementById('saveQuestionBtn').addEventListener('click', function() {
            const quizId = document.getElementById('currentQuizId').value;
            const questionText = document.getElementById('questionText').value.trim();
            const points = document.getElementById('questionPoints').value;
            const editingId = document.getElementById('editingQuestionId').value;
            
            // Get options
            const optionRows = document.querySelectorAll('#optionsContainer .option-row');
            const options = [];
            let correctOption = null;
            
            optionRows.forEach((row, index) => {
                const textInput = row.querySelector('input[type="text"]');
                const radio = row.querySelector('input[type="radio"]');
                
                if (textInput.value.trim()) {
                    options.push(textInput.value.trim());
                }
                
                if (radio.checked) {
                    correctOption = index;
                }
            });
            
            // Validation
            if (!questionText) {
                showToast('Error', 'Please enter a question', true);
                return;
            }
            
            if (options.length < 2) {
                showToast('Error', 'Please add at least 2 options', true);
                return;
            }
            
            if (correctOption === null) {
                showToast('Error', 'Please select the correct answer', true);
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', editingId ? 'update_question' : 'save_question');
            formData.append('quiz_id', quizId);
            formData.append('question_text', questionText);
            formData.append('points', points);
            formData.append('options', JSON.stringify(options));
            formData.append('correct_option', correctOption);
            
            if (editingId) {
                formData.append('question_id', editingId);
            }
            
            fetch('ane.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', editingId ? 'Question updated!' : 'Question added!');
                    
                    // Reset form
                    document.getElementById('questionText').value = '';
                    document.getElementById('questionPoints').value = '1';
                    document.getElementById('editingQuestionId').value = '';
                    document.getElementById('cancelEditBtn').style.display = 'none';
                    document.getElementById('saveQuestionBtn').textContent = 'Add Question';
                    
                    initializeOptions();
                    loadQuestions();
                } else {
                    showToast('Error', data.error, true);
                    console.error('Server error:', data.error);
                }
            })
            .catch(error => {
                showToast('Error', 'An error occurred', true);
                console.error('Fetch error:', error);
            });
        });
        
        // Cancel edit
        document.getElementById('cancelEditBtn').addEventListener('click', function() {
            document.getElementById('questionText').value = '';
            document.getElementById('questionPoints').value = '1';
            document.getElementById('editingQuestionId').value = '';
            document.getElementById('cancelEditBtn').style.display = 'none';
            document.getElementById('saveQuestionBtn').textContent = 'Add Question';
            initializeOptions();
        });
        
        // Load questions
        function loadQuestions() {
            const quizId = document.getElementById('currentQuizId').value;
            
            const formData = new FormData();
            formData.append('ajax_action', 'get_questions');
            formData.append('quiz_id', quizId);
            
            fetch('ane.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayQuestions(data.questions);
                    updateQuizSummary(data.quiz);
                    
                    // Calculate total points
                    const totalPoints = data.questions.reduce((sum, q) => sum + parseInt(q.points), 0);
                    document.getElementById('totalPointsDisplay').textContent = `Total Points: ${totalPoints}`;
                } else {
                    showToast('Error', data.error, true);
                }
            })
            .catch(error => {
                console.error('Error loading questions:', error);
            });
        }
        
        // Display questions
        function displayQuestions(questions) {
            const container = document.getElementById('questionsContainer');
            
            if (questions.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No questions yet. Add your first question above.</p>';
                return;
            }
            
            let html = '';
            questions.forEach((question, index) => {
                html += `
                    <div class="question-item" id="question-${question.id}">
                        <div class="question-number">${index + 1}</div>
                        <div class="question-actions">
                            <i class="bi bi-pencil edit-icon" onclick="editQuestion(${question.id})"></i>
                            <i class="bi bi-trash delete-icon" onclick="deleteQuestion(${question.id})"></i>
                        </div>
                        <p class="fw-bold mb-2">${question.question_text}</p>
                        <p class="text-muted small mb-2">Points: ${question.points}</p>
                        <div class="ms-3">
                            ${question.options.map(opt => `
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" disabled ${opt.is_correct ? 'checked' : ''}>
                                    <label class="form-check-label ${opt.is_correct ? 'text-success fw-bold' : ''}">
                                        ${opt.option_text}
                                    </label>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Update quiz summary
        function updateQuizSummary(quiz) {
            const summary = document.getElementById('quizSummary');
            summary.innerHTML = `
                <strong>${quiz.title}</strong><br>
                ${quiz.description ? quiz.description + '<br>' : ''}
                Topic: ${quiz.topic_name || 'No Topic'} | 
                Time Limit: ${quiz.time_limit || 'No'} minutes | 
                Due: ${quiz.due_date ? new Date(quiz.due_date).toLocaleDateString() : 'No due date'}
            `;
        }
        
        // Edit question
        window.editQuestion = function(questionId) {
            const formData = new FormData();
            formData.append('ajax_action', 'get_questions');
            formData.append('quiz_id', document.getElementById('currentQuizId').value);
            
            fetch('ane.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const question = data.questions.find(q => q.id == questionId);
                    if (question) {
                        document.getElementById('questionText').value = question.question_text;
                        document.getElementById('questionPoints').value = question.points;
                        document.getElementById('editingQuestionId').value = question.id;
                        document.getElementById('cancelEditBtn').style.display = 'inline-block';
                        document.getElementById('saveQuestionBtn').textContent = 'Update Question';
                        
                        // Load options
                        const container = document.getElementById('optionsContainer');
                        container.innerHTML = '';
                        
                        question.options.forEach((opt, index) => {
                            const optionDiv = document.createElement('div');
                            optionDiv.className = 'option-row';
                            optionDiv.innerHTML = `
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="correctOption" value="${index}" ${opt.is_correct ? 'checked' : ''}>
                                </div>
                                <input type="text" class="form-control" value="${opt.option_text}" required>
                                <i class="bi bi-x-circle remove-option"></i>
                            `;
                            
                            container.appendChild(optionDiv);
                            
                            optionDiv.querySelector('.remove-option').addEventListener('click', function() {
                                if (container.children.length > 2) {
                                    optionDiv.remove();
                                    updateOptionNumbers();
                                } else {
                                    showToast('Warning', 'At least 2 options are required', true);
                                }
                            });
                        });
                        
                        updateOptionNumbers();
                    }
                }
            });
        };
        
        // Delete question
        window.deleteQuestion = function(questionId) {
            if (!confirm('Are you sure you want to delete this question?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_question');
            formData.append('question_id', questionId);
            
            fetch('ane.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', 'Question deleted!');
                    loadQuestions();
                } else {
                    showToast('Error', data.error, true);
                }
            });
        };
        
        // Edit quiz button
        document.querySelectorAll('.edit-quiz-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const quizId = this.dataset.quizId;
                currentQuizId = quizId;
                document.getElementById('currentQuizId').value = quizId;
                document.getElementById('builderModalTitle').textContent = 'Edit Final Quiz';
                initializeOptions();
                loadQuestions();
                new bootstrap.Modal(document.getElementById('quizBuilderModal')).show();
            });
        });
        
        // Publish quiz button
        document.querySelectorAll('.publish-quiz-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const quizId = this.dataset.quizId;
                
                if (!confirm('Are you sure you want to publish this final quiz? Students will be able to take it.')) {
                    return;
                }
                
                const formData = new FormData();
                formData.append('ajax_action', 'publish_quiz');
                formData.append('quiz_id', quizId);
                
                fetch('ane.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Success', 'Final quiz published successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error', data.error, true);
                    }
                });
            });
        });
        
        // Finish quiz button
        document.getElementById('finishQuizBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to finish? You can still edit later if needed.')) {
                bootstrap.Modal.getInstance(document.getElementById('quizBuilderModal')).hide();
                setTimeout(() => location.reload(), 500);
            }
        });
        
        // Initialize on modal show
        document.getElementById('quizBuilderModal').addEventListener('show.bs.modal', function() {
            initializeOptions();
        });
        
        document.getElementById('quizBuilderModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('questionText').value = '';
            document.getElementById('questionPoints').value = '1';
            document.getElementById('editingQuestionId').value = '';
            document.getElementById('cancelEditBtn').style.display = 'none';
            document.getElementById('saveQuestionBtn').textContent = 'Add Question';
        });
    </script>
</body>
</html>