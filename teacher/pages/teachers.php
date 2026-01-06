<?php require_once "../db-config/security.php";

    // If already logged in and profile complete, redirect to dashboard
    if (!isLoggedIn()) {
        header('Location: ../');
        exit;
    }

// Fetch teachers with section info
$sql = "SELECT t.id, t.email, t.lastname, t.firstname, t.section_id, t.profile_picture, t.is_active,
               s.section_name
        FROM teachers t
        LEFT JOIN sections s ON s.id = t.section_id
        WHERE t.is_active = 1"; // show only active teachers WHERE t.is_active = 1
$stmt = $pdo->query($sql);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch sections for dropdown
$sections = $pdo->query("SELECT id, section_name FROM sections")->fetchAll(PDO::FETCH_ASSOC);
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
         
        <div class="table-responsive">
  <table id="teachersTable" class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Email</th>
        <th>Lastname</th>
        <th>Firstname</th>
        <!-- <th>Section</th> -->
        <th>Profile Picture</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($teachers as $t): ?>
        <tr>
          <td><?= $t['id'] ?></td>
          <td><?= htmlspecialchars($t['email']) ?></td>
          <td><?= htmlspecialchars($t['lastname']) ?></td>
          <td><?= htmlspecialchars($t['firstname']) ?></td>
          <!-- <td><?= $t['section_name'] ?: 'N/A' ?></td> -->
          <td>
            <img src="<?= !empty($t['profile_picture']) ? htmlspecialchars($t['profile_picture']) : '../assets/img/v1.png' ?>" 
                 class="img-thumbnail profile-pic" 
                 alt="Profile" width="50" height="50"
                 data-bs-toggle="modal" data-bs-target="#profileModal"
                 data-img="<?= !empty($t['profile_picture']) ? htmlspecialchars($t['profile_picture']) : '../assets/img/smaw.png' ?>">
          </td>
          <td>
            <button class="btn btn-sm btn-warning edit-btn"
                    data-id="<?= $t['id'] ?>"
                    data-email="<?= htmlspecialchars($t['email']) ?>"
                    data-lastname="<?= htmlspecialchars($t['lastname']) ?>"
                    data-firstname="<?= htmlspecialchars($t['firstname']) ?>"
                    data-section="<?= $t['section_id'] ?>">
              Edit
            </button>
            <button class="btn btn-sm btn-danger delete-btn"
                    data-id="<?= $t['id'] ?>">
              Delete
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>


<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border border-warning">
      <div class="modal-header bg-dark text-warning">
        <h5 class="modal-title">Edit Teacher</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editForm">
          <input type="hidden" id="editTeacherId">
          <div class="mb-3">
            <label for="editEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="editEmail" required>
          </div>
          <div class="mb-3">
            <label for="editLastname" class="form-label">Lastname</label>
            <input type="text" class="form-control" id="editLastname" required>
          </div>
          <div class="mb-3">
            <label for="editFirstname" class="form-label">Firstname</label>
            <input type="text" class="form-control" id="editFirstname" required>
          </div>
          <!-- <div class="mb-3">
            <label for="editSectionId" class="form-label">Assign Section</label>
            <select class="form-select" id="editSectionId">
              <option value="">-- Select Section --</option>
              <?php foreach ($sections as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['section_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div> -->
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
        <p>Are you sure you want to delete this teacher?</p>
        <button id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Profile Picture Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border border-warning">
      <div class="modal-header bg-dark text-warning">
        <h5 class="modal-title">Profile Picture</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img id="modalProfileImg" src="" class="img-fluid rounded" alt="Profile">
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

<script>
$(document).ready(function() {
  $('#teachersTable').DataTable();

  let deleteId = null;

  // Profile picture modal
  $('.profile-pic').on('click', function() {
    let imgSrc = $(this).data('img');
    $('#modalProfileImg').attr('src', imgSrc);
  });

  // Edit button click
  $('.edit-btn').on('click', function() {
    $('#editTeacherId').val($(this).data('id'));
    $('#editEmail').val($(this).data('email'));
    $('#editLastname').val($(this).data('lastname'));
    $('#editFirstname').val($(this).data('firstname'));
    $('#editSectionId').val($(this).data('section'));
    $('#editModal').modal('show');
  });

  // Edit form submit
  $('#editForm').on('submit', function(e) {
    e.preventDefault();
    let id = $('#editTeacherId').val();
    let email = $('#editEmail').val();
    let lastname = $('#editLastname').val();
    let firstname = $('#editFirstname').val();
    let sectionId = $('#editSectionId').val();

    $.post('teacher/teacher_update.php', {id, email, lastname, firstname, section_id: sectionId}, function(response) {
      showToast(response.success ? 'Teacher updated!' : response.error, response.success);
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

  // Confirm delete (soft delete)
  $('#confirmDeleteBtn').on('click', function() {
    $.post('teacher/teacher_delete.php', {id: deleteId}, function(response) {
      showToast(response.success ? 'Teacher deleted!' : response.error, response.success);
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
    

</html>