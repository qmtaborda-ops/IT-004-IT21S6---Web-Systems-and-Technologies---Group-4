<?php
// Serves a book's cover image straight from the database: get_cover.php?id=5
include 'connection.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT cover_image, cover_type FROM books WHERE id = ? AND cover_image IS NOT NULL");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!$row || !in_array($row['cover_type'], $allowed, true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $row['cover_type']);
header('Content-Length: ' . strlen($row['cover_image']));
header('X-Content-Type-Options: nosniff');
// The page adds ?v=<hash>, so the URL changes whenever the image changes
header('Cache-Control: public, max-age=31536000, immutable');
echo $row['cover_image'];