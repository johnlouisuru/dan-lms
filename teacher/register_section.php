<?php 
    require("db-config/security.php");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
    <?php
    require __DIR__ . '/headers/head.php'; //Included dito outside links and local styles
    ?>
    
  
</head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <?php
        require __DIR__ . '/bars/topbar.php'; //Topbar yung kasama Profile Icon
    ?>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
    <?php
        require __DIR__ . '/bars/sidebar.php'; //Sidebar yung kasama Logged in Session
    ?>   
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-lg-9">
                                <div class="card shadow-lg border-0 rounded-lg mt-5">
                                    <div class="card-header"><h3 class="text-center font-weight-light my-4">Add New Section</h3></div>
                                    <div class="card-body">
                                        <form action="process_section_register" method="POST">
                                            <div class="alert alert-primary" role="alert"><?=@$_SESSION['message']?></div>
                                        <div class="row mb-12">
                                            <div class="col-md-12">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="section_name" name="section_name" type="text" placeholder="Enter Section Name" required />
                                                    <label for="section_name">Section name</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-header"><h5 class="text-center font-weight-light my-4">* Optional: You can directly Assign Teacher to Section</h5></div>
                                        <div class="col-md-12">
                                            <div class="form-floating mb-3">
                                            <?php
                                                // Fetch all teachers
                                                $query_teachers = "SELECT * FROM teachers";
                                                $teachers_stmt = secure_query_no_params($pdo, $query_teachers);
                                            ?>
                                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                                <option value="0" selected>Select Teacher *Optional</option>
                                                <?php if ($teachers_stmt && $teachers_stmt->rowCount() > 0): ?>
                                                    <?php foreach ($teachers_stmt as $teacher): ?>
                                                        <?php
                                                            // Check if teacher is already assigned to a section
                                                            $stmt_check = $pdo->prepare("SELECT id FROM sections WHERE teacher_id = ?");
                                                            $stmt_check->execute([$teacher['id']]);
                                                            $is_assigned = $stmt_check->fetch(PDO::FETCH_ASSOC);
                                                        ?>
                                                        <option 
                                                            value="<?= $teacher['id'] ?>"
                                                            <?= $is_assigned ? 'disabled' : '' ?>>
                                                            <?= htmlspecialchars($teacher['fullname']) ?>
                                                            <?= $is_assigned ? ' (Already Assigned)' : '' ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option value="" disabled>No teachers available</option>
                                                <?php endif; ?>
                                            </select>
                                            <label for="teacher_id">Select Teacher</label>
                                        </div>

                                        </div>
                                        <div class="mt-4 mb-0">
                                            <div class="d-grid">
                                                <button class="btn btn-primary btn-block" type="submit">Create Account</button>
                                            </div>
                                        </div>
                                    </form>

                                    </div>
                                    <div class="card-footer text-center py-3">
                                        <div class="small"><a href="login.html">Have an account? Go to login</a></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                               <div class="card shadow-lg border-0 rounded-lg mt-5">
                                    <div class="card-header"><h5 class="text-center font-weight-light my-4">All Requirement</h5></div>
                                        <div class="card-body">
                                            <table class='table table-striped'>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Section Name</th>
                                                    <th>Assigned Teacher</th>
                                                </tr>
                                            
                                            <?php
                                                        $query_requirements = "SELECT * FROM sections";
                                                        $requirements_stmt = secure_query_no_params($pdo, $query_requirements);
                                                        $loop = 1;
                                                    ?>
                                                    <?php if ($requirements_stmt && $requirements_stmt->rowCount() > 0): ?>
                                                        <?php foreach ($requirements_stmt as $fetched): ?>
                                                            <tr>
                                                                <td><?=$loop?></td>
                                                                <td><?= htmlspecialchars($fetched['section_name']) ?></td>
                                                                <td><?php $section_name_holder = get_section_name($pdo, $fetched['id']); ?>
                                                                    <?= get_teacher_name($pdo, $section_name_holder['teacher_id'])?></td>
                                                            </tr>
                                                        <?php $loop++;
                                                              endforeach; ?>
                                                    <?php else: ?>
                                                       
                                                    <?php endif; ?>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </main>
            </div>
            <div id="layoutAuthentication_footer">
                <footer class="py-4 bg-light mt-auto">

                </footer>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
    </body>
</html>
<?php 
$_SESSION['message'] = '';
?>
