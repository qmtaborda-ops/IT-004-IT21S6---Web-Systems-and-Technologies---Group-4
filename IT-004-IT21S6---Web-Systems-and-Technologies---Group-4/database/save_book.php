<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Admin login required."]);
    exit;
}
include 'connection.php';

$id = $_POST['id'] ?? null;
$title = $_POST['title'] ?? '';
$author = $_POST['author'] ?? '';
$year = $_POST['year'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 1);
$status = $_POST['status'] ?? '';
$call_no = $_POST['call_no'] ?? '';
$genre = $_POST['genre'] ?? '';
$description = trim($_POST['description'] ?? '');
$remove_cover = ($_POST['remove_cover'] ?? '0') === '1';

$cover_data = null;
$cover_type = null;
if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['cover'];
    if ($f['error'] !== UPLOAD_ERR_OK) { echo json_encode(["success" => false, "error" => "Image upload failed."]); exit; }
    if ($f['size'] > 800 * 1024) { echo json_encode(["success" => false, "error" => "Image too large (max 800KB)."]); exit; }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, $allowed, true)) { echo json_encode(["success" => false, "error" => "Invalid image format."]); exit; }
    $cover_data = file_get_contents($f['tmp_name']);
    $cover_type = $mime;
}

if (empty(trim($title))) {
    echo json_encode(["success" => false, "error" => "Title is required."]);
    exit;
}

if ($id) {
    // Fetch current status
    $check_stmt = $conn->prepare("SELECT status FROM books WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $current_book = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    // If Admin clicks Borrowed, we approve the request and drop the stock
    if ($status === 'Borrowed' && $current_book['status'] !== 'Borrowed') {
        $req_stmt = $conn->prepare("SELECT quantity FROM borrow_requests WHERE book_id=? AND status='Pending' ORDER BY id ASC LIMIT 1");
        $req_stmt->bind_param("i", $id);
        $req_stmt->execute();
        $req = $req_stmt->get_result()->fetch_assoc();
        $req_stmt->close();

        $borrowed_amount = $req ? (int)$req['quantity'] : 1;
        $quantity = max(0, $quantity - $borrowed_amount); 

        // Mark request as Approved
        $approve_stmt = $conn->prepare("UPDATE borrow_requests SET status='Approved' WHERE book_id=? AND status='Pending' ORDER BY id ASC LIMIT 1");
        $approve_stmt->bind_param("i", $id);
        $approve_stmt->execute();
        $approve_stmt->close();
    }

    // If Admin clicks Available, we return the book
    if ($status === 'Available' && $current_book['status'] !== 'Available') {
        $ret_stmt = $conn->prepare("SELECT quantity FROM borrow_requests WHERE book_id=? AND status='Approved' ORDER BY id DESC LIMIT 1");
        $ret_stmt->bind_param("i", $id);
        $ret_stmt->execute();
        $ret = $ret_stmt->get_result()->fetch_assoc();
        $ret_stmt->close();

        if ($ret) {
            $returned_amount = (int)$ret['quantity'];
            $quantity = $quantity + $returned_amount;

            // Mark request as Returned
            $return_request_stmt = $conn->prepare("UPDATE borrow_requests SET status='Returned' WHERE book_id=? AND status='Approved' ORDER BY id DESC LIMIT 1");
            $return_request_stmt->bind_param("i", $id);
            $return_request_stmt->execute();
            $return_request_stmt->close();
        }
    }

    // Save the book with exactly the status you selected
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, year=?, quantity=?, status=?, call_no=?, genre_id=?, description=? WHERE id=?");
    $stmt->bind_param("sssissssi", $title, $author, $year, $quantity, $status, $call_no, $genre, $description, $id);
    
    if ($stmt->execute()) {
        if ($cover_data !== null) {
            $c = $conn->prepare("UPDATE books SET cover_image=?, cover_type=? WHERE id=?");
            $null = null;
            $c->bind_param("bsi", $null, $cover_type, $id);
            $c->send_long_data(0, $cover_data);
            $c->execute();
            $c->close();
        } elseif ($remove_cover) {
            $conn->query("UPDATE books SET cover_image=NULL, cover_type=NULL WHERE id=" . (int)$id);
        }
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
} else {
    // Insert new book
    $stmt = $conn->prepare("INSERT INTO books (title, author, year, quantity, status, call_no, genre_id, description, cover_image, cover_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $null = null;
    $stmt->bind_param("sssissssbs", $title, $author, $year, $quantity, $status, $call_no, $genre, $description, $null, $cover_type);
    if ($cover_data !== null) { $stmt->send_long_data(8, $cover_data); }
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>