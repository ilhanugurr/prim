<?php
/**
 * Primew Panel - Host Veritabanı Bağlantı Dosyası
 * Host için veritabanı ayarları
 */

// Host veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'seomewco_prim');
define('DB_USER', 'seomewco_prim');
define('DB_PASS', '6EsXGPBckD9c8Kr4MDFW');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Query Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function insert($table, $data) {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($data);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database Insert Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($table, $data, $where) {
        try {
            $set = [];
            foreach ($data as $key => $value) {
                $set[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $set);
            
            $whereClause = [];
            $whereParams = [];
            foreach ($where as $key => $value) {
                $whereClause[] = "{$key} = :where_{$key}";
                $whereParams["where_{$key}"] = $value;
            }
            $whereClause = implode(' AND ', $whereClause);
            
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
            $params = array_merge($data, $whereParams);
            
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database Update Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($table, $where) {
        try {
            $whereClause = [];
            $whereParams = [];
            foreach ($where as $key => $value) {
                $whereClause[] = "{$key} = :{$key}";
                $whereParams[$key] = $value;
            }
            $whereClause = implode(' AND ', $whereClause);
            
            $sql = "DELETE FROM {$table} WHERE {$whereClause}";
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($whereParams);
        } catch (PDOException $e) {
            error_log("Database Delete Error: " . $e->getMessage());
            return false;
        }
    }
}

// Global database instance
$db = Database::getInstance();
?>
