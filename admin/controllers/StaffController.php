<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/StaffModel.php';

/**
 * Staff Controller
 */
class StaffController extends BaseController {
    
    public function index() {
        // Check permission using canAccessSection (which uses permission_map)
        if (!$this->checkAccessSection('staff-manager')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        
        // Use old staff-manager.php which handles panel routing internally
        // It will check permissions for each panel (permission-panel, task-panel) internally
        include __DIR__ . '/../pages/staff-manager.php';
    }
    
    private function handleAddStaff($model) {
        if (!$this->checkPermission('employee.create')) {
            $_SESSION['message'] = 'Bạn không có quyền tạo nhân viên';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=staff-manager&panel=staff-panel');
            return;
        }
        
        $data = [
            'ho_ten' => $_POST['ho_ten'],
            'email' => $_POST['email'],
            'so_dien_thoai' => $_POST['so_dien_thoai'],
            'chuc_vu' => $_POST['chuc_vu'],
            'dia_chi' => $_POST['dia_chi'] ?? '',
            'ngay_sinh' => !empty($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : null,
            'gioi_tinh' => $_POST['gioi_tinh'] ?? 'Nam',
            'trang_thai' => $_POST['trang_thai'] ?? 'Active'
        ];
        
        // Handle password
        if (!empty($_POST['mat_khau'])) {
            $data['mat_khau'] = password_hash($_POST['mat_khau'], PASSWORD_DEFAULT);
        }
        
        if ($model->create($data)) {
            $_SESSION['message'] = 'Thêm nhân viên thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi thêm nhân viên';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=staff-manager&panel=staff-panel');
    }
    
    private function handleUpdateStaff($model) {
        if (!$this->checkPermission('employee.edit')) {
            $_SESSION['message'] = 'Bạn không có quyền sửa nhân viên';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=staff-manager&panel=staff-panel');
            return;
        }
        
        $id = intval($_POST['id_nhan_vien']);
        $data = [
            'ho_ten' => $_POST['ho_ten'],
            'email' => $_POST['email'],
            'so_dien_thoai' => $_POST['so_dien_thoai'],
            'chuc_vu' => $_POST['chuc_vu'],
            'dia_chi' => $_POST['dia_chi'] ?? '',
            'ngay_sinh' => !empty($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : null,
            'gioi_tinh' => $_POST['gioi_tinh'] ?? 'Nam',
            'trang_thai' => $_POST['trang_thai']
        ];
        
        if (!empty($_POST['mat_khau'])) {
            $data['mat_khau'] = password_hash($_POST['mat_khau'], PASSWORD_DEFAULT);
        }
        
        if ($model->update($id, $data)) {
            $_SESSION['message'] = 'Cập nhật nhân viên thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật nhân viên';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=staff-manager&panel=staff-panel');
    }
    
    private function handleDeleteStaff($model) {
        if (!$this->checkPermission('employee.delete')) {
            $_SESSION['message'] = 'Bạn không có quyền xóa nhân viên';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=staff-manager&panel=staff-panel');
            return;
        }
        
        $id = intval($_POST['id_nhan_vien']);
        if ($model->delete($id)) {
            $_SESSION['message'] = 'Xóa nhân viên thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi xóa nhân viên';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=staff-manager&panel=staff-panel');
    }
    
    private function getRoles() {
        $result = $this->mysqli->query("SELECT * FROM chuc_vu ORDER BY ten_chuc_vu");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

