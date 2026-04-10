<?php
// Bắt đầu output buffering để bắt lỗi
ob_start();

session_start();

// Kiểm tra lỗi PHP
error_reporting(E_ALL);
ini_set('display_errors', 0); // Không hiển thị lỗi ra màn hình
ini_set('log_errors', 1);

try {
    require_once __DIR__ . '/../includes/connect.php';
    require_once __DIR__ . '/../controller/email_helper.php';
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    exit;
}

// Xóa output buffer nếu có lỗi
ob_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'send_code') {
        // Gửi mã reset password
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email']);
            exit;
        }

        // Kiểm tra email có tồn tại không
        $stmt = $mysqli->prepare("SELECT customer_id, full_name, email FROM customer WHERE email = ? AND deleted IS NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Email không tồn tại trong hệ thống']);
            exit;
        }

        // Tạo mã 6 chữ số (đảm bảo không có khoảng trắng)
        $token = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = trim($token); // Đảm bảo không có khoảng trắng

        // Xóa các mã cũ của email này
        $deleteStmt = $mysqli->prepare("DELETE FROM password_resets WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Lưu mã mới vào database - thời gian hết hạn 15 phút từ bây giờ
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $insertStmt = $mysqli->prepare("INSERT INTO password_resets (email, token, expires_at, is_used, created_at) VALUES (?, ?, ?, 0, NOW())");
        $insertStmt->bind_param("sss", $email, $token, $expiresAt);
        
        // Log để debug
        error_log("Password reset code created - Email: $email, Token: $token, Expires: $expiresAt");
        
        if (!$insertStmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.']);
            $insertStmt->close();
            exit;
        }
        $insertStmt->close();

        // Gửi email
        try {
            $emailSent = EmailHelper::sendPasswordResetCode($email, $user['full_name'], $token);

            if ($emailSent) {
                // Lưu email vào session nhưng CHƯA verify
                $_SESSION['reset_email'] = $email;
                // KHÔNG set reset_verified để khi quay lại sẽ reset
                unset($_SESSION['reset_verified']);
                echo json_encode(['success' => true, 'message' => 'Mã xác nhận đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.']);
            } else {
                // Log lỗi chi tiết
                error_log("Failed to send password reset email to: $email");
                echo json_encode(['success' => false, 'message' => 'Không thể gửi email. Vui lòng kiểm tra cấu hình SMTP trong file .env hoặc thử lại sau.']);
            }
        } catch (Exception $e) {
            error_log("Exception when sending password reset email: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi gửi email: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'verify_code') {
        // Xác nhận mã
        $email = trim($_POST['email'] ?? '');
        $code = trim($_POST['code'] ?? '');
        
        // Loại bỏ tất cả khoảng trắng và ký tự không phải số
        $code = preg_replace('/[^0-9]/', '', $code);

        if (empty($email) || empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
            exit;
        }
        
        if (strlen($code) !== 6) {
            echo json_encode(['success' => false, 'message' => 'Mã OTP phải có đúng 6 chữ số']);
            exit;
        }

        // Log để debug
        error_log("Verify code attempt - Email: $email, Code entered (cleaned): $code, Code length: " . strlen($code));

        // Bước 1: Lấy TẤT CẢ mã của email này (kể cả đã hết hạn, đã sử dụng) để tìm mã khớp
        $stmt = $mysqli->prepare("SELECT * FROM password_resets WHERE email = ? ORDER BY created_at DESC");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset = null;
        
        // So sánh mã (loại bỏ khoảng trắng và so sánh chính xác)
        while ($row = $result->fetch_assoc()) {
            $dbToken = trim($row['token']);
            $dbTokenClean = preg_replace('/[^0-9]/', '', $dbToken);
            
            // Log để debug
            error_log("Comparing - DB Token (raw): '$dbToken', DB Token (cleaned): '$dbTokenClean', Entered Code: '$code', Is Used: " . $row['is_used'] . ", Expires: " . $row['expires_at']);
            
            if ($dbTokenClean === $code || $dbToken === $code) {
                // Mã khớp - kiểm tra điều kiện
                $now = new DateTime();
                $expiresAt = new DateTime($row['expires_at']);
                $isExpired = $now > $expiresAt;
                $isUsed = (int)$row['is_used'] === 1;
                
                error_log("Code match found! Token ID: " . $row['id'] . ", Is Expired: " . ($isExpired ? 'YES' : 'NO') . ", Is Used: " . ($isUsed ? 'YES' : 'NO'));
                
                if (!$isUsed && !$isExpired) {
                    // Mã hợp lệ
                    $reset = $row;
                    break;
                } else {
                    // Mã khớp nhưng đã hết hạn hoặc đã sử dụng
                    if ($isExpired) {
                        error_log("Code matched but expired. Expires: " . $row['expires_at'] . ", Now: " . $now->format('Y-m-d H:i:s'));
                    }
                    if ($isUsed) {
                        error_log("Code matched but already used.");
                    }
                }
            }
        }
        $stmt->close();

        if (!$reset) {
            // Debug: Log để kiểm tra
            error_log("Verify code failed - Email: $email, Code entered (cleaned): $code");
            $debugStmt = $mysqli->prepare("SELECT token, expires_at, is_used, NOW() as current_datetime, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_remaining FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
            $debugStmt->bind_param("s", $email);
            $debugStmt->execute();
            $debugResult = $debugStmt->get_result();
            if ($debugRow = $debugResult->fetch_assoc()) {
                $dbTokenClean = preg_replace('/[^0-9]/', '', $debugRow['token']);
                error_log("Debug - DB Token (raw): '" . $debugRow['token'] . "', DB Token (cleaned): '$dbTokenClean', Expires: " . $debugRow['expires_at'] . ", Current: " . $debugRow['current_datetime'] . ", Is Used: " . $debugRow['is_used'] . ", Seconds Remaining: " . $debugRow['seconds_remaining']);
                error_log("Code comparison - Entered: '$code' (length: " . strlen($code) . "), DB: '$dbTokenClean' (length: " . strlen($dbTokenClean) . "), Match: " . ($code === $dbTokenClean ? 'YES' : 'NO'));
            } else {
                error_log("Debug - No password reset record found for email: $email");
            }
            $debugStmt->close();
            
            echo json_encode(['success' => false, 'message' => 'Mã xác nhận không đúng hoặc đã hết hạn. Vui lòng kiểm tra lại mã hoặc yêu cầu gửi lại mã mới.']);
            exit;
        }

        // Đánh dấu mã đã sử dụng
        $updateStmt = $mysqli->prepare("UPDATE password_resets SET is_used = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $reset['id']);
        $updateStmt->execute();
        $updateStmt->close();

        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_verified'] = true;
        echo json_encode(['success' => true, 'message' => 'Mã xác nhận hợp lệ']);
        exit;
    }

    if ($action === 'reset_password') {
        // Đặt lại mật khẩu
        $email = trim($_POST['email'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (empty($email) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
            exit;
        }

        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
            exit;
        }

        // Kiểm tra session
        if (!isset($_SESSION['reset_email']) || $_SESSION['reset_email'] !== $email || !isset($_SESSION['reset_verified'])) {
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng thử lại từ đầu.']);
            exit;
        }

        // Cập nhật mật khẩu
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $mysqli->prepare("UPDATE customer SET password = ? WHERE email = ? AND deleted IS NULL");
        $updateStmt->bind_param("ss", $hashedPassword, $email);

        if ($updateStmt->execute()) {
            // Xóa session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified']);
            
            echo json_encode(['success' => true, 'message' => 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập ngay bây giờ.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.']);
        }
        $updateStmt->close();
        exit;
    }
    } catch (Exception $e) {
        error_log("Error in forgot-password.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

