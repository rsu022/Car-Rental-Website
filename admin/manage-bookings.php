<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    try {
        $new_status = '';
        switch($action) {
            case 'approve':
                $new_status = 'approved';
                break;
            case 'cancel':
                $new_status = 'cancelled';
                break;
            case 'pending':
                $new_status = 'pending';
                break;
        }
        
        if($new_status) {
            $query = "UPDATE bookings SET status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':status' => $new_status,
                ':id' => $id
            ]);
            $_SESSION['success'] = "Booking " . $new_status . " successfully.";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error updating booking: " . $e->getMessage();
    }
    header("Location: manage-bookings.php");
    exit();
}

require_once 'includes/header.php';

// Get all bookings with user and car details
$query = "SELECT b.*, u.name as user_name, u.email as user_email,
          c.brand, c.model, c.image,
          lp.name AS pickup_location, lr.name AS return_location,
          CONCAT(c.brand, ' ', c.model) AS car_name
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN cars c ON b.car_id = c.id
          LEFT JOIN locations lp ON b.pickup_location_id = lp.id
          LEFT JOIN locations lr ON b.return_location_id = lr.id
          ORDER BY b.created_at DESC";
$bookings = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Bookings</h1>
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

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="bookingsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Car</th>
                            <th>Pickup Location</th>
                            <th>Return Location</th>
                            <th>Dates</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Agreed to Terms</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($booking['user_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['user_email']); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../assets/images/<?php echo !empty($booking['image']) ? htmlspecialchars($booking['image']) : 'car-placeholder.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($booking['car_name']); ?>" 
                                             class="me-2" 
                                             style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                        <div>
                                            <?php echo htmlspecialchars($booking['car_name']); ?><br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($booking['pickup_location'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($booking['return_location'] ?? ''); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> -<br>
                                    <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                </td>
                                <td>Rs. <?php echo number_format($booking['total_price'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $booking['status'] === 'approved' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $booking['agreed_terms'] ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?>
                                </td>
                                <td>
                                    <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 