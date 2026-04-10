<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Review Model
 */
class ReviewModel extends BaseModel {
    protected $table = 'review';
    protected $primaryKey = 'review_id';
    
    /**
     * Get reviews with customer and room info
     */
    public function getReviewsWithDetails($where = '', $orderBy = 'review_id DESC', $limit = '') {
        $query = "
            SELECT r.*,
                   c.full_name, c.email,
                   bk.booking_id,
                   rm.room_number,
                   rt.room_type_name
            FROM {$this->table} r
            LEFT JOIN customer c ON r.customer_id = c.customer_id
            LEFT JOIN booking bk ON r.booking_id = bk.booking_id
            LEFT JOIN room rm ON bk.room_id = rm.room_id
            LEFT JOIN room_type rt ON rm.room_type_id = rt.room_type_id
            WHERE r.deleted IS NULL
        ";
        
        if ($where) {
            $query .= " AND {$where}";
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
     * Get review statistics
     */
    public function getStatistics($where = '') {
        $query = "
            SELECT 
                COUNT(*) as total,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected
            FROM {$this->table}
            WHERE deleted IS NULL
        ";
        
        if ($where) {
            $query .= " AND {$where}";
        }
        
        $result = $this->mysqli->query($query);
        return $result ? $result->fetch_assoc() : [];
    }
}









