<?php
session_start();

// Chỉ cần xóa session, không đụng tới login_tokens nữa
$_SESSION = array();

// Hủy session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect về trang đăng nhập
header("Location: /My-Web-Hotel/admin/pages/logIn.php");
exit;

