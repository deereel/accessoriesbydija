<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting: 5 requests per minute per IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'rate_limit_signup_' . md5($ip);
    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = ['count' => 0, 'reset_time' => time() + 60];
    }
    $rate = &$_SESSION[$rate_key];
    if (time() > $rate['reset_time']) {
        $rate['count'] = 0;
        $rate['reset_time'] = time() + 60;
    }
    if ($rate['count'] >= 5) {
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
        exit;
    }
    $rate['count']++;

    $data = json_decode(file_get_contents('php://input'), true);
    
    $first_name = trim($data['first_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $phone = trim($data['phone'] ?? '');
    $security_question_pair_id = $data['security_question_pair_id'] ?? null;
    $security_answer_1 = trim($data['security_answer_1'] ?? '');
    $security_answer_2 = trim($data['security_answer_2'] ?? '');

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($security_question_pair_id) || empty($security_answer_1) || empty($security_answer_2)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password must contain at least one uppercase letter']);
        exit;
    }
    if (!preg_match('/[a-z]/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password must contain at least one lowercase letter']);
        exit;
    }
    if (!preg_match('/[0-9]/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password must contain at least one number']);
        exit;
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        }
        
        // Hash password and security answers
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $security_answer_1_hash = password_hash($security_answer_1, PASSWORD_DEFAULT);
        $security_answer_2_hash = password_hash($security_answer_2, PASSWORD_DEFAULT);
        
        // Insert new customer
        $stmt = $pdo->prepare(
            "INSERT INTO customers (first_name, last_name, email, phone, password_hash, security_question_pair_id, security_answer_1_hash, security_answer_2_hash) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $first_name, 
            $last_name, 
            $email, 
            $phone, 
            $password_hash, 
            $security_question_pair_id, 
            $security_answer_1_hash, 
            $security_answer_2_hash
        ]);
        
        $customer_id = $pdo->lastInsertId();
        
        // Set session
        $_SESSION['customer_id'] = $customer_id;
        $_SESSION['customer_name'] = $first_name . ' ' . $last_name;
        $_SESSION['customer_email'] = $email;

        // Send welcome email (best-effort)
        $customer_name = $first_name . ' ' . $last_name;
        error_log("Attempting to send welcome email to $email for customer $customer_name");
        try {
            send_welcome_email($email, $customer_name);
        } catch (Exception $e) {
            // Log but don't fail signup if email fails
            error_log('Failed to send welcome email to ' . $email . ': ' . $e->getMessage());
        }

        $redirect = 'account.php';
        if (!empty($data['redirect'])) {
            $candidate = trim($data['redirect']);
            if (strpos($candidate, 'http') !== 0 && strpos($candidate, '//') !== 0) {
                $redirect = ltrim($candidate, '/');
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully',
            'redirect' => $redirect
        ]);
        
    } catch (PDOException $e) {
        // Log error properly in a real application
        echo json_encode(['success' => false, 'message' => 'An internal error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>