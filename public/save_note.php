<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::requireLogin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$noteTextAr = trim($input['note_text_ar'] ?? '');
$noteTextEn = trim($input['note_text_en'] ?? ''); // ملاحظة إنجليزية اختيارية
$groupId    = $input['group_id'] ?? null;

if (empty($noteTextAr) || !$groupId) {
    echo json_encode(['success' => false, 'error' => 'Note text (Arabic) and Group ID are required.']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. تحقق مما إذا كانت الملاحظة موجودة بالفعل
    $stmt = $db->prepare("SELECT id FROM Notes WHERE text_ar = :text_ar");
    $stmt->execute([':text_ar' => $noteTextAr]);
    $existingNote = $stmt->fetch(PDO::FETCH_ASSOC);

    $noteId = null;
    if ($existingNote) {
        $noteId = $existingNote['id'];
    } else {
        // 2. إذا لم تكن موجودة، أضف الملاحظة الجديدة
        $stmt = $db->prepare("INSERT INTO Notes (text_ar, text_en) VALUES (:text_ar, :text_en)");
        $stmt->execute([
            ':text_ar' => $noteTextAr,
            ':text_en' => !empty($noteTextEn) ? $noteTextEn : null
        ]);
        $noteId = $db->lastInsertId();
    }

    if ($noteId) {
        // 3. اربط الملاحظة بالمجموعة (تجنب الإضافة المكررة)
        $stmt = $db->prepare("SELECT COUNT(*) FROM Group_Notes WHERE group_id = :group_id AND note_id = :note_id");
        $stmt->execute([':group_id' => $groupId, ':note_id' => $noteId]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO Group_Notes (group_id, note_id) VALUES (:group_id, :note_id)");
            $stmt->execute([':group_id' => $groupId, ':note_id' => $noteId]);
        }
        $db->commit();
        echo json_encode(['success' => true, 'note_id' => $noteId, 'text_ar' => $noteTextAr, 'text_en' => $noteTextEn]);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => 'Failed to add note.']);
    }

} catch (PDOException $e) {
    $db->rollBack();
    error_log("Error saving note: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>