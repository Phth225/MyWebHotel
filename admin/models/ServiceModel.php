<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Service Model
 */
class ServiceModel extends BaseModel {
    protected $table = 'service';
    protected $primaryKey = 'service_id';
    
    /**
     * Get services with usage count
     */
    public function getServicesWithStats($where = '', $orderBy = 'service_id DESC', $limit = '') {
        $query = "
            SELECT s.*,
                   COUNT(bs.booking_service_id) as total_bookings,
                   SUM(bs.amount) as total_revenue
            FROM {$this->table} s
            LEFT JOIN booking_service bs ON s.service_id = bs.service_id AND bs.deleted IS NULL
            WHERE s.deleted IS NULL
        ";
        
        if ($where) {
            $query .= " AND {$where}";
        }
        
        $query .= " GROUP BY s.service_id";
        
        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $query .= " LIMIT {$limit}";
        }
        
        $result = $this->mysqli->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}









