<?php
// src/WarehouseInvoice.php

class WarehouseInvoice
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * جلب جميع الفواتير مع فلترة حسب المورد، النطاق الزمني، واسم المادة
     * @param array $filters ['supplier'=>string, 'date_from'=>string, 'date_to'=>string, 'item_name'=>string]
     * @return array
     */
    public function all(array $filters = []): array
    {
        $sql = "
          SELECT wi.id, w.name AS warehouse, wi.supplier, wi.date, wi.entry_type,
                 COUNT(wii.item_id) AS items_count,
                 SUM(wii.total_price) AS invoice_total,
                 i.name_ar
          FROM Warehouse_Invoices wi
          JOIN Warehouses w   ON wi.warehouse_id = w.id
          JOIN Warehouse_Invoice_Items wii ON wi.id = wii.invoice_id
          JOIN Items i        ON wii.item_id = i.id
          WHERE 1=1
        ";
        $params = [];
        if (!empty($filters['supplier'])) {
            $sql .= " AND wi.supplier LIKE :supplier";
            $params[':supplier'] = "%{$filters['supplier']}%";
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND wi.date >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND wi.date <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['item_name'])) {
            $sql .= " AND (i.name_ar LIKE :item_name OR i.name_en LIKE :item_name)";
            $params[':item_name'] = "%{$filters['item_name']}%";
        }
        $sql .= " GROUP BY wi.id ORDER BY wi.date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * جلب تفاصيل فاتورة محددة
     * @param int $invoiceId
     * @return array
     */
    public function findItems(int $invoiceId): array
    {
        $stmt = $this->db->prepare("
          SELECT wii.item_id, i.name_ar, i.name_en, wii.quantity, wii.unit_price, wii.total_price, wii.unit
          FROM Warehouse_Invoice_Items wii
          JOIN Items i ON wii.item_id = i.id
          WHERE wii.invoice_id = :iid
        ");
        $stmt->execute([':iid' => $invoiceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO Warehouse_Invoices (warehouse_id, supplier, date, entry_type)
             VALUES (:warehouse_id, :supplier, :date, :entry_type)'
        );
        $ok = $stmt->execute([
            ':warehouse_id' => $data['warehouse_id'],
            ':supplier'     => $data['supplier'],
            ':date'         => $data['date'],
            ':entry_type'   => $data['entry_type'],
        ]);
        if (! $ok) {
            return false;
        }
        return (int)$this->db->lastInsertId();
    }
    /**
     * حذف كل البنود المرتبطة بفاتورة معينة ثم حذف الفاتورة نفسها
     * @param int $invoiceId
     * @return bool
     */
    public function deleteInvoice(int $invoiceId): bool
    {
        try {
            $this->db->beginTransaction();

            // احصل على نوع الفاتورة وبنودها لضبط المخزون أولاً
            $stmtInv = $this->db->prepare('SELECT entry_type FROM Warehouse_Invoices WHERE id = :iid');
            $stmtInv->execute([':iid' => $invoiceId]);
            $invRow = $stmtInv->fetch(PDO::FETCH_ASSOC);
            $entryType = strtoupper(trim($invRow['entry_type'] ?? 'IN')) === 'OUT' ? 'OUT' : 'IN';

            // جلب كل البنود مع الكميات
            $stmtFetchItems = $this->db->prepare(
                'SELECT item_id, quantity FROM Warehouse_Invoice_Items WHERE invoice_id = :iid'
            );
            $stmtFetchItems->execute([':iid' => $invoiceId]);
            $items = $stmtFetchItems->fetchAll(PDO::FETCH_ASSOC);

            // عكس تأثير البنود على المخزون
            foreach ($items as $row) {
                $qty = (float)$row['quantity'];
                $itemId = (int)$row['item_id'];
                // إذا كانت الفاتورة إدخالاً فقد زادت الكمية سابقاً ⇒ ننقصها الآن
                // وإذا كانت إخراجاً فقد نُقصت الكمية سابقاً ⇒ نزيدها الآن
                $deltaSql = $entryType === 'IN'
                    ? 'UPDATE Items SET stock = stock - :q WHERE id = :id'
                    : 'UPDATE Items SET stock = stock + :q WHERE id = :id';
                $this->db->prepare($deltaSql)->execute([':q' => $qty, ':id' => $itemId]);
            }

            // 1) حذف البنود بعد تعديل المخزون
            $stmtItems = $this->db->prepare('DELETE FROM Warehouse_Invoice_Items WHERE invoice_id = :iid');
            $stmtItems->execute([':iid' => $invoiceId]);

            // 2) حذف الفاتورة
            $stmtDeleteInv = $this->db->prepare('DELETE FROM Warehouse_Invoices WHERE id = :iid');
            $stmtDeleteInv->execute([':iid' => $invoiceId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('WarehouseInvoice::deleteInvoice error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * حذف بند واحد من فاتورة معينة
     * @param int $invoiceId
     * @param int $itemId
     * @return bool
     */
    public function deleteItem(int $invoiceId, int $itemId): bool
    {
        try {
            $this->db->beginTransaction();

            // جلب نوع الفاتورة والكمية للبند المحدد
            $stmtType = $this->db->prepare('SELECT entry_type FROM Warehouse_Invoices WHERE id = :iid');
            $stmtType->execute([':iid' => $invoiceId]);
            $entryType = strtoupper(trim(($stmtType->fetch(PDO::FETCH_ASSOC)['entry_type'] ?? 'IN')));
            $entryType = $entryType === 'OUT' ? 'OUT' : 'IN';

            $stmtQty = $this->db->prepare('SELECT quantity FROM Warehouse_Invoice_Items WHERE invoice_id = :iid AND item_id = :itid');
            $stmtQty->execute([':iid' => $invoiceId, ':itid' => $itemId]);
            $row = $stmtQty->fetch(PDO::FETCH_ASSOC);
            $qty = (float)($row['quantity'] ?? 0);

            if ($qty > 0) {
                // عكس تأثير هذا البند على المخزون
                $deltaSql = $entryType === 'IN'
                    ? 'UPDATE Items SET stock = stock - :q WHERE id = :id'
                    : 'UPDATE Items SET stock = stock + :q WHERE id = :id';
                $this->db->prepare($deltaSql)->execute([':q' => $qty, ':id' => $itemId]);
            }

            // حذف البند
            $stmtDel = $this->db->prepare('DELETE FROM Warehouse_Invoice_Items WHERE invoice_id = :iid AND item_id = :itid');
            $stmtDel->execute([':iid' => $invoiceId, ':itid' => $itemId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('WarehouseInvoice::deleteItem error: ' . $e->getMessage());
            return false;
        }
    }
}
