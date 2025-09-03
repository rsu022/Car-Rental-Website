<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Checking for duplicate cars</h2>";

// Check for duplicates by brand and model
$stmt = $db->query("
    SELECT brand, model, COUNT(*) as count 
    FROM cars 
    GROUP BY brand, model 
    HAVING COUNT(*) > 1
");

$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($duplicates)) {
    echo "<p>No duplicate cars found.</p>";
} else {
    echo "<h3>Duplicate cars found:</h3>";
    foreach ($duplicates as $dup) {
        echo "<p>{$dup['brand']} {$dup['model']} - {$dup['count']} entries</p>";
    }
}

// Show all cars
echo "<h2>All cars in database:</h2>";
$stmt = $db->query("SELECT id, brand, model, status, image FROM cars ORDER BY brand, model");
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cars as $car) {
    echo "<p>ID: {$car['id']} - {$car['brand']} {$car['model']} (Status: {$car['status']}) - Image: {$car['image']}</p>";
}
?> 