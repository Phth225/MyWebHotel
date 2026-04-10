<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Booking Model
 * Xử lý các thao tác với bảng booking
 */
class BookingModel extends BaseModel {
    protected $table = 'booking';
    protected $primaryKey = 'booking_id';
    
    /**
     * Get bookings with customer and room info
     */
    public function getBookingsWithDetails($where = '', $orderBy = 'booking_id DESC', $limit = '') {
        $query = "
            SELECT b.*, 
                   COALESCE(c.full_name, w.full_name) as full_name,
                   COALESCE(c.phone, w.phone) as phone,
                   COALESCE(c.email, w.email) as email,
                   CASE WHEN b.walk_in_guest_id IS NOT NULL THEN 'Walk-in' ELSE 'Registered' END as guest_type,
                   r.room_number, rt.room_type_name, rt.base_price
            FROM {$this->table} b
            LEFT JOIN customer c ON b.customer_id = c.customer_id
            LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
            LEFT JOIN room r ON b.room_id = r.room_id
            LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
            WHERE b.deleted IS NULL
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
    public function checkAvailability($room_id, $check_in_date, $check_out_date, $exclude_booking_id = null) {
        $query = "
            SELECT COUNT(*) as count 
            FROM {$this->table}
            WHERE room_id = ? 
            AND status NOT IN ('Cancelled', 'Completed')
            AND deleted IS NULL
            AND (
                (check_in_date <= ? AND check_out_date >= ?) OR
                (check_in_date <= ? AND check_out_date >= ?) OR
                (check_in_date >= ? AND check_out_date <= ?)
            )
        ";
        
        if ($exclude_booking_id) {
            $query .= " AND booking_id != ?";
        }
        
        $stmt = $this->mysqli->prepare($query);
        
        if ($exclude_booking_id) {
            $stmt->bind_param("issssssi", $room_id, $check_in_date, $check_in_date, $check_out_date, $check_out_date, $check_in_date, $check_out_date, $exclude_booking_id);
        } else {
            $stmt->bind_param("issssss", $room_id, $check_in_date, $check_in_date, $check_out_date, $check_out_date, $check_in_date, $check_out_date);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return intval($row['count'] ?? 0) > 0;
    }
    
    /**
     * Create multiple bookings (for multiple rooms)
     */
    public function createMultiple($room_ids, $booking_data) {
        $created_ids = [];
        $errors = [];
        
        foreach ($room_ids as $room_id) {
            // Check availability
            if ($this->checkAvailability($room_id, $booking_data['check_in_date'], $booking_data['check_out_date'])) {
                $errors[] = "Phòng ID {$room_id} đã được đặt";
                continue;
            }
            
            $data = array_merge($booking_data, ['room_id' => $room_id]);
            $id = $this->create($data);
            
            if ($id) {
                $created_ids[] = $id;
            } else {
                $errors[] = "Không thể tạo booking cho phòng ID {$room_id}";
            }
        }
        
        return [
            'success' => count($created_ids) > 0,
            'created_ids' => $created_ids,
            'errors' => $errors
        ];
    }
}









