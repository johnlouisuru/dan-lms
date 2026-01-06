<?php
require_once "db-config/security.php";

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login');
    exit;
}

try {
    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $feed_title = $_POST['feed_title'] ?? '';
        $feed_content = $_POST['feed_content'] ?? '';
        $file_attached_path = null;
        $teacher_id = $_SESSION['user_id'] ?? null; // Get logged-in user ID
        
        // Validate required fields
        if (empty($feed_title) || empty($feed_content)) {
            throw new Exception('Title and content are required');
        }
        
        if (empty($teacher_id)) {
            throw new Exception('User not authenticated');
        }
        
        // Handle file upload
        if (isset($_FILES['file_attached']) && $_FILES['file_attached']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file_attached'];
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            // Get file extension
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validate file extension
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('Invalid file type. Only PDF and images (JPG, PNG, GIF) are allowed');
            }
            
            // Validate file type using mime
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only PDF and images are allowed');
            }
            
            // Validate file size
            if ($file['size'] > $max_size) {
                throw new Exception('File size must be less than 10MB');
            }
            
            // Create newsfeed folder if it doesn't exist
            $upload_dir = 'newsfeed';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename with current timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $new_filename = 'newsfeed_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . '/' . $new_filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                throw new Exception('Failed to upload file');
            }
            
            $file_attached_path = $file_path;
        }
        
        // Insert into database using prepared statement
        $sql = "INSERT INTO newsfeed (feed_title, feed_content, file_attached_path, teacher_id, created_at) 
                VALUES (:feed_title, :feed_content, :file_attached_path, :teacher_id, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':feed_title' => $feed_title,
            ':feed_content' => $feed_content,
            ':file_attached_path' => $file_attached_path,
            ':teacher_id' => $teacher_id
        ]);
        
        if (!$result) {
            throw new Exception('Failed to save newsfeed to database');
        }
        
        // Redirect back to the referring page with success message
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header('Location: ' . $redirect_url . '?success=1');
        exit;
        
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    // Redirect back with error message
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header('Location: ' . $redirect_url . '?error=' . urlencode($e->getMessage()));
    exit;
}
?>