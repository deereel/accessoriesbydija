<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting: 10 requests per minute per IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'rate_limit_login_' . md5($ip);
    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = ['count' => 0, 'reset_time' => time() + 60];
    }
    $rate = &$_SESSION[$rate_key];
    if (time() > $rate['reset_time']) {
        $rate['count'] = 0;
        $rate['reset_time'] = time() + 60;
    }
    if ($rate['count'] >= 10) {
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
        exit;
    }
    $rate['count']++;

    $data = json_decode(file_get_contents('php://input'), true);

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $remember_me = $data['remember_me'] ?? false;

    // Brute force protection
    $attempt_key = 'login_attempts_' . md5($email);
    if (!isset($_SESSION[$attempt_key])) {
        $_SESSION[$attempt_key] = ['count' => 0, 'last_attempt' => 0];
    }
    $attempts = &$_SESSION[$attempt_key];
    $current_time = time();
    if ($attempts['count'] >= 5 && $current_time - $attempts['last_attempt'] < 900) { // 15 minutes
        echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please try again later.']);
        exit;
    }
    if ($current_time - $attempts['last_attempt'] > 900) {
        $attempts['count'] = 0; // Reset after 15 min
    }

    // Validation
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }
    
    try {
        // Get customer by email
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password_hash, is_active, created_at FROM customers WHERE email = ?");
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
            $attempts['count']++;
            $attempts['last_attempt'] = $current_time;
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit;
        }

        // Check if user is new (created in the last 5 minutes)
        if (isset($customer['created_at']) && strtotime($customer['created_at']) > (time() - 300)) {
            $_SESSION['show_newsletter_popup'] = true;
        }
        
        // Check if admin-forced password reset is required (column may not exist)
        try {
            $fr = $pdo->prepare("SELECT force_password_reset FROM customers WHERE id = ?");
            $fr->execute([$customer['id']]);
            $frRow = $fr->fetch(PDO::FETCH_ASSOC);
            if ($frRow && intval($frRow['force_password_reset']) === 1) {
                echo json_encode(['success' => false, 'message' => 'Your account requires a password reset. Please reset your password to continue.', 'force_reset' => true]);
                exit;
            }
        } catch (PDOException $e) {
            // Column doesn't exist or other error — ignore to preserve backward compatibility
        }

        // Reset failed attempts on successful login
        unset($_SESSION[$attempt_key]);

        // Set session
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
        $_SESSION['customer_email'] = $customer['email'];

        // Update last login
        try {
            $stmt = $pdo->prepare("UPDATE customers SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$customer['id']]);
        } catch (PDOException $e) {
            // Column might not exist - ignore
        }

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