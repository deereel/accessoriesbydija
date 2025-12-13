<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../auth/check_session.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => 'Invalid request'];

try {
    switch ($method) {
        case 'GET':
            $response = getAddresses();
            break;
        case 'POST':
            $response = addAddress();
            break;
        case 'PUT':
            $response = updateAddress();
            break;
        case 'DELETE':
            $response = deleteAddress();
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);

function getAddresses() {
    global $pdo;

    $userId = getCurrentUserId();
    if (!$userId) {
        return ['success' => false, 'message' => 'User not logged in'];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT address_id, address_name, full_name, phone, street_address, city, state, country, postal_code, is_default
            FROM user_addresses
            WHERE user_id = ?
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$userId]);
        $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'addresses' => $addresses];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to load addresses: ' . $e->getMessage()];
    }
}

function addAddress() {
    global $pdo;

    $userId = getCurrentUserId();
    if (!$userId) {
        return ['success' => false, 'message' => 'User not logged in'];
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        return ['success' => false, 'message' => 'Invalid data'];
    }

    $addressName = $data['address_name'] ?? '';
    $fullName = $data['full_name'] ?? '';
    $phone = $data['phone'] ?? '';
    $streetAddress = $data['street_address'] ?? '';
    $city = $data['city'] ?? '';
    $state = $data['state'] ?? '';
    $country = $data['country'] ?? 'Nigeria';
    $postalCode = $data['postal_code'] ?? '';
    $isDefault = $data['is_default'] ?? false;

    if (empty($addressName) || empty($fullName) || empty($streetAddress) || empty($city) || empty($state)) {
        return ['success' => false, 'message' => 'Required fields are missing'];
    }

    try {
        // If this is set as default, unset other defaults
        if ($isDefault) {
            $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = FALSE WHERE user_id = ?");
            $stmt->execute([$userId]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO user_addresses (user_id, address_name, full_name, phone, street_address, city, state, country, postal_code, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $addressName, $fullName, $phone, $streetAddress, $city, $state, $country, $postalCode, $isDefault]);

        return ['success' => true, 'message' => 'Address added successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to add address: ' . $e->getMessage()];
    }
}

function updateAddress() {
    global $pdo;

    $userId = getCurrentUserId();
    if (!$userId) {
        return ['success' => false, 'message' => 'User not logged in'];
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        return ['success' => false, 'message' => 'Invalid data'];
    }

    $addressId = $data['address_id'] ?? 0;
    $addressName = $data['address_name'] ?? '';
    $fullName = $data['full_name'] ?? '';
    $phone = $data['phone'] ?? '';
    $streetAddress = $data['street_address'] ?? '';
    $city = $data['city'] ?? '';
    $state = $data['state'] ?? '';
    $country = $data['country'] ?? 'Nigeria';
    $postalCode = $data['postal_code'] ?? '';
    $isDefault = $data['is_default'] ?? false;

    if (!$addressId || empty($addressName) || empty($fullName) || empty($streetAddress) || empty($city) || empty($state)) {
        return ['success' => false, 'message' => 'Required fields are missing'];
    }

    try {
        // If this is set as default, unset other defaults
        if ($isDefault) {
            $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = FALSE WHERE user_id = ? AND address_id != ?");
            $stmt->execute([$userId, $addressId]);
        }

        $stmt = $pdo->prepare("
            UPDATE user_addresses SET
                address_name = ?, full_name = ?, phone = ?, street_address = ?,
                city = ?, state = ?, country = ?, postal_code = ?, is_default = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE address_id = ? AND user_id = ?
        ");
        $stmt->execute([$addressName, $fullName, $phone, $streetAddress, $city, $state, $country, $postalCode, $isDefault, $addressId, $userId]);

        return ['success' => true, 'message' => 'Address updated successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to update address: ' . $e->getMessage()];
    }
}

function deleteAddress() {
    global $pdo;

    $userId = getCurrentUserId();
    if (!$userId) {
        return ['success' => false, 'message' => 'User not logged in'];
    }

    $addressId = $_GET['address_id'] ?? 0;

    if (!$addressId) {
        return ['success' => false, 'message' => 'Address ID is required'];
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);

        return ['success' => true, 'message' => 'Address deleted successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to delete address: ' . $e->getMessage()];
    }
}

function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}
?>
