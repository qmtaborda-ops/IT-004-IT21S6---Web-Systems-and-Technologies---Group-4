<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

session_start(); // Start the session to get the user_id
include 'connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$book_id = $data['book_id'] ?? null;
$borrow_date = $data['borrow_date'] ?? '';
$return_date = $data['return_date'] ?? '';

if (!$book_id) {
    echo json_encode(["success" => false, "error" => "No book selected"]);
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'] ?? null; 

// If not logged in, stop them (or use a fallback ID for testing if you want)
if (!$user_id) {
    echo json_encode(["success" => false, "error" => "You must be logged in to borrow."]);
    exit;
}

$sql = "INSERT INTO borrow_requests (book_id, user_id, borrow_date, return_date, status) 
        VALUES ($book_id, $user_id, '$borrow_date', '$return_date', 'Pending')";

if (mysqli_query($conn, $sql)) {
    $update_sql = "UPDATE books SET status='In Process' WHERE id=$book_id";
    mysqli_query($conn, $update_sql);
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => mysqli_error($conn)]);
}
?>
