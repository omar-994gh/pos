<?php
class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * جلب جميع المستخدمين
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT id, username, role, created_at FROM Users ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * جلب مستخدم واحد بالمعرف
     * @param int $id
     * @return array|false
     */
    public function find(int $id)
    {
        $stmt = $this->db->prepare('SELECT id, username, role, created_at FROM Users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * تحديث بيانات المستخدم (باستثناء كلمة المرور)
     * @param int    $id
     * @param string $username
     * @param string $role
     * @return bool
     */
    public function update(int $id, string $username, string $role): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE Users SET username = :username, role = :role WHERE id = :id'
        );
        return $stmt->execute([
            ':username' => $username,
            ':role'     => $role,
            ':id'       => $id,
        ]);
    }

    /**
     * حذف مستخدم
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
