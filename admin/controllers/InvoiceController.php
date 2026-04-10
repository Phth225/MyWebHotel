<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/InvoiceModel.php';
require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../models/CustomerModel.php';

/**
 * Invoice Controller
 */
class InvoiceController extends BaseController {
    
    public function index() {
        if (!$this->checkPermission('invoice.view')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        $invoiceModel = new InvoiceModel($this->mysqli);
        
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_invoice'])) {
                $this->handleAdd($invoiceModel);
            } elseif (isset($_POST['update_invoice'])) {
                $this->handleUpdate($invoiceModel);
            } elseif (isset($_POST['delete_invoice'])) {
                $this->handleDelete($invoiceModel);
            }
        }
        
        // Pagination
        $page = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        // Filter
        $status = $_GET['status'] ?? '';
        $where = '';
        if ($status) {
            $where = "i.status = '{$status}'";
        }
        
        // Get data
        $invoices = $invoiceModel->getInvoicesWithDetails($where, 'invoice_id DESC', "{$offset}, {$perPage}");
        $total = $invoiceModel->count($where ? "deleted IS NULL AND ({$where})" : 'deleted IS NULL');
        $totalPages = ceil($total / $perPage);
        
        // Edit mode
        $editInvoice = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $editInvoice = $invoiceModel->getInvoiceWithServices($_GET['id']);
        }
        
        // Get bookings without invoice
        $bookingsWithoutInvoice = $this->getBookingsWithoutInvoice();
        
        $data = [
            'invoices' => $invoices,
            'editInvoice' => $editInvoice,
            'bookingsWithoutInvoice' => $bookingsWithoutInvoice,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'status' => $status,
            'canCreate' => $this->checkPermission('invoice.create'),
            'canEdit' => $this->checkPermission('invoice.edit'),
            'canDelete' => $this->checkPermission('invoice.delete')
        ];
        
        // For now, use old page structure until views are fully migrated
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        include __DIR__ . '/../pages/invoices-manager.php';
    }
    
    private function handleAdd($model) {
        if (!$this->checkPermission('invoice.create')) {
            $_SESSION['message'] = 'Bạn không có quyền tạo hóa đơn';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=invoices-manager');
            return;
        }
        
        $booking_id = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
        $customer_id = intval($_POST['customer_id']);
        
        // Calculate totals
        $room_charge = floatval($_POST['room_charge'] ?? 0);
        $service_charge = floatval($_POST['service_charge'] ?? 0);
        $vat = floatval($_POST['vat'] ?? 0);
        $total_amount = $room_charge + $service_charge + $vat;
        $deposit_amount = floatval($_POST['deposit_amount'] ?? 0);
        $remaining_amount = $total_amount - $deposit_amount;
        
        $data = [
            'booking_id' => $booking_id,
            'customer_id' => $customer_id,
            'room_charge' => $room_charge,
            'service_charge' => $service_charge,
            'vat' => $vat,
            'total_amount' => $total_amount,
            'deposit_amount' => $deposit_amount,
            'remaining_amount' => $remaining_amount,
            'status' => $_POST['status'] ?? 'Unpaid',
            'note' => $_POST['note'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($booking_id) {
            $data['payment_time'] = $data['status'] === 'Paid' ? date('Y-m-d H:i:s') : null;
        }
        
        $invoice_id = $model->create($data);
        
        if ($invoice_id) {
            // Link services if provided
            if (isset($_POST['service_ids']) && is_array($_POST['service_ids'])) {
                foreach ($_POST['service_ids'] as $booking_service_id) {
                    $this->linkServiceToInvoice($invoice_id, intval($booking_service_id));
                }
            }
            
            // Tự động cập nhật hạng khách hàng nếu invoice được tạo với status = Paid
            if ($data['status'] === 'Paid' && !empty($customer_id) && $customer_id > 0) {
                require_once __DIR__ . '/../includes/customer_rank_helper.php';
                $rankResult = updateCustomerRank($this->mysqli, $customer_id);
                if ($rankResult['success'] && $rankResult['old_rank'] !== $rankResult['new_rank']) {
                    error_log("Customer rank auto-updated: Customer ID {$customer_id} - {$rankResult['old_rank']} -> {$rankResult['new_rank']}");
                }
            }
            
            $_SESSION['message'] = 'Tạo hóa đơn thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi tạo hóa đơn';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=invoices-manager');
    }
    
    private function handleUpdate($model) {
        if (!$this->checkPermission('invoice.edit')) {
            $_SESSION['message'] = 'Bạn không có quyền sửa hóa đơn';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=invoices-manager');
            return;
        }
        
        $id = intval($_POST['invoice_id']);
        $booking_id = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
        
        $room_charge = floatval($_POST['room_charge'] ?? 0);
        $service_charge = floatval($_POST['service_charge'] ?? 0);
        $vat = floatval($_POST['vat'] ?? 0);
        $total_amount = $room_charge + $service_charge + $vat;
        $deposit_amount = floatval($_POST['deposit_amount'] ?? 0);
        $remaining_amount = $total_amount - $deposit_amount;
        
        $data = [
            'customer_id' => intval($_POST['customer_id']),
            'room_charge' => $room_charge,
            'service_charge' => $service_charge,
            'vat' => $vat,
            'total_amount' => $total_amount,
            'deposit_amount' => $deposit_amount,
            'remaining_amount' => $remaining_amount,
            'status' => $_POST['status'] ?? 'Unpaid',
            'note' => $_POST['note'] ?? ''
        ];
        
        if ($data['status'] === 'Paid' && !$model->getById($id)['payment_time']) {
            $data['payment_time'] = date('Y-m-d H:i:s');
        }
        
        if ($model->update($id, $data)) {
            // Tự động cập nhật hạng khách hàng nếu invoice được thanh toán
            if ($data['status'] === 'Paid' && !empty($data['customer_id']) && $data['customer_id'] > 0) {
                require_once __DIR__ . '/../includes/customer_rank_helper.php';
                $rankResult = updateCustomerRank($this->mysqli, $data['customer_id']);
                if ($rankResult['success'] && $rankResult['old_rank'] !== $rankResult['new_rank']) {
                    error_log("Customer rank auto-updated: Customer ID {$data['customer_id']} - {$rankResult['old_rank']} -> {$rankResult['new_rank']}");
                }
            }
            
            $_SESSION['message'] = 'Cập nhật hóa đơn thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật hóa đơn';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=invoices-manager');
    }
    
    private function handleDelete($model) {
        if (!$this->checkPermission('invoice.delete')) {
            $_SESSION['message'] = 'Bạn không có quyền xóa hóa đơn';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=invoices-manager');
            return;
        }
        
        $id = intval($_POST['invoice_id']);
        if ($model->delete($id)) {
            $_SESSION['message'] = 'Xóa hóa đơn thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi xóa hóa đơn';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=invoices-manager');
    }
    
    private function getBookingsWithoutInvoice() {
        $query = "
            SELECT b.booking_id, b.check_in_date, b.check_out_date,
                   c.full_name, c.phone,
                   r.room_number, rt.room_type_name
            FROM booking b
            LEFT JOIN invoice i ON b.booking_id = i.booking_id AND i.deleted IS NULL
            LEFT JOIN customer c ON b.customer_id = c.customer_id
            LEFT JOIN room r ON b.room_id = r.room_id
            LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
            WHERE i.invoice_id IS NULL
            AND b.deleted IS NULL
            AND b.status = 'Confirmed'
            ORDER BY b.booking_id DESC
            LIMIT 100
        ";
        
        $result = $this->mysqli->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    private function linkServiceToInvoice($invoice_id, $booking_service_id) {
        $stmt = $this->mysqli->prepare("INSERT INTO invoice_service (invoice_id, booking_service_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $invoice_id, $booking_service_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}

