<?php
require_once 'config/session.php';
require_once 'config/database.php';

// Debug session
error_log('Session data: ' . print_r($_SESSION, true));

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    error_log('User already logged in, redirecting based on role');
    if($_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Debug: Log the received credentials
    error_log("Login attempt - Email: " . $email);

    if(empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Debug: Check database connection
            if (!$db) {
                error_log("Database connection failed");
                throw new Exception("Database connection failed");
            }

            $query = "SELECT id, name, email, password, role FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            // Debug: Log number of rows found
            $rowCount = $stmt->rowCount();
            error_log("Number of users found: " . $rowCount);
            
            if($rowCount == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Debug: Log the hashed password from database
                error_log("Hashed password from DB: " . $user['password']);
                error_log("Password verification attempt for user: " . $user['email']);
                
                // Debug login attempt
                error_log('Comparing passwords - Input: ' . $password . ' DB: ' . $user['password']);
                
                // Verify password hash
                if(password_verify($password, $user['password'])) {
                    error_log('Password match successful');
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    error_log('Session data set: ' . print_r($_SESSION, true));

                    // Debug: Log successful login
                    error_log("Login successful for user: " . $user['email']);

                    if($user['role'] === 'admin') {
                        header("Location: admin/dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Invalid password.";
                    // Debug: Log password verification failure
                    error_log("Password verification failed for user: " . $email);
                }
            } else {
                $error = "No account found with this email.";
                // Debug: Log no user found
                error_log("No user found with email: " . $email);
            }
        } catch(Exception $e) {
            $error = "Login failed. Please try again later.";
            // Debug: Log the actual error
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Car Rental System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4">Login</h3>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 