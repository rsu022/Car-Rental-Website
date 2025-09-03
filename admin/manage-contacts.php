<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle message deletion via POST
if(isset($_POST['delete_message'])) {
    $message_id = $_POST['message_id'];
    $stmt = $db->prepare("DELETE FROM messages WHERE id = :id");
    $stmt->execute([':id' => $message_id]);
    $_SESSION['success'] = "Message deleted successfully.";
    header("Location: manage-contacts.php");
    exit();
}

// Handle message deletion via GET
if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $msgId = (int) $_GET['id'];
    $delStmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    if($delStmt->execute([$msgId])) {
        $_SESSION['success'] = "Message deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete message.";
    }
    header("Location: manage-contacts.php");
    exit();
}

require_once 'includes/header.php';

// Get all contact messages (with masked email addresses)
$messages = $db->query("
    SELECT id, 
           name, 
           email,
           subject,
           message,
           status,
           created_at
    FROM messages 
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Contact Messages</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($message['name']); ?></td>
                            <td><?php echo htmlspecialchars($message['email']); ?></td>
                            <td><?php echo htmlspecialchars($message['subject']); ?></td>
                            <td><?php echo htmlspecialchars($message['message']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                            <td>
                                <a href="manage-contacts.php?action=delete&id=<?php echo $message['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this message?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>