<?php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin login required.']);
    exit;
}

require_once __DIR__ . '/connection.php';
$result = $conn->query(
    "SELECT p.id, p.amount, p.method, p.gcash_reference, p.card_last4, p.notes, p.status,
            p.created_at, CONCAT(u.first_name, ' ', u.last_name) AS user_name, u.email
     FROM payment_requests p
     JOIN users u ON u.id = p.user_id
     ORDER BY p.id DESC"
);

$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}
echo json_encode($payments);
