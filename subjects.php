<?php
require_once 'config/database.php';
require_once 'auth.php';
requireLogin();

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$subject_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $error = 'Subject name is required.';
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO subjects (name) VALUES (?)");
                $stmt->execute([$name]);
                $message = 'Subject added successfully!';
                $action = 'list';
            } elseif ($action === 'edit' && $subject_id) {
                $stmt = $pdo->prepare("UPDATE subjects SET name = ? WHERE id = ?");
                $stmt->execute([$name, $subject_id]);
                $message = 'Subject updated successfully!';
                $action = 'list';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($action === 'delete' && $subject_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        $message = 'Subject deleted successfully!';
        $action = 'list';
    } catch (PDOException $e) {
        $error = 'Cannot delete subject. It may have associated grades.';
    }
}

// Get subject data for edit
$subject = null;
if ($action === 'edit' && $subject_id) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();
    if (!$subject) {
        $error = 'Subject not found.';
        $action = 'list';
    }
}

// Get all subjects for list view
$subjects = [];
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    if ($search) {
        $stmt = $pdo->prepare("SELECT s.*, COUNT(g.id) as grade_count FROM subjects s LEFT JOIN grades g ON s.id = g.subject_id WHERE s.name LIKE ? GROUP BY s.id ORDER BY s.name");
        $stmt->execute(["%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT s.*, COUNT(g.id) as grade_count FROM subjects s LEFT JOIN grades g ON s.id = g.subject_id GROUP BY s.id ORDER BY s.name");
    }
    $subjects = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .subject-card {
            transition: transform 0.2s;
            border-left: 4px solid #28a745;
        }
        .subject-card:hover {
            transform: translateY(-2px);
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
                            <a href="subjects.php" class="nav-link text-white active">
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
                        <!-- Subject List -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-book me-2"></i>Subject Management</h2>
                            <a href="subjects.php?action=add" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Add Subject
                            </a>
                        </div>

                        <!-- Search -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="GET" class="d-flex">
                                    <input type="text" name="search" class="form-control me-2" 
                                           placeholder="Search subjects..." 
                                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="subjects.php" class="btn btn-outline-secondary ms-2">Clear</a>
                                </form>
                            </div>
                        </div>

                        <!-- Subjects Grid -->
                        <?php if (empty($subjects)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5>No subjects found</h5>
                                <p class="text-muted">Start by adding your first subject.</p>
                                <a href="subjects.php?action=add" class="btn btn-success">Add Subject</a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($subjects as $s): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card subject-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h5 class="card-title text-success">
                                                        <i class="fas fa-book me-2"></i>
                                                        <?= htmlspecialchars($s['name']) ?>
                                                    </h5>
                                                    <span class="badge bg-primary"><?= $s['grade_count'] ?> grades</span>
                                                </div>
                                                
                                               
                                                
                                                <div class="card-text">
                                                    <small class="text-muted">
                                                         
                                                         
                                                    </small>
                                                </div>

                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <div class="btn-group w-100">
                                                    <a href="subjects.php?action=edit&id=<?= $s['id'] ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="grades.php?subject_id=<?= $s['id'] ?>" 
                                                       class="btn btn-outline-info btn-sm">
                                                        <i class="fas fa-chart-line"></i> Grades
                                                    </a>
                                                    <a href="subjects.php?action=delete&id=<?= $s['id'] ?>" 
                                                       class="btn btn-outline-danger btn-sm"
                                                       onclick="return confirm('Are you sure you want to delete this subject?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($action === 'add' || $action === 'edit'): ?>
                        <!-- Add/Edit Subject Form -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>
                                <i class="fas fa-<?= $action === 'add' ? 'plus' : 'edit' ?> me-2"></i>
                                <?= ucfirst($action) ?> Subject
                            </h2>
                            <a href="subjects.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-book"></i> Subject Name *
                                        </label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($subject['name'] ?? '') ?>" 
                                               placeholder="Enter subject name" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="description" class="form-label">
                                            <i class="fas fa-align-left"></i> Description
                                        </label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="4" placeholder="Enter subject description"><?= htmlspecialchars($subject['description'] ?? '') ?></textarea>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-2"></i>
                                            <?= $action === 'add' ? 'Add Subject' : 'Update Subject' ?>
                                        </button>
                                        <a href="subjects.php" class="btn btn-outline-secondary">
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