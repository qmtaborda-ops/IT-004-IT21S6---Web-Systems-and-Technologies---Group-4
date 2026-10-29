<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();
include 'connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$book_id = $data['book_id'] ?? null;
$quantity_requested = (int)($data['quantity'] ?? 1);
$borrow_date = $data['borrow_date'] ?? '';
$return_date = $data['return_date'] ?? '';

if (!$book_id || empty($borrow_date) || empty($return_date) || $quantity_requested < 1) {
    echo json_encode(["success" => false, "error" => "Missing book, quantity, or date information."]);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "error" => "You must be logged in to borrow."]);
    exit;
}

if (!$conn->begin_transaction()) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Could not start the borrowing transaction."]);
    exit;
}

$check_stmt = $conn->prepare("SELECT quantity FROM books WHERE id = ? FOR UPDATE");
if (!$check_stmt) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Could not check the book inventory."]);
    exit;
}
$check_stmt->bind_param("i", $book_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Book not found."]);
    exit;
}

if ((int)$book['quantity'] < $quantity_requested) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Not enough copies available."]);
    exit;
}
$check_stmt->close();

$active_stmt = $conn->prepare(
    "SELECT COALESCE(SUM(quantity), 0) AS active_quantity,
            COALESCE(SUM(book_id = ?), 0) AS same_book
     FROM borrow_requests
     WHERE user_id = ? AND status IN ('Pending', 'Approved')"
);
if (!$active_stmt) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Could not check your active borrowings."]);
    exit;
}
$active_stmt->bind_param("ii", $book_id, $user_id);
$active_stmt->execute();
$active = $active_stmt->get_result()->fetch_assoc();
$active_stmt->close();

if ((int)$active['same_book'] > 0) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "You already have an active request for this book. Return it before borrowing it again."]);
    exit;
}

if ((int)$active['active_quantity'] + $quantity_requested > 3) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "You can borrow a maximum of 3 books at a time. Return a book before borrowing another."]);
    exit;
}

$update_stmt = $conn->prepare("UPDATE books SET quantity = quantity - ?, status = CASE WHEN quantity - ? > 0 THEN 'Available' ELSE 'Out of Stock' END WHERE id = ? AND quantity >= ?");
if (!$update_stmt) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Could not reserve the book."]);
    exit;
}
$update_stmt->bind_param("iiii", $quantity_requested, $quantity_requested, $book_id, $quantity_requested);
$update_stmt->execute();
if ($update_stmt->affected_rows !== 1) {
    $update_stmt->close();
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Not enough copies available."]);
    exit;
}
$update_stmt->close();

$stmt = $conn->prepare("INSERT INTO borrow_requests (book_id, user_id, quantity, borrow_date, return_date, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
if (!$stmt) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Could not create the borrow request."]);
    exit;
}
$stmt->bind_param("iiiss", $book_id, $user_id, $quantity_requested, $borrow_date, $return_date);

if ($stmt->execute()) {
    $conn->commit();
    echo json_encode(["success" => true]);
} else {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
