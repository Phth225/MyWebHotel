<?php
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $stmt = $mysqli->prepare("SELECT user_id FROM login_tokens WHERE token = ? AND expires_at > NOW()");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['user_id'] = $row['user_id'];

            // Truy vấn thêm thông tin người dùng
            $stmtUser = $mysqli->prepare("SELECT username, full_name FROM users WHERE id = ?");
            if ($stmtUser) {
                $stmtUser->bind_param("i", $row['user_id']);
                $stmtUser->execute();
                $userResult = $stmtUser->get_result();
                if ($user = $userResult->fetch_assoc()) {
                    if (isset($user['full_name']) && trim($user['full_name']) !== '') {
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['username'] = $user['full_name'];
                    } else {
                        $_SESSION['username'] = $user['username'];
                    }
                }
                $stmtUser->close();
            }
        }
        $stmt->close();
    }
}
?>