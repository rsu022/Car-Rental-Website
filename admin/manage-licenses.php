<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['license_id']) && isset($_POST['action'])) {
    $license_id = $_POST['license_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $query = "UPDATE driver_licenses SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':status' => $status,
        ':id' => $license_id
    ]);
    
    header("Location: manage-licenses.php");
    exit();
}

// Get all license applications with user details
$query = "SELECT dl.*, u.name as user_name, u.email as user_email 
          FROM driver_licenses dl 
          JOIN users u ON dl.user_id = u.id 
          ORDER BY dl.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>
<style>
    .badge {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }
    .badge i {
        font-size: 0.8rem;
    }
    .img-thumbnail {
        transition: transform 0.2s;
    }
    .img-thumbnail:hover {
        transform: scale(1.1);
        cursor: pointer;
    }
    .modal-body img {
        max-height: 80vh;
        width: auto;
    }
</style>
<?php
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Driver's License Applications</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>License Number</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($licenses as $license): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($license['user_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($license['user_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($license['license_number']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($license['expiry_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $license['status'] === 'approved' ? 'success' : ($license['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($license['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($license['created_at'])); ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#licenseModal<?php echo $license['id']; ?>">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- License Modals -->
<?php foreach($licenses as $license): ?>
<div class="modal fade" id="licenseModal<?php echo $license['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Driver's License Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <?php if($license['image']): ?>
                            <div class="text-center mb-3">
                                <img src="../assets/images/<?php echo htmlspecialchars($license['image']); ?>" 
                                     alt="License" class="img-fluid rounded shadow-sm" style="max-height: 400px;">
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">No license image uploaded</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title mb-3">License Information</h6>
                                <div class="mb-3">
                                    <p class="mb-1"><strong>User:</strong> <?php echo htmlspecialchars($license['user_name']); ?></p>
                                    <p class="mb-1"><small class="text-muted"><?php echo htmlspecialchars($license['user_email']); ?></small></p>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-1"><strong>License Number:</strong></p>
                                    <p class="mb-1"><?php echo htmlspecialchars($license['license_number']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-1"><strong>Expiry Date:</strong></p>
                                    <p class="mb-1"><?php echo date('M d, Y', strtotime($license['expiry_date'])); ?></p>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-1"><strong>Status:</strong></p>
                                    <span class="badge bg-<?php echo $license['status'] === 'approved' ? 'success' : ($license['status'] === 'pending' ? 'warning' : 'danger'); ?> px-3 py-2">
                                        <?php echo ucfirst($license['status']); ?>
                                    </span>
                                </div>
                                
                                <?php if($license['status'] === 'pending'): ?>
                                    <hr>
                                    <div class="mt-3">
                                        <form method="POST">
                                            <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success w-100 mb-2">
                                                <i class="fas fa-check"></i> Approve License
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger w-100">
                                                <i class="fas fa-times"></i> Decline License
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>
