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
     * يقوم بتوحيد العملات باستخدام أسعار التعادل من الإعدادات
     *
     * @param string|null $dateFrom 'YYYY-MM-DD'
     * @param string|null $dateTo   'YYYY-MM-DD'
     * @param int|null $userId
     * @return array Each row: [user_id, username, sale_count, total_amount, avg_amount]
     */
    public function summary(?string $dateFrom, ?string $dateTo, ?int $userId = null): array
    {
        // Get exchange rate settings
        $settingsStmt = $this->db->query("SELECT base_currency, usd_to_syp_rate FROM System_Settings ORDER BY id DESC LIMIT 1");
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
        $baseCurrency = $settings['base_currency'] ?? 'SYP';
        $usdToSypRate = (float)($settings['usd_to_syp_rate'] ?? 15000.0);
        
        // Build conversion logic based on base currency
        if ($baseCurrency === 'SYP') {
            $convertExpression = "
                CASE 
                    WHEN i.currency = 'USD' THEN oi.quantity * oi.unit_price * $usdToSypRate
                    ELSE oi.quantity * oi.unit_price
                END
            ";
        } else { // USD
            $convertExpression = "
                CASE 
                    WHEN i.currency = 'SYP' THEN oi.quantity * oi.unit_price / $usdToSypRate
                    ELSE oi.quantity * oi.unit_price
                END
            ";
        }
        
        $sql = "
            SELECT u.id AS user_id,
                   u.username,
                   COUNT(DISTINCT o.id) AS sale_count,
                   SUM($convertExpression) AS total_amount,
                   SUM($convertExpression) / COUNT(DISTINCT o.id) AS avg_amount
              FROM Orders o
              JOIN Users u ON o.user_id = u.id
              JOIN Order_Items oi ON oi.order_id = o.id
              JOIN Items i ON i.id = oi.item_id
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
     * جلب تفاصيل الفواتير مع تحويل العملات
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param int|null $userId
     * @return array Each row: [order_id, created_at, total, username]
     */
    public function details(?string $dateFrom, ?string $dateTo, ?int $userId = null): array
    {
        // Get exchange rate settings
        $settingsStmt = $this->db->query("SELECT base_currency, usd_to_syp_rate FROM System_Settings ORDER BY id DESC LIMIT 1");
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
        $baseCurrency = $settings['base_currency'] ?? 'SYP';
        $usdToSypRate = (float)($settings['usd_to_syp_rate'] ?? 15000.0);
        
        // Build conversion logic based on base currency
        if ($baseCurrency === 'SYP') {
            $convertExpression = "
                CASE 
                    WHEN i.currency = 'USD' THEN oi.quantity * oi.unit_price * $usdToSypRate
                    ELSE oi.quantity * oi.unit_price
                END
            ";
        } else { // USD
            $convertExpression = "
                CASE 
                    WHEN i.currency = 'SYP' THEN oi.quantity * oi.unit_price / $usdToSypRate
                    ELSE oi.quantity * oi.unit_price
                END
            ";
        }
        
        $sql = "
            SELECT o.id AS order_id,
                   o.created_at,
                   SUM($convertExpression) AS total,
                   u.username
              FROM Orders o
              JOIN Users u ON o.user_id = u.id
              JOIN Order_Items oi ON oi.order_id = o.id
              JOIN Items i ON i.id = oi.item_id
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
        $sql .= " GROUP BY o.id, o.created_at, u.username ORDER BY o.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
