<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../app/config/database.php';

if (isset($_POST['image_id'])) {
    try {
        $image_id = (int)$_POST['image_id'];

        // Get image URL to delete the file
        $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            // Delete file from server
            if (file_exists('../' . $image['image_url'])) {
                unlink('../' . $image['image_url']);
            }

            // Delete from database
            $delete_stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
            $delete_stmt->execute([$image_id]);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Image not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request. Image ID is required.']);
}
?>
