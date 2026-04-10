<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Room Model
 */
class RoomModel extends BaseModel {
    protected $table = 'room';
    protected $primaryKey = 'room_id';
    
    /**
     * Get rooms with type info
     */
    public function getRoomsWithType($where = '', $orderBy = 'room_id ASC', $limit = '') {
        $query = "
            SELECT r.*,
                   rt.room_type_name,
                   rt.base_price,
                   rt.description AS type_description,
                   rt.capacity AS type_capacity,
                   rt.amenities
            FROM {$this->table} r
            LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
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
     * Check room availability
     */
    public function checkAvailability($room_id, $check_in, $check_out, $exclude_booking_id = null) {
        $query = "
            SELECT COUNT(*) as count
            FROM booking b
            WHERE b.room_id = ?
            AND b.status NOT IN ('Cancelled', 'Completed')
            AND b.deleted IS NULL
            AND (
                (b.check_in_date <= ? AND b.check_out_date >= ?) OR
                (b.check_in_date <= ? AND b.check_out_date >= ?) OR
                (b.check_in_date >= ? AND b.check_out_date <= ?)
            )
        ";
        
        if ($exclude_booking_id) {
            $query .= " AND b.booking_id != ?";
        }
        
        $stmt = $this->mysqli->prepare($query);
        
        if ($exclude_booking_id) {
            $stmt->bind_param("issssssi", $room_id, $check_in, $check_in, $check_out, $check_out, $check_in, $check_out, $exclude_booking_id);
        } else {
            $stmt->bind_param("issssss", $room_id, $check_in, $check_in, $check_out, $check_out, $check_in, $check_out);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return intval($row['count'] ?? 0) === 0;
    }
}









