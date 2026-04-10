<?php
session_start();
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

if (isset($_COOKIE['remember_token'])) {
    require_once '../includes/connect.php';
    $stmt = $mysqli->prepare("DELETE FROM login_tokens WHERE token = ?");
    if ($stmt) {
        $stmt->bind_param("s", $_COOKIE['remember_token']);
        $stmt->execute();
        $stmt->close();
    }
    setcookie("remember_token", "", time() - 3600, "/");
}

session_destroy();
header('Location: /My-Web-Hotel/client/index.php?page=home');
exit;
?>