<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        All Requirement Status
    </div>
    <div class="card-body">
        <?php 
        // Ensure $to_query is always set
        if (empty($to_query)) {
            $to_query = '';
        }

        // 1. Get all requirements once
        $query_requirements = "SELECT * FROM requirements";
        $requirements_stmt = secure_query_no_params($pdo, $query_requirements);
        $requirements = $requirements_stmt ? $requirements_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // 2. Get all students
        $query_students = "SELECT * FROM students " . $to_query;
        $students_stmt = secure_query_no_params($pdo, $query_students);
        $students = $students_stmt ? $students_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // 3. Get all statuses in one query
        $query_statuses = "SELECT * FROM requirements_status";
        $statuses_stmt = secure_query_no_params($pdo, $query_statuses);
        $statuses = $statuses_stmt ? $statuses_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // 4. Build a quick lookup: [student_id][req_id] => is_checked
        $status_map = [];
        foreach ($statuses as $s) {
            $status_map[$s['student_id']][$s['req_id']] = (int)$s['is_checked'];
        }
        ?>

        <table id="datatablesSimple">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Section</th>
                    <th>Email</th>
                    <th>Teacher Assigned</th>
                    <?php foreach ($requirements as $req): ?>
                        <th><?= htmlspecialchars($req['req_name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php $loop = 1; ?>
                <?php foreach ($students as $fetched): ?>
                    <?php $section_teacher_holder = get_section_name($pdo, $fetched['section_id']); ?>
                    <tr>
                        <td><?= $loop++ ?></td>
                        <td><?= htmlspecialchars($fetched['lastname']). ' '.htmlspecialchars($fetched['firstname'])  ?></td>
                        <td><?= htmlspecialchars($section_teacher_holder['section_name']) ?></td>
                        <td><?= htmlspecialchars($fetched['email']) ?></td>
                        <?php 
                            if($section_teacher_holder['teacher_id'] == 0 ){
                                    echo "<td>No Assigned Teacher.</td>";
                            }else {
                                ?>
                                <td><?= htmlspecialchars(get_teacher_name($pdo, $section_teacher_holder['teacher_id'])) ?></td>
                            <?php 
                        }
                        ?>
                        

                        <?php foreach ($requirements as $req): 
                            $is_checked = $status_map[$fetched['id']][$req['id']] ?? 0;
                        ?>
                            <td>
                                <input class="statusCheck" 
                                    type="checkbox" 
                                    data-student="<?= $fetched['id'] ?>" 
                                    data-id="<?= $req['id'] ?>" 
                                    <?= $is_checked ? 'checked' : '' ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
