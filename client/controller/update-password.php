<?php
session_start();
require_once '../includes/connect.php';

// Nếu chưa đăng nhập thì quay lại
if (!isset($_SESSION['user_id'])) {
    header("Location: /My-Web-Hotel/client/pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu từ form
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Kiểm tra dữ liệu đầu vào
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: ../pages/profile.php?pw_error=Vui lòng nhập đầy đủ thông tin!&tab=changePassword");
    exit();
}

if ($new_password !== $confirm_password) {
    header("Location: ../pages/profile.php?pw_error=Mật khẩu xác nhận không khớp!&tab=changePassword");
    exit();
}

// Lấy mật khẩu hiện tại từ DB
$stmt = $mysqli->prepare("SELECT password FROM customer WHERE customer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($hashed_password);
$stmt->fetch();
$stmt->close();

// Kiểm tra mật khẩu cũ
if (!password_verify($current_password, $hashed_password)) {
    header("Location: ../pages/profile.php?pw_error=Mật khẩu hiện tại không đúng!&tab=changePassword");
    exit();
}

// Cập nhật mật khẩu mới
$new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
$update_stmt = $mysqli->prepare("UPDATE customer SET password = ? WHERE customer_id = ?");
$update_stmt->bind_param("si", $new_hashed, $user_id);

if ($update_stmt->execute()) {
    header("Location: ../pages/profile.php?pw_success=1&tab=changePassword");
} else {
    header("Location: ../pages/profile.php?pw_error=Lỗi khi cập nhật mật khẩu!&tab=changePassword");
}
$update_stmt->close();
$mysqli->close();
exit();