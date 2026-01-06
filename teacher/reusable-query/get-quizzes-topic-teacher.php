<?php 

$quizzesStmt = $pdo->prepare("
    SELECT 
        q.id,
        q.title,
        q.description,
        q.points,
        q.file_path,
        q.due_date,
        q.created_at,
        q.status,
        t.topic_name,
        u.lastname AS teacher_name
    FROM quizzes q
    LEFT JOIN topics t ON q.topic_id = t.id
    LEFT JOIN teachers u ON q.teacher_id = u.id
    WHERE q.status = 'published'
    ORDER BY q.created_at DESC
");
$quizzesStmt->execute();
$quizzes = $quizzesStmt->fetchAll(PDO::FETCH_ASSOC);
