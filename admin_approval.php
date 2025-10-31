<?php
session_start();
require_once 'config/db.php';

// Check if user is admin (you should implement proper admin authentication)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) { // Assuming user_id 1 is admin
    header('Location: login.php');
    exit;
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET approved = 1 WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif ($action === 'reject') {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // First delete user's images from the filesystem
            $stmt = $pdo->prepare("SELECT filename FROM images WHERE user_id = ?");
            $stmt->execute([$userId]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($images as $filename) {
                $filePath = 'uploads/' . $filename;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            // Delete user's images from database
            $stmt = $pdo->prepare("DELETE FROM images WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Then delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND approved = 0");
            $stmt->execute([$userId]);
            
            // Commit transaction
            $pdo->commit();
        } catch (Exception $e) {
            // Rollback on error
            $pdo->rollBack();
            error_log("Error rejecting user: " . $e->getMessage());
        }
    }
}

// Get pending users
$stmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE approved = 0");
$stmt->execute();
$pendingUsers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Approval - Admin Panel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .user-list {
            margin-top: 20px;
        }
        .user-item {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .approve-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .reject-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <h1>User Approval Panel</h1>
    
    <div class="user-list">
        <?php if (empty($pendingUsers)): ?>
            <p>No pending approvals</p>
        <?php else: ?>
            <?php foreach ($pendingUsers as $user): ?>
                <div class="user-item">
                    <div>
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        <br>
                        <small>Registered: <?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></small>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                            <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>