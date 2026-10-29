<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Please log in to view your borrow receipt.");
}
include 'connection.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Query active borrow requests for this user
$stmt = $conn->prepare("
    SELECT b.title, b.author, b.call_no, br.quantity, br.borrow_date, br.return_date, br.status 
    FROM borrow_requests br 
    JOIN books b ON br.book_id = b.id 
    WHERE br.user_id = ? AND br.status IN ('Pending', 'Approved')
    ORDER BY br.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$borrowed_books = [];
while ($row = $result->fetch_assoc()) {
    $borrowed_books[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Receipt - Booker's Club</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; color: #000; background: #fff; margin: 0; padding: 20px; }
        .receipt-box { max-width: 340px; margin: 0 auto; border: 1px dashed #000; padding: 20px; }
        .text-center { text-align: center; }
        .header { margin-bottom: 15px; }
        .header h2 { margin: 0 0 5px 0; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 0; font-size: 12px; }
        .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
        .info-row { font-size: 12px; margin-bottom: 3px; }
        .item { font-size: 12px; margin-bottom: 8px; }
        .item-title { font-weight: bold; }
        .item-details { display: flex; justify-content: space-between; margin-top: 2px; }
        .status-tag { text-transform: uppercase; font-size: 10px; font-weight: bold; }
        @media print {
            body { padding: 0; background: transparent; }
            .receipt-box { border: none; width: 100%; max-width: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="receipt-box">
        <div class="header text-center">
            <h2>Booker's Club</h2>
            <p>123 Library Lane, Reading District</p>
            <p>Official Borrow Receipt</p>
        </div>
        <div class="divider"></div>
        <div class="info-row"><strong>Date Issued:</strong> <?php echo date('Y-m-d h:i A'); ?></div>
        <div class="info-row"><strong>Patron:</strong> <?php echo htmlspecialchars($user_name); ?></div>
        <div class="divider"></div>
        
        <?php if (empty($borrowed_books)): ?>
            <p class="text-center" style="font-size:12px; font-style:italic;">No active borrowed books.</p>
        <?php else: ?>
            <?php foreach ($borrowed_books as $book): ?>
                <div class="item">
                    <div class="item-title"><?php echo htmlspecialchars($book['title']); ?></div>
                    <div style="font-size: 11px; color: #333;">Author: <?php echo htmlspecialchars($book['author']); ?> | Call: <?php echo htmlspecialchars($book['call_no']); ?></div>
                    <div class="item-details">
                        <span>Qty: <?php echo (int)$book['quantity']; ?></span>
                        <span class="status-tag">[<?php echo htmlspecialchars($book['status']); ?>]</span>
                    </div>
                    <div class="item-details">
                        <span>Borrowed: <?php echo htmlspecialchars($book['borrow_date']); ?></span>
                        <span>Due: <?php echo htmlspecialchars($book['return_date']); ?></span>
                    </div>
                </div>
                <div class="divider"></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <p class="text-center" style="font-size: 11px; margin-top: 15px;">
            Please return all books on or before their specified due dates to avoid overdue fines.
        </p>
    </div>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
