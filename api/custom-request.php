<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/includes/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Validate required fields
$required = ['jewelry_type', 'budget', 'description', 'name', 'email'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $field]);
        exit;
    }
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    // Save to database (create custom_requests table if needed)
    $stmt = $pdo->prepare("INSERT INTO custom_requests
        (jewelry_type, occasion, budget_range, metals, stones, description, timeline, name, email, phone, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->execute([
        $data['jewelry_type'],
        $data['occasion'] ?? null,
        $data['budget'],
        isset($data['metals']) ? json_encode($data['metals']) : null,
        isset($data['adornments']) ? json_encode($data['adornments']) : null,
        $data['description'],
        $data['timeline'] ?? null,
        $data['name'],
        $data['email'],
        $data['phone'] ?? null
    ]);

    // Send notification email to admin
    $admin_email = $_ENV['ADMIN_EMAIL'] ?? 'admin@accessoriesbydija.uk';
    $subject = "New Custom Jewelry Request from " . htmlspecialchars($data['name']);
    $body = "A new custom jewelry request has been submitted:\n\n";
    $body .= "Name: " . htmlspecialchars($data['name']) . "\n";
    $body .= "Email: " . htmlspecialchars($data['email']) . "\n";
    $body .= "Phone: " . htmlspecialchars($data['phone'] ?? 'Not provided') . "\n\n";
    $body .= "Jewelry Type: " . htmlspecialchars($data['jewelry_type']) . "\n";
    $body .= "Occasion: " . htmlspecialchars($data['occasion'] ?? 'Not specified') . "\n";
    $body .= "Budget: " . htmlspecialchars($data['budget']) . "\n";
    $body .= "Timeline: " . htmlspecialchars($data['timeline'] ?? 'Not specified') . "\n\n";
    $body .= "Metals: " . (isset($data['metals']) ? implode(', ', $data['metals']) : 'Not specified') . "\n";
    $body .= "Adornments: " . (isset($data['adornments']) ? implode(', ', $data['adornments']) : 'Not specified') . "\n\n";
    $body .= "Description:\n" . htmlspecialchars($data['description']) . "\n\n";
    $body .= "Please contact the customer to discuss their custom design.";

    send_email_smtp($admin_email, $subject, $body);

    echo json_encode(['success' => true, 'message' => 'Custom design request submitted successfully! We will contact you within 24 hours.']);

} catch (PDOException $e) {
    error_log('Custom request error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>