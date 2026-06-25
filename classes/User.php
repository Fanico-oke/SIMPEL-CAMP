<?php
// classes/User.php
// Model untuk tabel users

require_once dirname(__DIR__) . '/config/database.php';

class User {

    /**
     * Register user baru
     * @return int|false User ID atau false jika gagal
     */
    public static function register($data) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO users (nama, email, password, no_telp, alamat, role, status, created_at)
                VALUES (:nama, :email, :password, :no_telp, :alamat, :role, 'aktif', NOW())
            ");
            $stmt->execute([
                ':nama'     => $data['nama'],
                ':email'    => $data['email'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':no_telp'  => $data['no_telp'] ?? null,
                ':alamat'   => $data['alamat'] ?? null,
                ':role'     => $data['role'] ?? 'pelanggan'
            ]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("User::register error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Login: cek email & password
     * @return array|false User data (tanpa password) atau false
     */
    public static function login($email, $password) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("User::login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil user berdasarkan ID
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id, nama, email, no_telp, alamat, foto, role, status, created_at, updated_at, last_login FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("User::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil semua user dengan filter opsional
     */
    public static function getAll($role = null, $search = null, $limit = 20, $offset = 0) {
        try {
            $db = Database::getInstance();
            $where = [];
            $params = [];

            if ($role) {
                $where[] = "role = :role";
                $params[':role'] = $role;
            }
            if ($search) {
                $where[] = "(nama LIKE :search OR email LIKE :search2)";
                $params[':search'] = "%{$search}%";
                $params[':search2'] = "%{$search}%";
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $db->prepare("
                SELECT id, nama, email, no_telp, alamat, foto, role, status, created_at, last_login
                FROM users
                {$whereClause}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("User::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update data user
     */
    public static function update($id, $data) {
        try {
            $db = Database::getInstance();
            $fields = [];
            $params = [':id' => $id];

            $allowed = ['nama', 'email', 'no_telp', 'alamat', 'foto', 'role', 'status'];
            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $fields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }

            if (empty($fields)) return false;

            $stmt = $db->prepare("UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id");
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("User::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ganti password dengan verifikasi password lama
     */
    public static function updatePassword($id, $oldPassword, $newPassword) {
        try {
            $db = Database::getInstance();

            // Ambil password lama
            $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($oldPassword, $user['password'])) {
                return false;
            }

            $stmt = $db->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
            return $stmt->execute([
                ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("User::updatePassword error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle status aktif/nonaktif
     */
    public static function toggleStatus($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE users 
                SET status = IF(status = 'aktif', 'nonaktif', 'aktif'), updated_at = NOW()
                WHERE id = :id
            ");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("User::toggleStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update timestamp last_login
     */
    public static function updateLastLogin($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("User::updateLastLogin error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hitung jumlah user berdasarkan role
     */
    public static function countByRole($role = null) {
        try {
            $db = Database::getInstance();
            if ($role) {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = :role");
                $stmt->execute([':role' => $role]);
            } else {
                $stmt = $db->query("SELECT COUNT(*) as total FROM users");
            }
            $result = $stmt->fetch();
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("User::countByRole error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cek apakah email sudah digunakan
     */
    public static function emailExists($email, $excludeId = null) {
        try {
            $db = Database::getInstance();
            if ($excludeId) {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE email = :email AND id != :id");
                $stmt->execute([':email' => $email, ':id' => $excludeId]);
            } else {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE email = :email");
                $stmt->execute([':email' => $email]);
            }
            $result = $stmt->fetch();
            return (int)$result['total'] > 0;
        } catch (PDOException $e) {
            error_log("User::emailExists error: " . $e->getMessage());
            return false;
        }
    }
}
