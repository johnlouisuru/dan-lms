<?php require_once "../db-config/security.php";

    // If already logged in and profile complete, redirect to dashboard
    if (!isLoggedIn()) {
        header('Location: ../');
        exit;
    }
// Fetch levels with stage info
$sql = "SELECT l.id, l.level_number, l.level_name, l.stage_id,
               s.stage_name, s.stage_difficulty
        FROM levels l
        LEFT JOIN stages s ON s.id = l.stage_id";
$stmt = $pdo->query($sql);
$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch stages for dropdown
$stages = $pdo->query("SELECT id, stage_name, stage_difficulty FROM stages")->fetchAll(PDO::FETCH_ASSOC);

// Fetch quizzes with stage difficulty via levels + stages
$sql = "SELECT q.id, q.question, q.correct_answer, q.stage_id,
               l.level_number, s.stage_difficulty
        FROM quizzes q
        LEFT JOIN levels l ON l.level_number = q.stage_id
        LEFT JOIN stages s ON s.id = l.stage_id";
$stmt = $pdo->query($sql);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
        
        <div class="table-responsive mb-5">
  <table id="levelsTable" class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Level Number</th>
        <th>Level Name</th>
        <th>Stage</th>
        <th>Stage Difficulty</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($levels as $l): ?>
        <tr>
          <td><?= $l['id'] ?></td>
          <td><?= htmlspecialchars($l['level_number']) ?></td>
          <td><?= htmlspecialchars($l['level_name']) ?></td>
          <td><?= htmlspecialchars($l['stage_name']) ?></td>
          <td><?= htmlspecialchars($l['stage_difficulty']) ?></td>
          <td>
            <button class="btn btn-sm btn-warning edit-level-btn"
                    data-id="<?= $l['id'] ?>"
                    data-number="<?= $l['level_number'] ?>"
                    data-name="<?= htmlspecialchars($l['level_name']) ?>"
                    data-stage="<?= $l['stage_id'] ?>">
              Edit
            </button>
            <button class="btn btn-sm btn-danger delete-level-btn"
                    data-id="<?= $l['id'] ?>">
              Delete
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Edit Level Modal -->
<div class="modal fade" id="editLevelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border border-warning">
      <div class="modal-header bg-dark text-warning">
        <h5 class="modal-title">Edit Level</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editLevelForm">
          <input type="hidden" id="editLevelId">
          <div class="mb-3">
            <label class="form-label">Level Number</label>
            <input type="text" class="form-control" id="editLevelNumber" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Level Name</label>
            <input type="text" class="form-control" id="editLevelName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Stage</label>
            <select class="form-select" id="editStageId">
              <?php foreach ($stages as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['stage_name']) ?> (<?= htmlspecialchars($s['stage_difficulty']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-warning">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Delete Level Modal -->
<div class="modal fade" id="deleteLevelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border border-danger">
      <div class="modal-header bg-dark text-danger">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this level?</p>
        <button id="confirmDeleteLevelBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-end mb-3"> 
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addQuizModal"> 
        <i class="bi bi-plus-circle"></i> Add New Quiz </button> 
    </div>
<div class="table-responsive">
  <table id="quizzesTable" class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Question</th>
        <th>Correct Answer</th>
        <th>Level Number</th>
        <th>Stage Difficulty</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($quizzes as $q): ?>
        <tr>
          <td><?= $q['id'] ?></td>
          <td><?= htmlspecialchars($q['question']) ?></td>
          <td><?= htmlspecialchars($q['correct_answer']) ?></td>
          <td><?= htmlspecialchars($q['level_number']) ?></td>
          <td><?= htmlspecialchars($q['stage_difficulty']) ?></td>
          <td>
            <button class="btn btn-sm btn-warning edit-quiz-btn"
                    data-id="<?= $q['id'] ?>"
                    data-question="<?= htmlspecialchars($q['question']) ?>"
                    data-answer="<?= htmlspecialchars($q['correct_answer']) ?>"
                    data-stage="<?= $q['stage_id'] ?>">
              Edit
            </button>
            <button class="btn btn-sm btn-danger delete-quiz-btn"
                    data-id="<?= $q['id'] ?>">
              Delete
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Quiz Modal -->
<div class="modal fade" id="addQuizModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border border-success">
      <div class="modal-header bg-dark text-success">
        <h5 class="modal-title">Add New Quiz</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="addQuizForm">
          <div class="mb-3">
            <label class="form-label">Question</label>
            <textarea class="form-control" id="addQuizQuestion" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Correct Answer</label>
            <input type="text" class="form-control" id="addQuizAnswer" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Stage</label>
            <select class="form-select" id="addQuizStageId" required>
              <option value="">-- Select Stage --</option>
              <?php
              $stageOptions = $pdo->query("
                SELECT l.level_number, s.stage_name, s.stage_difficulty
                FROM levels l
                JOIN stages s ON s.id = l.stage_id
                GROUP BY s.stage_difficulty
              ")->fetchAll(PDO::FETCH_ASSOC);

              foreach ($stageOptions as $opt) {
                  echo "<option value='{$opt['level_number']}'>" .
                       htmlspecialchars($opt['stage_name'] . " - " . $opt['stage_difficulty']) .
                       "</option>";
              }
              ?>
            </select>
          </div>
          <button type="submit" class="btn btn-success">Add Quiz</button>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- Edit Quiz Modal -->
<div class="modal fade" id="editQuizModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border border-warning">
      <div class="modal-header bg-dark text-warning">
        <h5 class="modal-title">Edit Quiz</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editQuizForm">
          <input type="hidden" id="editQuizId">
          <div class="mb-3">
            <label class="form-label">Question</label>
            <textarea class="form-control" id="editQuizQuestion" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Correct Answer</label>
            <input type="text" class="form-control" id="editQuizAnswer" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Stage ID (Level Number)</label>
            <input type="number" class="form-control" id="editQuizStageId" required>
          </div>
          <button type="submit" class="btn btn-warning">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Delete Quiz Modal -->
<div class="modal fade" id="deleteQuizModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border border-danger">
      <div class="modal-header bg-dark text-danger">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this quiz?</p>
        <button id="confirmDeleteQuizBtn" class="btn btn-danger">Delete</button>
      </div>
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

  
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
  <div id="ajaxToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div id="toastMessage" class="toast-body">Updated successfully!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
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
  $('#levelsTable').DataTable();
  $('#quizzesTable').DataTable();

  let deleteLevelId = null;
  let deleteQuizId = null;



  // ---------------- LEVELS ----------------

  // Edit Level button
  $('.edit-level-btn').on('click', function() {
    $('#editLevelId').val($(this).data('id'));
    $('#editLevelNumber').val($(this).data('number'));
    $('#editLevelName').val($(this).data('name'));
    $('#editStageId').val($(this).data('stage'));
    $('#editLevelModal').modal('show');
  });

  // Submit Level edit form
  $('#editLevelForm').on('submit', function(e) {
    e.preventDefault();
    let id = $('#editLevelId').val();
    let number = $('#editLevelNumber').val();
    let name = $('#editLevelName').val();
    let stageId = $('#editStageId').val();

    $.post('level/levels_update.php', {id, level_number: number, level_name: name, stage_id: stageId}, function(response) {
      showToast(response.success ? 'Level updated!' : response.error, response.success);
      if(response.success)
        setTimeout(function() {
            location.reload();
        }, 2000);
    }, 'json');
  });

  // Delete Level button
  $('.delete-level-btn').on('click', function() {
    deleteLevelId = $(this).data('id');
    $('#deleteLevelModal').modal('show');
  });

  // Confirm Delete Level
  $('#confirmDeleteLevelBtn').on('click', function() {
    $.post('level/levels_delete.php', {id: deleteLevelId}, function(response) {
      showToast(response.success ? 'Level deleted!' : response.error, response.success);
      if(response.success)
        setTimeout(function() {
            location.reload();
        }, 2000);
    }, 'json');
    setTimeout(function() {
            location.reload();
        }, 2000);
  });

  // ---------------- QUIZZES ----------------

  $('#addQuizForm').on('submit', 
  function(e) { e.preventDefault();
  let question = $('#addQuizQuestion').val(); 
  let answer = $('#addQuizAnswer').val(); 
  let stageId = $('#addQuizStageId').val(); 
  $.post('quiz/quizzes_add.php', {question, correct_answer: answer, stage_id: stageId},  
    function(response) {
      showToast(response.success ? 'Quiz updated!' : response.error, response.success);
      if(response.success)
        setTimeout(function() {
            location.reload();
        }, 2000);
    }, 'json');
    setTimeout(function() {
            location.reload();
        }, 2000);
    });

  // Edit Quiz button
  $('#quizzesTable').on('click', '.edit-quiz-btn', function() {
  $('#editQuizId').val($(this).data('id'));
  $('#editQuizQuestion').val($(this).data('question'));
  $('#editQuizAnswer').val($(this).data('answer'));
  $('#editQuizStageId').val($(this).data('stage'));
  $('#editQuizModal').modal('show');
});


  // Submit Quiz edit form
  $('#editQuizForm').on('submit', function(e) {
    e.preventDefault();
    let id = $('#editQuizId').val();
    let question = $('#editQuizQuestion').val();
    let answer = $('#editQuizAnswer').val();
    let stageId = $('#editQuizStageId').val();

    $.post('quiz/quizzes_update.php', {id, question, correct_answer: answer, stage_id: stageId}, 
    function(response) {
      showToast(response.success ? 'Quiz updated!' : response.error, response.success);
      if(response.success)
        setTimeout(function() {
            location.reload();
        }, 2000);
    }, 'json');
    setTimeout(function() {
            location.reload();
        }, 2000);
  });

  // Delete Quiz button
$('#quizzesTable').on('click', '.delete-quiz-btn', function() {
  let id = $(this).data('id');
  deleteQuizId = id;
  $('#deleteQuizModal').modal('show');
});


  // Confirm Delete Quiz
  $('#confirmDeleteQuizBtn').on('click', function() {
    $.post('quiz/quizzes_delete.php', {id: deleteQuizId}, function(response) {
      showToast(response.success ? 'Quiz deleted!' : response.error, response.success);
      if(response.success)
        setTimeout(function() {
            location.reload();
        }, 2000);
    }, 'json');
    setTimeout(function() {
            location.reload();
        }, 2000);
  });

  // ---------------- TOAST HELPER ----------------

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