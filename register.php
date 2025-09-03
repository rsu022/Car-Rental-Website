<?php
require_once 'config/session.php';
require_once 'config/database.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validation
    if(empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } 
    // Name validation (at least 6 characters, no numbers)
    elseif(!preg_match('/^[a-zA-Z\s]{6,}$/', $name)) {
        $error = "Name must be at least 6 characters long and contain only letters and spaces.";
    }
    // Email validation (must be @gmail.com)
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/', $email)) {
        $error = "Please enter a valid Gmail address.";
    }
    // Password validation (at least 8 characters, 1 number, 1 special character)
    elseif(!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $password)) {
        $error = "Password must be at least 8 characters long and contain at least one number and one special character.";
    }
    elseif($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $error = "Email already exists.";
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $hashed_password);
            
            if($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CarRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .password-requirements {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .requirement {
            margin-bottom: 0.25rem;
        }
        .requirement i {
            margin-right: 0.5rem;
        }
        .valid {
            color: #28a745;
        }
        .invalid {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Register</h2>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       pattern="[a-zA-Z\s]{6,}" 
                                       title="Name must be at least 6 characters long and contain only letters and spaces">
                                <small class="form-text text-muted">At least 6 characters, letters and spaces only</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" 
                                       title="Please enter a valid Gmail address">
                                <small class="form-text text-muted">Must be a Gmail address</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required 
                                       pattern="^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$" 
                                       title="Password must be at least 8 characters long and contain at least one number and one special character">
                                <div class="password-requirements">
                                    <div class="requirement" id="length"><i class="fas fa-times"></i> At least 8 characters</div>
                                    <div class="requirement" id="number"><i class="fas fa-times"></i> At least one number</div>
                                    <div class="requirement" id="special"><i class="fas fa-times"></i> At least one special character</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div id="passwordMatch" class="form-text"></div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('passwordMatch');
            
            // Password validation
            password.addEventListener('input', function() {
                const value = this.value;
                
                // Check length
                document.getElementById('length').className = value.length >= 8 ? 
                    'requirement valid' : 'requirement invalid';
                document.getElementById('length').querySelector('i').className = 
                    value.length >= 8 ? 'fas fa-check' : 'fas fa-times';
                
                // Check for number
                document.getElementById('number').className = /\d/.test(value) ? 
                    'requirement valid' : 'requirement invalid';
                document.getElementById('number').querySelector('i').className = 
                    /\d/.test(value) ? 'fas fa-check' : 'fas fa-times';
                
                // Check for special character
                document.getElementById('special').className = /[@$!%*#?&]/.test(value) ? 
                    'requirement valid' : 'requirement invalid';
                document.getElementById('special').querySelector('i').className = 
                    /[@$!%*#?&]/.test(value) ? 'fas fa-check' : 'fas fa-times';
            });
            
            // Password match validation
            confirmPassword.addEventListener('input', function() {
                if (this.value === password.value) {
                    passwordMatch.textContent = 'Passwords match';
                    passwordMatch.className = 'form-text text-success';
                } else {
                    passwordMatch.textContent = 'Passwords do not match';
                    passwordMatch.className = 'form-text text-danger';
                }
            });
        });
    </script>
</body>
</html> 