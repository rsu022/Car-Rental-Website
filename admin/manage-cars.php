<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    if($action === 'delete') {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Delete car
            $query = "DELETE FROM cars WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id]);
            
            $db->commit();
            $_SESSION['success'] = "Car deleted successfully.";
        } catch(PDOException $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error deleting car: " . $e->getMessage();
        }
        
        // Use JavaScript to redirect to avoid headers already sent issue
        echo '<script>window.location.href = "manage-cars.php";</script>';
        exit();
    }
}

require_once 'includes/header.php';

// Get all cars
$query = "SELECT * FROM cars ORDER BY created_at DESC";
$cars = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Cars</h1>
        <a href="add-car.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Car
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

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="carsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Year</th>
                            <th>Category</th>
                            <th>Price/Day</th>
                            <th>Status</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cars as $car): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($car['brand']); ?></td>
                                <td><?php echo htmlspecialchars($car['model']); ?></td>
                                <td><?php echo htmlspecialchars($car['year']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($car['category'])); ?></td>
                                <td>Rs. <?php echo htmlspecialchars($car['price_per_day']); ?></td>
                                <td><?php echo $car['status'] === 'available' ? '<span class="badge bg-success">Available</span>' : '<span class="badge bg-danger">Not Available</span>'; ?></td>
                                <td>
                                    <?php if ($car['image']): ?>
                                        <img src="../assets/images/<?php echo htmlspecialchars($car['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($car['brand']); ?> <?php echo htmlspecialchars($car['model']); ?>" 
                                             style="max-width: 100px; height: 75px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="../assets/images/car-placeholder.png" 
                                             alt="No Image Available"
                                             style="max-width: 100px; height: 75px; object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit-car.php?id=<?php echo $car['id']; ?>" 
                                       class="btn btn-sm btn-primary">Edit</a>
                                    <a href="manage-cars.php?action=delete&id=<?php echo $car['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this car?')">Delete</a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>