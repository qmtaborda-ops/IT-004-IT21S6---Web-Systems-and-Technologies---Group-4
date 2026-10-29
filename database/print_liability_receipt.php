<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Please log in to view your receipt.");
}
include 'connection.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

$paid_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM payment_requests WHERE user_id = ? AND status = 'Paid'");
$paid_stmt->bind_param("i", $user_id);
$paid_stmt->execute();
$paid_total = (float)$paid_stmt->get_result()->fetch_row()[0];
$paid_stmt->close();

$stmt = $conn->prepare("
    SELECT 
        b.title, 
        b.price, 
        br.quantity, 
        br.status, 
        br.item_condition,
        br.return_date, 
        DATEDIFF(CURDATE(), br.return_date) as days_late 
    FROM borrow_requests br 
    JOIN books b ON br.book_id = b.id 
    WHERE br.user_id = ? 
    AND (
        br.item_condition IN ('Damaged', 'Missing') 
        OR (br.status = 'Approved' AND CURDATE() > br.return_date)
    )
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$liabilities = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $price = max(0, (float)$row['price']);
    $qty = max(1, (int)$row['quantity']);
    $max_fee = $price * $qty;
    
    $item_cond = strtolower((string)$row['item_condition']);
    

    if ($item_cond === 'damaged' || $item_cond === 'missing') {
        $amount = $max_fee;
        $reason = ucfirst($item_cond) . ' item (Qty: ' . $qty . ')';
    } 
    else {
        $days_late = (int)$row['days_late'];
        
        $late_fee = ($price * 0.01) * $days_late * $qty;
        $amount = min($late_fee, $max_fee);
        
        $cap_text = ($amount == $max_fee) ? ' (Max fee reached)' : '';
        $reason = $days_late . ' days overdue' . $cap_text;
    }
    
    if ($amount > 0) {
        $total += $amount;
        $liabilities[] = [
            'title' => $row['title'],
            'reason' => $reason,
            'amount' => $amount
        ];
    }
}
$stmt->close();
$conn->close();

$remaining_balance = max(0, $total - $paid_total);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liability Receipt - Booker's Club</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; color: #000; background: #fff; margin: 0; padding: 20px; }
        .receipt-box { max-width: 320px; margin: 0 auto; border: 1px dashed #000; padding: 20px; }
        .text-center { text-align: center; }
        .header { margin-bottom: 15px; }
        .header h2 { margin: 0 0 5px 0; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 0; font-size: 12px; }
        .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
        .info-row { font-size: 12px; margin-bottom: 3px; }
        .item { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
        .total { font-weight: bold; font-size: 14px; margin-top: 10px; text-transform: uppercase; }
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
            <p>Official Liability Receipt</p>
        </div>
        <div class="divider"></div>
        <div class="info-row"><strong>Date:</strong> <?php echo date('Y-m-d h:i A'); ?></div>
        <div class="info-row"><strong>Patron:</strong> <?php echo htmlspecialchars($user_name); ?></div>
        <div class="divider"></div>
        
        <?php if (empty($liabilities)): ?>
            <p class="text-center" style="font-size:12px; font-style:italic;">No outstanding liabilities.</p>
        <?php else: ?>
            <div style="font-size: 12px; font-weight: bold; display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Item & Reason</span>
                <span>Amount</span>
            </div>
            <div class="divider"></div>
            <?php foreach ($liabilities as $item): ?>
                <div class="item">
                    <div style="flex: 1; padding-right: 15px;">
                        <?php echo htmlspecialchars($item['title']); ?><br>
                        <small>(<?php echo $item['reason']; ?>)</small>
                    </div>
                    <div>&#8369;<?php echo number_format($item['amount'], 2); ?></div>
                </div>
            <?php endforeach; ?>
            <div class="divider"></div>
            
            <div class="item text-muted">
                <span>Total Fines:</span>
                <span>&#8369;<?php echo number_format($total, 2); ?></span>
            </div>
            
            <?php if ($paid_total > 0): ?>
            <div class="item" style="color: #2e7d32;">
                <span>Payments Applied:</span>
                <span>-&#8369;<?php echo number_format($paid_total, 2); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="item total">
                <span>Remaining Balance:</span>
                <span>&#8369;<?php echo number_format($remaining_balance, 2); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="divider"></div>
        <p class="text-center" style="font-size: 11px;">Please present this receipt to the librarian when settling your account.</p>
    </div>
    <script>
        // Automatically open the print dialog when the file is loaded
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
