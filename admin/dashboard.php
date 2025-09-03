<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
// Load header after authentication
require_once 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [
    'total_cars' => $db->query("SELECT COUNT(*) FROM cars")->fetchColumn(),
    'total_bookings' => $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'total_users' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'total_reviews' => $db->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
    'pending_messages' => $db->query("SELECT COUNT(*) FROM messages WHERE status = 'unread'")->fetchColumn(),
    'pending_licenses' => $db->query("SELECT COUNT(*) FROM driver_licenses WHERE status = 'pending'")->fetchColumn(),
    'pending_bookings' => $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'avg_rating' => $db->query("SELECT ROUND(AVG(rating), 1) FROM reviews")->fetchColumn() ?: 0
];

// Get recent bookings
$query = "SELECT b.*, u.name as user_name, CONCAT(c.brand, ' ', c.model) as car_name 
          FROM bookings b 
          JOIN users u ON b.user_id = u.id 
          JOIN cars c ON b.car_id = c.id 
          ORDER BY b.created_at DESC LIMIT 5";
$recent_bookings = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Get recent messages
$query = "SELECT * FROM messages ORDER BY created_at DESC LIMIT 5";
$recent_messages = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root {
        --primary: #1a1a1a;
        --secondary: #333333;
        --accent: #e63946;
        --text: #2b2d42;
        --text-light: #8d99ae;
        --background: #ffffff;
        --background-alt: #f8f9fa;
        --border: #e9ecef;
        --shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        --radius: 4px;
        --font-primary: 'Inter', sans-serif;
        --font-secondary: 'Poppins', sans-serif;
    }

    body {
        font-family: var(--font-primary);
        background-color: var(--background-alt);
        color: var(--text);
        line-height: 1.6;
    }

    .sidebar {
        background: var(--background);
        border-right: 1px solid var(--border);
        height: 100vh;
        position: fixed;
        width: 250px;
        padding: 2rem 0;
    }

    .sidebar-brand {
        padding: 0 1.5rem;
        margin-bottom: 2rem;
    }

    .sidebar-brand h2 {
        font-family: var(--font-secondary);
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
    }

    .nav-link {
        padding: 0.8rem 1.5rem;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.3s ease;
    }

    .nav-link:hover, .nav-link.active {
        background: var(--background-alt);
        color: var(--accent);
    }

    .nav-link i {
        width: 20px;
        text-align: center;
    }

    .main-content {
        margin-left: 250px;
        padding: 2rem;
    }

    .dashboard-header {
        margin-bottom: 2rem;
    }

    .dashboard-title {
        font-family: var(--font-secondary);
        font-size: 2rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.5rem;
    }

    .dashboard-subtitle {
        color: var(--text-light);
        font-size: 1rem;
    }

    .stats-card {
        background: var(--background);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stats-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        background: var(--background-alt);
        color: var(--accent);
    }

    .stats-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.5rem;
    }

    .stats-label {
        color: var(--text-light);
        font-size: 0.875rem;
    }

    .table {
        background: var(--background);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }

    .table thead th {
        background: var(--background-alt);
        border-bottom: 1px solid var(--border);
        color: var(--text);
        font-weight: 600;
        padding: 1rem;
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--border);
    }

    .badge {
        padding: 0.35em 0.8em;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .badge-success {
        background: #28a745;
        color: white;
    }

    .badge-warning {
        background: #ffc107;
        color: var(--text);
    }

    .badge-danger {
        background: #dc3545;
        color: white;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: var(--radius);
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: var(--accent);
        color: white;
        border: none;
    }

    .btn-primary:hover {
        background: #d62839;
        transform: translateY(-2px);
    }

    .btn-outline {
        border: 1px solid var(--border);
        color: var(--text);
        background: transparent;
    }

    .btn-outline:hover {
        background: var(--background-alt);
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            padding: 1rem;
        }

        .main-content {
            margin-left: 0;
            padding: 1rem;
        }

        .stats-card {
            margin-bottom: 1rem;
        }
    }

    .card-header-flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        background: none;
        border-bottom: none;
        padding: 1.25rem 1.5rem 0.5rem 1.5rem;
    }
    .card-header-flex .card-title {
        margin-bottom: 0;
        font-size: 1.15rem;
        font-weight: 600;
        color: #0066cc;
    }
    .card-header-flex .btn {
        min-width: 110px;
        font-size: 1rem;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 500;
    }
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Dashboard Overview</h1>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Cars</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_cars']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-car fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Bookings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_bookings']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Messages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_messages']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header-flex">
                    <div class="card-title">Recent Bookings</div>
                    <a href="manage-bookings.php" class="btn btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Car</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['car_name']); ?></td>
                                        <td>
                                            <?php echo date('M d', strtotime($booking['start_date'])); ?> - 
                                            <?php echo date('M d', strtotime($booking['end_date'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] === 'approved' ? 'success' : 
                                                    ($booking['status'] === 'pending' ? 'warning' : 
                                                    ($booking['status'] === 'completed' ? 'info' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="manage-bookings.php?action=view&id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-sm btn-info">View</a>
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <a href="manage-bookings.php?action=cancel&id=<?php echo $booking['id']; ?>" 
                                                       class="btn btn-sm btn-danger">Cancel</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reviews -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header-flex">
                    <div class="card-title">Recent Reviews</div>
                    <a href="manage-reviews.php" class="btn btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Car</th>
                                    <th>Rating</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT r.*, u.name as user_name, c.brand, c.model 
                                          FROM reviews r 
                                          JOIN users u ON r.user_id = u.id 
                                          JOIN cars c ON r.car_id = c.id 
                                          ORDER BY r.created_at DESC LIMIT 5";
                                $recent_reviews = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($recent_reviews as $review):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($review['brand'] . ' ' . $review['model']); ?></td>
                                        <td>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="col-xl-12 col-lg-12 px-0">

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Messages</h6>
                    <a href="manage-contacts.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_messages as $message): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($message['name']); ?></td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $message['status'] === 'replied' ? 'success' : 
                                                    ($message['status'] === 'read' ? 'info' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($message['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="manage-contacts.php?action=view&id=<?php echo $message['id']; ?>" 
                                               class="btn btn-sm btn-info">View</a>
                                            <a href="manage-contacts.php?action=delete&id=<?php echo $message['id']; ?>" 
                                               class="btn btn-sm btn-danger">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>