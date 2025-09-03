<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $car_id = isset($_POST['car_id']) ? (int)$_POST['car_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Validate inputs
    if ($car_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
        $_SESSION['error'] = 'Invalid review data. Please try again.';
        header('Location: book-car.php?id=' . $car_id);
        exit();
    }

    // Insert the review
    $database = new Database();
    $db = $database->getConnection();
    $query = "INSERT INTO reviews (user_id, car_id, rating, comment) VALUES (:user_id, :car_id, :rating, :comment)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':car_id', $car_id);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':comment', $comment);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Your review has been submitted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to submit review. Please try again.';
    }
    header('Location: book-car.php?id=' . $car_id);
    exit();
}

// For non-POST or invalid method
// Redirect to booking page
$car_id = isset($_POST['car_id']) ? (int)$_POST['car_id'] : 0;
if ($car_id) {
    header('Location: book-car.php?id=' . $car_id);
} else {
    header('Location: cars.php');
}
exit();
?>
