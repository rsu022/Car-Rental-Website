<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'delete') {
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "Review deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete review.";
        }
    }
    
    header("Location: manage-reviews.php");
    exit();
}

require_once 'includes/header.php';

// Get all reviews with car and user details
$query = "SELECT r.*, c.brand, c.model, u.name as user_name 
          FROM reviews r 
          JOIN cars c ON r.car_id = c.id 
          JOIN users u ON r.user_id = u.id 
          ORDER BY r.created_at DESC";
$reviews = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Manage Reviews</h1>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="reviewsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Car</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?php echo $review['id']; ?></td>
                                <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($review['brand'] . ' ' . $review['model']); ?></td>
                                <td>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </td>
                                <td><?php echo htmlspecialchars($review['comment']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <a href="manage-reviews.php?action=delete&id=<?php echo $review['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this review?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#reviewsTable').DataTable({
        order: [[5, 'desc']]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
