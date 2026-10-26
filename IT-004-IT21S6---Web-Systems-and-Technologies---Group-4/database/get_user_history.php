<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all borrow requests for this user, joining with the books table to get the title
$sql = "SELECT br.id, br.borrow_date, br.return_date, br.status, b.title 
        FROM borrow_requests br 
        JOIN books b ON br.book_id = b.id 
        WHERE br.user_id = ? 
        ORDER BY br.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$history = array();

while($row = $result->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode($history);

$stmt->close();
$conn->close();
?>