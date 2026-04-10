<?php
/**
 * Base Model
 * Tất cả models sẽ kế thừa từ class này
 */
class BaseModel {
    protected $mysqli;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Get all records
     */
    public function getAll($where = '', $orderBy = '', $limit = '') {
        $query = "SELECT * FROM {$this->table}";
        if ($where) {
            $query .= " WHERE {$where}";
        }
        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }
        if ($limit) {
            $query .= " LIMIT {$limit}";
        }
        
        $result = $this->mysqli->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    /**
     * Get one record by ID
     */
    public function getById($id) {
        $stmt = $this->mysqli->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? AND deleted IS NULL");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        
        $types = '';
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            $stmt->close();
            return $id;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $set = [];
        $values = [];
        $types = '';
        
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
            $values[] = $value;
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $values[] = $id;
        $types .= 'i';
        
        $setClause = implode(', ', $set);
        $query = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ? AND deleted IS NULL";
        
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param($types, ...$values);
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Delete record (soft delete)
     */
    public function delete($id) {
        $stmt = $this->mysqli->prepare("UPDATE {$this->table} SET deleted = NOW() WHERE {$this->primaryKey} = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Count records
     */
    public function count($where = '') {
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($where) {
            $query .= " WHERE {$where}";
        }
        
        $result = $this->mysqli->query($query);
        $row = $result->fetch_assoc();
        return intval($row['total'] ?? 0);
    }
}









