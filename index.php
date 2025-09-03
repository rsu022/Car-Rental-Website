<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once __DIR__ . '/src/Service/RecommendationService.php';
use App\Service\RecommendationService;

// Redirect admin to admin dashboard
if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get featured cars by category to ensure all categories are represented
$categories = $db->query("SELECT DISTINCT category FROM cars WHERE status = 'available' AND category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$featured_cars = [];
$limitPerCat = ceil(6 / max(count($categories), 1));
foreach ($categories as $cat) {
    $stmt = $db->prepare("SELECT * FROM cars WHERE status = 'available' AND category = ? LIMIT $limitPerCat");
    $stmt->execute([$cat]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $car) {
        $featured_cars[] = $car;
    }
}
$featured_cars = array_slice($featured_cars, 0, 6);

// Get features for each car
$featureQuery = "SELECT features FROM cars WHERE id = ?";
$featureStmt = $db->prepare($featureQuery);
foreach ($featured_cars as &$car) {
    $featureStmt->execute([$car['id']]);
    $features = $featureStmt->fetchColumn();
    $car['features'] = $features ? explode(',', $features) : [];
    
    // Get review stats
    $reviewQuery = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                    FROM reviews 
                    WHERE car_id = ?";
    $reviewStmt = $db->prepare($reviewQuery);
    $reviewStmt->execute([$car['id']]);
    $reviewStats = $reviewStmt->fetch(PDO::FETCH_ASSOC);
    $car['avg_rating'] = $reviewStats['avg_rating'] ?? 0;
    $car['review_count'] = $reviewStats['review_count'] ?? 0;
}
unset($car); // Break the reference


// Get car categories
$query = "SELECT DISTINCT category FROM cars WHERE category IS NOT NULL";
$categories = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);

// Banner images array for hero section
$banner_images = [
    'assets/images/banner1.jpg',
    'assets/images/banner2.jpg',
    'assets/images/banner3.jpg'
];
$current_banner = $banner_images[0]; // Default first image

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarEase - Car Rental System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --secondary: #333333;
            --accent: #0066cc;
            --accent-hover: #0052a3;
            --text: #1a1a1a;
            --text-light: #666666;
            --background: #ffffff;
            --background-alt: #f8f9fa;
            --border: #e5e5e5;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            --radius: 0;
            --font-primary: 'Inter', sans-serif;
            --font-secondary: 'Poppins', sans-serif;
        }

        body {
            font-family: var(--font-primary);
            background-color: var(--background);
            color: var(--text);
            line-height: 1.6;
        }

        /* Banner Styles */
        .hero-banner {
            position: relative;
            height: 85vh;
            min-height: 650px;
            overflow: hidden;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .banner-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .banner-slide.active {
            opacity: 1;
        }

        .hero-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
            padding: 0 1rem;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-family: 'Poppins', sans-serif;
            letter-spacing: -0.02em;
            color: #fff;
            text-shadow: 0 4px 24px rgba(0,0,0,0.7), 0 1.5px 0 rgba(0,0,0,0.25);
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            line-height: 1.6;
            text-shadow: 0 2px 12px rgba(0,0,0,0.6);
            font-weight: 400;
            font-family: 'Inter', sans-serif;
            color: #fff;
        }

        .hero-btn, .hero-btn-primary {
            text-decoration: none !important;
        }

        .hero-btn {
            padding: 1rem 2.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 0;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: var(--accent);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.3);
            font-family: 'Inter', sans-serif;
        }

        .hero-btn:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 102, 204, 0.4);
        }

        /* Section Styles */
        .section {
            padding: 6rem 0;
        }

        .section-title {
            font-size: 2.25rem;
            font-weight: 600;
            margin-bottom: 3rem;
            text-align: center;
            color: var(--text);
            font-family: var(--font-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Card Styles */
        .car-card {
            background: var(--background);
            border: 1px solid var(--border);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .car-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .car-details {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .car-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-family: var(--font-primary);
        }

        .car-price {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--accent);
            margin: 1rem 0;
        }

        .category-badge {
            display: inline-block;
            padding: 0.25em 0.75em;
            background-color: var(--background-alt);
            color: var(--text);
            font-size: 0.875rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* FAQ Styles */
        .faq-section {
            background-color: var(--background-alt);
            padding: 4rem 0;
        }

        .faq-item {
            margin-bottom: 1rem;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .faq-question {
            padding: 1.25rem;
            background: var(--background);
            cursor: pointer;
            font-weight: 500;
            color: var(--text);
            transition: all 0.3s ease;
            font-family: var(--font-primary);
        }

        .faq-question:hover {
            background: var(--background-alt);
        }

        .faq-answer {
            padding: 1.25rem;
            background: var(--background);
            border-top: 1px solid var(--border);
            color: var(--text-light);
        }

        /* Button Styles */
        .btn-primary {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        /* Footer Styles */
        footer {
            background: var(--primary);
            color: white;
            padding: 4rem 0 2rem;
        }

        footer h5 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-family: var(--font-primary);
        }

        footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        footer a:hover {
            color: white;
        }

        /* Why Choose Us Section */
        .why-us-section {
            background-color: var(--background-alt);
            padding: 6rem 0;
        }

        .why-us-section .section-title {
            margin-bottom: 4rem;
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
        }

        .why-us-section h4 {
            font-family: var(--font-secondary);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .why-us-section p {
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Map Section */
        .map-section {
            padding: 6rem 0;
        }

        .map-section h2 {
            font-family: var(--font-secondary);
            font-size: 2.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text);
        }

        .map-section .lead {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .map-section p {
            margin-bottom: 1rem;
            color: var(--text);
        }

        .map-section i {
            color: var(--accent);
            width: 24px;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .hero-banner {
                height: 70vh;
                min-height: 500px;
            }

            .hero-title {
                font-size: 2.75rem;
            }

            .hero-subtitle {
                font-size: 1.25rem;
                margin-bottom: 2rem;
            }

            .section {
                padding: 4rem 0;
            }

            .section-title {
                font-size: 2rem;
                margin-bottom: 2.5rem;
            }

            .why-us-section {
                padding: 4rem 0;
            }

            .map-section {
                padding: 4rem 0;
            }
        }

        /* Recommended Cars Section */
        .recommended-section {
            margin-bottom: 4rem;
        }
        .recommended-section .car-card {
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            padding: 0;
        }
        .recommended-section .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .recommended-section .card-img-top {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .recommended-section .card-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .recommended-section .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-family: var(--font-secondary);
        }
        .recommended-section .card-text {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }
        .recommended-section .btn {
            background: var(--accent);
            color: #fff;
            border-radius: 8px;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.95rem;
            transition: background 0.3s, transform 0.3s;
            margin-top: auto;
            text-decoration: none;
        }
        .recommended-section .btn:hover {
            background: var(--accent-hover);
            color: #fff;
            transform: translateY(-2px);
        }
        /* Adjust gap between recommended and featured */
        .featured-section {
            margin-top: 2.5rem;
        }


    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Banner Section -->
    <section class="hero-banner">
        <?php foreach ($banner_images as $index => $image): ?>
        <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
             style="background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('<?php echo htmlspecialchars($image); ?>')">
        </div>
        <?php endforeach; ?>
        
        <div class="hero-content">
            <h1 class="hero-title">Find Your Perfect Ride</h1>
            <p class="hero-subtitle">Choose from our wide range of premium vehicles for your next adventure</p>
            
            <div class="hero-buttons">
                <a href="cars.php" class="hero-btn hero-btn-primary">Browse Cars</a>
            </div>
        </div>
    </section>

    <?php
    // Show recommendations only if user is logged in and has at least one booking
    if (isset($_SESSION['user_id'])) {
        $stmtB = $db->prepare("SELECT car_id FROM bookings WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmtB->execute([$_SESSION['user_id']]);
        $lastBooking = $stmtB->fetch(PDO::FETCH_ASSOC);
        if ($lastBooking) {
            $recService = new RecommendationService($db);
            $recCars = $recService->getSimilarCars($lastBooking['car_id'], 4);
            if (!empty($recCars)) {
    ?>
    <section class="py-5 bg-light recommended-section">
        <div class="container">
            <h2 class="text-center mb-4" style="font-family: var(--font-secondary); font-weight:700;">Recommended For You</h2>
            <div class="row">
                <?php foreach ($recCars as $r): ?>
                <div class="col-md-3 mb-4 d-flex">
                    <div class="car-card w-100 d-flex flex-column">
                        <img src="assets/images/<?php echo htmlspecialchars($r['image'] ?: 'car-placeholder.png'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($r['brand'].' '.$r['model']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($r['brand'].' '.$r['model']); ?></h5>
                            <p class="card-text">Rs. <?php echo number_format($r['price_per_day'],2); ?>/day</p>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <a href="car-details.php?id=<?php echo $r['id']; ?>" class="btn">View Details</a>
                            <?php else: ?>
                                <a href="book-car.php?id=<?php echo $r['id']; ?>" class="btn">Book Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
            }
        }
    }
    ?>

    <!-- Featured Cars Section -->
    <section class="py-5 featured-section">
        <div class="container">
            <h2 class="text-center mb-5">Featured Cars</h2>
            <div class="row">
                <?php foreach($featured_cars as $car): ?>
                    <div class="col-md-4 mb-4">
                        <div class="car-card">
                            <img src="assets/images/<?php echo !empty($car['image']) ? htmlspecialchars($car['image']) : 'car-placeholder.png'; ?>" class="car-image" alt="<?php echo htmlspecialchars($car['brand'].' '.$car['model']); ?>">
                            <div class="car-details">
                                <h3 class="car-title"><?php echo htmlspecialchars($car['brand'].' '.$car['model']); ?></h3>
                                <span class="category-badge"><?php echo htmlspecialchars($car['category']); ?></span>
                                <div class="car-price mb-3">Rs. <?php echo number_format($car['price_per_day'],2); ?>/day</div>
                                <div class="d-grid">
                                    <?php if (!isset($_SESSION['user_id'])): ?>
                                        <a href="car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary"><i class="fas fa-eye"></i> View Details</a>
                                    <?php else: ?>
                                        <a href="book-car.php?id=<?php echo $car['id']; ?>" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Book Now</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-us-section">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose CarEase</h2>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <i class="fas fa-shield-alt feature-icon"></i>
                        <h4>Safe & Secure</h4>
                        <p>All our vehicles are regularly maintained and insured for your safety.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <i class="fas fa-hand-holding-usd feature-icon"></i>
                        <h4>Best Prices</h4>
                        <p>Competitive rates with no hidden charges. Special rates for long-term rentals.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <i class="fas fa-headset feature-icon"></i>
                        <h4>24/7 Support</h4>
                        <p>Our dedicated support team is available round the clock to assist you.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container h-100">
            <div class="row h-100 align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-4">Our Location in Kathmandu</h2>
                    <p class="lead">Visit our office in the heart of Kathmandu for the best car rental experience.</p>
                    <div class="mt-4">
                        <p><i class="fas fa-map-marker-alt me-2"></i> Thamel, Kathmandu, Nepal</p>
                        <p><i class="fas fa-phone me-2"></i> +977-1-1234567</p>
                        <p><i class="fas fa-envelope me-2"></i> info@carrental.com</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.462444447567!2d85.3142483150621!3d27.709031982793!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1980%3A0x93a1a1c1!2sThamel%2C%20Kathmandu%2044600!5e0!3m2!1sen!2snp!4v1620000000000!5m2!1sen!2snp" 
                                style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2 class="text-center mb-5">Frequently Asked Questions</h2>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="faq-item">
                        <div class="faq-question">
                            What documents do I need to rent a car?
                        </div>
                        <div class="faq-answer">
                            You'll need a valid driver's license and submit citizenship for renting the cars.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            What is included in the rental price?
                        </div>
                        <div class="faq-answer">
                            The rental price includes unlimited mileage, basic insurance coverage, and 24/7 roadside assistance. Additional services like GPS or child seats can be added for an extra fee.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            Can I modify or cancel my booking?
                        </div>
                        <div class="faq-answer">
                            Yes, you can modify or cancel your booking up to 24 hours before the rental start time. Please contact our office for assistance.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            What is your fuel policy?
                        </div>
                        <div class="faq-answer">
                            We provide the car with a full tank of fuel, and we expect it to be returned with a full tank. Alternatively, you can choose our pre-paid fuel option.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>CarRental</h5>
                    <p>Your trusted partner for car rentals in Kathmandu.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="cars.php" class="text-white">Cars</a></li>
                        <li><a href="about.php" class="text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Thamel, Kathmandu</li>
                        <li><i class="fas fa-phone me-2"></i> +977-1-1234567</li>
                        <li><i class="fas fa-envelope me-2"></i> info@carrental.com</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> CarRental. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Banner auto-scroll functionality
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.banner-slide');
            let currentSlide = 0;

            function nextSlide() {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }

            // Change slide every 5 seconds
            setInterval(nextSlide, 5000);
        });
    </script>
    <script>
        // Set minimum dates for pickup and return
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const pickupInput = document.querySelector('input[name="pickup_date"]');
            const returnInput = document.querySelector('input[name="return_date"]');
            
            pickupInput.min = today;
            returnInput.min = today;
            
            pickupInput.addEventListener('change', function() {
                returnInput.min = this.value;
                if (returnInput.value && returnInput.value < this.value) {
                    returnInput.value = this.value;
                }
            });

            // FAQ Accordion
            const faqQuestions = document.querySelectorAll('.faq-question');
            faqQuestions.forEach(question => {
                question.addEventListener('click', () => {
                    const answer = question.nextElementSibling;
                    const isOpen = answer.style.display === 'block';
                    
                    // Close all answers
                    document.querySelectorAll('.faq-answer').forEach(ans => {
                        ans.style.display = 'none';
                    });
                    
                    // Toggle current answer
                    answer.style.display = isOpen ? 'none' : 'block';
                });
            });
        });
    </script>
</body>
</html>