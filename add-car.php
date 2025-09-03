<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $category = $_POST['category'];
    $price_per_day = $_POST['price_per_day'];
    $description = $_POST['description'];
    $features = implode(',', $_POST['features'] ?? []); // Convert features array to comma-separated string

    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/images/cars/';
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image = $target_path;
        }
    }

    try {
        $db->beginTransaction();

        // Insert car
        $stmt = $db->prepare("INSERT INTO cars (brand, model, year, category, price_per_day, description, image, features, status, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())");
        $stmt->execute([$brand, $model, $year, $category, $price_per_day, $description, $image, $features]);

        $db->commit();
        header('Location: cars.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error adding car: " . $e->getMessage();
    }
}

// Get available features for the form
$features = [];
$features_query = "SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(features, ',', n.n), ',', -1)) AS name
                  FROM cars
                  CROSS JOIN (
                      SELECT a.N + b.N * 10 + 1 n
                      FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
                      CROSS JOIN (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
                      ORDER BY n
                  ) n
                  WHERE n.n <= 1 + (LENGTH(features) - LENGTH(REPLACE(features, ',', '')))
                  AND features IS NOT NULL
                  ORDER BY name";
$stmt_features = $db->prepare($features_query);
$stmt_features->execute();
$features = $stmt_features->fetchAll(PDO::FETCH_ASSOC); 