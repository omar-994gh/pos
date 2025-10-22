<?php
class Group
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Retrieve all groups with optional attached printer name */
    public function all(): array
    {
        $stmt = $this->db->query('
            SELECT g.id, g.name, g.printer_id, g.visible, p.name AS printer_name
            FROM Groups g
            LEFT JOIN Printers p ON g.printer_id = p.id
            ORDER BY g.name
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Retrieve a group by id */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM Groups WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Create a new group */
    public function create(string $name, ?int $printerId, int $visible = 1): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO Groups (name, printer_id, visible) VALUES (:name, :printer_id, :visible)'
        );
        return $stmt->execute([
            ':name'       => $name,
            ':printer_id' => $printerId,
            ':visible'    => $visible,
        ]);
    }

    /** Update a group */
    public function update(int $id, string $name, ?int $printerId, ?int $visible = null): bool
    {
        if ($visible !== null) {
            $stmt = $this->db->prepare(
                'UPDATE Groups SET name = :name, printer_id = :printer_id, visible = :visible WHERE id = :id'
            );
            return $stmt->execute([
                ':name'       => $name,
                ':printer_id' => $printerId,
                ':visible'    => $visible,
                ':id'         => $id,
            ]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE Groups SET name = :name, printer_id = :printer_id WHERE id = :id'
            );
            return $stmt->execute([
                ':name'       => $name,
                ':printer_id' => $printerId,
                ':id'         => $id,
            ]);
        }
    }

    /** Delete a group */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Groups WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /** Toggle group visibility */
    public function toggleVisibility(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE Groups SET visible = CASE WHEN visible = 1 THEN 0 ELSE 1 END WHERE id = :id'
        );
        return $stmt->execute([':id' => $id]);
    }

    /** Get visible groups only */
    public function visible(): array
    {
        $stmt = $this->db->query('
            SELECT g.id, g.name, g.printer_id, g.visible, p.name AS printer_name
            FROM Groups g
            LEFT JOIN Printers p ON g.printer_id = p.id
            WHERE g.visible = 1
            ORDER BY g.name
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
