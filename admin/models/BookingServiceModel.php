<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Booking Service Model
 * Xử lý các thao tác với bảng booking_service
 */
class BookingServiceModel extends BaseModel {
    protected $table = 'booking_service';
    protected $primaryKey = 'booking_service_id';
    
    /**
     * Get booking services with details
     */
    public function getBookingServicesWithDetails($where = '', $orderBy = 'booking_service_id DESC', $limit = '') {
        $query = "
            SELECT bs.*,
                   s.service_name, s.service_type, s.price,
                   COALESCE(c.full_name, w.full_name) as full_name,
                   COALESCE(c.phone, w.phone) as phone,
                   COALESCE(c.email, w.email) as email,
                   CASE WHEN bs.customer_id IS NULL THEN 'Walk-in' ELSE 'Registered' END as guest_type,
                   b.booking_id, b.check_in_date, b.check_out_date, b.walk_in_guest_id,
                   r.room_number, rt.room_type_name
            FROM {$this->table} bs
            LEFT JOIN service s ON bs.service_id = s.service_id
            LEFT JOIN customer c ON bs.customer_id = c.customer_id AND bs.customer_id IS NOT NULL
            LEFT JOIN booking b ON bs.booking_id = b.booking_id
            LEFT JOIN walk_in_guest w ON (b.walk_in_guest_id = w.id OR (bs.customer_id IS NULL AND b.walk_in_guest_id IS NOT NULL AND b.walk_in_guest_id = w.id))
            LEFT JOIN room r ON b.room_id = r.room_id
            LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
            WHERE bs.deleted IS NULL
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
     * Create multiple booking services
     */
    public function createMultiple($services_data, $customer_id, $booking_id = null) {
        $created_ids = [];
        $errors = [];
        
        foreach ($services_data as $service_data) {
            // Get service price and unit
            $service_stmt = $this->mysqli->prepare("SELECT price, unit FROM service WHERE service_id = ?");
            $service_stmt->bind_param("i", $service_data['service_id']);
            $service_stmt->execute();
            $service_result = $service_stmt->get_result();
            $service_info = $service_result->fetch_assoc();
            $service_stmt->close();
            
            if (!$service_info) {
                $errors[] = "Không tìm thấy dịch vụ ID {$service_data['service_id']}";
                continue;
            }
            
            $data = [
                'customer_id' => $customer_id,
                'service_id' => $service_data['service_id'],
                'quantity' => $service_data['quantity'],
                'usage_date' => $service_data['usage_date'],
                'usage_time' => $service_data['usage_time'],
                'amount' => $service_data['amount'],
                'unit_price' => $service_info['price'],
                'unit' => $service_info['unit'],
                'notes' => $service_data['notes'] ?? '',
                'status' => $service_data['status'] ?? 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($booking_id) {
                $data['booking_id'] = $booking_id;
            }
            
            $id = $this->create($data);
            
            if ($id) {
                $created_ids[] = $id;
            } else {
                $errors[] = "Không thể tạo booking dịch vụ cho service ID {$service_data['service_id']}";
            }
        }
        
        return [
            'success' => count($created_ids) > 0,
            'created_ids' => $created_ids,
            'errors' => $errors
        ];
    }
}









