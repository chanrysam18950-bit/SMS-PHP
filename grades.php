<?php
require_once 'config/database.php';
require_once 'auth.php';

requireLogin();

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$grade_id = $_GET['id'] ?? null;
$student_filter = $_GET['student_id'] ?? '';
$subject_filter = $_GET['subject_id'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $score = floatval($_POST['score']);
    $exam_date = $_POST['exam_date'];

    if (empty($student_id) || empty($subject_id) || empty($score) || empty($exam_date)) {
        $error = 'All fields are required.';
    } elseif ($score < 0 || $score > 100) {
        $error = 'Score must be between 0 and 100.';
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject_id, score, exam_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$student_id, $subject_id, $score, $exam_date]);
                $message = 'Grade added successfully!';
                $action = 'list';
            } elseif ($action === 'edit' && $grade_id) {
                $stmt = $pdo->prepare("UPDATE grades SET student_id = ?, subject_id = ?, score = ?, exam_date = ? WHERE id = ?");
                $stmt->execute([$student_id, $subject_id, $score, $exam_date, $grade_id]);
                $message = 'Grade updated successfully!';
                $action = 'list';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($action === 'delete' && $grade_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
        $stmt->execute([$grade_id]);
        $message = 'Grade deleted successfully!';
        $action = 'list';
    } catch (PDOException $e) {
        $error = 'Error deleting grade.';
    }
}

// Get grade data for edit
$grade = null;
if ($action === 'edit' && $grade_id) {
    $stmt = $pdo->prepare("SELECT * FROM grades WHERE id = ?");
    $stmt->execute([$grade_id]);
    $grade = $stmt->fetch();
    if (!$grade) {
        $error = 'Grade not found.';
        $action = 'list';
    }
}

// Get students and subjects for dropdowns
$students = $pdo->query("SELECT id, name FROM students ORDER BY name")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects ORDER BY name")->fetchAll();

// Get grades for list view
$grades = [];
if ($action === 'list') {
    $sql = "SELECT g.*, s.name as student_name, sub.name as subject_name 
            FROM grades g 
            JOIN students s ON g.student_id = s.id 
            JOIN subjects sub ON g.subject_id = sub.id";
    
    $conditions = [];
    $params = [];
    
    if ($student_filter) {
        $conditions[] = "g.student_id = ?";
        $params[] = $student_filter;
    }
    
    if ($subject_filter) {
        $conditions[] = "g.subject_id = ?";
        $params[] = $subject_filter;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY g.exam_date DESC, s.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $grades = $stmt->fetchAll();
}

function getGradeLetter($score) {
    if ($score >= 90) return 'A';
    if ($score >= 80) return 'B';
    if ($score >= 70) return 'C';
    if ($score >= 60) return 'D';
    return 'F';
}

function getGradeColor($score) {
    if ($score >= 90) return 'success';
    if ($score >= 80) return 'primary';
    if ($score >= 70) return 'info';
    if ($score >= 60) return 'warning';
    return 'danger';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Management - SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .grade-badge {
            font-size: 1.2em;
            font-weight: bold;
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
                            <a href="grades.php" class="nav-link text-white active">
                                <i class="fas fa-chart-line me-2"></i> Grades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports.php" class="nav-link text-white">
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
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($action === 'list'): ?>
                        <!-- Grade List -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-chart-line me-2"></i>Grade Management</h2>
                            <a href="grades.php?action=add" class="btn btn-warning">
                                <i class="fas fa-plus me-2"></i>Add Grade
                            </a>
                        </div>

                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <label for="student_id" class="form-label">Filter by Student</label>
                                        <select class="form-select" name="student_id" id="student_id">
                                            <option value="">All Students</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?= $student['id'] ?>" 
                                                        <?= $student_filter == $student['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($student['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="subject_id" class="form-label">Filter by Subject</label>
                                        <select class="form-select" name="subject_id" id="subject_id">
                                            <option value="">All Subjects</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?= $subject['id'] ?>" 
                                                        <?= $subject_filter == $subject['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($subject['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-outline-primary me-2">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <a href="grades.php" class="btn btn-outline-secondary">Clear</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Grades Table -->
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($grades)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                        <h5>No grades found</h5>
                                        <p class="text-muted">Start by adding grades for your students.</p>
                                        <a href="grades.php?action=add" class="btn btn-warning">Add Grade</a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Subject</th>
                                                    <th>Score</th>
                                                    <th>Grade</th>
                                                    <th>Exam Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($grades as $g): ?>
                                                    <tr>
                                                        <td>
                                                            <i class="fas fa-user text-muted me-2"></i>
                                                            <strong><?= htmlspecialchars($g['student_name']) ?></strong>
                                                        </td>
                                                        <td>
                                                            <i class="fas fa-book text-muted me-2"></i>
                                                            <?= htmlspecialchars($g['subject_name']) ?>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold"><?= number_format($g['score'], 1) ?>%</span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= getGradeColor($g['score']) ?> grade-badge">
                                                                <?= getGradeLetter($g['score']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <i class="fas fa-calendar text-muted me-2"></i>
                                                            <?= date('M d, Y', strtotime($g['exam_date'])) ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="grades.php?action=edit&id=<?= $g['id'] ?>" 
                                                                   class="btn btn-outline-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="grades.php?action=delete&id=<?= $g['id'] ?>" 
                                                                   class="btn btn-outline-danger"
                                                                   onclick="return confirm('Are you sure you want to delete this grade?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'add' || $action === 'edit'): ?>
                        <!-- Add/Edit Grade Form -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>
                                <i class="fas fa-<?= $action === 'add' ? 'plus' : 'edit' ?> me-2"></i>
                                <?= ucfirst($action) ?> Grade
                            </h2>
                            <a href="grades.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="student_id" class="form-label">
                                                <i class="fas fa-user"></i> Student *
                                            </label>
                                            <select class="form-select" id="student_id" name="student_id" required>
                                                <option value="">Select Student</option>
                                                <?php foreach ($students as $student): ?>
                                                    <option value="<?= $student['id'] ?>" 
                                                            <?= ($grade['student_id'] ?? '') == $student['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($student['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="subject_id" class="form-label">
                                                <i class="fas fa-book"></i> Subject *
                                            </label>
                                            <select class="form-select" id="subject_id" name="subject_id" required>
                                                <option value="">Select Subject</option>
                                                <?php foreach ($subjects as $subject): ?>
                                                    <option value="<?= $subject['id'] ?>" 
                                                            <?= ($grade['subject_id'] ?? '') == $subject['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($subject['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="score" class="form-label">
                                                <i class="fas fa-percentage"></i> Score (0-100) *
                                            </label>
                                            <input type="number" class="form-control" id="score" name="score" 
                                                   min="0" max="100" step="0.01" 
                                                   value="<?= htmlspecialchars($grade['score'] ?? '') ?>" 
                                                   placeholder="Enter score" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="exam_date" class="form-label">
                                                <i class="fas fa-calendar"></i> Exam Date *
                                            </label>
                                            <input type="date" class="form-control" id="exam_date" name="exam_date"
                                                   value="<?= htmlspecialchars($grade['exam_date'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save me-2"></i>
                                            <?= $action === 'add' ? 'Add Grade' : 'Update Grade' ?>
                                        </button>
                                        <a href="grades.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>