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
    // Use prepared statements to prevent SQL injection and errors
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, year=?, status=?, call_no=?, genre_id=? WHERE id=?");
    $stmt->bind_param("ssssssi", $title, $author, $year, $status, $call_no, $genre, $id);
    
    if ($stmt->execute()) {
        // If status is changed to Borrowed, make sure there is a borrow request
        if ($status === 'Borrowed') {
            // Check if an approved request exists
            $check = $conn->prepare("SELECT id FROM borrow_requests WHERE book_id=? AND status='Approved'");
            $check->bind_param("i", $id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows == 0) {
                // If no approved request exists, check for a pending one and approve it
                $approve_sql = "UPDATE borrow_requests SET status='Approved' WHERE book_id=? AND status='Pending' ORDER BY id ASC LIMIT 1";
                $approve_stmt = $conn->prepare($approve_sql);
                $approve_stmt->bind_param("i", $id);
                $approve_stmt->execute();
                $approve_stmt->close();
            }
            $check->close();
        }
        elseif ($status === 'Available') {
            // If status is Available, mark the approved request as Returned
            $return_sql = "UPDATE borrow_requests SET status='Returned' WHERE book_id=? AND status='Approved' ORDER BY id DESC LIMIT 1";
            $return_stmt = $conn->prepare($return_sql);
            $return_stmt->bind_param("i", $id);
            $return_stmt->execute();
            $return_stmt->close();
        }
        
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
} else {
    // Insert new book
    $stmt = $conn->prepare("INSERT INTO books (title, author, year, status, call_no, genre_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $title, $author, $year, $status, $call_no, $genre);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>
