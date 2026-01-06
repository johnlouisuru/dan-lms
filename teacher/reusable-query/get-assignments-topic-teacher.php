<?php 

$assignmentsStmt = $pdo->prepare("
    SELECT 
        a.id,
        a.title,
        a.description,
        a.points,
        a.file_path,
        a.due_date,
        a.created_at,
        t.topic_name,
        a.status,
        u.lastname AS teacher_name
    FROM assignments a
    LEFT JOIN topics t ON a.topic_id = t.id
    LEFT JOIN teachers u ON a.teacher_id = u.id
    WHERE a.status = 'published'
    ORDER BY a.created_at DESC
");
$assignmentsStmt->execute();
$assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);
