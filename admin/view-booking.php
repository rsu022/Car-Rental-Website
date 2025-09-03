<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Check if booking ID is provided
if(!isset($_GET['id'])) {
    header("Location: manage-bookings.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$booking_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Get booking details with user and car information
$query = "SELECT b.*, u.name as user_name, u.email as user_email,
                 c.brand, c.model, c.image as car_image, c.price_per_day,
                 DATEDIFF(b.end_date, b.start_date) + 1 as total_days,
                 lp.name AS pickup_location, lr.name AS return_location
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN cars c ON b.car_id = c.id
          LEFT JOIN locations lp ON b.pickup_location_id = lp.id
          LEFT JOIN locations lr ON b.return_location_id = lr.id
          WHERE b.id = :id";

$stmt = $db->prepare($query);
$stmt->execute([':id' => $booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['error'] = "Booking not found.";
    header("Location: manage-bookings.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $valid_statuses = ['pending', 'approved', 'cancelled'];
    
    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE bookings SET status = :status WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([':status' => $new_status, ':id' => $booking_id])) {
            $_SESSION['success'] = "Booking status updated successfully.";
            echo '<script>window.location.href = "view-booking.php?id=' . $booking_id . '";</script>';
            exit();
        } else {
            $_SESSION['error'] = "Failed to update booking status.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">View Booking</h1>
        <a href="manage-bookings.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Bookings
        </a>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Booking Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <img src="../assets/images/<?php echo !empty($booking['car_image']) ? htmlspecialchars($booking['car_image']) : 'car-placeholder.png'; ?>" 
                                 class="img-fluid rounded" 
                                 style="max-height: 300px; width: 100%; object-fit: cover;"
                                 alt="<?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>">
                        </div>
                        <div class="col-md-6">
                            <h5><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h5>
                            <p class="text-muted mb-2">Booking ID: #<?php echo $booking['id']; ?></p>
                            <p class="mb-2">
                                Status: 
                                <span class="badge bg-<?php 
                                    echo match($booking['status']) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'cancelled' => 'danger',
                                        'completed' => 'info'
                                    };
                                ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Customer Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?></p>
                            <p class="mb-1"><strong>Agreed to Terms:</strong> <?php echo $booking['agreed_terms'] ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Booking Information</h6>
                            <p class="mb-1"><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($booking['start_date'])); ?></p>
                            <p class="mb-1"><strong>End Date:</strong> <?php echo date('M d, Y', strtotime($booking['end_date'])); ?></p>
                            <p class="mb-1"><strong>Total Days:</strong> <?php echo $booking['total_days']; ?> days</p>
                            <p class="mb-1"><strong>Pickup Location:</strong> <?php echo htmlspecialchars($booking['pickup_location'] ?? 'N/A'); ?></p>
                            <p class="mb-1"><strong>Return Location:</strong> <?php echo htmlspecialchars($booking['return_location'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Payment Information</h6>
                            <p class="mb-1"><strong>Price Per Day:</strong> Rs. <?php echo number_format($booking['price_per_day'], 2); ?></p>
                            <p class="mb-1"><strong>Total Price:</strong> Rs. <?php echo number_format($booking['total_price'], 2); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Update Status</h6>
                            <form method="POST" class="mt-2">
                                <div class="input-group">
                                    <select name="status" class="form-control">
                                        <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $booking['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <?php if ($booking['status'] === 'pending'): ?>
                        <a href="manage-bookings.php?action=cancel&id=<?php echo $booking['id']; ?>" 
                           class="btn btn-danger btn-block mb-2"
                           onclick="return confirm('Are you sure you want to cancel this booking?')">
                            <i class="fas fa-times me-2"></i> Cancel Booking
                        </a>
                    <?php endif; ?>
                    
                    <a href="mailto:<?php echo htmlspecialchars($booking['user_email']); ?>" 
                       class="btn btn-info btn-block mb-2">
                        <i class="fas fa-envelope me-2"></i> Email Customer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
