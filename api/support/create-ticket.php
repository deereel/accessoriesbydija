<?php
/**
 * Create Support Ticket API
 * POST /api/support/create-ticket.php
 * 
 * Creates a new support ticket
 * Can be from authenticated customers or guests
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../app/config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['email', 'name', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    $customer_id = $_SESSION['customer_id'] ?? null;
    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    $name = htmlspecialchars(trim($data['name']));
    $subject = htmlspecialchars(trim($data['subject']));
    $message = htmlspecialchars(trim($data['message']));
    $category = $data['category'] ?? 'general';
    $priority = in_array($data['priority'] ?? 'medium', ['low', 'medium', 'high']) ? $data['priority'] : 'medium';
    
    // Clean category
    $allowed_categories = ['password-reset', 'product-issue', 'order-issue', 'payment-issue', 'general', 'other'];
    if (!in_array($category, $allowed_categories)) {
        $category = 'general';
    }
    
    // Validate message length
    if (strlen($message) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message must be at least 10 characters long']);
        exit;
    }
    
    if (strlen($message) > 5000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message cannot exceed 5000 characters']);
        exit;
    }
    
    // Insert ticket
    $stmt = $pdo->prepare("INSERT INTO support_tickets (customer_id, customer_email, customer_name, subject, message, category, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $customer_id,
        $email,
        $name,
        $subject,
        $message,
        $category,
        $priority
    ]);
    
    if ($result) {
        $ticket_id = $pdo->lastInsertId();
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Support ticket created successfully. We will respond shortly.',
            'ticket_id' => $ticket_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create support ticket']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Support ticket creation error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the ticket'
    ]);
}
?>
