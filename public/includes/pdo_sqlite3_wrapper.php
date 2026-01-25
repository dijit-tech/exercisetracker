<?php
/**
 * SQLite3 PDO Wrapper
 * Allows using PDO syntax (prepare, execute, fetch) with the native SQLite3 class.
 * Used when pdo_sqlite extension is missing but sqlite3 extension is present.
 */

class PdoSqlite3Wrapper {
    private $db;
    
    public function __construct($path) {
        // Strip 'sqlite:' prefix if present
        $path = str_replace('sqlite:', '', $path);
        
        $this->db = new SQLite3($path);
        $this->db->enableExceptions(true);
    }
    
    public function setAttribute($attr, $val) {
        // No-op for compatibility
        return true;
    }
    
    public function exec($query) {
        return $this->db->exec($query);
    }
    
    public function prepare($query) {
        return new PdoSqlite3Statement($this->db, $query);
    }
    
    public function lastInsertId() {
        return $this->db->lastInsertRowID();
    }
}

class PdoSqlite3Statement {
    private $db;
    private $sql;
    private $stmt;
    private $result;
    
    public function __construct($db, $sql) {
        $this->db = $db;
        
        // Convert PDO named parameters (:name) to SQLite3 format (still :name)
        // Convert PDO positional (?) to SQLite3 format
        // SQLite3 supports both, but binding logic differs slightly.
        $this->sql = $sql;
        $this->stmt = $this->db->prepare($sql);
    }
    
    public function execute($params = []) {
        if (!empty($params)) {
             $index = 1; // SQLite3 binds ? starting at 1
             foreach ($params as $key => $value) {
                 if (is_int($key)) {
                     // Positional parameter (?)
                     $this->stmt->bindValue($index++, $value);
                 } else {
                     // Named parameter (:name)
                     $this->stmt->bindValue($key, $value);
                 }
             }
        }
        
        $this->result = $this->stmt->execute();
        return $this->result !== false;
    }
    
    public function fetch($mode = PDO::FETCH_ASSOC) {
        if (!$this->result) return false;
        return $this->result->fetchArray($mode === PDO::FETCH_ASSOC ? SQLITE3_ASSOC : SQLITE3_NUM);
    }
    
    public function fetchAll($mode = PDO::FETCH_ASSOC) {
        if (!$this->result) return [];
        $rows = [];
        while ($row = $this->result->fetchArray($mode === PDO::FETCH_ASSOC ? SQLITE3_ASSOC : SQLITE3_NUM)) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function fetchColumn($columnNumber = 0) {
        if (!$this->result) return false;
        $row = $this->result->fetchArray(SQLITE3_NUM);
        return $row ? $row[$columnNumber] : false;
    }

    public function rowCount() {
        // SQLite3 doesn't easily support rowCount for SELECTs
        // But for INSERT/UPDATE it works via changes() on db, but strictly speaking it returns changed rows
        return 0; 
    }
}
?>