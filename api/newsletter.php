<?php

// Newsletter subscription API
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

if ($method !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    json_response(['success' => false, 'message' => 'Email is required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email address'], 400);
}

try {
    // Check if email already exists and is active
    $stmt = $pdo->prepare("SELECT id, is_active FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['is_active']) {
            json_response(['success' => false, 'message' => 'Email already subscribed'], 409);
        } else {
            // Reactivate subscription
            $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET is_active = TRUE, subscribed_at = NOW(), unsubscribed_at = NULL WHERE email = ?");
            $result = $stmt->execute([$email]);

            if ($result) {
                json_response(['success' => true, 'message' => 'Successfully resubscribed to newsletter']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to reactivate subscription'], 500);
            }
        }
    }

    // Insert new subscriber
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, is_active, subscribed_at) VALUES (?, TRUE, NOW())");
    $result = $stmt->execute([$email]);

    if ($result) {
        json_response(['success' => true, 'message' => 'Successfully subscribed to newsletter']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to save subscription'], 500);
    }

} catch (PDOException $e) {
    error_log('Newsletter subscription error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()], 500);
}
?>