<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 999999;
$selected_features = isset($_GET['features']) ? $_GET['features'] : [];

// Build base query
$query = "SELECT DISTINCT c.* FROM cars c";
$params = array();
$where_conditions = [];

// Add search filter
if(!empty($search)) {
    $where_conditions[] = "(c.brand LIKE :search OR c.model LIKE :search OR c.category LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add category filter
if(!empty($category)) {
    $where_conditions[] = "c.category = :category";
    $params[':category'] = $category;
}

// Add price filter
if($price_min > 0 || $price_max < 999999) {
    $where_conditions[] = "c.price_per_day BETWEEN :price_min AND :price_max";
    $params[':price_min'] = $price_min;
    $params[':price_max'] = $price_max;
}

// Add feature filter if selected
if (!empty($selected_features)) {
    $feature_conditions = [];
    foreach ($selected_features as $index => $feature) {
        $param_name = ":feature" . $index;
        $feature_conditions[] = "c.features LIKE " . $param_name;
        $params[$param_name] = '%' . $feature . '%';
    }
    if (!empty($feature_conditions)) {
        $where_conditions[] = "(" . implode(" OR ", $feature_conditions) . ")";
    }
}

// Add WHERE clause if there are conditions
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
}

// Prepare and execute the query
$stmt = $db->prepare($query);

// Bind all parameters
foreach($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Log the number of cars found
error_log("Number of cars found: " . count($cars));
if (count($cars) == 0) {
    error_log("No cars found in database");
}

// Then get reviews and features for each car
foreach($cars as &$car) {
    // Get features for the car
    $featureQuery = "SELECT features FROM cars WHERE id = ?";
    $featureStmt = $db->prepare($featureQuery);
    $featureStmt->execute([$car['id']]);
    $features = $featureStmt->fetchColumn();
    $car['features'] = $features ? explode(',', $features) : [];
    
    // Get review stats
    $reviewQuery = "SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as review_count 
                    FROM reviews 
                    WHERE car_id = ?";
    $reviewStmt = $db->prepare($reviewQuery);
    $reviewStmt->execute([$car['id']]);
    $reviewStats = $reviewStmt->fetch(PDO::FETCH_ASSOC);
    $car['avg_rating'] = $reviewStats['avg_rating'];
    $car['review_count'] = $reviewStats['review_count'];

    // Check if car is booked for the selected dates
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];
        
        $availability_query = "SELECT COUNT(*) as booking_count FROM bookings 
                             WHERE car_id = :car_id 
                             AND status != 'cancelled' 
                             AND status != 'rejected'
                             AND (
                                 (start_date <= :start_date AND end_date >= :start_date) OR
                                 (start_date <= :end_date AND end_date >= :end_date) OR
                                 (start_date >= :start_date AND end_date <= :end_date)
                             )";
        $availability_stmt = $db->prepare($availability_query);
        $availability_stmt->execute([
            ':car_id' => $car['id'],
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ]);
        $booking_count = $availability_stmt->fetch(PDO::FETCH_ASSOC)['booking_count'];
        $car['is_available_for_dates'] = $booking_count == 0;
    } else {
        $car['is_available_for_dates'] = true;
    }
}
unset($car); // Break the reference

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
$stmt_features = $db->prepare($features_query);
$stmt_features->execute();
$features = $stmt_features->fetchAll(PDO::FETCH_COLUMN);

// Get categories for filtering
$categories_query = "SELECT DISTINCT category FROM cars WHERE category IS NOT NULL ORDER BY category";
$categories = $db->query($categories_query)->fetchAll(PDO::FETCH_COLUMN);

// Check if user has an approved license
$hasApprovedLicense = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT status FROM driver_licenses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $license = $stmt->fetch();
    if ($license && $license['status'] === 'approved') {
        $hasApprovedLicense = true;
    }
}

// Add check for existing bookings if user is logged in
$user_bookings = [];
if (isset($_SESSION['user_id'])) {
    $bookings_query = "SELECT car_id FROM bookings 
                      WHERE user_id = :user_id 
                      AND DATE(created_at) = CURDATE()
                      AND status != 'cancelled' 
                      AND status != 'rejected'";
    $bookings_stmt = $db->prepare($bookings_query);
    $bookings_stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user_bookings = $bookings_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Add booking status to each car
foreach($cars as &$car) {
    $car['is_booked_today'] = in_array($car['id'], $user_bookings);
}
unset($car); // Break the reference
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cars - Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
            padding: 20px;
            min-height: 400px;
        }
        .car-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .car-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .car-card:hover .car-image {
            transform: scale(1.05);
        }
        .car-details {
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: calc(100% - 200px);
        }
        
        .car-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .car-features {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
        }
        .feature-badge {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin: 2px;
            display: inline-block;
        }
        .car-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: #444;
            margin-top: auto;
            margin-bottom: 1rem;
        }
        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            background-color: #e9ecef;
            border-radius: 20px;
            font-size: 0.875rem;
            color: #495057;
            margin-bottom: 1rem;
            text-transform: capitalize;
        }
        .rating {
            color: #ffc107;
            margin-bottom: 10px;
        }
        .btn-view-details {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-view-details:hover {
            background: #0056b3;
            color: white;
            transform: translateY(-2px);
        }
        .filters-sidebar {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .rating-stars {
            color: #ffc107;
        }
        .feature-badge {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin: 2px;
            display: inline-block;
        }
        .filter-section {
            font-family: 'Inter', Arial, sans-serif;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(60, 72, 88, 0.07);
            padding: 2rem 1.5rem;
            margin-bottom: 2rem;
            min-width: 260px;
        }
        .filter-section h5,
        .filter-section label,
        .filter-section .form-label {
            font-weight: 600;
            color: #22223b;
            margin-bottom: 0.5rem;
            letter-spacing: 0.01em;
        }
        .filter-section .form-label {
            font-size: 1rem;
        }
        .filter-section select,
        .filter-section input[type="text"],
        .filter-section input[type="number"] {
            width: 100%;
            padding: 0.6rem 0.9rem;
            border: 1px solid #e0e1dd;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            font-family: 'Inter', Arial, sans-serif;
            transition: border-color 0.2s;
        }
        .filter-section select:focus,
        .filter-section input:focus {
            border-color: #4f8cff;
            outline: none;
        }
        .filter-section .features-list {
            margin-bottom: 1.5rem;
        }
        .filter-section .features-list label {
            display: flex;
            align-items: center;
            font-size: 1rem;
            font-weight: 400;
            color: #343a40;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .filter-section .features-list input[type="checkbox"] {
            accent-color: #4f8cff;
            margin-right: 0.6em;
            width: 1.1em;
            height: 1.1em;
            border-radius: 4px;
        }
        .filter-section .apply-btn {
            width: 100%;
            padding: 0.8rem 0;
            background: #4f8cff;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            font-size: 1.05rem;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .filter-section .apply-btn:disabled,
        .filter-section .apply-btn[disabled] {
            background: #bfc9d1;
            color: #555;
            cursor: not-allowed;
        }
        .filter-section .apply-btn:hover:not(:disabled) {
            background: #3867d6;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid mt-5 px-4">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-md-3">
                <div class="filters-sidebar">
                    <div class="filter-section">
                        <h5>Filters</h5>
                        <form method="GET" action="cars.php" id="filterForm">
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price Range (per day)</label>
                                <div class="d-flex gap-2">
                                    <input type="number" class="form-control" name="price_min" placeholder="Min" value="<?php echo $price_min > 0 ? $price_min : ''; ?>">
                                    <input type="number" class="form-control" name="price_max" placeholder="Max" value="<?php echo $price_max < 999999 ? $price_max : ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3 features-list">
                                <label class="form-label">Features</label>
                                <?php foreach($features as $feature): ?>
                                <label>
                                    <input type="checkbox" id="feature_<?php echo md5($feature); ?>" 
                                           name="features[]" value="<?php echo htmlspecialchars($feature); ?>" 
                                           <?php echo in_array($feature, $selected_features) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($feature); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="apply-btn">Apply Filters</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Cars List -->
            <div class="col-md-9">
                <div class="car-grid" style="min-height: 500px;">
                    <?php foreach($cars as $car): ?>
                        <div class="car-card">
                            <?php 
                            $image_path = 'assets/images/' . ($car['image'] ?: 'car-placeholder.png');
                            $image_exists = file_exists($image_path);
                            
                            // If image doesn't exist, try to find a similar image
                            if (!$image_exists && !empty($car['image'])) {
                                $brand_lower = strtolower($car['brand']);
                                $model_lower = strtolower($car['model']);
                                
                                // Look for images that might match
                                $possible_images = [
                                    "assets/images/{$brand_lower} {$model_lower}.jpg",
                                    "assets/images/{$brand_lower}-{$model_lower}.jpg",
                                    "assets/images/{$brand_lower}_{$model_lower}.jpg",
                                    "assets/images/{$car['brand']} {$car['model']}.jpg",
                                    "assets/images/{$car['brand']}-{$car['model']}.jpg",
                                    "assets/images/Hyndai Venue.jpg", // Specific for Hyundai Venue
                                    "assets/images/1753892999_Hyndai Venue.jpg" // Specific for uploaded Hyundai Venue
                                ];
                                
                                foreach ($possible_images as $possible_image) {
                                    if (file_exists($possible_image)) {
                                        $image_path = $possible_image;
                                        $image_exists = true;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <img src="<?php echo $image_exists ? $image_path : 'assets/images/Hyndai Venue.jpg'; ?>" 
                                 class="car-image" 
                                 alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>"
                                 onerror="this.src='assets/images/Hyndai Venue.jpg';">

                            <div class="car-details">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h5>
                                    <span class="badge <?php echo $car['status'] === 'available' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($car['status']); ?>
                                    </span>
                                </div>
                                <span class="category-badge"><?php echo htmlspecialchars($car['category']); ?></span>
                                
                                <div class="car-price mb-3">Rs. <?php echo number_format($car['price_per_day'], 2); ?>/day</div>
                                
                                <div class="d-grid">
                                    <?php if ($car['status'] === 'unavailable'): ?>
                                        <div class="alert alert-danger mb-3">
                                            <i class="fas fa-times-circle me-2"></i>This car is currently unavailable
                                        </div>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-calendar-times"></i> Not Available
                                        </button>
                                    <?php elseif ($car['is_booked_today']): ?>
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle me-2"></i>You have already booked this car today
                                        </div>
                                    <?php elseif (isset($_GET['start_date']) && isset($_GET['end_date']) && !$car['is_available_for_dates']): ?>
                                        <div class="alert alert-warning mb-3">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Car is not available for selected dates
                                        </div>
                                    <?php else: ?>
                                        <a href="book-car.php?id=<?php echo $car['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-calendar-check"></i> Book Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 