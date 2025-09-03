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

// Define available features
$default_features = ['Air Conditioning','GPS Navigation','Bluetooth','Automatic Transmission','Child Seat'];

// Get car ID from URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header("Location: manage-cars.php");
    exit();
}

// Get car details
$query = "SELECT * FROM cars WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    header("Location: manage-cars.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Update car details
        $query = "UPDATE cars SET 
                  brand = :brand,
                  model = :model,
                  year = :year,
                  category = :category,
                  price_per_day = :price_per_day,
                  description = :description,
                  status = :status,
                  features = :features
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':brand' => $_POST['brand'],
            ':model' => $_POST['model'],
            ':year' => $_POST['year'],
            ':category' => $_POST['category'],
            ':price_per_day' => $_POST['price_per_day'],
            ':description' => $_POST['description'],
            ':status' => $_POST['status'],
            ':features' => !empty($_POST['features']) ? implode(',', $_POST['features']) : null,
            ':id' => $id
        ]);

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $image = $_FILES['image'];
            $image_name = time() . '_' . $image['name'];
            $image_path = '../assets/images/cars/' . $image_name;
            
            if (move_uploaded_file($image['tmp_name'], $image_path)) {
                // Delete old image if exists
                if (!empty($car['image'])) {
                    $old_image_path = '../assets/images/cars/' . $car['image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                
                // Update image in database
                $stmt = $db->prepare("UPDATE cars SET image = ? WHERE id = ?");
                $stmt->execute([$image_name, $id]);
            }
        }

        $db->commit();
        $_SESSION['success'] = "Car updated successfully!";
        header("Location: manage-cars.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error updating car: " . $e->getMessage();
    }
}

// Get existing features from the features column
$existing_features = $car['features'] ? explode(',', $car['features']) : [];

// Include header after all redirects and before any HTML output
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Edit Car</h6>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Brand</label>
                            <input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($car['brand']); ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Model</label>
                            <input type="text" name="model" class="form-control" value="<?php echo htmlspecialchars($car['model']); ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Year</label>
                            <input type="number" name="year" class="form-control" value="<?php echo htmlspecialchars($car['year']); ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Price per Day (Rs.)</label>
                            <input type="number" name="price_per_day" class="form-control" step="0.01" value="<?php echo htmlspecialchars($car['price_per_day']); ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($car['description']); ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Category</label>
                            <select name="category" class="form-control" required>
                                <option value="economy" <?php echo $car['category'] === 'economy' ? 'selected' : ''; ?>>Economy</option>
                                <option value="luxury" <?php echo $car['category'] === 'luxury' ? 'selected' : ''; ?>>Luxury</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Current Image</label>
                            <?php if ($car['image']): ?>
                                <div class="mb-2">
                                    <img src="../assets/images/cars/<?php echo htmlspecialchars($car['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($car['brand']); ?> <?php echo htmlspecialchars($car['model']); ?>"
                                         style="max-width: 200px; height: 150px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="form-text text-muted">Leave empty to keep current image</small>
                        </div>
                        <div class="form-group mb-3">
                            <label class="d-block">Features</label>
                            <div class="row g-3">
                                <?php foreach($default_features as $feature): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="features[]" value="<?php echo htmlspecialchars($feature); ?>" id="feature_<?php echo md5($feature); ?>" <?php echo in_array($feature, $existing_features) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="feature_<?php echo md5($feature); ?>"><?php echo htmlspecialchars($feature); ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="available" <?php echo $car['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="unavailable" <?php echo $car['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Car</button>
                <a href="manage-cars.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>