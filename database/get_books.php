<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'connection.php';

$sql = "SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) AS borrower_name 
        FROM books b 
        LEFT JOIN borrow_requests br ON b.id = br.book_id AND br.status = 'Approved'
        LEFT JOIN users u ON br.user_id = u.id";

$result = mysqli_query($conn, $sql);
$books = array();

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }
    echo json_encode($books);
} else {
    echo json_encode(["error" => mysqli_error($conn)]);
}
?>
