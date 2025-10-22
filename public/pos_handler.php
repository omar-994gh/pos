<?php
// public/pos_handler.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Order.php';
require_once __DIR__ . '/../src/Item.php';

Auth::requireLogin();

// Authorization: non-admin users must have btn_checkout permission
if (!Auth::isAdmin()) {
	$authStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM Authorizations WHERE user_id = :uid AND element_key = :ek');
	$authStmt->execute([':uid'=>(int)$_SESSION['user_id'], ':ek'=>'btn_checkout']);
	if ((int)($authStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) === 0) {
		echo json_encode(['success'=>false,'error'=>'Unauthorized: checkout is disabled for this user']);
		exit;
	}
}

$payload = json_decode(file_get_contents('php://input'), true);
$items   = $payload['items'] ?? [];
$total   = floatval($payload['total'] ?? 0);

try {
	// Normalize items and attach group_id
	$itemModel = new Item($db);
	$merged = [];
	foreach ($items as $it) {
		$id = (int)$it['item_id'];
		if (!isset($merged[$id])) {
			// fetch group_id
			$meta = $itemModel->find($id) ?: [];
			$merged[$id] = [
				'item_id'    => $id,
				'quantity'   => (float)$it['quantity'],
				'unit_price' => (float)$it['unit_price'],
				'group_id'   => (int)($meta['group_id'] ?? 0),
			];
		} else {
			$merged[$id]['quantity'] += (float)$it['quantity'];
		}
	}
	$cleanItems = array_values($merged);

	// Daily sequence
	$stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM Orders WHERE DATE(created_at) = DATE('now','localtime')");
	$stmt->execute();
	$todayCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
	$orderSeq = $todayCount + 1;

	// Create order
	$orderModel = new Order($db);
	$success = $orderModel->create((int)$_SESSION['user_id'], $cleanItems, $total, $orderSeq);
	if (!$success) { throw new Exception('Failed to persist order or items'); }
	$orderId = (int)$db->lastInsertId();

	// Map groups to printer ids
	$groupIds = array_values(array_unique(array_map(fn($r)=> (int)$r['group_id'], $cleanItems)));
	$printersMap = [];
	if (!empty($groupIds)) {
		$in = implode(',', array_fill(0, count($groupIds), '?'));
		$q = $db->prepare("SELECT id, printer_id FROM Groups WHERE id IN ($in)");
		$q->execute($groupIds);
		foreach ($q->fetchAll() as $row) { $printersMap[(int)$row['id']] = (int)($row['printer_id'] ?? 0); }
	}

	$unassignedPrinters = [];
	$qa = $db->query("SELECT id FROM Printers WHERE id NOT IN (SELECT DISTINCT printer_id FROM Groups WHERE printer_id IS NOT NULL)");
	$unassignedPrinters = array_map(fn($r)=> (int)$r['id'], $qa->fetchAll(PDO::FETCH_ASSOC));

	echo json_encode([
		'success'    => true,
		'orderId'    => $orderId,
		'orderSeq'   => $orderSeq,
		'items'      => $cleanItems,
		'groupPrinters' => $printersMap,
		'unassignedPrinters' => $unassignedPrinters,
	]);

} catch (Exception $e) {
	echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
