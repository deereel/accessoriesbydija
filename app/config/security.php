<?php
// Security Configuration
require_once 'environment.php';

// CSRF Token Generation and Validation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input Sanitization
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Rate Limiting (Simple file-based)
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 300) {
    $file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
    $current_time = time();
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($current_time - $data['first_attempt'] < $time_window) {
            if ($data['attempts'] >= $max_attempts) {
                return false;
            }
            $data['attempts']++;
        } else {
            $data = ['first_attempt' => $current_time, 'attempts' => 1];
        }
    } else {
        $data = ['first_attempt' => $current_time, 'attempts' => 1];
    }
    
    file_put_contents($file, json_encode($data));
    return true;
}

// Security Headers
function setSecurityHeaders() {
    global $environment;

    // Basic security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions Policy (formerly Feature Policy)
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

    // Environment-specific headers
    if ($environment === 'live') {
        // Production-only strict headers
        header('Cross-Origin-Embedder-Policy: require-corp');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');

        // Certificate Transparency enforcement
        header('Expect-CT: max-age=86400, enforce');
    }

    // Content Security Policy - Environment-aware
    $csp = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com fonts.googleapis.com cdn.tailwindcss.com cdn.jsdelivr.net js.stripe.com; ";
    $csp .= "style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com fonts.googleapis.com; ";
    $csp .= "font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com data:; ";
    $csp .= "img-src 'self' data: https:; ";
    $csp .= "connect-src 'self' api.exchangerate-api.com cdnjs.cloudflare.com js.stripe.com api.stripe.com; ";
    $csp .= "frame-src 'self' js.stripe.com; ";
    $csp .= "object-src 'none'; ";
    $csp .= "base-uri 'self'; ";
    $csp .= "form-action 'self'; ";

    // Only add upgrade-insecure-requests in production
    if ($environment === 'live') {
        $csp .= "upgrade-insecure-requests;";
    }

    header('Content-Security-Policy: ' . $csp);
}

// Call security headers on every page load
setSecurityHeaders();
?>