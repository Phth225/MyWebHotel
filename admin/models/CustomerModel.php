<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Customer Model
 */
class CustomerModel extends BaseModel {
    protected $table = 'customer';
    protected $primaryKey = 'customer_id';
    
    /**
     * Get customers with booking count
     */
    public function getCustomersWithStats($where = '', $orderBy = 'customer_id DESC', $limit = '') {
        $query = "
            SELECT c.*,
                   COUNT(DISTINCT b.booking_id) as total_bookings,
                   COUNT(DISTINCT bs.booking_service_id) as total_service_bookings
            FROM {$this->table} c
            LEFT JOIN booking b ON c.customer_id = b.customer_id AND b.deleted IS NULL
            LEFT JOIN booking_service bs ON c.customer_id = bs.customer_id AND bs.deleted IS NULL
            WHERE c.deleted IS NULL
        ";
        
        if ($where) {
            $query .= " AND {$where}";
        }
        
        $query .= " GROUP BY c.customer_id";
        
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
     * Search customers
     */
    public function search($keyword) {
        $keyword = "%{$keyword}%";
        $stmt = $this->mysqli->prepare("
            SELECT * FROM {$this->table}
            WHERE deleted IS NULL
            AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)
            ORDER BY full_name
            LIMIT 50
        ");
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        $stmt->close();
        return $customers;
    }
}









