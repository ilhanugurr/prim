<?php
/**
 * Primew Panel - Veritabanı Bağlantı Dosyası
 * MySQL veritabanı bağlantı ayarları
 */

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'primew');
define('DB_USER', 'root');
define('DB_PASS', '123456');
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
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Türkçe karakter desteği için
            $this->connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->connection->exec("SET CHARACTER SET utf8mb4");
            
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
    
    // Genel CRUD işlemleri
    public function select($table, $where = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM $table";
        $params = [];
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "$key = :$key";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->connection->prepare($sql);
        
        if ($stmt->execute($data)) {
            return $this->connection->lastInsertId();
        }
        return false;
    }
    
    public function update($table, $data, $where) {
        $setClause = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :set_$key";
            $params["set_$key"] = $value;
        }
        
        $whereClause = [];
        foreach ($where as $key => $value) {
            $whereClause[] = "$key = :where_$key";
            $params["where_$key"] = $value;
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);
        $stmt = $this->connection->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    public function delete($table, $where) {
        $whereClause = [];
        $params = [];
        
        foreach ($where as $key => $value) {
            $whereClause[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereClause);
        $stmt = $this->connection->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }
}

// Veritabanı bağlantısını başlat
$db = Database::getInstance();
$pdo = $db->getConnection();

// Yardımcı fonksiyonlar
function getFirmalar() {
    global $db;
    return $db->select('firmalar', [], 'firma_adi ASC');
}

function getPersonel() {
    global $db;
    return $db->query("
        SELECT * FROM personel 
        WHERE durum = 'aktif' 
        ORDER BY ad_soyad ASC
    ");
}

function getUrunHizmet() {
    global $db;
    return $db->query("
        SELECT u.*, f.firma_adi 
        FROM urun_hizmet u 
        LEFT JOIN firmalar f ON u.firma_id = f.id 
        ORDER BY u.urun_adi ASC
    ");
}

function getYapilar() {
    global $db;
    return $db->select('yapilar', [], 'yapi_adi ASC');
}

function getMailAyarlari() {
    global $db;
    return $db->select('mail_ayarlari', ['durum' => 'aktif']);
}

function getChecklist() {
    global $db;
    return $db->select('checklist', [], 'oncelik DESC, son_tarih ASC');
}

// Komisyon fonksiyonları
function getFirmaKomisyonlar($firma_id) {
    global $db;
    return $db->select('firma_komisyon', ['firma_id' => $firma_id], 'min_fiyat ASC');
}

function getFirmaKomisyonDetay($firma_id) {
    global $db;
    return $db->query("
        SELECT fk.*, f.firma_adi 
        FROM firma_komisyon fk 
        LEFT JOIN firmalar f ON fk.firma_id = f.id 
        WHERE fk.firma_id = ? 
        ORDER BY fk.min_fiyat ASC
    ", [$firma_id]);
}

function addFirmaKomisyon($data) {
    global $db;
    return $db->insert('firma_komisyon', $data);
}

function updateFirmaKomisyon($data, $id) {
    global $db;
    return $db->update('firma_komisyon', $data, ['id' => $id]);
}

function deleteFirmaKomisyon($id) {
    global $db;
    return $db->delete('firma_komisyon', ['id' => $id]);
}

// Müşteri fonksiyonları
function getMusteriler() {
    global $db;
    return $db->select('musteriler', [], 'firma_adi ASC');
}

function getMusteri($id) {
    global $db;
    return $db->select('musteriler', ['id' => $id])[0] ?? null;
}

function addMusteri($data) {
    global $db;
    return $db->insert('musteriler', $data);
}

function updateMusteri($data, $id) {
    global $db;
    return $db->update('musteriler', $data, ['id' => $id]);
}

function deleteMusteri($id) {
    global $db;
    return $db->delete('musteriler', ['id' => $id]);
}

// İstatistik fonksiyonları
function getStats() {
    global $db;
    
    $stats = [
        'firmalar' => $db->query("SELECT COUNT(*) as count FROM firmalar WHERE durum = 'aktif'")[0]['count'],
        'personel' => $db->query("SELECT COUNT(*) as count FROM personel WHERE durum = 'aktif'")[0]['count'],
        'urun_hizmet' => $db->query("SELECT COUNT(*) as count FROM urun_hizmet WHERE durum = 'aktif'")[0]['count'],
        'satislar' => $db->query("SELECT COUNT(*) as count FROM satislar")[0]['count'],
        'musteriler' => $db->query("SELECT COUNT(*) as count FROM musteriler WHERE durum = 'aktif'")[0]['count'],
        'hedefler' => $db->query("SELECT COUNT(*) as count FROM hedefler WHERE durum = 'aktif'")[0]['count']
    ];
    
    return $stats;
}

// Hedef fonksiyonları
function getHedefler() {
    global $db;
    return $db->query("
        SELECT h.*, p.ad_soyad as personel_adi, f.firma_adi 
        FROM hedefler h 
        LEFT JOIN personel p ON h.personel_id = p.id 
        LEFT JOIN firmalar f ON h.firma_id = f.id 
        WHERE h.durum = 'aktif' 
        ORDER BY h.yil DESC, h.ay DESC, p.ad_soyad ASC
    ");
}

function getHedef($id) {
    global $db;
    return $db->query("
        SELECT h.*, p.ad_soyad as personel_adi, f.firma_adi 
        FROM hedefler h 
        LEFT JOIN personel p ON h.personel_id = p.id 
        LEFT JOIN firmalar f ON h.firma_id = f.id 
        WHERE h.id = ? AND h.durum = 'aktif'
    ", [$id])[0] ?? null;
}

function addHedef($data) {
    global $db;
    return $db->insert('hedefler', $data);
}

function updateHedef($data, $id) {
    global $db;
    return $db->update('hedefler', $data, ['id' => $id]);
}

function deleteHedef($id) {
    global $db;
    return $db->delete('hedefler', ['id' => $id]);
}
?>
