<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::requireLogin();

header('Content-Type: application/json');

$groupId = $_GET['group_id'] ?? null;

if (!$groupId) {
    echo json_encode(['success' => false, 'error' => 'Group ID is required.']);
    exit;
}

try {
    // جلب الملاحظات المرتبطة بهذه المجموعة
    $stmt = $db->prepare("
        SELECT N.id, N.text_ar, N.text_en
        FROM Notes N
        JOIN Group_Notes GN ON N.id = GN.note_id
        WHERE GN.group_id = :group_id
        ORDER BY N.text_ar
    ");
    $stmt->execute([':group_id' => $groupId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'notes' => $notes]);

} catch (PDOException $e) {
    error_log("Error fetching notes: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>