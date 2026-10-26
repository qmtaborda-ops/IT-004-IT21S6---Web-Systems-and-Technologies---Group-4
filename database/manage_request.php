<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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
$item_condition = $data['item_condition'] ?? 'Good';

if (!$request_id || !in_array($action, ['approve', 'return']) ||
    ($action === 'return' && !in_array($item_condition, ['Good', 'Damaged', 'Missing'], true))) {
    echo json_encode(["success" => false, "error" => "Invalid request."]);
    exit;
}

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE borrow_requests SET status='Approved' WHERE id=? AND status='Pending'");
    $stmt->bind_param("i", $request_id);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        echo json_encode(["success" => false, "error" => "Pending request not found or already processed."]);
    } else {
        echo json_encode(["success" => true]);
    }
    $stmt->close();
} 
elseif ($action === 'return') {
    // Get the quantity and book_id to return stock
    $stmt = $conn->prepare("SELECT book_id, quantity FROM borrow_requests WHERE id=? AND status='Approved'");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $res = $result->fetch_assoc();
    $result->free();
    $stmt->close();
    
    if ($res) {
        $book_id = $res['book_id'];
        $qty = $res['quantity'];
        
        $conn->begin_transaction();
        $return_stmt = $conn->prepare("UPDATE borrow_requests SET status='Returned', item_condition=? WHERE id=? AND status='Approved'");
        $return_stmt->bind_param("si", $item_condition, $request_id);
        if ($item_condition === 'Good') {
            $book_stmt = $conn->prepare("UPDATE books SET quantity = quantity + ?, status='Available' WHERE id=?");
            $book_stmt->bind_param("ii", $qty, $book_id);
        }

        $return_succeeded = $return_stmt->execute() && $return_stmt->affected_rows === 1;
        $stock_succeeded = $item_condition !== 'Good' ||
            ($book_stmt->execute() && $book_stmt->affected_rows === 1);
        if ($return_succeeded && $stock_succeeded) {
            $conn->commit();
            echo json_encode(["success" => true]);
        } else {
            $conn->rollback();
            echo json_encode(["success" => false, "error" => "Could not complete the return."]);
        }
        $return_stmt->close();
        if ($item_condition === 'Good') {
            $book_stmt->close();
        }
    } else {
        echo json_encode(["success" => false, "error" => "Request not found."]);
    }
}
?>
