<?php
// src/WarehouseEntry.php

class Warehouse
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** حفظ فاتورة إدخال/إخراج */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO Warehouse_Entries 
             (warehouse_id, item_id, supplier, date, qty, unit_price, total_price, entry_type)
             VALUES (:wid, :iid, :sup, :date, :qty, :up, :tp, :type)'
        );
        return $stmt->execute([
            ':wid'  => $data['warehouse_id'],
            ':iid'  => $data['item_id'],
            ':sup'  => $data['supplier'],
            ':date' => $data['date'],         // "YYYY-MM-DD HH:MM:SS"
            ':qty'  => $data['quantity'],
            ':up'   => $data['unit_price'],
            ':tp'   => $data['quantity'] * $data['unit_price'],
            ':type' => $data['entry_type'],   // "IN" أو "OUT"
        ]);
    }

    /** جلب كل الفواتير */
    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT we.id, w.name AS warehouse, i.name_ar AS item, we.supplier,
                    we.date, we.qty, we.unit_price, we.total_price, we.entry_type
             FROM Warehouse_Entries we
             JOIN Warehouses w ON we.warehouse_id = w.id
             JOIN Items i      ON we.item_id = i.id
             ORDER BY we.date DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /** جلب كل المخازن */
    public function allWh(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name FROM Warehouses ORDER BY id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
