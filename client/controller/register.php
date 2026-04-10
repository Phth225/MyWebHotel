<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once '../includes/connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Kiểm tra dữ liệu rỗng
    if (empty($full_name) ||empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin.";
        header("Location: ../pages/signUp.php");
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Email không hợp lệ.";
        header("Location: ../pages/signUp.php");
        exit;
    }

    // Kiểm tra độ dài mật khẩu
    if (strlen($password) < 6) {
        $_SESSION['error'] = "Mật khẩu phải có ít nhất 6 ký tự.";
        header("Location: ../pages/signUp.php");
        exit;
    }

    // Kiểm tra mật khẩu khớp
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Mật khẩu xác nhận không khớp.";
        header("Location: ../pages/signUp.php");
        exit;
    }

    // Kiểm tra email đã tồn tại
    $stmt = $mysqli->prepare("SELECT customer_id FROM customer WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email đã được sử dụng.";
        $stmt->close();
        header("Location: ../pages/signUp.php");
        exit;
    }
    $stmt->close();

    // Băm mật khẩu và thêm người dùng
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $insert = $mysqli->prepare("INSERT INTO customer (full_name, username, email, phone, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $insert->bind_param("sssss", $full_name, $username, $email, $phone, $hashed_password);

    if ($insert->execute()) {
        $_SESSION['success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
        $insert->close();
        $mysqli->close();
        header("Location: ../pages/signUp.php"); // Đã sửa lỗi URL
        exit;
    } else {
        $_SESSION['error'] = "Đã có lỗi xảy ra: " . $mysqli->error;
        $insert->close();
        header("Location: ../pages/signUp.php");
        exit;
    }
}
?>