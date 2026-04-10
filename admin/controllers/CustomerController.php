<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/CustomerModel.php';

/**
 * Customer Controller
 */
class CustomerController extends BaseController {
    
    public function index() {
        if (!$this->checkPermission('customer.view')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        $customerModel = new CustomerModel($this->mysqli);
        
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_customer'])) {
                $this->handleAdd($customerModel);
            } elseif (isset($_POST['update_customer'])) {
                $this->handleUpdate($customerModel);
            } elseif (isset($_POST['delete_customer'])) {
                $this->handleDelete($customerModel);
            }
        }
        
        // Pagination
        $page = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        // Search
        $search = $_GET['search'] ?? '';
        $where = '';
        if ($search) {
            $where = "c.full_name LIKE '%{$search}%' OR c.email LIKE '%{$search}%' OR c.phone LIKE '%{$search}%'";
        }
        
        // Get data
        $customers = $customerModel->getCustomersWithStats($where, 'customer_id DESC', "{$offset}, {$perPage}");
        $total = $customerModel->count($where ? "deleted IS NULL AND ({$where})" : 'deleted IS NULL');
        $totalPages = ceil($total / $perPage);
        
        // Edit mode
        $editCustomer = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $editCustomer = $customerModel->getById($_GET['id']);
        }
        
        $data = [
            'customers' => $customers,
            'editCustomer' => $editCustomer,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'canCreate' => $this->checkPermission('customer.create'),
            'canEdit' => $this->checkPermission('customer.edit'),
            'canDelete' => $this->checkPermission('customer.delete')
        ];
        
        // For now, use old page structure until views are fully migrated
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        include __DIR__ . '/../pages/customers-manager.php';
    }
    
    private function handleAdd($model) {
        if (!$this->checkPermission('customer.create')) {
            $_SESSION['message'] = 'Bạn không có quyền tạo khách hàng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=customers-manager');
            return;
        }
        
        $data = [
            'full_name' => $_POST['full_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
            'gender' => $_POST['gender'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($model->create($data)) {
            $_SESSION['message'] = 'Thêm khách hàng thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi thêm khách hàng';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=customers-manager');
    }
    
    private function handleUpdate($model) {
        if (!$this->checkPermission('customer.edit')) {
            $_SESSION['message'] = 'Bạn không có quyền sửa khách hàng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=customers-manager');
            return;
        }
        
        $id = intval($_POST['customer_id']);
        $data = [
            'full_name' => $_POST['full_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
            'gender' => $_POST['gender'] ?? null
        ];
        
        if ($model->update($id, $data)) {
            $_SESSION['message'] = 'Cập nhật khách hàng thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật khách hàng';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=customers-manager');
    }
    
    private function handleDelete($model) {
        if (!$this->checkPermission('customer.delete')) {
            $_SESSION['message'] = 'Bạn không có quyền xóa khách hàng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=customers-manager');
            return;
        }
        
        $id = intval($_POST['customer_id']);
        if ($model->delete($id)) {
            $_SESSION['message'] = 'Xóa khách hàng thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi xóa khách hàng';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=customers-manager');
    }
}

