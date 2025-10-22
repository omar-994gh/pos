<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST,GET,OPTIONS');
header('Access-Control-Allow-Headers:Content-Type');

// Load DB using same path logic as config/db.php
$dbFile = getenv('DB_PATH') ?: (__DIR__ . '/../config/config.sqlite');
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$payload = json_decode(file_get_contents('php://input'), true);
// Backward compatibility: allow single image string
$images = [];
if (!empty($payload['image'])) {
	$images[] = [ 'image' => $payload['image'], 'printer_ids' => [] ];
}
if (!empty($payload['images']) && is_array($payload['images'])) {
	foreach ($payload['images'] as $img) {
		if (!empty($img['image'])) {
			$images[] = [
				'image' => $img['image'],
				'printer_ids' => isset($img['printer_ids']) && is_array($img['printer_ids']) ? $img['printer_ids'] : []
			];
		}
	}
}
if (empty($images)) {
	echo json_encode(['success'=>false,'error'=>'No image payload provided']);
	exit;
}

require __DIR__.'/../vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\EscposImage;

// Fetch printers
$stmt = $pdo->query("SELECT id, name AS printer_name, address AS ip_address, type FROM Printers");
$allPrinters = $stmt->fetchAll();
if (!$allPrinters) {
	echo json_encode(['success'=>false,'error'=>'No printers configured']);
	exit;
}

// Separate assigned and unassigned printers
$assignedPrinterIds = [];
$unassignedPrinterIds = [];

foreach ($allPrinters as $printer) {
	$printerId = (int)$printer['id'];
	// Check if this printer is assigned to any group
	$stmt = $pdo->prepare("SELECT COUNT(*) FROM Groups WHERE printer_id = ?");
	$stmt->execute([$printerId]);
	$isAssigned = $stmt->fetchColumn() > 0;
	
	if ($isAssigned) {
		$assignedPrinterIds[] = $printerId;
	} else {
		$unassignedPrinterIds[] = $printerId;
	}
}

$errors = [];
foreach ($images as $imgSpec) {
	$img = str_replace('data:image/png;base64,','',$imgSpec['image']);
	$bin = base64_decode($img);
	$temp = __DIR__ . '/temp_invoice_' . uniqid() . '.png';
	file_put_contents($temp, $bin);

	$targetPrinters = [];
	
	// If printer_ids are specified, use those (for group-specific receipts)
	if (!empty($imgSpec['printer_ids'])) {
		$ids = array_map('intval', $imgSpec['printer_ids']);
		$targetPrinters = array_values(array_filter($allPrinters, function($p) use($ids){ 
			return in_array((int)$p['id'], $ids, true); 
		}));
	} else {
		// If no printer_ids specified, this is the full cart receipt - send to unassigned printers
		$targetPrinters = array_values(array_filter($allPrinters, function($p) use($unassignedPrinterIds){ 
			return in_array((int)$p['id'], $unassignedPrinterIds, true); 
		}));
	}

	foreach ($targetPrinters as $p) {
		try {
			if ($p['type'] === 'usb') {
				$conn = new WindowsPrintConnector($p['printer_name']);
			} else {
				$conn = new NetworkPrintConnector($p['ip_address'], 9100);
			}
			$printer = new Printer($conn);
			$imgObj = EscposImage::load($temp);
			$printer->bitImage($imgObj);
			$printer->pulse();
			$printer->cut();
			$printer->close();
		} catch (Exception $e) {
			$errors[] = $e->getMessage();
		}
	}
	//@unlink($temp);
}

echo json_encode([
	'success' => empty($errors),
	'error'   => empty($errors) ? '' : implode('; ', $errors)
]);

?>