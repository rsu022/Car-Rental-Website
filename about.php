<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - CarRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- About Hero Section -->
    <section class="about-hero py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1 class="display-4">About CarEase</h1>
                    <p class="lead">Your trusted partner in car rental services</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Story Section -->
    <section class="company-story py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="section-title">Our Story</h2>
                    <p class="lead">Founded in 2025, CarEase has been providing reliable and affordable car rental services..</p>
                    <p>We started with a simple mission: to make car rental services accessible, reliable, and affordable for everyone. Over the years, we've grown our fleet, expanded our services, and built a reputation for excellence in customer service.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <section class="mission-vision py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-bullseye fa-3x mb-3 text-primary"></i>
                            <h3 class="card-title">Our Mission</h3>
                            <p class="card-text">To provide reliable, affordable, and convenient car rental services while maintaining the highest standards of customer satisfaction and vehicle safety.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-eye fa-3x mb-3 text-primary"></i>
                            <h3 class="card-title">Our Vision</h3>
                            <p class="card-text">To become Nepal's leading car rental service provider, setting new standards in customer service and vehicle management.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-md-12">
                    <h2 class="section-title">Our Core Values</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="value-card text-center">
                        <i class="fas fa-handshake fa-3x mb-3 text-primary"></i>
                        <h4>Trust</h4>
                        <p>Building lasting relationships through transparency and reliability</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="value-card text-center">
                        <i class="fas fa-star fa-3x mb-3 text-primary"></i>
                        <h4>Excellence</h4>
                        <p>Striving for the highest standards in everything we do</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="value-card text-center">
                        <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                        <h4>Customer Focus</h4>
                        <p>Putting our customers at the heart of our business</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="value-card text-center">
                        <i class="fas fa-leaf fa-3x mb-3 text-primary"></i>
                        <h4>Sustainability</h4>
                        <p>Committing to environmentally responsible practices</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>