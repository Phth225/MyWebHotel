<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../models/BookingServiceModel.php';
require_once __DIR__ . '/../includes/invoice_helper.php';

/**
 * Booking Controller
 * Xử lý booking phòng và dịch vụ
 */
class BookingController extends BaseController {
    
    public function index() {
        // Check permission
        if (!$this->checkAccessSection('booking-manager')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        $panel = $_GET['panel'] ?? 'roomBooking-panel';
        
        // Route to appropriate panel
        if ($panel === 'roomBooking-panel') {
            $this->roomBookingPanel();
        } elseif ($panel === 'serviceBooking-panel') {
            $this->serviceBookingPanel();
        } elseif ($panel === 'calendar-booking-panel') {
            // Check permission for calendar panel
            if (!$this->checkAccessSection('calendar-booking-panel')) {
                $this->redirect('index.php?page=403');
                return;
            }
            $this->calendarBookingPanel();
        } else {
            $this->redirect('index.php?page=booking-manager&panel=roomBooking-panel');
        }
    }
    
    /**
     * Room Booking Panel
     */
    private function roomBookingPanel() {
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        
        // For now, use old page structure
        $panel = 'roomBooking-panel';
        include __DIR__ . '/../pages/booking-manager.php';
    }
    
    /**
     * Service Booking Panel
     */
    private function serviceBookingPanel() {
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        
        // For now, use old page structure
        $panel = 'serviceBooking-panel';
        include __DIR__ . '/../pages/booking-manager.php';
    }
    
    /**
     * Calendar Booking Panel
     */
    private function calendarBookingPanel() {
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        
        // Use booking-manager.php structure with calendar panel
        $panel = 'calendar-booking-panel';
        include __DIR__ . '/../pages/booking-manager.php';
    }
    
    /**
     * Handle add room booking
     */
    private function handleAddRoomBooking($bookingModel) {
        $customer_id = intval($_POST['customer_id']);
        $room_ids = isset($_POST['room_id']) && is_array($_POST['room_id']) 
            ? array_map('intval', $_POST['room_id']) 
            : [intval($_POST['room_id'] ?? 0)];
        $room_ids = array_filter($room_ids, function($id) { return $id > 0; });
        
        $booking_data = [
            'booking_date' => date('Y-m-d H:i:s'),
            'check_in_date' => $_POST['check_in_date'],
            'check_out_date' => $_POST['check_out_date'],
            'quantity' => intval($_POST['quantity']),
            'special_request' => $_POST['special_request'] ?? '',
            'booking_method' => $_POST['booking_method'] ?? 'Website',
            'deposit' => !empty($_POST['deposit']) ? floatval($_POST['deposit']) : null,
            'status' => $_POST['status'] ?? 'Pending',
            'customer_id' => $customer_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $bookingModel->createMultiple($room_ids, $booking_data);
        
        if ($result['success']) {
            // Auto create invoices if confirmed
            if ($booking_data['status'] === 'Confirmed') {
                foreach ($result['created_ids'] as $booking_id) {
                    createInvoiceForRoomBooking($this->mysqli, $booking_id);
                }
            }
            
            $_SESSION['message'] = 'Đã tạo thành công ' . count($result['created_ids']) . ' booking';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Không thể tạo booking: ' . implode(', ', $result['errors']);
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=booking-manager&panel=roomBooking-panel');
    }
    
    /**
     * Handle add service booking
     */
    private function handleAddServiceBooking($bookingServiceModel) {
        $customer_id = intval($_POST['customer_id']);
        $booking_id = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
        
        // Get services data from arrays
        $services_data = [];
        $service_ids = $_POST['service_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $usage_dates = $_POST['usage_date'] ?? [];
        $usage_times = $_POST['usage_time'] ?? [];
        $amounts = $_POST['amount'] ?? [];
        $notes = $_POST['note'] ?? [];
        $statuses = $_POST['status'] ?? [];
        
        $max_count = max(
            count($service_ids),
            count($quantities),
            count($usage_dates),
            count($usage_times),
            count($amounts)
        );
        
        for ($i = 0; $i < $max_count; $i++) {
            if (empty($service_ids[$i])) continue;
            
            $services_data[] = [
                'service_id' => intval($service_ids[$i]),
                'quantity' => intval($quantities[$i] ?? 1),
                'usage_date' => $usage_dates[$i] ?? '',
                'usage_time' => $usage_times[$i] ?? '',
                'amount' => floatval($amounts[$i] ?? 1),
                'notes' => $notes[$i] ?? '',
                'status' => $statuses[$i] ?? 'pending'
            ];
        }
        
        $result = $bookingServiceModel->createMultiple($services_data, $customer_id, $booking_id);
        
        if ($result['success']) {
            // Auto create invoices if confirmed
            foreach ($result['created_ids'] as $booking_service_id) {
                $service_data = $services_data[array_search($booking_service_id, $result['created_ids'])];
                if (($service_data['status'] ?? 'pending') === 'confirmed') {
                    createInvoiceForServiceBooking($this->mysqli, $booking_service_id);
                }
            }
            
            $_SESSION['message'] = 'Đã tạo thành công ' . count($result['created_ids']) . ' booking dịch vụ';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Không thể tạo booking dịch vụ';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=booking-manager&panel=serviceBooking-panel');
    }
    
    /**
     * Handle update room booking
     */
    private function handleUpdateRoomBooking($bookingModel) {
        $booking_id = intval($_POST['booking_id']);
        $data = [
            'customer_id' => intval($_POST['customer_id']),
            'room_id' => intval($_POST['room_id']),
            'quantity' => intval($_POST['quantity']),
            'check_in_date' => $_POST['check_in_date'],
            'check_out_date' => $_POST['check_out_date'],
            'status' => $_POST['status'] ?? 'Pending',
            'special_request' => $_POST['special_request'] ?? '',
            'booking_method' => $_POST['booking_method'] ?? 'Website',
            'deposit' => !empty($_POST['deposit']) ? floatval($_POST['deposit']) : null
        ];
        
        if ($bookingModel->update($booking_id, $data)) {
            // Auto create invoice if confirmed
            if ($data['status'] === 'Confirmed') {
                createInvoiceForRoomBooking($this->mysqli, $booking_id);
            }
            
            $_SESSION['message'] = 'Cập nhật booking thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật booking';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=booking-manager&panel=roomBooking-panel');
    }
    
    /**
     * Handle update service booking
     */
    private function handleUpdateServiceBooking($bookingServiceModel) {
        $booking_service_id = intval($_POST['booking_service_id']);
        
        // Get service info
        $service_stmt = $this->mysqli->prepare("SELECT price, unit FROM service WHERE service_id = ?");
        $service_id = intval($_POST['service_id']);
        $service_stmt->bind_param("i", $service_id);
        $service_stmt->execute();
        $service_result = $service_stmt->get_result();
        $service_info = $service_result->fetch_assoc();
        $service_stmt->close();
        
        $data = [
            'customer_id' => intval($_POST['customer_id']),
            'service_id' => $service_id,
            'quantity' => intval($_POST['quantity']),
            'usage_date' => $_POST['usage_date'],
            'usage_time' => $_POST['usage_time'],
            'amount' => floatval($_POST['amount']),
            'unit_price' => $service_info['price'],
            'unit' => $service_info['unit'],
            'notes' => $_POST['note'] ?? '',
            'status' => $_POST['status'] ?? 'pending'
        ];
        
        if ($bookingServiceModel->update($booking_service_id, $data)) {
            // Auto create invoice if confirmed
            if ($data['status'] === 'confirmed') {
                createInvoiceForServiceBooking($this->mysqli, $booking_service_id);
            }
            
            $_SESSION['message'] = 'Cập nhật booking dịch vụ thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật booking dịch vụ';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=booking-manager&panel=serviceBooking-panel');
    }
    
    /**
     * Handle delete room booking
     */
    private function handleDeleteRoomBooking($bookingModel) {
        $booking_id = intval($_POST['booking_id']);
        if ($bookingModel->delete($booking_id)) {
            $_SESSION['message'] = 'Xóa booking thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi xóa booking';
            $_SESSION['messageType'] = 'danger';
        }
        $this->redirect('index.php?page=booking-manager&panel=roomBooking-panel');
    }
    
    /**
     * Handle delete service booking
     */
    private function handleDeleteServiceBooking($bookingServiceModel) {
        $booking_service_id = intval($_POST['booking_service_id']);
        if ($bookingServiceModel->delete($booking_service_id)) {
            $_SESSION['message'] = 'Xóa booking dịch vụ thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi xóa booking dịch vụ';
            $_SESSION['messageType'] = 'danger';
        }
        $this->redirect('index.php?page=booking-manager&panel=serviceBooking-panel');
    }
    
    /**
     * Helper: Get customers
     */
    private function getCustomers() {
        $result = $this->mysqli->query("SELECT customer_id, full_name, phone, email FROM customer WHERE deleted IS NULL ORDER BY full_name");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    /**
     * Helper: Get available rooms
     */
    private function getAvailableRooms() {
        $result = $this->mysqli->query("
            SELECT r.room_id, r.room_number, rt.room_type_name, rt.base_price
            FROM room r
            JOIN room_type rt ON r.room_type_id = rt.room_type_id
            WHERE r.deleted IS NULL AND r.status = 'Available'
            ORDER BY r.room_number
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    /**
     * Helper: Get services
     */
    private function getServices() {
        $result = $this->mysqli->query("SELECT * FROM service WHERE deleted IS NULL AND status = 'Active' ORDER BY service_name");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

