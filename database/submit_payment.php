<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();
require_once __DIR__ . '/connection.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in to submit a payment.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$method = $data['method'] ?? '';
$gcashReference = trim($data['gcash_reference'] ?? '');
$cardReference = trim($data['card_reference'] ?? '');
$notes = trim($data['notes'] ?? '');

if (!in_array($method, ['cash', 'gcash', 'card'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Choose a valid payment method.']);
    exit;
}

if ($method === 'gcash' && !preg_match('/^\d{13}$/', $gcashReference)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'GCash reference number must contain exactly 13 digits.']);
    exit;
}

if ($method === 'card' && !preg_match('/^\d{16}$/', $cardReference)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Card payment reference must contain exactly 16 digits.']);
    exit;
}
$cardLast4 = $method === 'card' ? substr($cardReference, -4) : null;

$statement = $conn->prepare(
    "SELECT br.id, br.quantity, br.return_date, br.status, br.item_condition, b.price
     FROM borrow_requests br
     JOIN books b ON b.id = br.book_id
     WHERE br.user_id = ?"
);
$statement->bind_param('i', $userId);
$statement->execute();
$result = $statement->get_result();
$today = new DateTimeImmutable('today');
$total = 0.0;

while ($record = $result->fetch_assoc()) {
    $price = max(0.0, (float)$record['price']);
    $quantity = max(1, (int)$record['quantity']);
    $itemCondition = strtolower((string)$record['item_condition']);

    if ($itemCondition === 'damaged' || $itemCondition === 'missing') {
        $total += $price * $quantity;
        continue;
    }

    if ($record['status'] === 'Returned') {
        continue;
    }

    try {
        $dueDate = new DateTimeImmutable($record['return_date']);
    } catch (Exception $exception) {
        continue;
    }

    if ($today > $dueDate) {
        $overdueDays = (int)$dueDate->diff($today)->days;
        $total += min($price * $quantity, $price * $quantity * 0.01 * $overdueDays);
    }
}
$statement->close();

$paidStatement = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS paid
     FROM payment_requests
     WHERE user_id = ? AND status = 'Paid'"
);
$paidStatement->bind_param('i', $userId);
$paidStatement->execute();
$paid = (float)$paidStatement->get_result()->fetch_assoc()['paid'];
$paidStatement->close();

$amountDue = max(0.0, round($total - $paid, 2));
if ($amountDue <= 0) {
    echo json_encode(['success' => false, 'error' => 'You have no outstanding liability to pay.']);
    exit;
}

$insert = $conn->prepare(
    "INSERT INTO payment_requests (user_id, amount, method, gcash_reference, card_last4, notes, status)
     VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
);
$insert->bind_param('idssss', $userId, $amountDue, $method, $gcashReference, $cardLast4, $notes);

if (!$insert->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not submit the payment request.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Payment request submitted for librarian verification.',
    'amount' => number_format($amountDue, 2, '.', '')
]);
$insert->close();
$conn->close();
