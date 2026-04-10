<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Invoice Model
 * Xử lý các thao tác với bảng invoice
 */
class InvoiceModel extends BaseModel {
    protected $table = 'invoice';
    protected $primaryKey = 'invoice_id';
    
    /**
     * Get invoices with details
     */
    public function getInvoicesWithDetails($where = '', $orderBy = 'invoice_id DESC', $limit = '') {
        $query = "
            SELECT i.*,
                   COALESCE(c.full_name, w.full_name) as full_name,
                   COALESCE(c.phone, w.phone) as phone,
                   COALESCE(c.email, w.email) as email,
                   CASE WHEN (i.customer_id IS NULL OR i.customer_id = 0) OR b.walk_in_guest_id IS NOT NULL THEN 'Walk-in' ELSE 'Registered' END as guest_type,
                   b.booking_id, b.check_in_date, b.check_out_date, b.walk_in_guest_id,
                   r.room_number, rt.room_type_name
            FROM {$this->table} i
            LEFT JOIN customer c ON i.customer_id = c.customer_id AND i.customer_id IS NOT NULL AND i.customer_id > 0
            LEFT JOIN booking b ON i.booking_id = b.booking_id
            LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
            LEFT JOIN room r ON b.room_id = r.room_id
            LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
            WHERE i.deleted IS NULL
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
     * Get invoice with services
     */
    public function getInvoiceWithServices($invoice_id) {
        // Get invoice basic info
        $invoice = $this->getById($invoice_id);
        
        if (!$invoice) {
            return null;
        }
        
        // Get booking info if exists
        if ($invoice['booking_id']) {
            $booking_stmt = $this->mysqli->prepare("
                SELECT b.*, 
                       COALESCE(c.full_name, w.full_name) as full_name,
                       COALESCE(c.phone, w.phone) as phone,
                       COALESCE(c.email, w.email) as email,
                       r.room_number, rt.room_type_name
                FROM booking b
                LEFT JOIN customer c ON b.customer_id = c.customer_id
                LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
                LEFT JOIN room r ON b.room_id = r.room_id
                LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
                WHERE b.booking_id = ?
            ");
            $booking_stmt->bind_param("i", $invoice['booking_id']);
            $booking_stmt->execute();
            $booking_result = $booking_stmt->get_result();
            $invoice['booking_info'] = $booking_result->fetch_assoc();
            $booking_stmt->close();
        }
        
        // Get services
        $services_stmt = $this->mysqli->prepare("
            SELECT bs.*, s.service_name, s.service_type
            FROM invoice_service isv
            INNER JOIN booking_service bs ON isv.booking_service_id = bs.booking_service_id
            INNER JOIN service s ON bs.service_id = s.service_id
            WHERE isv.invoice_id = ?
        ");
        $services_stmt->bind_param("i", $invoice_id);
        $services_stmt->execute();
        $services_result = $services_stmt->get_result();
        $invoice['services'] = [];
        while ($service = $services_result->fetch_assoc()) {
            $invoice['services'][] = $service;
        }
        $services_stmt->close();
        
        return $invoice;
    }
}









