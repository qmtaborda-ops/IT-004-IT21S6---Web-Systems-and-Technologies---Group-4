<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Admin login required."]);
    exit;
}
include 'connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$request_id = $data['request_id'] ?? null;
$action = $data['action'] ?? ''; // 'approve' or 'return'

if (!$request_id || !in_array($action, ['approve', 'return'])) {
    echo json_encode(["success" => false, "error" => "Invalid request."]);
    exit;
}

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE borrow_requests SET status='Approved' WHERE id=? AND status='Pending'");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    echo json_encode(["success" => true]);
} 
elseif ($action === 'return') {
    // Get the quantity and book_id to return stock
    $stmt = $conn->prepare("SELECT book_id, quantity FROM borrow_requests WHERE id=? AND status='Approved'");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    if ($res) {
        $book_id = $res['book_id'];
        $qty = $res['quantity'];
        
        // Mark returned
        $conn->query("UPDATE borrow_requests SET status='Returned' WHERE id=$request_id");
        
        // Add stock back
        $conn->query("UPDATE books SET quantity = quantity + $qty, status='Available' WHERE id=$book_id");
        
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Request not found."]);
    }
}
?>
