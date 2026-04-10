<?php
session_start();

// Cấu hình DB
require_once '../includes/connect.php';

define('GOOGLE_CLIENT_ID', '868208581571-lih6bdqrvbj0b4a7oqbtq9bnnf4lhiep.apps.googleusercontent.com');

// Khởi tạo biến error
$error = '';

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']);
    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập email và mật khẩu.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        $sql = 'SELECT customer_id, full_name, email, password, username FROM customer WHERE email = ? LIMIT 1';
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $email);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result && $result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $stored = $user['password'];
                    $loginOk = false;

                    // Thử verify hash trước
                    if (password_verify($password, $stored)) {
                        $loginOk = true;
                        // rehash nếu cần
                        if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            if ($newHash !== false) {
                                $upd = $mysqli->prepare('UPDATE customer SET password = ? WHERE customer_id = ?');
                                if ($upd) {
                                    $upd->bind_param('si', $newHash, $user['customer_id']);
                                    $upd->execute();
                                    $upd->close();
                                }
                            }
                        }
                    } else {
                        // fallback plaintext (nếu DB đang lưu plaintext)
                        if ($password === $stored) {
                            $loginOk = true;
                            // migrate lên hash
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            if ($newHash !== false) {
                                $upd = $mysqli->prepare('UPDATE customer SET password = ? WHERE customer_id = ?');
                                if ($upd) {
                                    $upd->bind_param('si', $newHash, $user['customer_id']);
                                    $upd->execute();
                                    $upd->close();
                                }
                            }
                        }
                    }

                    if ($loginOk) {
                        // set session
                        $_SESSION['user_id'] = $user['customer_id'];
                        if(isset($user['full_name']) && trim($user['full_name']) !== '') {
                            $_SESSION['username'] = $user['full_name'];
                        } else {
                            $_SESSION['username'] = $user['full_name'];
                        }
                        $_SESSION['logged_in_at'] = time();

                        // Nếu người dùng chọn "Lưu phiên đăng nhập"
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', time() + (86400 * 7));

                        $stmtToken = $mysqli->prepare("INSERT INTO login_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                        if ($stmtToken) {
                            $stmtToken->bind_param("iss", $user['id'], $token, $expires);
                            $stmtToken->execute();
                            $stmtToken->close();

                            setcookie("remember_token", $token, time() + (86400 * 30), "/", "", true, true);
                        }
                    }

                    // xử lý redirect an toàn
                    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/My-Web-Hotel/client/index.php?page=home';
                    $parsed = parse_url($redirect);
                    if (isset($parsed['host']) && $parsed['host'] !== $_SERVER['SERVER_NAME']) {
                        $redirect = '/My-Web-Hotel/client/index.php?page=home';
                    }

                    header("Location: $redirect");
                    $stmt->close();
                    $mysqli->close();
                    exit;
                }else {
                    $error = 'Email hoặc mật khẩu không đúng.';
                }
                } else {
                    $error = 'Email hoặc mật khẩu không đúng.';
                }
            } else {
                error_log('Execute failed: ' . $stmt->error);
                $error = 'Có lỗi. Vui lòng thử lại.';
            }
            $stmt->close();
        } else {
            error_log('Prepare failed: ' . $mysqli->error);
            $error = 'Có lỗi. Vui lòng thử lại.';
        }
    }
}

// Đóng kết nối (nếu chưa đóng khi redirect)
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="origin-trial" content="">
    <meta name="referrer" content="no-referrer-when-downgrade">
    <title>Login Page</title>
    <!-- favicon -->
    <link rel="icon" href="/My-Web-Hotel/client/assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/login.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/loading.css?v=<?php echo time(); ?>">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Google Sign-In Script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>

<body>
    <?php
    include '../includes/loading.php';
    ?>
    <div class="login-box">
        <h2>Chào Mừng</h2>
        <?php if ($error !== ''): ?>
        <div class="error" style="color:red; margin-bottom:12px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" id="loginForm">
            <div class="input-box">
                <input type="email" name="email" id="loginEmail" placeholder="Email" required autocomplete="email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
            </div>
            <div class="input-box">
                <input type="password" id="password" name="password" placeholder="Mật khẩu" required
                    autocomplete="current-password" />
                <span class="toggle-password" onclick="togglePassword()">
                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                </span>
            </div>
            <label class="remember-login">
                Lưu thông tin đăng nhập
                <input type="checkbox" name="remember">
                <span class="checkmark"></span>
            </label>
            <button type="submit" class="btn">Đăng Nhập</button>
            <div class="options">
                <a href="/My-Web-Hotel/client/pages/forgotPass.php">Quên mật khẩu ?</a>
                <a href="/My-Web-Hotel/client/pages/signUp.php">Đăng ký</a>
            </div>
        </form>
    </div>
    <script src="/My-Web-Hotel/client/assets/js/loading.js?v=<?php echo time(); ?>"></script>
    <script>
    function togglePassword() {
        const passwordInput = document.getElementById("password");
        const eyeIcon = document.getElementById("eyeIcon");

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            eyeIcon.classList.remove("fa-eye");
            eyeIcon.classList.add("fa-eye-slash");
        } else {
            passwordInput.type = "password";
            eyeIcon.classList.remove("fa-eye-slash");
            eyeIcon.classList.add("fa-eye");
        }
    }
    </script>
</body>

</html>