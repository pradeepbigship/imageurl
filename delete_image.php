<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    if (isset($data['ids']) && is_array($data['ids'])) {
        // Multiple delete
        $ids = array_map('intval', $data['ids']);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        // Get files to delete
        $stmt = $pdo->prepare("SELECT filename FROM images WHERE id IN ($placeholders) AND user_id = ?");
        $params = array_merge($ids, [$_SESSION['user_id']]);
        $stmt->execute($params);
        $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Delete from database
        $deleteStmt = $pdo->prepare("DELETE FROM images WHERE id IN ($placeholders) AND user_id = ?");
        $deleteResult = $deleteStmt->execute($params);

        if ($deleteResult) {
            // Delete physical files
            foreach ($files as $filename) {
                $filePath = 'uploads/' . $filename;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete records']);
        }
    } else {
        // Single delete (existing code)
        $imageId = isset($data['id']) ? (int)$data['id'] : 0;
        // Get image details first
        $stmt = $pdo->prepare("SELECT filename, url_path FROM images WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$imageId, $_SESSION['user_id']]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            // Delete from database first
            $deleteStmt = $pdo->prepare("DELETE FROM images WHERE id = ? AND user_id = ?");
            if ($deleteStmt->execute([$imageId, $_SESSION['user_id']])) {
                // Then delete physical file
                $filePath = 'uploads/' . $image['filename'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete record']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Image not found']);
        }
    }
} catch (PDOException $e) {
    error_log("Delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>