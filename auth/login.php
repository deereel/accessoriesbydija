<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $remember_me = $data['remember_me'] ?? false;
    
    // Validation
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }
    
    try {
        // Get customer by email
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password_hash, is_active FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit;
        }
        
        // Check if account is active
        if (!$customer['is_active']) {
            echo json_encode(['success' => false, 'message' => 'Account is deactivated']);
            exit;
        }
        
        // Verify password
        if (!password_verify($password, $customer['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit;
        }
        
        // Set session
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
        $_SESSION['customer_email'] = $customer['email'];

        // Determine redirect if provided by client (validate to prevent open redirects)
        $redirect = null;
        if (!empty($data['redirect'])) {
            $candidate = trim($data['redirect']);
            // Only allow relative paths within site (no scheme or host)
            if (strpos($candidate, 'http://') === false && strpos($candidate, 'https://') === false && strpos($candidate, '//') === false) {
                // Basic normalization: strip leading slashes
                $redirect = ltrim($candidate, '/');
            }
        }

        // If there is a session cart, migrate it into DB
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
            foreach ($_SESSION['cart'] as $item) {
                $pid = isset($item['product_id']) ? (int)$item['product_id'] : 0;
                $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                if (!$pid) continue;

                // Check existing cart row
                $cstmt = $pdo->prepare('SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?');
                $cstmt->execute([$customer['id'], $pid]);
                $crow = $cstmt->fetch(PDO::FETCH_ASSOC);
                if ($crow) {
                    $newQty = $crow['quantity'] + $qty;
                    $upd = $pdo->prepare('UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?');
                    $upd->execute([$newQty, $crow['id']]);
                } else {
                    $ins = $pdo->prepare('INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)');
                    $ins->execute([$customer['id'], $pid, $qty]);
                }
            }
            // Clear session cart after migrating
            unset($_SESSION['cart']);
        }
        
        // Set remember me cookie if requested
        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
            
            // Store token in database (you may want to create a remember_tokens table)
            $stmt = $pdo->prepare("UPDATE customers SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $customer['id']]);
        }
        
        $resp = [
            'success' => true,
            'message' => 'Login successful',
            'customer' => [
                'id' => $customer['id'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name'],
                'email' => $customer['email']
            ]
        ];
        if ($redirect) $resp['redirect'] = $redirect;
        echo json_encode($resp);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>