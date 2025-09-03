<?php
ob_start();
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

require_once 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Insert car
        $query = "INSERT INTO cars (brand, model, year, category, price_per_day, description, status, features) 
                  VALUES (:brand, :model, :year, :category, :price_per_day, :description, :status, :features)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':brand' => $_POST['brand'],
            ':model' => $_POST['model'],
            ':year' => $_POST['year'],
            ':category' => $_POST['category'],
            ':price_per_day' => $_POST['price_per_day'],
            ':description' => $_POST['description'],
            ':status' => $_POST['status'],
            ':features' => !empty($_POST['features']) ? implode(',', $_POST['features']) : null
        ]);

        $car_id = $db->lastInsertId();

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $image = $_FILES['image'];
            $image_name = time() . '_' . $image['name'];
            $image_path = '../assets/images/' . $image_name;
            
            if (move_uploaded_file($image['tmp_name'], $image_path)) {
                $stmt = $db->prepare("UPDATE cars SET image = ? WHERE id = ?");
                $stmt->execute([$image_name, $car_id]);
            }
        }

        $db->commit();
        $_SESSION['success'] = "Car added successfully!";
        header("Location: manage-cars.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error adding car: " . $e->getMessage();
    }
}

// Get all available features for filter
$features_query = "SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(features, ',', n.n), ',', -1)) as feature
                  FROM cars c
                  CROSS JOIN (
                      SELECT a.N + b.N * 10 + 1 n
                      FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
                      CROSS JOIN (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
                      ORDER BY n
                  ) n
                  WHERE n.n <= 1 + (LENGTH(c.features) - LENGTH(REPLACE(c.features, ',', '')))
                  AND c.features IS NOT NULL
                  ORDER BY feature";
$features = $db->query($features_query)->fetchAll(PDO::FETCH_COLUMN);

?>

<style>
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        display: none;
        margin-top: 10px;
    }
</style>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Add New Car</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Brand</label>
                            <input type="text" name="brand" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Model</label>
                            <input type="text" name="model" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Year</label>
                            <input type="number" name="year" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Price per Day (Rs.)</label>
                            <input type="number" name="price_per_day" class="form-control" step="0.01" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Category</label>
                            <select name="category" class="form-control" required>
                                <option value="economy">Economy</option>
                                <option value="luxury">Luxury</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="d-block">Features</label>
                            <div class="row g-3">
                                <?php foreach($default_features as $feature): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="features[]" value="<?php echo htmlspecialchars($feature); ?>" id="feature_<?php echo md5($feature); ?>">
                                            <label class="form-check-label" for="feature_<?php echo md5($feature); ?>"><?php echo htmlspecialchars($feature); ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add Car</button>
                <a href="manage-cars.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="js/add-car.js"></script>