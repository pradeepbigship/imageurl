<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['ids'])) {
    $ids = explode(',', $_GET['ids']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT filename, original_name, url_path FROM images 
                          WHERE id IN ($placeholders) AND user_id = ?");
    
    $params = array_merge($ids, [$_SESSION['user_id']]);
    $stmt->execute($params);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="exported_images_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Create CSV file
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, ['Original Name', 'Filename', 'URL']);
    
    // Add data
    foreach ($images as $image) {
        fputcsv($output, [
            $image['original_name'],
            $image['filename'],
            $image['url_path']
        ]);
    }
    
    fclose($output);
    exit;
}

header('Location: upload.php');
exit;