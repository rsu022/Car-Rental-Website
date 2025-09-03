<?php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/src/Service/RecommendationService.php';
use App\Service\RecommendationService;

if(!isset($_GET['id'])) {
    header("Location: cars.php");
    exit();
}

$car_id = $_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Get car details
$query = "SELECT * FROM cars WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $car_id);
$stmt->execute();

if($stmt->rowCount() == 0) {
    header("Location: cars.php");
    exit();
}

$car = $stmt->fetch(PDO::FETCH_ASSOC);

// Convert features string to array
$features = [];
if (!empty($car['features'])) {
    $features = array_map(function($feature) {
        return ['name' => trim($feature)];
    }, explode(',', $car['features']));
}

// Get car reviews
$query = "SELECT r.*, u.name as user_name 
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          WHERE r.car_id = :car_id 
          ORDER BY r.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":car_id", $car_id);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avg_rating = 0;
if(count($reviews) > 0) {
    $total_rating = 0;
    foreach($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $avg_rating = $total_rating / count($reviews);
}

// After getting car details, add this check
if (isset($_SESSION['user_id'])) {
    $check_query = "SELECT * FROM bookings 
                   WHERE user_id = :user_id 
                   AND car_id = :car_id 
                   AND DATE(created_at) = CURDATE()
                   AND status != 'cancelled' 
                   AND status != 'rejected'";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':car_id' => $car_id
    ]);
    $has_booked_today = $check_stmt->rowCount() > 0;
} else {
    $has_booked_today = false;
}

// Handle booking form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Calculate total price
    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    $total_price = $days * $car['price_per_day'];
    
    // Check if car is available for the selected dates
    $query = "SELECT id FROM bookings 
              WHERE car_id = :car_id 
              AND status != 'cancelled'
              AND (
                  (start_date <= :start_date AND end_date >= :start_date) OR
                  (start_date <= :end_date AND end_date >= :end_date) OR
                  (start_date >= :start_date AND end_date <= :end_date)
              )";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":car_id", $car_id);
    $stmt->bindParam(":start_date", $start_date);
    $stmt->bindParam(":end_date", $end_date);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        // Create booking
        $query = "INSERT INTO bookings (user_id, car_id, start_date, end_date, total_price) 
                  VALUES (:user_id, :car_id, :start_date, :end_date, :total_price)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":car_id", $car_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":total_price", $total_price);
        
        if($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        }
    } else {
        $error = "Car is not available for the selected dates.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['name']); ?> - Car Rental System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .car-gallery img {
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        .feature-badge {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin: 2px;
            display: inline-block;
        }
        .review-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .rating-stars {
            color: #ffc107;
        }
        .booking-card {
            position: sticky;
            top: 20px;
        }
        .total-price {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .review-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid mt-5 px-4">
        <div class="row">
            <!-- Car Details -->
            <div class="col-lg-8">
                <!-- Car Gallery -->
                <div class="car-gallery mb-4">
                    <?php if($car['image']): ?>
                        <img src="assets/images/<?php echo htmlspecialchars($car['image']); ?>" 
                             class="img-fluid w-100" 
                             alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                    <?php endif; ?>
                </div>

                <!-- Car Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h1 class="mb-0"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h1>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($car['year']); ?> • <?php echo htmlspecialchars(ucfirst($car['category'])); ?></p>
                            </div>
                            <div class="text-end">
                                <div class="rating-stars mb-2">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?php echo $i <= $avg_rating ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-muted"><?php echo number_format($avg_rating, 1); ?> (<?php echo count($reviews); ?> reviews)</div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Car Details</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Category:</strong> <?php echo htmlspecialchars($car['category']); ?></li>
                                    <li><strong>Year:</strong> <?php echo htmlspecialchars($car['year']); ?></li>
                                    <li><strong>Price:</strong> Rs. <?php echo number_format($car['price_per_day'], 2); ?>/day</li>
                                    <li>
                                        <strong>Status:</strong> 
                                        <span class="badge <?php echo $car['status'] === 'available' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo ucfirst($car['status']); ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Features</h5>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach($features as $feature): ?>
                                        <span class="feature-badge">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <?php echo htmlspecialchars($feature['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <?php if($car['description']): ?>
                            <h5>Description</h5>
                            <p class="mb-4"><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                        <?php endif; ?>

                        <!-- Reviews Section -->
                        <h5 class="mb-3">Reviews & Ratings</h5>
                        <?php if(empty($reviews)): ?>
                            <p class="text-muted">No reviews yet. Be the first to review this car!</p>
                        <?php else: ?>
                            <?php foreach($reviews as $review): ?>
                                <div class="review-card card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                            <div class="rating-stars">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        <small class="text-muted">
                                            <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Review Form -->
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="review-form">
                                <h5 class="mb-3">Write a Review</h5>
                                <form method="POST" action="submit-review.php">
                                    <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="rating-input mb-2">
                                            <?php for($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                                <label for="star<?php echo $i; ?>"><i class="far fa-star"></i></label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="comment" class="form-label">Your Review</label>
                                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Review</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="col-lg-4">
                <div class="card booking-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Book This Car</h5>
                        
                        <?php if($car['status'] === 'unavailable'): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                This car is currently unavailable for booking.
                            </div>
                        <?php elseif(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Please log in to book this car.
                            </div>
                            <a href="login.php" class="btn btn-primary">Login to Book</a>
                        <?php elseif ($has_booked_today): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You have already booked this car today.
                            </div>
                        <?php else: ?>
                            <a href="book-car.php?id=<?php echo $car['id']; ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-calendar-check me-2"></i>Book Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Similar Cars Section -->
    <div class="container-fluid mt-5 px-4">
        <h3 class="mb-4">Similar Cars You Might Like</h3>
        <div class="row">
            <?php
            $recommendationService = new RecommendationService($db);
            $similar_cars = $recommendationService->getHybridRecommendations(
                $_SESSION['user_id'] ?? null,
                $car_id
            );

            // Attach avg_rating and review_count to each recommended car
            foreach ($similar_cars as &$sim_car) {
                $stmtStats = $db->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE car_id = ?");
                $stmtStats->execute([$sim_car['id']]);
                $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                $sim_car['avg_rating'] = $stats['avg_rating'] ?? 0;
                $sim_car['review_count'] = $stats['review_count'] ?? 0;
            }
            unset($sim_car);

            foreach($similar_cars as $similar_car): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <?php if($similar_car['image']): ?>
                            <img src="assets/images/<?php echo htmlspecialchars($similar_car['image']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($similar_car['brand'] . ' ' . $similar_car['model']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($similar_car['brand'] . ' ' . $similar_car['model']); ?></h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rating-stars">
                                    <?php 
                                    $rating = round($similar_car['avg_rating']);
                                    for($i = 1; $i <= 5; $i++): 
                                        if($i <= $rating): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif;
                                    endfor; ?>
                                    <small class="text-muted">(<?php echo $similar_car['review_count']; ?>)</small>
                                </div>
                                <div class="price">
                                    <strong>Rs. <?php echo number_format($similar_car['price_per_day']); ?></strong>/day
                                </div>
                            </div>
                            <a href="car-details.php?id=<?php echo $similar_car['id']; ?>" class="btn btn-primary mt-3 w-100">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script src="assets/js/car.js"></script>
    <script>
        // Set minimum date for pickup and return dates
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').min = today;
            document.getElementById('end_date').min = today;
            
            // Update return date minimum when pickup date changes
            document.getElementById('start_date').addEventListener('change', function() {
                document.getElementById('end_date').min = this.value;
            });
        });

        // Add daily rate as a hidden input for price calculation
        document.addEventListener('DOMContentLoaded', function() {
            const bookingForm = document.getElementById('bookingForm');
            if (bookingForm) {
                const dailyRateInput = document.createElement('input');
                dailyRateInput.type = 'hidden';
                dailyRateInput.id = 'daily_rate';
                dailyRateInput.value = <?php echo $car['price_per_day']; ?>;
                bookingForm.appendChild(dailyRateInput);
            }
        });
    </script>
</body>
</html>