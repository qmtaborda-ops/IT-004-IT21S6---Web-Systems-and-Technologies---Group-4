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

$check_stmt = $conn->prepare("SELECT status, quantity FROM books WHERE id = ?");
$check_stmt->bind_param("i", $book_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    echo json_encode(["success" => false, "error" => "Book not found."]);
    exit;
}

if ($book['status'] !== 'Available' || $book['quantity'] < $quantity_requested) {
    echo json_encode(["success" => false, "error" => "Not enough copies available."]);
    exit;
}
$check_stmt->close();

$stmt = $conn->prepare("INSERT INTO borrow_requests (book_id, user_id, quantity, borrow_date, return_date, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
$stmt->bind_param("iiiss", $book_id, $user_id, $quantity_requested, $borrow_date, $return_date);

if ($stmt->execute()) {
    $new_qty = $book['quantity'] - $quantity_requested;
    $new_status = ($new_qty > 0) ? 'Available' : 'In Process';
    
    $update_stmt = $conn->prepare("UPDATE books SET quantity=?, status=? WHERE id=?");
    $update_stmt->bind_param("isi", $new_qty, $new_status, $book_id);
    $update_stmt->execute();
    $update_stmt->close();

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>