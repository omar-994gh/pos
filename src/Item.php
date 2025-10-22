<?php
class Item
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Get all items in a specific group */
    public function allByGroup(int $groupId): array
    {
        $stmt = $this->db->prepare('
            SELECT i.*, g.name AS group_name, i.group_id
            FROM Items i
            JOIN Groups g ON i.group_id = g.id
            WHERE g.id = :gid
            ORDER BY i.name_ar
        ');
        $stmt->execute([':gid' => $groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Find item by id */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM Items WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Create item */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO Items (name_ar, name_en, barcode, group_id, price, stock, unit, currency)
            VALUES (:name_ar, :name_en, :barcode, :group_id, :price, :stock, :unit, :currency)
        ');
        return $stmt->execute([
            ':name_ar'  => $data['name_ar'],
            ':name_en'  => $data['name_en'],
            ':barcode'  => $data['barcode'],
            ':group_id' => $data['group_id'],
            ':price'    => $data['price'],
            ':stock'    => $data['stock'],
            ':unit'     => $data['unit'],
            ':currency' => $data['currency'] ?? 'SYP',
        ]);
    }

    /** Update item */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE Items
            SET name_ar = :name_ar,
                name_en = :name_en,
                barcode = :barcode,
                group_id = :group_id,
                price = :price,
                stock = :stock,
                unit = :unit,
                currency = :currency
            WHERE id = :id
        ');
        return $stmt->execute([
            ':name_ar'  => $data['name_ar'],
            ':name_en'  => $data['name_en'],
            ':barcode'  => $data['barcode'],
            ':group_id' => $data['group_id'],
            ':price'    => $data['price'],
            ':stock'    => $data['stock'],
            ':unit'     => $data['unit'],
            ':currency' => $data['currency'] ?? 'SYP',
            ':id'       => $id,
        ]);
    }

    /** Delete item */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Items WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function allWithStock(): array {
        $stmt = $this->db->query("
            SELECT id, name_ar, name_en, stock, unit
            FROM Items
            ORDER BY name_ar
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allWithStockAndGroup(): array {
        $stmt = $this->db->query("
            SELECT i.id, i.name_ar, i.name_en, i.stock, i.unit, i.price, i.currency, g.name AS group_name
            FROM Items i
            LEFT JOIN Groups g ON g.id = i.group_id
            ORDER BY i.name_ar
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
