<?php
require("db-config/security.php"); // your PDO connection

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $section_name = trim($_POST['section_name']);
    
    if(!isset($_POST['teacher_id'])){
        $teacher_id = 0;
    }else {
        $teacher_id = $_POST['teacher_id'];
    }

    // Basic validation

    if (empty($section_name)) {
        $_SESSION['message'] = "❌ Section Name must not be Empty.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    try {

        // ✅ Insert new student
        $stmt = $pdo->prepare("
            INSERT INTO sections (section_name, teacher_id)
            VALUES (:section_name, :teacher_id)
        ");
        $stmt->execute([
            ":section_name" => $section_name,
            ":teacher_id" => $teacher_id
        ]);

        $_SESSION['message'] = "✅ Section created successfully!";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;

    } catch (PDOException $e) {
         $_SESSION['message'] = "❌ Database Error: " . $e->getMessage();
         header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}
