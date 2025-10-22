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
            $updateStmt = $this->db->prepare(
                'UPDATE Items SET stock = stock + :quantity WHERE id = :item_id'
            );
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