<?php
require_once 'config/database.php';
require_once 'auth.php';

requireLogin();

// Get statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
    $total_students = $stmt->fetch()['total_students'];

    $stmt = $pdo->query("SELECT COUNT(*) as total_subjects FROM subjects");
    $total_subjects = $stmt->fetch()['total_subjects'];

    $stmt = $pdo->query("SELECT COUNT(*) as total_grades FROM grades");
    $total_grades = $stmt->fetch()['total_grades'];

    $stmt = $pdo->query("SELECT ROUND(AVG(score), 2) as avg_score FROM grades");
    $avg_score = $stmt->fetch()['avg_score'] ?? 0;
} catch (PDOException $e) {
    $error = "Error fetching statistics";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-card.primary { border-left-color: #0d6efd; }
        .stat-card.success { border-left-color: #198754; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.info { border-left-color: #0dcaf0; }
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
                        <small class="text-light">Student Management</small>
                    </div>

                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link text-white active">
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
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                        <span class="badge bg-primary">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card primary h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <p class="card-text text-muted mb-1">Total Students</p>
                                            <h3 class="card-title"><?= $total_students ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-users fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stat-card success h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <p class="card-text text-muted mb-1">Total Subjects</p>
                                            <h3 class="card-title"><?= $total_subjects ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-book fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stat-card warning h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <p class="card-text text-muted mb-1">Total Grades</p>
                                            <h3 class="card-title"><?= $total_grades ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-chart-line fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stat-card info h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <p class="card-text text-muted mb-1">Average Score</p>
                                            <h3 class="card-title"><?= number_format($avg_score, 1) ?>%</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-percentage fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2"></i>Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="students.php?action=add" class="btn btn-outline-primary">
                                            <i class="fas fa-user-plus me-2"></i>Add New Student
                                        </a>
                                        <a href="subjects.php?action=add" class="btn btn-outline-success">
                                            <i class="fas fa-book me-2"></i>Add New Subject
                                        </a>
                                        <a href="grades.php?action=add" class="btn btn-outline-warning">
                                            <i class="fas fa-plus me-2"></i>Add Grade
                                        </a>
                                        <a href="reports.php" class="btn btn-outline-info">
                                            <i class="fas fa-file-alt me-2"></i>View Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>System Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>System Version:</strong> 1.0</p>
                                    <p><strong>Database:</strong> MySQL</p>
                                    <p><strong>Framework:</strong> Bootstrap 5</p>
                                    <p><strong>Last Login:</strong> <?= date('Y-m-d H:i:s') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>