<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
session_start();
// Only a logged-in admin may add/edit/delete books or upload cover images
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Admin login required."]);
    exit;
}
include 'connection.php';

// Data arrives as multipart/form-data (FormData) so a cover image can be uploaded
$id = $_POST['id'] ?? null;
$title = $_POST['title'] ?? '';
$author = $_POST['author'] ?? '';
$year = $_POST['year'] ?? '';
$status = $_POST['status'] ?? '';
$call_no = $_POST['call_no'] ?? '';
$genre = $_POST['genre'] ?? '';
$description = trim($_POST['description'] ?? '');
$remove_cover = ($_POST['remove_cover'] ?? '0') === '1';

// ---- Cover image (optional). It is stored INSIDE the database (books.cover_image),
// so anyone who has the database also has the images - no extra files to share.
$cover_data = null;
$cover_type = null;
if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['cover'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "error" => "Image upload failed (code " . $f['error'] . ")."]);
        exit;
    }
    if ($f['size'] > 800 * 1024) {
        echo json_encode(["success" => false, "error" => "Image is too large (max 800KB)."]);
        exit;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        echo json_encode(["success" => false, "error" => "Only JPG, PNG, WEBP or GIF images are allowed."]);
        exit;
    }
    $cover_data = file_get_contents($f['tmp_name']);
    $cover_type = $mime;
}

if (empty(trim($title))) {
    echo json_encode(["success" => false, "error" => "No data submitted"]);
    exit;
}

if ($id) {
    // Use prepared statements to prevent SQL injection and errors
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, year=?, status=?, call_no=?, genre_id=?, description=? WHERE id=?");
    $stmt->bind_param("sssssssi", $title, $author, $year, $status, $call_no, $genre, $description, $id);
    
    if ($stmt->execute()) {
        // Replace or remove the cover image only when the admin asked for it
        if ($cover_data !== null) {
            $c = $conn->prepare("UPDATE books SET cover_image=?, cover_type=? WHERE id=?");
            $null = null;
            $c->bind_param("bsi", $null, $cover_type, $id);
            $c->send_long_data(0, $cover_data);
            if (!$c->execute()) {
                echo json_encode(["success" => false, "error" => "Could not save the image: " . $c->error]);
                exit;
            }
            $c->close();
        } elseif ($remove_cover) {
            $conn->query("UPDATE books SET cover_image=NULL, cover_type=NULL WHERE id=" . (int)$id);
        }

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
    $stmt = $conn->prepare("INSERT INTO books (title, author, year, status, call_no, genre_id, description, cover_image, cover_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $null = null;
    $types = "sssssss" . ($cover_data !== null ? "b" : "s") . "s"; // "b" only when an image was uploaded
    $stmt->bind_param($types, $title, $author, $year, $status, $call_no, $genre, $description, $null, $cover_type);
    if ($cover_data !== null) { $stmt->send_long_data(7, $cover_data); }
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>
