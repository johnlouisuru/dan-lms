 <!-- Total Students -->
        <?php
          // Query to count students
          $stmt = $pdo->query("SELECT COUNT(id) AS total_students FROM students");
          $row  = $stmt->fetch(PDO::FETCH_ASSOC);

          $total_students = $row ? $row['total_students'] : 0;
        ?>
        
          <div class="col-xl-6 col-sm-6 mb-xl-0 mb-4">
            <a href="students">
            <div class="card">
              <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                  <div>
                    <p class="text-sm mb-0 text-capitalize">All Students</p>
                    <h4 class="mb-0"><?= htmlspecialchars($total_students) ?></h4>
                  </div>
                  <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                    <i class="material-symbols-rounded opacity-10">weekend</i>
                  </div>
                </div>
              </div>
            </div>
            </a>
          </div>
        
            <!-- Total Sections -->
             <?php
          // Query to count sections
          $stmt = $pdo->query("SELECT COUNT(id) AS total_sections FROM sections");
          $row  = $stmt->fetch(PDO::FETCH_ASSOC);

          $total_sections = $row ? $row['total_sections'] : 0;
        ?>

          <!-- <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <a href="sections">
            <div class="card">
              <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                  <div>
                    <p class="text-sm mb-0 text-capitalize">All Sections</p>
                    <h4 class="mb-0"><?= htmlspecialchars($total_sections) ?></h4>
                  </div>
                  <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                    <i class="material-symbols-rounded opacity-10">weekend</i>
                  </div>
                </div>
              </div>
            </div>
            </a>
          </div> -->

          <!-- Total Teachers -->
             <?php
          // Query to count teachers
          $stmt = $pdo->query("SELECT COUNT(id) AS total_teachers FROM teachers");
          $row  = $stmt->fetch(PDO::FETCH_ASSOC);

          $total_teachers = $row ? $row['total_teachers'] : 0;
        ?>

          <div class="col-xl-6 col-sm-6 mb-xl-0 mb-4">
          <a href="teachers">
            <div class="card">
              <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                  <div>
                    <p class="text-sm mb-0 text-capitalize">All Teachers</p>
                    <h4 class="mb-0"><?= htmlspecialchars($total_teachers) ?></h4>
                  </div>
                  <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                    <i class="material-symbols-rounded opacity-10">weekend</i>
                  </div>
                </div>
              </div>
            </div>
            </a>
          </div>

          <!-- Total Levels -->
             <?php
          // Query to count Levels
          // $stmt = $pdo->query("SELECT COUNT(id) AS all_levels FROM levels");
          // $row  = $stmt->fetch(PDO::FETCH_ASSOC);

          // $all_levels = $row ? $row['all_levels'] : 0;
        ?>

          <!-- <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <a href="stages">
            <div class="card">
              <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                  <div>
                    <p class="text-sm mb-0 text-capitalize">All Stages</p>
                    <h4 class="mb-0"><?= htmlspecialchars($all_levels) ?></h4>
                  </div>
                  <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                    <i class="material-symbols-rounded opacity-10">weekend</i>
                  </div>
                </div>
              </div>
            </div>
            </a>
          </div> -->