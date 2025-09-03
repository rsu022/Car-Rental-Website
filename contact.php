<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        try {
            $query = "INSERT INTO messages (name, email, subject, message, created_at) 
                     VALUES (:name, :email, :subject, :message, NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            
            if ($stmt->execute()) {
                $success_message = "Thank you for your message! We'll get back to you soon.";
            } else {
                $error_message = "Sorry, there was an error sending your message. Please try again.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - CarRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Contact Hero Section -->
    <section class="contact-hero py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1 class="display-4">Contact Us</h1>
                    <p class="lead">Get in touch with us for any queries or support</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title mb-4">Send us a Message</h3>
                            
                            <?php if ($success_message): ?>
                                <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="contact.php">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title mb-4">Contact Information</h3>
                            <div class="contact-info">
                                <div class="mb-4">
                                    <i class="fas fa-map-marker-alt fa-2x text-primary mb-3"></i>
                                    <h5>Our Location</h5>
                                    <p>Thamel, Kathmandu, Nepal</p>
                                </div>
                                <div class="mb-4">
                                    <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                                    <h5>Phone Numbers</h5>
                                    <p>+977 1234567890</p>
                                    <p>+977 9876543210</p>
                                </div>
                                <div class="mb-4">
                                    <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                                    <h5>Email Address</h5>
                                    <p>info@carease.com</p>
                                    <p>support@carease.com</p>
                                </div>
                                <div class="mb-4">
                                    <i class="fas fa-clock fa-2x text-primary mb-3"></i>
                                    <h5>Business Hours</h5>
                                    <p>Sunday - Friday: 7:00 AM - 7:00 PM</p>
                                    <p>Saturday: 8:00 AM - 5:00 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="map-container">
                        <iframe 
                            src=" https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.462444447567!2d85.3142483150621!3d27.709031982793!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1980%3A0x93a1a1c1!2sThamel%2C%20Kathmandu%2044600!5e0!3m2!1sen!2snp!4v1620000000000!5m2!1sen!2snp" 
                            width="100%" 
                            height="450" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 