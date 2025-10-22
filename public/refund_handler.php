<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::requireLogin();
if (!Auth::isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

// Support bulk refund: refund all items for an order
$action = $_POST['action'] ?? 'refund_item';
if ($action === 'refund_all') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $refundReason = trim($_POST['refund_reason'] ?? '');

    if (!$orderId || $refundReason === '') {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    try {
        $db->beginTransaction();

        // Fetch all items of the order
        $stmtItems = $db->prepare("SELECT item_id, quantity, unit_price FROM Order_Items WHERE order_id = ?");
        $stmtItems->execute([$orderId]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        if (empty($orderItems)) {
            throw new Exception('No items to refund for this order');
        }

        $totalRefund = 0.0;
        foreach ($orderItems as $row) {
            $itemId = (int)$row['item_id'];
            $qty = (float)$row['quantity'];
            $unitPrice = (float)$row['unit_price'];
            $amount = $qty * $unitPrice;
            $totalRefund += $amount;

            // Restore stock
            $st = $db->prepare('UPDATE Items SET stock = stock + ? WHERE id = ?');
            $st->execute([$qty, $itemId]);

            // Log refund per item
            $ins = $db->prepare("INSERT INTO Refunds (order_id, item_id, quantity, amount, reason, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))");
            $ins->execute([$orderId, $itemId, $qty, $amount, $refundReason, (int)$_SESSION['user_id']]);
        }

        // Remove all order items
        $db->prepare('DELETE FROM Order_Items WHERE order_id = ?')->execute([$orderId]);

        // Update order total ensuring non-negative
        $curStmt = $db->prepare('SELECT total FROM Orders WHERE id = ?');
        $curStmt->execute([$orderId]);
        $currentTotal = (float)$curStmt->fetchColumn();
        $newTotal = max($currentTotal - $totalRefund, 0);
        $db->prepare('UPDATE Orders SET total = ? WHERE id = ?')->execute([$newTotal, $orderId]);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'تم استرداد جميع الأصناف', 'refund_value' => $totalRefund]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Single item refund (default)
$itemId = (int)($_POST['item_id'] ?? 0);
$orderId = (int)($_POST['order_id'] ?? 0);
$refundAmount = (int)($_POST['refund_amount'] ?? 0);
$refundReason = trim($_POST['refund_reason'] ?? '');

if (!$itemId || !$orderId || !$refundAmount || $refundReason === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $db->beginTransaction();

    // Get order item details
    $stmt = $db->prepare("
        SELECT oi.*, o.total as order_total, o.user_id
        FROM Order_Items oi
        JOIN Orders o ON oi.order_id = o.id
        WHERE oi.order_id = ? AND oi.item_id = ?
    ");
    $stmt->execute([$orderId, $itemId]);
    $orderItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orderItem) {
        throw new Exception('Order item not found');
    }

    if ($refundAmount > $orderItem['quantity']) {
        throw new Exception('Refund amount cannot exceed sold quantity');
    }

    // Calculate refund amount
    $refundValue = $refundAmount * $orderItem['unit_price'];

    // Update order total
    $newOrderTotal = $orderItem['order_total'] - $refundValue;
    $stmt = $db->prepare('UPDATE Orders SET total = ? WHERE id = ?');
    $stmt->execute([$newOrderTotal, $orderId]);

    // Update order item quantity
    $newQuantity = $orderItem['quantity'] - $refundAmount;
    if ($newQuantity > 0) {
        $stmt = $db->prepare('UPDATE Order_Items SET quantity = ? WHERE order_id = ? AND item_id = ?');
        $stmt->execute([$newQuantity, $orderId, $itemId]);
    } else {
        // Remove the item completely if all quantity is refunded
        $stmt = $db->prepare('DELETE FROM Order_Items WHERE order_id = ? AND item_id = ?');
        $stmt->execute([$orderId, $itemId]);
    }

    // Update item stock (add back the refunded quantity)
    $stmt = $db->prepare('UPDATE Items SET stock = stock + ? WHERE id = ?');
    $stmt->execute([$refundAmount, $itemId]);

    // Log the refund
    $stmt = $db->prepare("
        INSERT INTO Refunds (order_id, item_id, quantity, amount, reason, user_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
    ");
    $stmt->execute([$orderId, $itemId, $refundAmount, $refundValue, $refundReason, $_SESSION['user_id']]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'تم الاسترداد بنجاح',
        'refund_amount' => $refundAmount,
        'refund_value' => $refundValue
    ]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>