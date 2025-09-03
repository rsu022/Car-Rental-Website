<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit();
}

// After auth & param checks, load admin header
require_once 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

$user_id = (int)$_GET['id'];

// Get user details
$query = "SELECT * FROM users WHERE id = ? AND role != 'admin'";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: manage-users.php");
    exit();
}

// Get user's bookings
$query = "SELECT b.*, c.brand, c.model 
          FROM bookings b 
          JOIN cars c ON b.car_id = c.id 
          WHERE b.user_id = ? 
          ORDER BY b.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's reviews
$query = "SELECT r.*, c.brand, c.model 
          FROM reviews r 
          JOIN cars c ON r.car_id = c.id 
          WHERE r.user_id = ? 
          ORDER BY r.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's driver license
$query = "SELECT * FROM driver_licenses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$license = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">User Details</h1>
        <a href="manage-users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>

    <div class="row">
        <!-- User Information -->
        <div class="col-xl-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Role:</strong> 
                        <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Joined:</strong> 
                        <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                    </div>
                    <?php if ($license): ?>
                        <div class="mb-3">
                            <strong>Driver License:</strong><br>
                            Number: <?php echo htmlspecialchars($license['license_number']); ?><br>
                            Expiry: <?php echo date('M d, Y', strtotime($license['expiry_date'])); ?><br>
                            Status: 
                            <span class="badge bg-<?php 
                                echo $license['status'] === 'approved' ? 'success' : 
                                    ($license['status'] === 'pending' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($license['status']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bookings -->
        <div class="col-xl-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Booking History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Car</th>
                                    <th>Dates</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No bookings found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></td>
                                            <td>
                                                <?php echo date('M d', strtotime($booking['start_date'])); ?> - 
                                                <?php echo date('M d', strtotime($booking['end_date'])); ?>
                                            </td>
                                            <td>Rs. <?php echo number_format($booking['total_price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $booking['status'] === 'approved' ? 'success' : 
                                                        ($booking['status'] === 'pending' ? 'warning' : 
                                                        ($booking['status'] === 'completed' ? 'info' : 'danger')); 
                                                ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reviews -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Reviews</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($reviews)): ?>
                        <p class="text-center">No reviews found</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="mb-4 border-bottom pb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">
                                        <?php echo htmlspecialchars($review['brand'] . ' ' . $review['model']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
