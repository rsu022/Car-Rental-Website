<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get user's current license status if any
$query = "SELECT * FROM driver_licenses WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$license = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $license_number = $_POST['license_number'];
    $expiry_date = $_POST['expiry_date'];
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['license_image']) && $_FILES['license_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['license_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $image = uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['license_image']['tmp_name'], $upload_dir . $image);
        }
    }

    try {
        if ($license) {
            // Update existing license
            $query = "UPDATE driver_licenses SET 
                     license_number = :license_number,
                     expiry_date = :expiry_date,
                     image = :image,
                     status = 'pending'
                     WHERE user_id = :user_id";
        } else {
            // Insert new license
            $query = "INSERT INTO driver_licenses (user_id, license_number, expiry_date, image) 
                     VALUES (:user_id, :license_number, :expiry_date, :image)";
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':license_number' => $license_number,
            ':expiry_date' => $expiry_date,
            ':image' => $image
        ]);
        
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $error = "Error uploading license: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Driver's License - CarRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h4 class="mb-0">Upload Driver's License</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($license): ?>
                            <div class="alert alert-info">
                                <strong>Current Status:</strong> 
                                <?php echo ucfirst($license['status']); ?>
                                <?php if ($license['status'] === 'approved'): ?>
                                    <br>
                                    <strong>Expiry Date:</strong> 
                                    <?php echo date('M d, Y', strtotime($license['expiry_date'])); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="license_number" class="form-label">License Number</label>
                                <input type="text" class="form-control" id="license_number" name="license_number" 
                                       value="<?php echo $license ? htmlspecialchars($license['license_number']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                       value="<?php echo $license ? $license['expiry_date'] : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="license_image" class="form-label">License Image</label>
                                <input type="file" class="form-control" id="license_image" name="license_image" 
                                       accept="image/*,.pdf" required>
                                <small class="form-text text-muted">
                                    Upload a clear image or PDF of your driver's license
                                </small>
                            </div>

                            <?php if ($license && $license['image']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current License Image</label>
                                    <div>
                                        <img src="assets/images/<?php echo htmlspecialchars($license['image']); ?>" 
                                             alt="Current License" style="max-width: 200px;">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary">
                                <?php echo $license ? 'Update License' : 'Upload License'; ?>
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 