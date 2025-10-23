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
     * يقوم بتوحيد العملات باستخدام أسعار التعادل
     * @param array $filters ['supplier'=>string, 'date_from'=>string, 'date_to'=>string, 'item_name'=>string]
     * @return array
     */
    public function all(array $filters = []): array
    {
        // Get exchange rate settings
        $settingsStmt = $this->db->query("SELECT base_currency, usd_to_syp_rate FROM System_Settings ORDER BY id DESC LIMIT 1");
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
        $baseCurrency = $settings['base_currency'] ?? 'SYP';
        $usdToSypRate = (float)($settings['usd_to_syp_rate'] ?? 15000.0);
        
        // Build conversion logic based on base currency
        if ($baseCurrency === 'SYP') {
            $convertExpression = "CASE WHEN i.currency = 'USD' THEN wii.total_price * $usdToSypRate ELSE wii.total_price END";
        } else { // USD
            $convertExpression = "CASE WHEN i.currency = 'SYP' THEN wii.total_price / $usdToSypRate ELSE wii.total_price END";
        }
        
        $sql = "
          SELECT wi.id, w.name AS warehouse, wi.supplier, wi.date, wi.entry_type,
                 COUNT(wii.item_id) AS items_count,
                 SUM($convertExpression) AS invoice_total,
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

            // 1) حذف البنود
            $stmtItems = $this->db->prepare(
                'DELETE FROM Warehouse_Invoice_Items WHERE invoice_id = :iid'
            );
            $stmtItems->execute([':iid' => $invoiceId]);

            // 2) حذف الفاتورة
            $stmtInv = $this->db->prepare(
                'DELETE FROM Warehouse_Invoices WHERE id = :iid'
            );
            $stmtInv->execute([':iid' => $invoiceId]);

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
        $stmt = $this->db->prepare(
            'DELETE FROM Warehouse_Invoice_Items 
             WHERE invoice_id = :iid AND item_id = :itid'
        );
        return $stmt->execute([
            ':iid'  => $invoiceId,
            ':itid' => $itemId,
        ]);
    }
}
