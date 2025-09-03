<?php
// Database connection and stats
$database = new Database();
$db = $database->getConnection();

// Get admin stats
$database = new Database();
$db = $database->getConnection();

$stats = [
    'total_cars' => 0,
    'total_bookings' => 0,
    'total_users' => 0,
    'total_messages' => 0,
    'total_reviews' => 0,
    'avg_rating' => 0
];

// Get total cars
$query = "SELECT COUNT(*) as count FROM cars";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_cars'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total bookings
$query = "SELECT COUNT(*) as count FROM bookings";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total users (excluding admins)
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total messages
$query = "SELECT COUNT(*) as count FROM messages";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_messages'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total reviews
$query = "SELECT COUNT(*) as count FROM reviews";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_reviews'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get average rating
$query = "SELECT ROUND(AVG(rating), 1) as avg FROM reviews";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['avg_rating'] = $stmt->fetch(PDO::FETCH_ASSOC)['avg'] ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CarEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: #fff;
            color: #22223b;
            border-right: 1px solid #e5e5e5;
        }
        .sidebar .nav-link {
            color: #22223b;
            padding: 0.75rem 1.25rem;
            margin: 0.2rem 0;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar .nav-link:hover {
            color: #0066cc;
            background: #f5f8fa;
        }
        .sidebar .nav-link.active {
            color: #0066cc;
            background: #eaf2fb;
            font-weight: 600;
        }
        .sidebar .nav-link i {
            color: #0066cc;
            margin-right: 0.75rem;
        }
        .sidebar .nav-link.logout {
            color: #e63946;
        }
        .sidebar .nav-link.logout:hover {
            background: #fbeaec;
            color: #b71c1c;
        }
        .main-content {
            padding: 20px;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="p-0">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4>CarEase Admin</h4>
                        <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-cars.php' ? 'active' : ''; ?>" href="manage-cars.php">
                                <i class="fas fa-car me-2"></i> Manage Cars
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-bookings.php' ? 'active' : ''; ?>" href="manage-bookings.php">
                                <i class="fas fa-calendar-check me-2"></i> Manage Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-contacts.php' ? 'active' : ''; ?>" href="manage-contacts.php">
                                <i class="fas fa-envelope me-2"></i> Contact Messages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-users.php' ? 'active' : ''; ?>" href="manage-users.php">
                                <i class="fas fa-users me-2"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-reviews.php' ? 'active' : ''; ?>" href="manage-reviews.php">
                                <i class="fas fa-star me-2"></i> Manage Reviews
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-licenses.php' ? 'active' : ''; ?>" href="manage-licenses.php">
                                <i class="fas fa-id-card me-2"></i> Driver's Licenses
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="d-flex align-items-center">
                            <span class="navbar-text">
                                <i class="fas fa-clock me-2"></i>
                                <?php echo date('Y-m-d H:i:s'); ?>
                            </span>
                        </div>
                    </div>
                </nav>