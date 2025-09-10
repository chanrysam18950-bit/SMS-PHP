<?php
require_once 'config/database.php';
require_once 'auth.php';

requireLogin();

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$student_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $gender = $_POST['gender'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($name) || empty($gender)) {
        $error = 'Name and gender are required fields.';
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO students (name, gender, email, phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $gender, $email, $phone]);
                $message = 'Student added successfully!';
                $action = 'list';
            } elseif ($action === 'edit' && $student_id) {
                $stmt = $pdo->prepare("UPDATE students SET name = ?, gender = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $gender, $email, $phone, $student_id]);
                $message = 'Student updated successfully!';
                $action = 'list';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($action === 'delete' && $student_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $message = 'Student deleted successfully!';
        $action = 'list';
    } catch (PDOException $e) {
        $error = 'Cannot delete student. They may have associated grades.';
    }
}

// Get student data for edit
$student = null;
if ($action === 'edit' && $student_id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    if (!$student) {
        $error = 'Student not found.';
        $action = 'list';
    }
}

// Get all students for list view
$students = [];
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    if ($search) {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE name LIKE ? OR email LIKE ? ORDER BY name");
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM students ORDER BY name");
    }
    $students = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                            <a href="students.php" class="nav-link text-white active">
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
                        <!-- Student List -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-users me-2"></i>Student Management</h2>
                            <a href="students.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Student
                            </a>
                        </div>

                        <!-- Search -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="GET" class="d-flex">
                                    <input type="text" name="search" class="form-control me-2" 
                                           placeholder="Search by name or email..." 
                                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="students.php" class="btn btn-outline-secondary ms-2">Clear</a>
                                </form>
                            </div>
                        </div>

                        <!-- Students Table -->
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($students)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h5>No students found</h5>
                                        <p class="text-muted">Start by adding your first student.</p>
                                        <a href="students.php?action=add" class="btn btn-primary">Add Student</a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Gender</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $s): ?>
                                                    <tr>
                                                        <td><?= $s['id'] ?></td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($s['name']) ?></strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= $s['gender'] === 'Male' ? 'primary' : 'pink' ?>">
                                                                <?= $s['gender'] ?>
                                                            </span>
                                                        </td>
                                                        <td><?= htmlspecialchars($s['email']) ?></td>
                                                        <td><?= htmlspecialchars($s['phone']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="students.php?action=edit&id=<?= $s['id'] ?>" 
                                                                   class="btn btn-outline-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="grades.php?student_id=<?= $s['id'] ?>" 
                                                                   class="btn btn-outline-info">
                                                                    <i class="fas fa-chart-line"></i>
                                                                </a>
                                                                <a href="students.php?action=delete&id=<?= $s['id'] ?>" 
                                                                   class="btn btn-outline-danger"
                                                                   onclick="return confirm('Are you sure you want to delete this student?')">
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
                        <!-- Add/Edit Student Form -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>
                                <i class="fas fa-<?= $action === 'add' ? 'plus' : 'edit' ?> me-2"></i>
                                <?= ucfirst($action) ?> Student
                            </h2>
                            <a href="students.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">
                                                <i class="fas fa-user"></i> Full Name *
                                            </label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?= htmlspecialchars($student['name'] ?? '') ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="gender" class="form-label">
                                                <i class="fas fa-venus-mars"></i> Gender *
                                            </label>
                                            <select class="form-select" id="gender" name="gender" required>
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>
                                                    Male
                                                </option>
                                                <option value="Female" <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>
                                                    Female
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope"></i> Email
                                            </label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                   value="<?= htmlspecialchars($student['email'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">
                                                <i class="fas fa-phone"></i> Phone
                                            </label>
                                            <input type="text" class="form-control" id="phone" name="phone"
                                                   value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            <?= $action === 'add' ? 'Add Student' : 'Update Student' ?>
                                        </button>
                                        <a href="students.php" class="btn btn-outline-secondary">
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
    <style>
        .bg-pink {
            background-color: #e91e63 !important;
        }
    </style>
</body>
</html>