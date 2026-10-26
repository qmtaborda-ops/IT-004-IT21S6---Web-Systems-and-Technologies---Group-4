<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Browsers send an OPTIONS "preflight" request before a JSON POST
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

header("Content-Type: application/json");
require __DIR__ . "/connection.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data["id"]) ? (int)$data["id"] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid book id"]);
    exit;
}

// Delete related borrow requests first so they don't block the delete.
// Change book_id if your borrow_requests column has a different name.
$stmt = $conn->prepare("DELETE FROM borrow_requests WHERE book_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["success" => true]);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Book not found or could not be deleted"]);
}
