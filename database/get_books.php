<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include 'connection.php';

// Get all books with their pending request count
$sql = "SELECT b.id, b.title, b.author, b.year, b.status, b.call_no, b.genre_id, b.is_hidden,
               b.description, b.quantity, b.price,
               IF(b.cover_image IS NULL, NULL, MD5(b.cover_image)) AS cover_hash,
               (SELECT GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') 
                FROM borrow_requests br 
                JOIN users u ON br.user_id = u.id 
                WHERE br.book_id = b.id AND br.status = 'Approved') AS borrower_name,
               (SELECT COUNT(*) FROM borrow_requests br WHERE br.book_id = b.id AND br.status = 'Pending') AS pending_count
        FROM books b";

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
