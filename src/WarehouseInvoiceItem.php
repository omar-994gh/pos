<?php
// src/WarehouseInvoiceItem.php

class WarehouseInvoiceItem
{
    /** @var PDO */
    private $db;

    /**
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * إضافة بند إلى فاتورة المخزن
     * @param array $data [invoice_id, item_id, quantity, unit_price, total_price, sale_price, unit]
     * @return bool
     */
    public function create(array $data): bool
    {
        try {
            // بدء المعاملة لضمان تكامل البيانات
            $this->db->beginTransaction();

            // معرفة نوع الفاتورة (إدخال/إخراج) من رأس الفاتورة
            $entryType = 'IN';
            $invTypeStmt = $this->db->prepare('SELECT entry_type FROM Warehouse_Invoices WHERE id = :iid');
            $invTypeStmt->execute([':iid' => $data['invoice_id']]);
            $rowType = $invTypeStmt->fetch(PDO::FETCH_ASSOC);
            if ($rowType && !empty($rowType['entry_type'])) {
                $entryType = strtoupper(trim($rowType['entry_type'])) === 'OUT' ? 'OUT' : 'IN';
            }

            // 1. إدراج سجل في جدول فواتير المستودع
            $stmt = $this->db->prepare(
                'INSERT INTO Warehouse_Invoice_Items
                (invoice_id, item_id, quantity, unit_price, total_price, sale_price, unit)
                VALUES (:invoice_id, :item_id, :quantity, :unit_price, :total_price, :sale_price, :unit)'
            );
            $stmt->execute([
                ':invoice_id'  => $data['invoice_id'],
                ':item_id'     => $data['item_id'],
                ':quantity'    => $data['quantity'],
                ':unit_price'  => $data['unit_price'],
                ':total_price' => $data['total_price'],
                ':sale_price'  => $data['sale_price'],
                ':unit'        => $data['unit'],
            ]);

            // 2. تحديث كمية الصنف في جدول الأصناف
            $sqlUpdate = $entryType === 'OUT'
                ? 'UPDATE Items SET stock = stock - :quantity WHERE id = :item_id'
                : 'UPDATE Items SET stock = stock + :quantity WHERE id = :item_id';
            $updateStmt = $this->db->prepare($sqlUpdate);
            $updateStmt->execute([
                ':quantity' => $data['quantity'],
                ':item_id'  => $data['item_id'],
            ]);

            // تأكيد المعاملة إذا نجح كل شيء
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // التراجع عن التغييرات في حالة الخطأ
            $this->db->rollBack();
            
            // يمكنك تسجيل الخطأ هنا (اختياري)
            // error_log('Database error: ' . $e->getMessage());
            
            return false;
        }
    }
}