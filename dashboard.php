<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once __DIR__ . '/src/Service/RecommendationService.php';
use App\Service\RecommendationService;

// Ensure user is logged in
requireLogin();

// Redirect admin to admin dashboard
if (isAdmin()) {
    header('Location: admin/dashboard.php');
    exit();
}

// Database connection
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch user data
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user exists
if (!$user) {
    // Clear session and redirect to login
    session_destroy();
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update profile
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        if (!$name || !$email || !$phone) {
            $error_msg = 'All fields are required.';
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error_msg = 'Phone number must be exactly 10 digits.';
        } else {
            $stmt = $db->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
            if ($stmt->execute([$name, $email, $phone, $user_id])) {
                $success_msg = 'Profile updated successfully.';
            } else {
                $error_msg = 'Failed to update profile.';
            }
        }
    }
    // Update or upload license
    if (isset($_POST['update_license'])) {
        $license_number = trim($_POST['license_number']);
        $expiry_date = trim($_POST['expiry_date']);
        if (!$license_number || !$expiry_date) {
            $error_msg = 'License number and expiry date are required.';
        } else {
            // Handle file upload
            $file_name = null;
            if (!empty($_FILES['license_image']['name']) && $_FILES['license_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['license_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','pdf'];
                if (!in_array($ext, $allowed)) {
                    $error_msg = 'Invalid file type.';
                } else {
                    $file_name = 'license_' . uniqid() . '.' . $ext;
                    $dest = __DIR__ . '/assets/images/' . $file_name;
                    if (!move_uploaded_file($_FILES['license_image']['tmp_name'], $dest)) {
                        $error_msg = 'Failed to upload file.';
                    }
                }
            }
            if (!$error_msg) {
                // Check existing license
                $stmt = $db->prepare('SELECT id FROM driver_licenses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
                $stmt->execute([$user_id]);
                $exists = $stmt->fetchColumn();
                if ($exists) {
                    $sql = 'UPDATE driver_licenses SET license_number = ?, expiry_date = ?, status = \'pending\'';
                    $params = [$license_number, $expiry_date];
                    if ($file_name) { $sql .= ', image = ?'; array_push($params, $file_name); }
                    $sql .= ' WHERE id = ?'; array_push($params, $exists);
                } else {
                    $sql = 'INSERT INTO driver_licenses (user_id, license_number, expiry_date, status';
                    $vals = 'VALUES (?, ?, ?, \'pending\'';
                    $params = [$user_id, $license_number, $expiry_date];
                    if ($file_name) { $sql .= ', image'; $vals .= ', ?'; array_push($params, $file_name); }
                    $sql .= ') ' . $vals . ')';
                }
                $stmt = $db->prepare($sql);
                if ($stmt->execute($params)) {
                    $success_msg = 'License information updated successfully.';
                } else {
                    $error_msg = 'Failed to update license.';
                }
            }
        }
    }
    // Cancel booking
    if (isset($_POST['cancel_booking'])) {
        $id = (int)$_POST['booking_id'];
        $stmt = $db->prepare('UPDATE bookings SET status = \'cancelled\' WHERE id = ? AND user_id = ?');
        if ($stmt->execute([$id, $user_id])) {
            $success_msg = 'Booking cancelled.';
        } else {
            $error_msg = 'Failed to cancel booking.';
        }
    }
}

// Fetch user, license, bookings
$stmt = $db->prepare('SELECT * FROM driver_licenses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$stmt->execute([$user_id]);
$license = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $db->prepare("SELECT b.*, c.brand, c.model, c.image, c.price_per_day, pl.name AS pickup_location, dl.name AS return_location, DATEDIFF(b.end_date, b.start_date)+1 AS total_days, (DATEDIFF(b.end_date, b.start_date)+1)*c.price_per_day AS total_price FROM bookings b JOIN cars c ON b.car_id = c.id JOIN locations pl ON b.pickup_location_id = pl.id JOIN locations dl ON b.return_location_id = dl.id WHERE b.user_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare recommendations based on most recent booking
$recCars = [];
if (!empty($bookings)) {
    $lastCarId = $bookings[0]['car_id'];
    $recService = new RecommendationService($db);
    $recCars = $recService->getSimilarCars($lastCarId, 4);
}

// Fetch user reviews
$stmt = $db->prepare('SELECT r.*, c.brand, c.model FROM reviews r JOIN cars c ON r.car_id = c.id WHERE r.user_id = ? ORDER BY r.created_at DESC');
$stmt->execute([$user_id]);
$user_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Booking notifications
$notifications = [];
// Notify only the most recent booking
if (!empty($bookings)) {
    $booking = $bookings[0];
    if (in_array($booking['status'], ['approved','cancelled']) && empty($_SESSION['notified_booking_'.$booking['id']])) {
        $type = $booking['status'] === 'approved' ? 'success' : 'danger';
        $notifications[] = [
            'type' => $type,
            'msg' => "Your booking for {$booking['brand']} {$booking['model']} from " . date('M d, Y', strtotime($booking['start_date'])) . " to " . date('M d, Y', strtotime($booking['end_date'])) . " has been {$booking['status']}."
        ];
        $_SESSION['notified_booking_'.$booking['id']] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <title>Dashboard - Car Rental</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
    body, .dashboard-main {
        font-family: var(--font-primary);
        background: var(--gray-100);
        color: var(--gray-800);
    }
    .dashboard-header {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 2rem;
        color: #22223b;
        letter-spacing: 0.01em;
    }
    .dashboard-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(60, 72, 88, 0.07);
        padding: 2rem 1.5rem;
        margin-bottom: 2rem;
        transition: box-shadow 0.2s;
    }
    .dashboard-card:hover {
        box-shadow: 0 6px 24px rgba(60, 72, 88, 0.13);
    }
    .dashboard-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(60, 72, 88, 0.07);
        margin-bottom: 2rem;
    }
    .dashboard-table th, .dashboard-table td {
        padding: 1rem 1.2rem;
        text-align: left;
        font-size: 1rem;
    }
    .dashboard-table th {
        background: #f1f3f7;
        font-weight: 600;
        color: #22223b;
        border-bottom: 2px solid #e0e1dd;
    }
    .dashboard-table tr:not(:last-child) td {
        border-bottom: 1px solid #f1f3f7;
    }
    .dashboard-table tr:hover td {
        background: #f8faff;
    }
    .dashboard-badge {
        display: inline-block;
        padding: 0.35em 0.8em;
        border-radius: 12px;
        font-size: 0.95em;
        font-weight: 600;
        color: #fff;
        background: #4f8cff;
        margin-right: 0.5em;
    }
    .dashboard-badge.success { background: #28a745; }
    .dashboard-badge.warning { background: #ffc107; color: #22223b; }
    .dashboard-badge.danger { background: #dc3545; }
    .dashboard-badge.info { background: #17a2b8; }
    .dashboard-section-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #22223b;
        letter-spacing: 0.01em;
    }
    .dashboard-btn {
        background: #4f8cff;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.6em 1.4em;
        font-weight: 600;
        font-size: 1rem;
        transition: background 0.2s;
        margin-right: 0.5em;
    }
    .dashboard-btn:hover {
        background: #3867d6;
        color: #fff;
    }
    @media (max-width: 768px) {
        .dashboard-card, .dashboard-table {
            padding: 1rem;
        }
        .dashboard-header {
            font-size: 1.4rem;
        }
    }
    .recommended-section {
        margin-bottom: 4rem;
    }
    .recommended-section .car-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        padding: 0;
    }
    .recommended-section .car-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
        color: #1a1a1a;
        font-family: 'Poppins', sans-serif;
    }
    .recommended-section .card-text {
        font-size: 1.1rem;
        color: #666666;
        margin-bottom: 1rem;
    }
    .recommended-section .btn {
        background: #0066cc;
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
        background: #0052a3;
        color: #fff;
        transform: translateY(-2px);
    }
    .featured-section {
        margin-top: 2.5rem;
    }
    .dashboard-section-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .dashboard-section-title-row .dashboard-section-title {
        margin-bottom: 0;
    }
    .dashboard-section-actions {
        display: flex;
        gap: 0.5rem;
    }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container mt-5 pt-4 dashboard-main">
        <div class="dashboard-header">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</div>
        <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
        <?php foreach ($notifications as $notification): ?>
            <div class="alert alert-<?php echo $notification['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($notification['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="dashboard-section-title-row">
                        <div class="dashboard-section-title"><i class="fas fa-user me-2"></i>Profile Information</div>
                        <div class="dashboard-section-actions">
                            <button class="dashboard-btn btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit Profile</button>
                        </div>
                    </div>
                    <div>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="dashboard-section-title-row">
                        <div class="dashboard-section-title"><i class="fas fa-id-card me-2"></i>Driver's License</div>
                        <div class="dashboard-section-actions">
                            <?php if ($license && $license['image']): ?>
                                <button class="dashboard-btn btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#viewLicenseModal">View License</button>
                            <?php endif; ?>
                            <button class="dashboard-btn btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editLicenseModal"><?php echo $license ? 'Update License' : 'Upload License'; ?></button>
                        </div>
                    </div>
                    <div>
                        <?php if ($license): ?>
                            <p><strong>Number:</strong> <?php echo htmlspecialchars($license['license_number']); ?></p>
                            <p><strong>Expiry:</strong> <?php echo date('M d, Y', strtotime($license['expiry_date'])); ?></p>
                            <p><strong>Status:</strong> <span class="dashboard-badge <?php echo $license['status']==='approved'?'success':($license['status']==='pending'?'warning':'danger'); ?>"><?php echo ucfirst($license['status']); ?></span> <button class="dashboard-btn btn btn-info btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#viewLicenseModal"><i class="fas fa-eye"></i></button></p>
                        <?php else: ?>
                            <p>No license uploaded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="dashboard-section-title"><i class="fas fa-calendar-alt me-2"></i>Your Bookings</div>
                    <div>
                        <?php if ($bookings): ?>
                            <table class="dashboard-table">
                                <thead><tr><th>Car</th><th>Pickup</th><th>Return</th><th>Dates</th><th>Days</th><th>Price</th><th>Status</th><th></th></tr></thead>
                                <tbody><?php foreach ($bookings as $b): ?><tr>
                                    <td><?php echo htmlspecialchars($b['brand'].' '.$b['model']); ?></td>
                                    <td><?php echo htmlspecialchars($b['pickup_location']); ?></td>
                                    <td><?php echo htmlspecialchars($b['return_location']); ?></td>
                                    <td><?php echo date('M d',strtotime($b['start_date'])).' - '.date('M d, Y',strtotime($b['end_date'])); ?></td>
                                    <td><?php echo $b['total_days']; ?></td>
                                    <td>Rs. <?php echo number_format($b['total_price'],2); ?>/day</td>
                                    <td><span class="dashboard-badge <?php echo $b['status']==='confirmed'?'success':($b['status']==='pending'?'warning':'danger'); ?>"><?php echo ucfirst($b['status']); ?></span></td>
                                    <td><?php if ($b['status']==='pending'): ?><form method="POST" style="display:inline;"><input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>"><button type="submit" name="cancel_booking" class="dashboard-btn btn btn-sm btn-danger" onclick="return confirm('Cancel this booking?');"><i class="fas fa-times"></i></button></form><?php endif; ?></td>
                                </tr><?php endforeach; ?></tbody>
                            </table>
                        <?php else: ?>
                            <p>No bookings found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="dashboard-section-title"><i class="fas fa-star me-2"></i>Your Reviews</div>
                    <div>
                        <?php if ($user_reviews): ?>
                            <table class="dashboard-table">
                                <thead><tr><th>Car</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($user_reviews as $ur): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ur['brand'] . ' ' . $ur['model']); ?></td>
                                            <td><?php for ($i = 1; $i <= 5; $i++): ?><i class="<?php echo $i <= $ur['rating'] ? 'fas' : 'far'; ?> fa-star text-warning"></i><?php endfor; ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($ur['comment'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($ur['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No reviews submitted yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($recCars)): ?>
        <section class="py-5 bg-light recommended-section">
            <div class="container">
                <h4 class="mb-4" style="font-family: 'Poppins', sans-serif; font-weight:700;">Recommended For You</h4>
                <div class="row">
                    <?php foreach ($recCars as $r): ?>
                    <div class="col-md-3 mb-4 d-flex">
                        <div class="car-card w-100 d-flex flex-column">
                            <img src="assets/images/<?php echo htmlspecialchars($r['image'] ?: 'car-placeholder.png'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($r['brand'].' '.$r['model']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($r['brand'] . ' ' . $r['model']); ?></h5>
                                <p class="card-text">Rs. <?php echo number_format($r['price_per_day'],2); ?>/day</p>
                                <a href="book-car.php?id=<?php echo $r['id']; ?>" class="btn">Book Now</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5 class="modal-title">Edit Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required></div><div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div><div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" pattern="[0-9]{10}" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required
    oninvalid="this.setCustomValidity('The phone number must be exactly 10 digits.')"
    oninput="this.setCustomValidity('')"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="update_profile" class="btn btn-primary">Save</button></div></form></div></div></div>
    <!-- Edit License Modal -->
    <div class="modal fade" id="editLicenseModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" enctype="multipart/form-data"><div class="modal-header"><h5 class="modal-title"><?php echo $license?'Update License':'Upload License'; ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">License Number</label><input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($license['license_number'] ?? ''); ?>" required></div><div class="mb-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control" value="<?php echo htmlspecialchars($license['expiry_date'] ?? ''); ?>" required></div><div class="mb-3"><label class="form-label">Image (JPG/PNG/PDF)</label><input type="file" name="license_image" class="form-control"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="update_license" class="btn btn-primary">Save</button></div></form></div></div></div>
    <!-- View License Modal -->
    <div class="modal fade" id="viewLicenseModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">View License</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body text-center"><?php if ($license && $license['image']): ?><img src="assets/images/<?php echo htmlspecialchars($license['image']); ?>" class="img-fluid" alt="License"><?php else: ?><p>No image available.</p><?php endif; ?></div></div></div></div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set booking date inputs to start at tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.min = tomorrowStr;
        });
    </script>
    <script>
        // Auto-dismiss flash notifications after 5 seconds
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.alert-dismissible').forEach(alert => {
                setTimeout(() => {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                }, 5000);
            });
        });
    </script>
</body>
</html>