<?php require_once "../db-config/security.php";

    // If already logged in and profile complete, redirect to dashboard
    if (!isLoggedIn()) {
        header('Location: ../');
        exit;
    }

    // Fetch students with joins
    $sql = "SELECT * FROM students
                   ";
    $stmt     = $pdo->query($sql);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Fetch grade levels for dropdown
    // $grades = $pdo->query("SELECT id, grade_level FROM grade_level")->fetchAll(PDO::FETCH_ASSOC);
    // Fetch sections for dropdown
    // $sections = $pdo->query("SELECT id FROM sections")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../<?php echo $_ENV['PAGE_ICON'] ?>">
  <link rel="icon" type="image/png" href="../<?php echo $_ENV['PAGE_ICON'] ?>">
  <title><?php echo $_ENV['PAGE_HEADER'] ?></title>
  <!-- Added for Toast -->
   <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <!-- CSS Files -->
  <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />
</head>

<body class="g-sidenav-show  bg-gray-100">

  <!-- Sidebar -->
  <?php
      require_once "navbars/sidebar.php";
  ?>
  <!-- End of Sidebar -->

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

    <!-- TOpbar -->
      <?php
          require_once "navbars/topbar.php";
      ?>
    <!-- End TOpbar -->

    <div class="container-fluid py-2">
      <div class="row">
        <div class="ms-3">
          <h3 class="mb-0 h4 font-weight-bolder"><?php echo $currentPath ?></h3>
          <p class="mb-4">
            Management
          </p>
        </div>

        <!-- Start ng Content -->
         <div class="card table-responsive p-3"> 
            <table id="studentsTable" class="table table-striped bg-white"> 
                <thead class="table-dark"> 
                    <tr> 
                        <th>ID</th> <th>Email</th> 
                        <th>Lastname</th> <th>Firstname</th> 
                        <th>LRN</th> 
                        <!-- <th>Grade Level</th>  -->
                        <!-- <th>Section</th>  -->
                        <!-- <th>Teacher</th>  -->
                        <th>Profile Picture</th> <th>Created At</th> 
                    </tr> </thead> 
                    <tbody>
            <?php foreach ($students as $st): ?> 
                <tr> 
                    <td><?php echo $st['id'] ?></td> 
                    <td><?php echo htmlspecialchars($st['email']) ?></td> 
                    <td><?php echo htmlspecialchars($st['lastname']) ?></td> 
                    <td><?php echo htmlspecialchars($st['firstname']) ?></td> 
                    <td><?php echo htmlspecialchars($st['lrn']) ?></td> 
                    <!-- <td> <select class="form-select form-select-sm grade-level-dropdown" data-id="<?php echo $st['id'] ?>">
                        <?php foreach ($grades as $g): ?> 
                            <option value="<?php echo $g['id'] ?>"<?php echo($st['grade_level'] == $g['id']) ? 'selected' : '' ?>>
                                <?php echo htmlspecialchars($g['grade_level']) ?> </option><?php endforeach; ?> 
                            </select> 
                    </td>  -->
                    <!-- <td> 
                        <select class="form-select form-select-sm section-dropdown" data-id="<?php echo $st['id'] ?>"> 
                            <option value="0">N/A</option><?php foreach ($sections as $sec): ?> 
                                <option value="<?php echo $sec['id'] ?>"<?php echo($st['section_id'] == $sec['id']) ? 'selected' : '' ?>>
                                    <?php echo htmlspecialchars($sec['section_name']) ?> 
                                </option><?php endforeach; ?> 
                        </select> 
                    </td>  -->
                    <!-- <td><?php echo $st['teacher_name'] ?: 'N/A' ?></td>  -->
                    <td> 
                        <img src="<?php echo ! empty($st['profile_picture']) ? htmlspecialchars($st['profile_picture']) : '../assets/img/smaw.png' ?>" 
                        class="img-thumbnail profile-pic" 
                        alt="Profile" width="50" height="50" 
                        data-bs-toggle="modal" 
                        data-bs-target="#profileModal" 
                        data-img="<?php echo ! empty($st['profile_picture']) ? htmlspecialchars($st['profile_picture']) : 'assets/img/smaw.png' ?>"> 
                    </td> 
                    <td>
                        <?php echo htmlspecialchars($st['created_at']) ?>
                    </td> 
                </tr><?php endforeach; ?> 
            </tbody> 
        </table> 
    </div> <!-- Profile Picture Modal --> 
        

      </div>
      <!-- Lagayan dito ng Table with Roadmap -->

      <!-- Lagayan dito ng Table with Roadmap -->

      <!-- Footer Area -->
      <?php
          require_once "navbars/footers.php";
      ?>
      <!-- Footer Area -->
    </div>
  </main>

  <!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
  <div id="ajaxToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div id="toastMessage" class="toast-body">Updated successfully!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<!-- Toast Notification -->

<!-- Image Modal -->
 <div class="modal fade " id="profileModal" tabindex="-1" aria-hidden="true"> 
            <div class="modal-dialog modal-dialog-centered"> 
                <div class="modal-content border border-warning"> 
                    <div class="modal-header bg-dark text-warning"> 
                        <h5 class="modal-title text text-info">Profile Picture</h5> 
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">

                        </button> 
                    </div> 
                    <div class="modal-body text-center"> 
                        <img id="modalProfileImg" src="" class="img-fluid rounded" alt="Profile"> 
                    </div> 
                </div> 
            </div> 
        </div>

  <!--   Core JS Files   -->
 <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/chartjs.min.js"></script>

  
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <script> 
  $(document).ready(function() {
    //  $('#studentsTable').DataTable(); 
     $('#studentsTable').DataTable({
        language: {
            search: "Search students:",
            searchPlaceholder: "Type Name Any Unique Data",
             paginate: {
                previous: '«',
                next: '»'
            }
        }
    });
     
    // Profile picture modal 
    $('.profile-pic').on('click', function() { let imgSrc = $(this).data('img'); $('#modalProfileImg').attr('src', imgSrc); }); 
    // Grade level AJAX update 
    $('.grade-level-dropdown').on('change', function() { 
        let studentId = $(this).data('id'); 
        let gradeId = $(this).val(); 
        $.post('grade_level/grade_level_process_update.php', {
            id: studentId, grade_level: gradeId
        }, function(response) { 
            showToast(response.success ? 'Grade level updated!' : response.error, response.success); 
        }, 'json'); }); 
        // Section AJAX update 
        $('.section-dropdown').on('change', function() { 
            let studentId = $(this).data('id'); 
            let sectionId = $(this).val(); 
            $.post('section/student_section_update.php', {
                id: studentId, section_id: sectionId
            }, function(response) { 
                showToast(response.success ? 'Section updated!' : response.error, response.success); 
            }, 'json'); }); // Toast helper 
            function showToast(message, success) { 
                let toastEl = $('#ajaxToast'); 
                let toastMessage = $('#toastMessage'); 
                toastMessage.text(message); 
                toastEl.removeClass('text-bg-success text-bg-danger'); 
                toastEl.addClass(success ? 'text-bg-success' : 'text-bg-danger'); 
                new bootstrap.Toast(toastEl[0]).show(); } 
            }); 
        </script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>
</body>

</html>