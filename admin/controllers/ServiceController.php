<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/ServiceModel.php';

/**
 * Service Controller
 */
class ServiceController extends BaseController {
    
    public function index() {
        if (!$this->checkPermission('service.view')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        // For now, use old page structure until views are fully migrated
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        include __DIR__ . '/../pages/services-manager.php';
    }
    
    private function handleAdd($model) {
        if (!$this->checkPermission('service.create')) {
            $_SESSION['message'] = 'Bạn không có quyền tạo dịch vụ';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=services-manager');
            return;
        }
        
        $data = [
            'service_name' => $_POST['service_name'],
            'service_type' => $_POST['service_type'],
            'price' => floatval($_POST['price']),
            'unit' => $_POST['unit'] ?? 'lần',
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'Active'
        ];
        
        if ($model->create($data)) {
            $_SESSION['message'] = 'Thêm dịch vụ thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi thêm dịch vụ';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=services-manager');
    }
    
    private function handleUpdate($model) {
        if (!$this->checkPermission('service.edit')) {
            $_SESSION['message'] = 'Bạn không có quyền sửa dịch vụ';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=services-manager');
            return;
        }
        
        $id = intval($_POST['service_id']);
        $data = [
            'service_name' => $_POST['service_name'],
            'service_type' => $_POST['service_type'],
            'price' => floatval($_POST['price']),
            'unit' => $_POST['unit'] ?? 'lần',
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status']
        ];
        
        if ($model->update($id, $data)) {
            $_SESSION['message'] = 'Cập nhật dịch vụ thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật dịch vụ';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=services-manager');
    }
    
    private function handleDelete($model) {
        if (!$this->checkPermission('service.delete')) {
            $_SESSION['message'] = 'Bạn không có quyền xóa dịch vụ';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=services-manager');
            return;
        }
        
        $id = intval($_POST['service_id']);
        if ($model->delete($id)) {
            $_SESSION['message'] = 'Xóa dịch vụ thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi xóa dịch vụ';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=services-manager');
    }
}

