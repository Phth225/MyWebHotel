<?php
session_start();
require_once '../includes/connect.php';
require_once __DIR__ . '/../../admin/includes/cloudinary_helper.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: /My-Web-Hotel/client/pages/logIn.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kiểm tra form gửi bằng POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $username    = trim($_POST['full_name'] ?? '');
    $ngay_sinh   = trim($_POST['birth_day'] ?? '');
    $dia_chi     = trim($_POST['address'] ?? '');
    $gioi_tinh   = trim($_POST['gender'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $sdt         = trim($_POST['phone'] ?? '');

    // Kiểm tra các trường bắt buộc
    if (empty($username) || empty($email)) {
        header("Location: ../pages/profile.php?error=missing");
        exit();
    }

    // Lấy avatar cũ trước khi cập nhật
    $oldAvatarStmt = $mysqli->prepare("SELECT avatar FROM customer WHERE customer_id = ?");
    $oldAvatarStmt->bind_param("i", $user_id);
    $oldAvatarStmt->execute();
    $oldAvatarResult = $oldAvatarStmt->get_result();
    $oldAvatar = $oldAvatarResult->fetch_assoc()['avatar'] ?? null;
    $oldAvatarStmt->close();

    // Xử lý upload ảnh đại diện lên Cloudinary
    $avatarPath = $oldAvatar; // Giữ nguyên avatar cũ nếu không upload mới
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        // Kiểm tra loại file
        if (!in_array($avatar['type'], $allowedTypes)) {
            header("Location: ../pages/profile.php?error=invalid_file_type");
            exit();
        }

        // Kiểm tra kích thước file
        if ($avatar['size'] > $maxSize) {
            header("Location: ../pages/profile.php?error=file_too_large");
            exit();
        }

        // Upload lên Cloudinary
        $cloudinaryUrl = CloudinaryHelper::upload($avatar['tmp_name'], 'customer');
        
        if ($cloudinaryUrl !== false) {
            $avatarPath = $cloudinaryUrl;
            
            // Xóa ảnh cũ trên Cloudinary nếu có
            if (!empty($oldAvatar) && strpos($oldAvatar, 'cloudinary.com') !== false) {
                CloudinaryHelper::deleteByUrl($oldAvatar);
            }
        } else {
            header("Location: ../pages/profile.php?error=avatar");
            exit();
        }
    }

    // Cập nhật thông tin người dùng
    if ($avatarPath) {
        // Cập nhật cả avatar
        $sql = "UPDATE customer 
                SET full_name = ?, date_of_birth = ?, address = ?, gender = ?, email = ?, phone = ?, avatar = ?
                WHERE customer_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssssssi", $username, $ngay_sinh, $dia_chi, $gioi_tinh, $email, $sdt, $avatarPath, $user_id);
    } else {
        // Chỉ cập nhật thông tin cơ bản
        $sql = "UPDATE customer 
                SET full_name = ?, date_of_birth = ?, address = ?, gender = ?, email = ?, phone = ?
                WHERE customer_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssssi", $username, $ngay_sinh, $dia_chi, $gioi_tinh, $email, $sdt, $user_id);
    }

    if (!$stmt) {
        die("Lỗi prepare: " . $mysqli->error);
    }

    if ($stmt->execute()) {
        // Thành công → quay lại trang profile với thông báo
        header("Location: ../pages/profile.php?success=1");
        exit();
    } else {
        // Lỗi khi cập nhật
        header("Location: ../pages/profile.php?error=1");
        exit();
    }
    
    $stmt->close();
}
?>