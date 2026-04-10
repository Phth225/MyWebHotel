<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Room Type Model
 */
class RoomTypeModel extends BaseModel {
    protected $table = 'room_type';
    protected $primaryKey = 'room_type_id';
    
    /**
     * Get room types with room count
     */
    public function getRoomTypesWithCount($where = '', $orderBy = 'room_type_id ASC', $limit = '') {
        $query = "
            SELECT rt.*,
                   COUNT(r.room_id) as total_rooms,
                   COUNT(CASE WHEN r.status = 'Available' THEN 1 END) as available_rooms
            FROM {$this->table} rt
            LEFT JOIN room r ON rt.room_type_id = r.room_type_id AND r.deleted IS NULL
            WHERE rt.deleted IS NULL
        ";
        
        if ($where) {
            $query .= " AND {$where}";
        }
        
        $query .= " GROUP BY rt.room_type_id";
        
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









