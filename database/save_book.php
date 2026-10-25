<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;
$title = $data['title'] ?? '';
$author = $data['author'] ?? '';
$year = $data['year'] ?? '';
$status = $data['status'] ?? '';
$call_no = $data['call_no'] ?? '';
$genre = $data['genre'] ?? '';

if (empty(trim($title))) {
    echo json_encode(["success" => false, "error" => "No data submitted"]);
    exit;
}

if ($id) {
    $sql = "UPDATE books SET title='$title', author='$author', year='$year', status='$status', call_no='$call_no', genre_id='$genre' WHERE id=$id";
    
    if ($status === 'Borrowed') {
        $approve_sql = "UPDATE borrow_requests SET status='Approved' WHERE book_id=$id AND status='Pending' ORDER BY id ASC LIMIT 1";
        mysqli_query($conn, $approve_sql);
    }
    elseif ($status === 'Available') {
        $return_sql = "UPDATE borrow_requests SET status='Returned' WHERE book_id=$id AND status='Approved' ORDER BY id DESC LIMIT 1";
        mysqli_query($conn, $return_sql);
    }
} else {
    $sql = "INSERT INTO books (title, author, year, status, call_no, genre_id) VALUES ('$title', '$author', '$year', '$status', '$call_no', '$genre')";
}

if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => mysqli_error($conn)]);
}
?>
