<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if user has ever uploaded a driver's license
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT id FROM driver_licenses WHERE user_id = :user_id ORDER BY id DESC LIMIT 1");
$stmt->execute([':user_id' => $user_id]);
$license_exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

$database = new Database();
$db = $database->getConnection();

// Fetch pickup/dropoff locations
$stmt_loc = $db->prepare('SELECT * FROM locations');
$stmt_loc->execute();
$locations = $stmt_loc->fetchAll(PDO::FETCH_ASSOC);

// Get car ID from URL
$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$car_id) {
    header("Location: cars.php");
    exit();
}

// Get car details with features
$query = "SELECT c.* FROM cars c WHERE c.id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $car_id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

// Get review stats
$reviewQuery = "SELECT COUNT(*) as review_count, COALESCE(AVG(rating), 0) as avg_rating 
                FROM reviews 
                WHERE car_id = :car_id";
$reviewStmt = $db->prepare($reviewQuery);
$reviewStmt->execute([':car_id' => $car_id]);
$reviewStats = $reviewStmt->fetch(PDO::FETCH_ASSOC);
$car['review_count'] = $reviewStats['review_count'];
$car['avg_rating'] = $reviewStats['avg_rating'];

// Check if car exists
if (!$car) {
    header('Location: cars.php');
    exit();
}

// Check if car is available for booking
if ($car['status'] === 'unavailable') {
    $_SESSION['error'] = 'This car is currently unavailable for booking.';
    header('Location: cars.php');
    exit();
}

// Get reviews for the car
$query_reviews = "SELECT r.*, u.name as user_name 
                 FROM reviews r 
                 JOIN users u ON r.user_id = u.id 
                 WHERE r.car_id = :car_id 
                 ORDER BY r.created_at DESC
                 LIMIT 5";
$stmt_reviews = $db->prepare($query_reviews);
$stmt_reviews->execute([':car_id' => $car_id]);
$reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Check if terms are agreed
    if (!isset($_POST['agreeTerms'])) {
        $_SESSION['error'] = 'You must agree to the Terms and Conditions to proceed.';
        header('Location: book-car.php?id=' . $car_id);
        exit();
    }
    $agreed_terms = 1;

    // Check for existing bookings for this car by the same user on the same day
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
    
    if ($check_stmt->rowCount() > 0) {
        $_SESSION['error'] = 'You have already booked this car today. You cannot book the same car multiple times in one day.';
        header('Location: book-car.php?id=' . $car_id);
        exit();
    }

    // Check if car is available for the selected dates
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
        ':car_id' => $car_id,
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ]);
    
    if ($availability_stmt->fetch(PDO::FETCH_ASSOC)['booking_count'] > 0) {
        $_SESSION['error'] = 'This car is not available for the selected dates. Please choose different dates.';
        header('Location: book-car.php?id=' . $car_id);
        exit();
    }

    // Calculate total days and price
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $end->diff($start)->days + 1;
    $maxDays = 30;
    if ($days > $maxDays) {
        $_SESSION['error'] = 'You cannot book a car for more than 30 days.';
        header('Location: book-car.php?id=' . $car_id);
        exit();
    }
    $total_price = $days * $car['price_per_day'];
    
    // Insert booking
    $query = "INSERT INTO bookings (user_id, car_id, pickup_location_id, return_location_id, start_date, end_date, total_price, status, agreed_terms) 
              VALUES (:user_id, :car_id, :pickup_location_id, :return_location_id, :start_date, :end_date, :total_price, 'pending', :agreed_terms)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':car_id' => $car_id,
        ':pickup_location_id' => $_POST['pickup_location_id'],
        ':return_location_id' => $_POST['return_location_id'],
        ':start_date' => $start_date,
        ':end_date' => $end_date,
        ':total_price' => $total_price,
        ':agreed_terms' => $agreed_terms
    ]);
    
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Car - Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .star-rating { direction: rtl; font-size: 1.5rem; }
        .star-rating input { display: none; }
        .star-rating label { color: #ccc; cursor: pointer; }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #fc0; }
        .car-image-container {
            width: 100%;
            height: 320px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .car-image-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-4" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-4" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="container mt-5">
        <div class="row">
            <!-- Booking Form -->
            <div class="col-md-4 order-md-2">
                <div class="card shadow position-sticky" style="top: 2rem;">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Book This Car</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <p class="mb-2">Please log in to book this car.</p>
                                <a href="login.php" class="btn btn-primary">Login Now</a>
                            </div>
                        <?php else: ?>
                            <?php if (! $license_exists): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-id-card me-2"></i>
                                    <p class="mb-2">Please upload your driver's license before booking.</p>
                                    <a href="upload-license.php" class="btn btn-primary">Upload License</a>
                                </div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="pickup_location_id" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Pickup Location</label>
                                    <select id="pickup_location_id" name="pickup_location_id" class="form-select" required>
                                        <option value="">Select pickup location</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="return_location_id" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Return Car Location</label>
                                    <select id="return_location_id" name="return_location_id" class="form-select" required>
                                        <option value="">Select return car location</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="start_date" class="form-label"><i class="fas fa-calendar me-2"></i>Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="end_date" class="form-label"><i class="fas fa-calendar-check me-2"></i>End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-money-bill-wave me-2"></i>Total Price</label>
                                    <div class="alert alert-info mb-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="mb-0">Rs. <span id="total_price">0.00</span></h4>
                                                <small class="text-muted" id="days_count">Select dates to calculate total</small>
                                            </div>
                                            <i class="fas fa-calculator fa-2x text-muted"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" name="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-2">Book Now</button>
                                <a href="cars.php" class="btn btn-outline-secondary w-100">Cancel</a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8 order-md-1">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h4 class="card-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h4>
                        
                        <?php if ($car['image']): ?>
                        <div class="car-image-container">
                            <img src="assets/images/<?php echo htmlspecialchars($car['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                        </div>
                        <?php endif; ?>
                        
                        <div class="car-details">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <p class="mb-1"><i class="fas fa-calendar-alt me-2"></i> <strong>Year:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($car['year']); ?></p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><i class="fas fa-tag me-2"></i> <strong>Category:</strong></p>
                                    <p class="text-muted"><?php echo ucfirst(htmlspecialchars($car['category'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="price-section bg-light p-3 rounded mb-3">
                                <h5 class="mb-2">Price per Day</h5>
                                <h3 class="text-primary mb-0">Rs. <?php echo number_format($car['price_per_day'], 2); ?></h3>
                            </div>
                        
                        <?php if ($car['features']): ?>
                            <div class="features-section mb-3">
                                <h5 class="mb-3">Features</h5>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach (explode(',', $car['features']) as $feature): ?>
                                        <?php if(trim($feature)): ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-check-circle me-1 text-success"></i>
                                                <?php echo htmlspecialchars(trim($feature)); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <p><strong>Description:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                    </div>
                </div>
                <!-- Reviews Section -->
                <?php if ($car['review_count'] > 0): ?>
                    <div class="card shadow mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Reviews</h5>
                            <div class="rating-summary">
                                <span class="text-warning">
                                    <?php 
                                    $rating = round($car['avg_rating']);
                                    for($i = 1; $i <= 5; $i++):
                                        if($i <= $rating): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif;
                                    endfor; ?>
                                </span>
                                <span class="ms-2 text-muted">(<?php echo $car['review_count']; ?> reviews)</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php foreach($reviews as $review): ?>
                                <div class="review-item mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                            <div class="text-warning">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Review Submission Form -->
                <div class="review-form card shadow p-4 mt-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <h5 class="mb-3">Submit Your Review</h5>
                        <form action="submit-review.php" method="POST">
                            <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                            <!-- Star Rating Input -->
                            <div class="mb-3 star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> Stars"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Comment</label>
                                <textarea name="comment" id="comment" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    <?php else: ?>
                        <p>Please <a href="login.php">login</a> to submit a review.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate total price when dates change
        document.getElementById('start_date').addEventListener('change', function() {
            // Set max end date to 30 days after start date
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            if (startInput.value) {
                const startDate = new Date(startInput.value);
                const maxEndDate = new Date(startDate);
                maxEndDate.setDate(startDate.getDate() + 29); // 30 days including start
                endInput.min = startInput.value;
                endInput.max = maxEndDate.toISOString().split('T')[0];
                // If current end date is out of range, reset it
                if (endInput.value > endInput.max) {
                    endInput.value = '';
                }
            } else {
                endInput.max = '';
            }
            calculateTotal();
        });
        document.getElementById('end_date').addEventListener('change', calculateTotal);

        function calculateTotal() {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            const daysCountEl = document.getElementById('days_count');
            const totalPriceEl = document.getElementById('total_price');
            
            if(startDate && endDate) {
                if(startDate > endDate) {
                    daysCountEl.textContent = 'End date must be after start date';
                    daysCountEl.classList.add('text-danger');
                    totalPriceEl.textContent = '0.00';
                    return;
                }
                
                const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
                const totalPrice = days * <?php echo $car['price_per_day']; ?>;
                
                daysCountEl.textContent = days + (days === 1 ? ' day' : ' days') + ' rental';
                daysCountEl.classList.remove('text-danger');
                totalPriceEl.textContent = totalPrice.toFixed(2);
            } else {
                daysCountEl.textContent = 'Select dates to calculate total';
                daysCountEl.classList.remove('text-danger');
                totalPriceEl.textContent = '0.00';
            }
        }
    </script>
    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
            <ol>
             <li><b>Citizenship Submission:</b> You must submit your citizenship document to the office before picking up the car to ensure security. <b>This is mandatory.</b></li>
              <li><b>Age & License:</b> You must be at least 21 years old (age limit may vary by vehicle category). A valid Driving License (minimum 1 year old) is mandatory.</li>
              <li><b>Pickup/Drop-off:</b> Vehicles must be picked up and dropped off at the designated location or as per the selected delivery option.</li>
              <li><b>Late Returns:</b> Any late returns beyond the grace period will incur extra charges per hour.</li>
              <li><b>Condition & Fuel:</b> Cars should be returned in the same condition and fuel level as received.</li>
              <li><b>Authorized Drivers:</b> Only the registered driver(s) in the booking form are allowed to drive the car.</li>
              <li><b>User Liability:</b> The renter is fully liable for any damage caused due to negligence, traffic violations, or misuse of the vehicle during the rental period.</li>
              <li><b>Insurance Deductible:</b> If the damage is minor and falls under insurance, the user must pay only the deductible amount (e.g., NPR 5,000–15,000), as per the vehicle's insurance policy.</li>
              <li><b>Full Repair Charges:</b> In case of major damage, misuse, or if insurance is denied, the user must pay the entire repair cost, including downtime loss if applicable.</li>
              <li><b>Security Deposit Adjustment:</b> Repair charges will be first deducted from the security deposit, and any remaining amount must be paid by the user within the specified timeframe.</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
</body>
</html>