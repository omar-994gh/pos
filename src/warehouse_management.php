<?php
// src/Warehouse.php

class Warehouse
{
    /** @var PDO */
    private $db;

    /**
     * Warehouse constructor.
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * جلب جميع المخازن
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT id, name FROM Warehouses ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * جلب مخزن حسب المعرف
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, name FROM Warehouses WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * إنشاء مخزن جديد
     * @param string $name
     * @return bool
     */
    public function create(string $name): bool
    {
        $stmt = $this->db->prepare("INSERT INTO Warehouses (name) VALUES (:name)");
        return $stmt->execute([':name' => $name]);
    }

    /**
     * تحديث اسم مخزن
     * @param int    $id
     * @param string $name
     * @return bool
     */
    public function update(int $id, string $name): bool
    {
        $stmt = $this->db->prepare("UPDATE Warehouses SET name = :name WHERE id = :id");
        return $stmt->execute([':name' => $name, ':id' => $id]);
    }

    /**
     * حذف مخزن
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Warehouses WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
