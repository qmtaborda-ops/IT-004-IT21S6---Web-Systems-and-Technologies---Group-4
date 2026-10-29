<?php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin login required.']);
    exit;
}

require_once __DIR__ . '/connection.php';
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$id = (int)($data['payment_id'] ?? 0);
$status = $data['status'] ?? '';

if ($id <= 0 || !in_array($status, ['Paid', 'Rejected'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payment update.']);
    exit;
}

$statement = $conn->prepare(
    "UPDATE payment_requests
     SET status = ?, verified_at = CURRENT_TIMESTAMP
     WHERE id = ? AND status = 'Pending'"
);
$statement->bind_param('si', $status, $id);
if (!$statement->execute() || $statement->affected_rows !== 1) {
    echo json_encode(['success' => false, 'error' => 'Payment request not found or already processed.']);
    exit;
}
echo json_encode(['success' => true]);
