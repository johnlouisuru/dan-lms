<?php require_once "../db-config/security.php";

// If already logged in and profile complete, redirect to dashboard
if (!isLoggedIn()) {
    header('Location: ../');
    exit;
}

$sql = "SELECT q.id AS quiz_id, COUNT(qwa.id) AS total_submitted
        FROM quizzes q
        LEFT JOIN quiz_work_attachment qwa ON q.id = qwa.quiz_id
        GROUP BY q.id
        ORDER BY q.id";

$stmt = $pdo->query($sql);

// Prepare arrays for Chart.js
$labels = [];
$data   = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Use quiz title if you have it, otherwise just "Quiz X"
    $labels[] = "Quiz " . $row['quiz_id'];
    $data[]   = (int)$row['total_submitted'];
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../<?=$_ENV['PAGE_ICON']?>">
  <link rel="icon" type="image/png" href="../<?=$_ENV['PAGE_ICON']?>">
  <title><?=$_ENV['PAGE_HEADER']?></title>
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
          <h3 class="mb-0 h4 font-weight-bolder">Dashboard</h3>
          <p class="mb-4">
            Main Dashboard for the ALS Admin
          </p>
        </div>

        <!-- Card -->
         
        <?php 
            require_once "cards/dashboard-cards.php";
        ?>

        <!-- End of Card -->
      </div>
      <div class="row">
        <div class="col-lg-6 col-md-6 mt-4 mb-4">
          <div class="card">
            <div class="card-body">
              <h6 class="mb-0 ">Total Students Submission</h6>
              <p class="text-sm ">Student's Quizzes</p>
              <div class="pe-2">
                <div class="chart">
                  <canvas id="chart-bars" class="chart-canvas" height="170"></canvas>
                </div>
              </div>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm"> Student's Performance Minutes Ago. </p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6 col-md-6 mt-4 mb-4">
          <div class="card">
            <div class="card-body">
              <h6 class="mb-0 ">Total Students Submission</h6>
              <p class="text-sm ">Student's Assignments</p>
              <div class="pe-2">
                <div class="chart">
                  <canvas id="chart-bars2" class="chart-canvas" height="170"></canvas>
                </div>
              </div>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm"> Student's Performance Minutes Ago. </p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-12 mt-4 mb-3">
          <div class="card">
            <div class="card-body">
              <h6 class="mb-0 ">Student's Quizzes and Assignments Submission</h6>
              <p class="text-sm ">Student's Performance</p>
              <div class="pe-2">
                <div class="chart">
                  <canvas id="chart-line-tasks" class="chart-canvas" height="170"></canvas>
                </div>
              </div>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm">just updated</p>
              </div>
            </div>
          </div>
        </div>
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
  <script>
    var ctx = document.getElementById("chart-bars").getContext("2d");

new Chart(ctx, {
  type: "bar",
  data: {
    labels: <?= json_encode($labels) ?>,
  datasets: [{
    label: "Students Submission",
    backgroundColor: "#f9ed0bff",
    data: <?= json_encode($data) ?>,
  }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    interaction: {
      intersect: false,
      mode: 'index',
    },
    scales: {
      y: {
        grid: {
          drawBorder: false,
          beginAtZero: true,
          display: true,
          drawOnChartArea: true,
          drawTicks: false,
          borderDash: [5, 5],
          color: '#e5e5e5'
        },
        ticks: {
          suggestedMin: 0,
          beginAtZero: true,
          padding: 10,
          font: { size: 14, lineHeight: 2 },
          color: "#737373"
        },
      },
      x: {
        grid: {
          drawBorder: false,
          display: false,
          drawOnChartArea: false,
          drawTicks: false,
          borderDash: [5, 5]
        },
        ticks: {
          display: true,
          color: '#737373',
          padding: 10,
          font: { size: 14, lineHeight: 2 },
        }
      },
    },
  },
});

</script>
<?php
$sql = "SELECT a.id AS assignment_id, COUNT(awa.id) AS total_submitted
        FROM assignments a
        LEFT JOIN assignment_work_attachment awa ON a.id = awa.assignment_id
        GROUP BY a.id
        ORDER BY a.id";

$stmt = $pdo->query($sql);

$labels = [];
$data   = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Use assignment title if available, otherwise just "Assignment X"
    $labels[] = "Asgmnt " . $row['assignment_id'];
    $data[]   = (int)$row['total_submitted'];
}
?>

<script>
    var ctx = document.getElementById("chart-bars2").getContext("2d");

new Chart(ctx, {
  type: "bar",
  data: {
    labels: <?= json_encode($labels) ?>,
datasets: [{
  label: "Students Submitted",
  backgroundColor: "#30803dff",
  data: <?= json_encode($data) ?>,
}]
,
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    interaction: {
      intersect: false,
      mode: 'index',
    },
    scales: {
      y: {
        grid: {
          drawBorder: false,
          beginAtZero: true,
          display: true,
          drawOnChartArea: true,
          drawTicks: false,
          borderDash: [5, 5],
          color: '#e5e5e5'
        },
        ticks: {
          suggestedMin: 0,
          beginAtZero: true,
          padding: 10,
          font: { size: 14, lineHeight: 2 },
          color: "#737373"
        },
      },
      x: {
        grid: {
          drawBorder: false,
          display: false,
          drawOnChartArea: false,
          drawTicks: false,
          borderDash: [5, 5]
        },
        ticks: {
          display: true,
          color: '#737373',
          padding: 10,
          font: { size: 14, lineHeight: 2 },
        }
      },
    },
  },
});

</script>

<?php
// Quiz submissions
$sqlQuiz = "SELECT quiz_id, COUNT(id) AS total_submitted
            FROM quiz_work_attachment
            GROUP BY quiz_id
            ORDER BY quiz_id";
$stmtQuiz = $pdo->query($sqlQuiz);
$quizData = $stmtQuiz->fetchAll(PDO::FETCH_ASSOC);

// Assignment submissions
$sqlAssign = "SELECT assignment_id, COUNT(id) AS total_submitted
              FROM assignment_work_attachment
              GROUP BY assignment_id
              ORDER BY assignment_id";
$stmtAssign = $pdo->query($sqlAssign);
$assignData = $stmtAssign->fetchAll(PDO::FETCH_ASSOC);

// Build arrays for Chart.js
$quizCounts   = [];
$assignCounts = [];
$labels       = [];

// Normalize labels so both datasets align
$maxQuizId    = !empty($quizData) ? max(array_column($quizData, 'quiz_id')) : 0;
$maxAssignId  = !empty($assignData) ? max(array_column($assignData, 'assignment_id')) : 0;
$highestId    = max($maxQuizId, $maxAssignId);

// Loop through IDs up to highestId
for ($i = 1; $i <= $highestId; $i++) {
    $labels[] = $i;

    // Find quiz submissions for this ID
    $quizRow = array_filter($quizData, fn($row) => $row['quiz_id'] == $i);
    $quizCounts[] = $quizRow ? (int)array_values($quizRow)[0]['total_submitted'] : 0;

    // Find assignment submissions for this ID
    $assignRow = array_filter($assignData, fn($row) => $row['assignment_id'] == $i);
    $assignCounts[] = $assignRow ? (int)array_values($assignRow)[0]['total_submitted'] : 0;
}
?>



<script>
var ctx = document.getElementById("chart-line-tasks").getContext("2d");

new Chart(ctx, {
  type: "line",
  data: {
    labels: <?= json_encode($labels) ?>, // IDs from 1 to highest
    datasets: [
      {
        label: "Quiz Submissions",
        borderColor: "yellow",
        backgroundColor: "yellow",
        fill: false,
        tension: 0.3,
        data: <?= json_encode($quizCounts) ?>
      },
      {
        label: "Assignment Submissions",
        borderColor: "green",
        backgroundColor: "green",
        fill: false,
        tension: 0.3,
        data: <?= json_encode($assignCounts) ?>
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: true }
    },
    scales: {
      y: {
        beginAtZero: true,
        suggestedMax: <?= $highestId ?>,
        ticks: { color: "#737373" }
      },
      x: {
        ticks: { color: "#737373" }
      }
    }
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