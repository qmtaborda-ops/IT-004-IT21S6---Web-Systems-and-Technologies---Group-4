<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include 'connection.php';

// The cover image itself is NOT included here (too big for JSON); the page loads it
// from get_cover.php. cover_hash changes whenever the image changes (cache busting).
$sql = "SELECT b.id, b.title, b.author, b.year, b.status, b.call_no, b.genre_id, b.is_hidden,
               b.description,
               IF(b.cover_image IS NULL, NULL, MD5(b.cover_image)) AS cover_hash,
               CONCAT(u.first_name, ' ', u.last_name) AS borrower_name 
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
