<?php
// Allow cross-origin requests if needed (adjust for your production environment)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Start the session to get the user_id
session_start(); 
include 'connection.php';

// Get the JSON payload from the request
$data = json_decode(file_get_contents("php://input"), true);

$book_id = $data['book_id'] ?? null;
$borrow_date = $data['borrow_date'] ?? '';
$return_date = $data['return_date'] ?? '';

// 1. Validate inputs
if (!$book_id || empty($borrow_date) || empty($return_date)) {
    echo json_encode(["success" => false, "error" => "Missing book or date information."]);
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'] ?? null; 

// 2. Prevent borrowing if not logged in
if (!$user_id) {
    echo json_encode(["success" => false, "error" => "You must be logged in to borrow."]);
    exit;
}

// 3. Check if the book is actually available (prevents double borrowing)
$check_stmt = $conn->prepare("SELECT status FROM books WHERE id = ?");
$check_stmt->bind_param("i", $book_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    echo json_encode(["success" => false, "error" => "Book not found."]);
    exit;
}

if ($book['status'] !== 'Available') {
    echo json_encode(["success" => false, "error" => "This book is currently not available."]);
    exit;
}
$check_stmt->close();

// 4. Insert the borrow request using Prepared Statements (Prevents SQL Injection)
$stmt = $conn->prepare("INSERT INTO borrow_requests (book_id, user_id, borrow_date, return_date, status) VALUES (?, ?, ?, ?, 'Pending')");
$stmt->bind_param("iiss", $book_id, $user_id, $borrow_date, $return_date);

if ($stmt->execute()) {
    // 5. Update the book status to "In Process"
    $update_stmt = $conn->prepare("UPDATE books SET status='In Process' WHERE id=?");
    $update_stmt->bind_param("i", $book_id);
    $update_stmt->execute();
    $update_stmt->close();

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
