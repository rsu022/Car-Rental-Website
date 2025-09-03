<?php
require_once 'config/database.php';

$name = 'Admin';
$email = 'admin@gmail.com';
$password = 'admin@123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!\n";
        echo "Email: admin@gmail.com\n";
        echo "Password: admin@123\n";
    } else {
        echo "Error creating admin user: " . $stmt->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 