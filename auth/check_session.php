<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['customer_id'])) {
    echo json_encode([
        'logged_in' => true,
        'customer' => [
            'id' => $_SESSION['customer_id'],
            'name' => $_SESSION['customer_name'],
            'email' => $_SESSION['customer_email']
        ]
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>