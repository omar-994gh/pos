<?php
class Auth
{
    /**
     * محاولة تسجيل الدخول
     * @param PDO    $db
     * @param string $username
     * @param string $password
     * @return array|false  بيانات المستخدم في حال النجاح، أو false عند الفشل
     */
    public static function login(PDO $db, string $username, string $password)
    {
        $stmt = $db->prepare('SELECT id, username, password_hash, role FROM Users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // تخزين بيانات المستخدم في الجلسة
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            return [
                'id'   => (int)$user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
            ];
        }
        return false;
    }

    /**
     * تسجيل مستخدم جديد
     * @param PDO    $db
     * @param string $username
     * @param string $password
     * @param string $role     ('Admin' أو 'Cashier')
     * @return bool|string     true عند النجاح، أو رسالة خطأ
     */
    public static function register(PDO $db, string $username, string $password, string $role = 'Cashier')
    {
        // تحقق من وجود المستخدم مسبقاً
        $stmt = $db->prepare('SELECT COUNT(*) FROM Users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        if ($stmt->fetchColumn() > 0) {
            return 'اسم المستخدم موجود بالفعل.';
        }

        // إنشاء الهاش
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            'INSERT INTO Users (username, password_hash, role) 
             VALUES (:username, :hash, :role)'
        );
        $success = $stmt->execute([
            ':username' => $username,
            ':hash'     => $hash,
            ':role'     => $role,
        ]);

        return $success ? true : 'حدث خطأ أثناء التسجيل.';
    }

    /**
     * تسجيل الخروج
     */
    public static function logout()
    {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }

    /**
     * هل المستخدم مسجل دخول؟
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * فرض تسجيل الدخول قبل الوصول للصفحة
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * هل المستخدم مشرف (Admin)؟
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::isLoggedIn() && ($_SESSION['role'] === 'Admin');
    }

    /**
     * هل المستخدم كاشير (Cashier)؟
     * @return bool
     */
    public static function isCashier(): bool
    {
        return self::isLoggedIn() && ($_SESSION['role'] === 'Cashier');
    }

    /**
     * التحقق من صلاحية محددة للمستخدم
     * @param PDO $db
     * @param string $elementKey
     * @return bool
     */
    public static function hasPrivilege(PDO $db, string $elementKey): bool
    {
        if (self::isAdmin()) {
            return true; // Admin has all privileges
        }
        
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $stmt = $db->prepare('SELECT COUNT(*) FROM Authorizations WHERE user_id = :uid AND element_key = :ek AND is_enabled = 1');
        $stmt->execute([':uid' => $_SESSION['user_id'], ':ek' => $elementKey]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * التحقق من صلاحية تعديل السعر
     * @param PDO $db
     * @return bool
     */
    public static function canEditPrice(PDO $db): bool
    {
        return self::hasPrivilege($db, 'input_edit_price');
    }

    /**
     * التحقق من صلاحية إضافة خصم
     * @param PDO $db
     * @return bool
     */
    public static function canAddDiscount(PDO $db): bool
    {
        return self::hasPrivilege($db, 'input_discount');
    }
}
