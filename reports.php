<?php
require_once 'config/database.php';
require_once 'auth.php';

requireLogin();

$search_student = $_GET['search_student'] ?? '';
$search_subject = $_GET['search_subject'] ?? '';

// Student performance report
$student_reports = [];
if (!empty($search_student)) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.gender, s.email,
               COUNT(g.id) as total_exams,
               ROUND(AVG(g.score), 2) as avg_score,
               MAX(g.score) as max_score,
               MIN(g.score) as min_score
        FROM students s
        LEFT JOIN grades g ON s.id = g.student_id
        WHERE s.name LIKE ?
        GROUP BY s.id
        ORDER BY s.name
    ");
    $stmt->execute(["%$search_student%"]);
    $student_reports = $stmt->fetchAll();
}

// Subject performance report
$subject_reports = [];
if (!empty($search_subject)) {
    $stmt = $pdo->prepare("
        SELECT sub.id, sub.name, sub.description,
               COUNT(g.id) as total_grades,
               ROUND(AVG(g.score), 2) as avg_score,
               MAX(g.score) as max_score,
               MIN(g.score) as min_score,
               COUNT(DISTINCT g.student_id) as student_count
        FROM subjects sub
        LEFT JOIN grades g ON sub.id = g.subject_id
        WHERE sub.name LIKE ?
        GROUP BY sub.id
        ORDER BY sub.name
    ");
    $stmt->execute(["%$search_subject%"]);
    $subject_reports = $stmt->fetchAll();
}

// Overall statistics
$overall_stats = $pdo->query("
    SELECT 
        COUNT(DISTINCT s.id) as total_students,
        COUNT(DISTINCT sub.id) as total_subjects,
        COUNT(g.id) as total_grades,
        ROUND(AVG(g.score), 2) as overall_avg
    FROM students s
    CROSS JOIN subjects sub
    LEFT JOIN grades g ON s.id = g.student_id AND sub.id = g.subject_id
")->fetch();

// Top performing students
$top_students = $pdo->query("
    SELECT s.name, ROUND(AVG(g.score), 2) as avg_score, COUNT(g.id) as exam_count
    FROM students s
    JOIN grades g ON s.id = g.student_id
    GROUP BY s.id, s.name
    HAVING exam_count >= 2
    ORDER BY avg_score DESC
    LIMIT 5
")->fetchAll();

// Grade distribution
$grade_distribution = $pdo->query("
    SELECT 
        CASE 
            WHEN score >= 90 THEN 'A'
            WHEN score >= 80 THEN 'B'
            WHEN score >= 70 THEN 'C'
            WHEN score >= 60 THEN 'D'
            ELSE 'F'
        END as letter_grade,
        COUNT(*) as count
    FROM grades
    GROUP BY letter_grade
    ORDER BY letter_grade
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .report-card {
            transition: transform 0.2s;
            border-left: 4px solid #17a2b8;
        }
        .report-card:hover {
            transform: translateY(-2px);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar d-flex flex-column p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-graduation-cap fa-2x text-white mb-2"></i>
                        <h5 class="text-white">SMS</h5>
                    </div>

                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link text-white">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="students.php" class="nav-link text-white">
                                <i class="fas fa-users me-2"></i> Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="subjects.php" class="nav-link text-white">
                                <i class="fas fa-book me-2"></i> Subjects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="grades.php" class="nav-link text-white">
                                <i class="fas fa-chart-line me-2"></i> Grades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports.php" class="nav-link text-white active">
                                <i class="fas fa-file-alt me-2"></i> Reports
                            </a>
                        </li>
                    </ul>

                    <hr class="text-white">
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" 
                           id="dropdownUser" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-file-alt me-2"></i>Reports & Analytics</h2>
                         
                    </div>

                    <!-- Overall Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card report-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <h3 class="card-title"><?= $overall_stats['total_students'] ?></h3>
                                    <p class="card-text text-muted">Total Students</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card report-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-book fa-2x text-success mb-2"></i>
                                    <h3 class="card-title"><?= $overall_stats['total_subjects'] ?></h3>
                                    <p class="card-text text-muted">Total Subjects</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card report-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-chart-line fa-2x text-warning mb-2"></i>
                                    <h3 class="card-title"><?= $overall_stats['total_grades'] ?></h3>
                                    <p class="card-text text-muted">Total Grades</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card report-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-percentage fa-2x text-info mb-2"></i>
                                    <h3 class="card-title"><?= number_format($overall_stats['overall_avg'], 1) ?>%</h3>
                                    <p class="card-text text-muted">Overall Average</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Student Search & Report -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-user-graduate me-2"></i>Student Performance Report
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="mb-3">
                                        <div class="input-group">
                                            <input type="text" name="search_student" class="form-control" 
                                                   placeholder="Search student by name..." 
                                                   value="<?= htmlspecialchars($search_student) ?>">
                                            <input type="hidden" name="search_subject" value="<?= htmlspecialchars($search_subject) ?>">
                                            <button type="submit" class="btn btn-outline-primary">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </form>

                                    <?php if (!empty($student_reports)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Student</th>
                                                        <th>Exams</th>
                                                        <th>Average</th>
                                                        <th>Best/Worst</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($student_reports as $report): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?= htmlspecialchars($report['name']) ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?= $report['gender'] ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary"><?= $report['total_exams'] ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="fw-bold"><?= number_format($report['avg_score'], 1) ?>%</span>
                                                            </td>
                                                            <td>
                                                                <small>
                                                                    <span class="text-success"><?= number_format($report['max_score'], 1) ?>%</span> / 
                                                                    <span class="text-danger"><?= number_format($report['min_score'], 1) ?>%</span>
                                                                </small>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php elseif (!empty($search_student)): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-search fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No students found matching your search.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-user-graduate fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">Search for a student to view their performance report.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Subject Search & Report -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-book me-2"></i>Subject Performance Report
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="mb-3">
                                        <div class="input-group">
                                            <input type="text" name="search_subject" class="form-control" 
                                                   placeholder="Search subject by name..." 
                                                   value="<?= htmlspecialchars($search_subject) ?>">
                                            <input type="hidden" name="search_student" value="<?= htmlspecialchars($search_student) ?>">
                                            <button type="submit" class="btn btn-outline-success">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </form>

                                    <?php if (!empty($subject_reports)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Subject</th>
                                                        <th>Students</th>
                                                        <th>Average</th>
                                                        <th>Range</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($subject_reports as $report): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?= htmlspecialchars($report['name']) ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info"><?= $report['student_count'] ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="fw-bold"><?= number_format($report['avg_score'], 1) ?>%</span>
                                                            </td>
                                                            <td>
                                                                <small>
                                                                    <?= number_format($report['min_score'], 1) ?>% - 
                                                                    <?= number_format($report['max_score'], 1) ?>%
                                                                </small>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php elseif (!empty($search_subject)): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-search fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No subjects found matching your search.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-book fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">Search for a subject to view its performance report.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Top Students -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-trophy me-2"></i>Top Performing Students
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($top_students)): ?>
                                        <?php foreach ($top_students as $index => $student): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded 
                                                        <?= $index === 0 ? 'bg-warning bg-opacity-25' : ($index < 3 ? 'bg-light' : '') ?>">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-<?= $index === 0 ? 'warning' : 'secondary' ?> me-2">
                                                        #<?= $index + 1 ?>
                                                    </span>
                                                    <strong><?= htmlspecialchars($student['name']) ?></strong>
                                                    <small class="text-muted ms-2">(<?= $student['exam_count'] ?> exams)</small>
                                                </div>
                                                <span class="badge bg-success"><?= number_format($student['avg_score'], 1) ?>%</span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-trophy fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No student data available yet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Grade Distribution Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>Grade Distribution
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($grade_distribution)): ?>
                                        <div class="chart-container">
                                            <canvas id="gradeChart"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <div class="row text-center">
                                                <?php foreach ($grade_distribution as $grade): ?>
                                                    <div class="col">
                                                        <div class="border rounded p-2">
                                                            <strong><?= $grade['letter_grade'] ?></strong>
                                                            <br>
                                                            <span class="text-muted"><?= $grade['count'] ?></span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No grade data available yet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($grade_distribution)): ?>
    <script>
        // Grade Distribution Chart
        const ctx = document.getElementById('gradeChart').getContext('2d');
        const gradeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($grade_distribution, 'letter_grade')) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($grade_distribution, 'count')); ?>],
                    backgroundColor: [
                        '#28a745', // A - Green
                        '#007bff', // B - Blue  
                        '#17a2b8', // C - Info
                        '#ffc107', // D - Warning
                        '#dc3545'  // F - Danger
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed * 100) / total).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>