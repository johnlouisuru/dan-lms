<?php require_once "../db-config/security.php";

    // If already logged in and profile complete, redirect to dashboard
    if (!isLoggedIn()) {
        header('Location: ../');
        exit;
    }

    // Fetch sections with teacher info
    $sql = "SELECT s.id, s.section_name, s.teacher_id,
               CONCAT(t.firstname, ' ', t.lastname) AS teacher_name
        FROM sections s
        LEFT JOIN teachers t ON t.id = s.teacher_id";
    $stmt     = $pdo->query($sql);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
         <div class="d-flex justify-content-end mb-3"> 
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal"> <i class="bi bi-plus-circle"></i> Add Section 
        </button> </div>
        <div class="table-responsive">
  <table id="sectionsTable" class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Section Name</th>
        <th>Teacher</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($sections as $sec): ?>
        <tr>
          <td><?php echo $sec['id']?></td>
          <td><?php echo htmlspecialchars($sec['section_name'])?></td>
          <td><?php echo $sec['teacher_name'] ?: 'N/A'?></td>
          <td>
            <button class="btn btn-sm btn-warning edit-btn"
                    data-id="<?php echo $sec['id']?>"
                    data-name="<?php echo htmlspecialchars($sec['section_name'])?>">
              Edit
            </button>
            <button class="btn btn-sm btn-danger delete-btn"
                    data-id="<?php echo $sec['id']?>">
              Delete
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Section Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border border-success">
      <div class="modal-header bg-dark text-success">
        <h5 class="modal-title">Add New Section</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="addForm">
          <div class="mb-3">
            <label for="addSectionName" class="form-label">Section Name</label>
            <input type="text" class="form-control" id="addSectionName" required>
          </div>
          <div class="mb-3">
            <label for="addTeacherId" class="form-label">Assign Teacher</label>
            <select class="form-select" id="addTeacherId">
              <option value="">-- Select Teacher --</option>
              <?php
              $teachers = $pdo->query("SELECT id, firstname, lastname FROM teachers")->fetchAll(PDO::FETCH_ASSOC);
              foreach ($teachers as $t) {
                  echo "<option value='{$t['id']}'>" . htmlspecialchars($t['firstname'] . " " . $t['lastname']) . "</option>";
              }
              ?>
            </select>
          </div>
          <button type="submit" class="btn btn-success">Add Section</button>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border border-warning">
      <div class="modal-header bg-dark text-warning">
        <h5 class="modal-title">Edit Section</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editForm">
          <input type="hidden" id="editSectionId">
          <div class="mb-3">
            <label for="editSectionName" class="form-label">Section Name</label>
            <input type="text" class="form-control" id="editSectionName" required>
          </div>
          <div class="mb-3">
            <label for="editTeacherId" class="form-label">Assign Teacher</label>
            <select class="form-select" id="editTeacherId">
              <option value="" selected>-- Select Teacher --</option>
              <?php
              $teachers = $pdo->query("SELECT id, firstname, lastname FROM teachers")->fetchAll(PDO::FETCH_ASSOC);
              foreach ($teachers as $t) {
                  echo "<option value='{$t['id']}'>" . htmlspecialchars($t['lastname'] . ", " . $t['firstname']) . "</option>";
              }
              ?>
            </select>
          </div>
          <button type="submit" class="btn btn-warning">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border border-danger">
      <div class="modal-header bg-dark text-danger">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this section?</p>
        <button id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
  <div id="ajaxToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div id="toastMessage" class="toast-body">Updated successfully!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

        <!-- End ng Content -->


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
  $('#sectionsTable').DataTable();

  let deleteId = null;

  // Add form submit
  $('#addForm').on('submit', function(e) {
    e.preventDefault();
    let name = $('#addSectionName').val();
    let teacherId = $('#addTeacherId').val();

    $.post('section/section_add.php', {section_name: name, teacher_id: teacherId}, function(response) {
      showToast(response.success ? 'Section added!' : response.error, response.success);
      if(response.success)
        setTimeout(function() {
            location.reload();
        }, 2000);
    }, 'json');
  });




  // Edit button click
$('.edit-btn').on('click', function() {
  let id = $(this).data('id');
  let name = $(this).data('name');
  let teacherId = $(this).data('teacher'); // pass teacher_id from table row

  $('#editSectionId').val(id);
  $('#editSectionName').val(name);
  $('#editTeacherId').val(teacherId); // preselect teacher
  $('#editModal').modal('show');
});

// Edit form submit
$('#editForm').on('submit', function(e) {
  e.preventDefault();
  let id = $('#editSectionId').val();
  let name = $('#editSectionName').val();
  let teacherId = $('#editTeacherId').val();

  $.post('section/section_name_update.php', {id: id, section_name: name, teacher_id: teacherId}, function(response) {
    showToast(response.success ? 'Section updated!' : response.error, response.success);
    if(response.success)
        setTimeout(function() {
            location.reload();
        }, 2000);
  }, 'json');
});


  // Delete button click
  $('.delete-btn').on('click', function() {
    deleteId = $(this).data('id');
    $('#deleteModal').modal('show');
  });

  // Confirm delete
  $('#confirmDeleteBtn').on('click', function() {
    $.post('section/section_delete.php', {id: deleteId}, function(response) {
      showToast(response.success ? 'Section deleted!' : response.error, response.success);
        if(response.success)
        setTimeout(function() {
            location.reload();
        }, 2000);
    }, 'json');
  });

  // Toast helper
  function showToast(message, success) {
    let toastEl = $('#ajaxToast');
    let toastMessage = $('#toastMessage');
    toastMessage.text(message);
    toastEl.removeClass('text-bg-success text-bg-danger');
    toastEl.addClass(success ? 'text-bg-success' : 'text-bg-danger');
    new bootstrap.Toast(toastEl[0]).show();
  }
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