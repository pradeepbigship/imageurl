<?php
session_start();
require_once 'config/db.php';

// Get total images count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM images");
$totalImages = $stmt->fetch()['total'];

// Get recent uploads
$stmt = $pdo->query("SELECT i.*, u.username FROM images i 
                     JOIN users u ON i.user_id = u.id 
                     ORDER BY i.upload_date DESC LIMIT 6");
$recentImages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Image Upload Platform - Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .nav {
            margin-bottom: 20px;
            text-align: right;
        }
        .nav a {
            margin-left: 15px;
            text-decoration: none;
            color: #007bff;
        }
        .stats {
            text-align: center;
            margin-bottom: 30px;
        }
        .recent-uploads {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .image-card {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .image-card img {
            max-width: 100%;
            height: auto;
        }
        .welcome {
            text-align: center;
            margin: 40px 0;
        }
    </style>
</head>
<body>
    <div class="nav">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="upload.php">Upload Images</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>

    <div class="header">
        <h1>Welcome to Image Upload Platform</h1>
        <p>Share your images securely with the world</p>
    </div>

    <div class="stats">
        <h3>Platform Statistics</h3>
        <p>Total Images Shared: <?php echo $totalImages; ?></p>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="welcome">
            <h2>Get Started Today!</h2>
            <p>Create an account to start uploading and sharing your images.</p>
            <a href="register.php" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Sign Up Now</a>
        </div>
    <?php endif; ?>

    <h2>Recent Uploads</h2>
    <div class="recent-uploads">
        <?php foreach ($recentImages as $image): ?>
            <div class="image-card">
                <img src="<?php echo htmlspecialchars($image['url_path']); ?>" alt="Uploaded image">
                <p>Uploaded by: <?php echo htmlspecialchars($image['username']); ?></p>
                <p>Date: <?php echo date('M d, Y', strtotime($image['upload_date'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>