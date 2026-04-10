<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/StaffModel.php';

/**
 * Profile Controller
 */
class ProfileController extends BaseController {
    
    public function index() {
        $staff_id = $this->getCurrentStaffId();
        
        if (!$staff_id) {
            $this->redirect('pages/logIn.php');
            return;
        }
        
        $staffModel = new StaffModel($this->mysqli);
        
        // Handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['update_profile'])) {
                $this->handleUpdateProfile($staffModel, $staff_id);
            } elseif (isset($_POST['change_password'])) {
                $this->handleChangePassword($staff_id);
            }
        }
        
        $staff = $staffModel->getById($staff_id);
        
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        
        // Use old profile.php for now
        include __DIR__ . '/../pages/profile.php';
    }
    
    private function handleUpdateProfile($model, $staff_id) {
        // Lấy thông tin hiện tại để giữ ảnh cũ nếu không upload mới
        $currentStaff = $model->getById($staff_id);
        $anh_dai_dien = $currentStaff['anh_dai_dien'] ?? '';
        
        // Xử lý upload ảnh nếu có lên Cloudinary
        if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] == UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (in_array($_FILES['anh_dai_dien']['type'], $allowedTypes) && $_FILES['anh_dai_dien']['size'] <= $maxSize) {
                require_once __DIR__ . '/../includes/cloudinary_helper.php';
                
                // Upload lên Cloudinary
                $cloudinaryUrl = CloudinaryHelper::upload($_FILES['anh_dai_dien']['tmp_name'], 'staff');
                
                if ($cloudinaryUrl !== false) {
                    // Xóa ảnh cũ trên Cloudinary nếu có
                    if (!empty($anh_dai_dien)) {
                        CloudinaryHelper::deleteByUrl($anh_dai_dien);
                    }
                    $anh_dai_dien = $cloudinaryUrl;
                }
            }
        }
        
        $data = [
            'ho_ten' => $_POST['ho_ten'] ?? '',
            'dien_thoai' => $_POST['dien_thoai'] ?? '',
            'dia_chi' => $_POST['dia_chi'] ?? '',
            'ngay_sinh' => !empty($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : null,
            'gioi_tinh' => $_POST['gioi_tinh'] ?? 'Nam',
            'cmnd_cccd' => $_POST['cmnd_cccd'] ?? '',
            'anh_dai_dien' => $anh_dai_dien
        ];
        
        // Update trực tiếp bằng SQL để đảm bảo tất cả field được cập nhật
        $stmt = $this->mysqli->prepare("UPDATE nhan_vien SET ho_ten=?, dien_thoai=?, ngay_sinh=?, gioi_tinh=?, cmnd_cccd=?, dia_chi=?, anh_dai_dien=? WHERE id_nhan_vien=?");
        $stmt->bind_param("sssssssi", $data['ho_ten'], $data['dien_thoai'], $data['ngay_sinh'], $data['gioi_tinh'], $data['cmnd_cccd'], $data['dia_chi'], $anh_dai_dien, $staff_id);
        
        if ($stmt->execute()) {
            $_SESSION['ho_ten'] = $data['ho_ten'];
            $_SESSION['anh_dai_dien'] = $anh_dai_dien;
            $_SESSION['message'] = 'Cập nhật thông tin thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật thông tin: ' . $stmt->error;
            $_SESSION['messageType'] = 'danger';
        }
        $stmt->close();
        
        // Dùng JavaScript redirect để tránh lỗi headers already sent
        echo '<script>window.location.href = "index.php?page=profile&t=' . time() . '";</script>';
        exit;
    }
    
    private function handleChangePassword($staff_id) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $_SESSION['message'] = 'Mật khẩu mới không khớp';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=profile');
            return;
        }
        
        // Verify current password
        $stmt = $this->mysqli->prepare("SELECT mat_khau FROM nhan_vien WHERE id_nhan_vien = ?");
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();
        
        if (!password_verify($current_password, $staff['mat_khau'])) {
            $_SESSION['message'] = 'Mật khẩu hiện tại không đúng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=profile');
            return;
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->mysqli->prepare("UPDATE nhan_vien SET mat_khau = ? WHERE id_nhan_vien = ?");
        $stmt->bind_param("si", $hashed_password, $staff_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Đổi mật khẩu thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi đổi mật khẩu';
            $_SESSION['messageType'] = 'danger';
        }
        $stmt->close();
        
        $this->redirect('index.php?page=profile');
    }
}

