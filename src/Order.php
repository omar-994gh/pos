<?php
// src/Order.php

class Order
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new order with its items
     * @param int   $userId
     * @param array $items Each: ['item_id'=>int,'quantity'=>float,'unit_price'=>float,'group_id'?:int]
     * @param float $total
     * @param int   $orderSeq
     * @return bool
     */
    public function create(int $userId, array $items, float $total, int $orderSeq): bool
    {
        $stmt = $this->db->prepare('INSERT INTO Orders (user_id, total, order_seq) VALUES (:uid, :tot, :seq)');
        $ok = $stmt->execute([':uid' => $userId, ':tot' => $total, ':seq' => $orderSeq]);
        if (!$ok) { return false; }
        $orderId = (int)$this->db->lastInsertId();

        $stmtItem = $this->db->prepare('INSERT INTO Order_Items (order_id, item_id, quantity, unit_price) VALUES (:oid, :iid, :qty, :up)');
        foreach ($items as $it) {
            $res = $stmtItem->execute([':oid' => $orderId, ':iid' => $it['item_id'], ':qty' => $it['quantity'], ':up'  => $it['unit_price']]);
            if (!$res) { return false; }
            $this->db->prepare('UPDATE Items SET stock = stock - :qty WHERE id = :iid')->execute([':qty' => $it['quantity'], ':iid' => $it['item_id']]);
        }
        return true;
    }
}
