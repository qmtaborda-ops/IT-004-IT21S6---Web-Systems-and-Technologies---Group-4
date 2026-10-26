<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Admin login required."]);
    exit;
}
include 'connection.php';

$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

$stmt = $conn->prepare("
    SELECT br.id, br.borrow_date, br.return_date, br.status, 
           CONCAT(u.first_name, ' ', u.last_name) AS user_name 
    FROM borrow_requests br 
    JOIN users u ON br.user_id = u.id 
    WHERE br.book_id = ? AND br.status IN ('Pending', 'Approved')
    ORDER BY br.id ASC
");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

$requests = array();
while($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

echo json_encode($requests);
$stmt->close();
$conn->close();
?>
