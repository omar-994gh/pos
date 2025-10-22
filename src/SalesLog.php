<?php
// src/SalesLog.php

class SalesLog
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * جلب ملخص الإحصاءات لكل مستخدم في الفترة المحددة
     *
     * @param string|null $dateFrom 'YYYY-MM-DD'
     * @param string|null $dateTo   'YYYY-MM-DD'
     * @param int|null $userId
     * @return array Each row: [user_id, username, sale_count, total_amount, avg_amount]
     */
    public function summary(?string $dateFrom, ?string $dateTo, ?int $userId = null): array
    {
        $sql = "
            SELECT u.id AS user_id,
                   u.username,
                   COUNT(o.id)       AS sale_count,
                   SUM(o.total)      AS total_amount,
                   AVG(o.total)      AS avg_amount
              FROM Orders o
              JOIN Users u ON o.user_id = u.id
             WHERE 1=1
        ";
        $params = [];
        if ($dateFrom) {
            $sql .= " AND DATE(o.created_at) >= :from";
            $params[':from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(o.created_at) <= :to";
            $params[':to'] = $dateTo;
        }
        if ($userId) {
            $sql .= " AND o.user_id = :userId";
            $params[':userId'] = $userId;
        }
        $sql .= " GROUP BY u.id, u.username ORDER BY total_amount DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * جلب تفاصيل الفواتير
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param int|null $userId
     * @return array Each row: [order_id, created_at, total, username]
     */
    public function details(?string $dateFrom, ?string $dateTo, ?int $userId = null): array
    {
        $sql = "
            SELECT o.id AS order_id,
                   o.created_at,
                   o.total,
                   u.username
              FROM Orders o
              JOIN Users u ON o.user_id = u.id
             WHERE 1=1
        ";
        $params = [];
        if ($dateFrom) {
            $sql .= " AND DATE(o.created_at) >= :from";
            $params[':from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(o.created_at) <= :to";
            $params[':to'] = $dateTo;
        }
        if ($userId) {
            $sql .= " AND o.user_id = :userId";
            $params[':userId'] = $userId;
        }
        $sql .= " ORDER BY o.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
