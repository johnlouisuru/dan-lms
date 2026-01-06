<?php
require_once "db-config/security.php";

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

/* FETCH TEACHERS */
$teachersStmt = $pdo->query("
    SELECT lastname, firstname, email, id
    FROM teachers
    ORDER BY lastname, firstname
");
$teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

/* FETCH STUDENTS */
$studentsStmt = $pdo->query("
    SELECT lastname, firstname, email, profile_picture, id
    FROM students
    ORDER BY lastname, firstname
");
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

function initials($firstname, $lastname) {
    return strtoupper(
        mb_substr($firstname, 0, 1) .
        mb_substr($lastname, 0, 1)
    );
}

function avatarGradient($seed) {
    $gradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        'linear-gradient(135deg, #f7971e 0%, #ffd200 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
    ];
    return $gradients[$seed % count($gradients)];
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
</head>
<body>
    <!-- Top Bar -->
    <?php 
        require_once "bars/topbar.php";
    ?>

    <!-- Prof and Students Area -->
    <div class="content-area" id="contentArea">

        <div id="students">

            <!-- TEACHERS -->
            <div class="section-title">
                Teachers (<?= count($teachers) ?>)
            </div>

            <?php foreach ($teachers as $i => $t): ?>
                <div class="student-item">

                    <div class="student-avatar"
                        style="background: <?= avatarGradient($i) ?>;">
                        <?= initials($t['firstname'], $t['lastname']) ?>
                    </div>

                    <div class="student-info">
                        <div class="student-name">
                            <?= htmlspecialchars($t['firstname'] . ' ' . $t['lastname']) ?>
                             <?= ($t['id'] == $_SESSION['user_id']) ? '(You)' : '' ?>
                        </div>
                        <div class="student-email">
                            <?= htmlspecialchars($t['email']) ?>
                        </div>
                    </div>

                    <i class="bi bi-three-dots-vertical" style="color:#5f6368;"></i>
                </div>
            <?php endforeach; ?>


            <!-- STUDENTS -->
            <div class="section-title">
                Classmates (<?= count($students) ?>)
            </div>

            <?php foreach ($students as $i => $s): ?>
                <div class="student-item">

                    <?php if (!empty($s['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($s['profile_picture']) ?>"
                            class="student-avatar"
                            alt="Profile">
                    <?php else: ?>
                        <div class="student-avatar"
                            style="background: <?= avatarGradient($i + 10) ?>;">
                            <?= initials($s['firstname'], $s['lastname']) ?>
                            
                        </div>
                    <?php endif; ?>

                    <div class="student-info">
                        <div class="student-name">
                            <?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname']) ?>
                        </div>
                        <div class="student-email">
                            <?= htmlspecialchars($s['email']) ?>
                        </div>
                    </div>

                    <i class="bi bi-three-dots-vertical" style="color:#5f6368;"></i>
                </div>
            <?php endforeach; ?>

        </div>
    </div>


  

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="classroom" class="nav-item" >
            <i class="bi bi-chat-left-text"></i>
            <span>Newsfeed</span>
        </a>
        <a href="classwork" class="nav-item" >
            <i class="bi bi-journal-text"></i>
            <span>ClassWork</span>
        </a>
        <a href="#" class="nav-item active" >
            <i class="bi bi-people-fill"></i>
            <span>Students</span>
        </a>
    </div>
    <?php 
        // require_once "bars/bottom-bar.php";
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
       
    </script>
</body>
</html>