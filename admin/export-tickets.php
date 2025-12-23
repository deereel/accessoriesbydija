<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Unauthorized');
}
require_once __DIR__ . '/../config/database.php';

// Build query with filters
$where = ['1=1'];
$params = [];

if (!empty($_GET['status'])) {
    $where[] = "status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['priority'])) {
    $where[] = "priority = ?";
    $params[] = $_GET['priority'];
}
if (!empty($_GET['category'])) {
    $where[] = "category = ?";
    $params[] = $_GET['category'];
}
if (!empty($_GET['assigned'])) {
    $assigned_id = (int)$_GET['assigned'];
    if ($assigned_id === 0) {
        $where[] = "assigned_to IS NULL";
    } else {
        $where[] = "assigned_to = ?";
        $params[] = $assigned_id;
    }
}

$sql = "SELECT t.id, t.customer_name, t.customer_email, t.subject, t.category, t.priority, t.status, t.created_at, t.response_date, u.username as assigned_username FROM support_tickets t LEFT JOIN admin_users u ON t.assigned_to = u.id WHERE " . implode(' AND ', $where) . " ORDER BY t.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tickets = [];
}

// Generate CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="support-tickets-' . date('Y-m-d-His') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Customer Name', 'Email', 'Subject', 'Category', 'Priority', 'Status', 'Assigned To', 'Created At', 'Response Date']);

foreach ($tickets as $t) {
    fputcsv($output, [
        $t['id'],
        $t['customer_name'],
        $t['customer_email'],
        $t['subject'],
        $t['category'],
        $t['priority'],
        $t['status'],
        $t['assigned_username'] ?? 'Unassigned',
        $t['created_at'],
        $t['response_date'] ?? ''
    ]);
}

fclose($output);
exit;
?>
