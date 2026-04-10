<?php
session_start();
require '../includes/connect.php';

$error = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']);
    
    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập email và mật khẩu.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        // Kiểm tra nhân viên trong bảng nhan_vien
        $sql = 'SELECT id_nhan_vien, ma_nhan_vien, ho_ten, email, mat_khau, chuc_vu, trang_thai, anh_dai_dien 
                FROM nhan_vien 
                WHERE email = ? AND trang_thai = "Đang làm việc" 
                LIMIT 1';
        $stmt = $mysqli->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param('s', $email);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result && $result->num_rows === 1) {
                    $nhanVien = $result->fetch_assoc();
                    $storedPassword = $nhanVien['mat_khau'];
                    $loginOk = false;
                    
                    // Kiểm tra mật khẩu
                    if (!empty($storedPassword)) {
                        // Thử verify hash trước
                        if (password_verify($password, $storedPassword)) {
                            $loginOk = true;
                            // Rehash nếu cần
                            if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                if ($newHash !== false) {
                                    $upd = $mysqli->prepare('UPDATE nhan_vien SET mat_khau = ? WHERE id_nhan_vien = ?');
                                    if ($upd) {
                                        $upd->bind_param('si', $newHash, $nhanVien['id_nhan_vien']);
                                        $upd->execute();
                                        $upd->close();
                                    }
                                }
                            }
                        } else {
                            // Fallback plaintext (nếu DB đang lưu plaintext)
                            if ($password === $storedPassword) {
                                $loginOk = true;
                                // Migrate lên hash
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                if ($newHash !== false) {
                                    $upd = $mysqli->prepare('UPDATE nhan_vien SET mat_khau = ? WHERE id_nhan_vien = ?');
                                    if ($upd) {
                                        $upd->bind_param('si', $newHash, $nhanVien['id_nhan_vien']);
                                        $upd->execute();
                                        $upd->close();
                                    }
                                }
                            }
                        }
                    } else {
                        // Nếu chưa có mật khẩu, cho phép đăng nhập với mật khẩu mặc định
                        // Hoặc yêu cầu đặt mật khẩu
                        $error = 'Tài khoản chưa được thiết lập mật khẩu. Vui lòng liên hệ quản trị viên.';
                    }
                    
                    if ($loginOk) {
                        // Set session
                        $_SESSION['id_nhan_vien'] = $nhanVien['id_nhan_vien'];
                        $_SESSION['ma_nhan_vien'] = $nhanVien['ma_nhan_vien'];
                        $_SESSION['ho_ten'] = $nhanVien['ho_ten'];
                        $_SESSION['email'] = $nhanVien['email'];
                        $_SESSION['chuc_vu'] = $nhanVien['chuc_vu'];
                        $_SESSION['anh_dai_dien'] = $nhanVien['anh_dai_dien'] ?? '';
                        $_SESSION['logged_in_at'] = time();
                        $_SESSION['is_staff'] = true;
                        
                        // Nếu người dùng chọn "Ghi nhớ đăng nhập"
                        // Hiện tại hệ thống KHÔNG sử dụng bảng login_tokens, nên chỉ giữ session
                        // và không lưu token vào cookie/DB để tránh lỗi khi thiếu bảng.
                        
                        // Redirect - Nhân viên vào my-tasks, Quản lý vào home
                        $chuc_vu = $nhanVien['chuc_vu'];
                        $isManager = (
                            stripos($chuc_vu, 'Quản lý') !== false ||
                            stripos($chuc_vu, 'Manager') !== false ||
                            stripos($chuc_vu, 'Admin') !== false ||
                            stripos($chuc_vu, 'Giám đốc') !== false ||
                            stripos($chuc_vu, 'Director') !== false
                        );
                        
                        if ($isManager) {
                            // Quản lý: redirect đến trang được chỉ định hoặc home
                            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/My-Web-Hotel/admin/index.php';
                            $parsed = parse_url($redirect);
                            if (isset($parsed['host']) && $parsed['host'] !== $_SERVER['SERVER_NAME']) {
                                $redirect = '/My-Web-Hotel/admin/index.php';
                            }
                        } else {
                            // Nhân viên: luôn redirect đến my-tasks
                            $redirect = '/My-Web-Hotel/admin/index.php?page=my-tasks';
                        }
                        
                        header("Location: $redirect");
                        $stmt->close();
                        $mysqli->close();
                        exit;
                    } else {
                        $error = 'Email hoặc mật khẩu không đúng.';
                    }
                } else {
                    $error = 'Email hoặc mật khẩu không đúng.';
                }
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
            $stmt->close();
        } else {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng nhập quản lý khách sạn</title>
    <!-- favicon -->
    <link rel="icon" href="/My-Web-Hotel/client/assets/images/favicon.png" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
    :root {
        --primary: #deb666;
        --dark: #1e1e2f;
        --light: #ffffff;
    }

    body {
        margin: 0;
        padding: 0;
        background: url("https://images.unsplash.com/photo-1532274402911-5a369e4c4bb5?auto=format&fit=crop&q=60&w=900") no-repeat center center fixed;
        background-size: cover;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: "Poppins", sans-serif;
    }

    .login-card {
        background-color: rgba(30, 30, 47, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        width: 380px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        color: var(--light);
    }

    .login-card h2 {
        text-align: center;
        color: var(--primary);
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: #fff;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.25);
        box-shadow: 0 0 0 0.2rem rgba(222, 182, 102, 0.3);
        color: #fff;
    }

    ::placeholder {
        color: #ddd;
    }

    .btn-primary {
        background-color: var(--primary);
        border: none;
        font-weight: 500;
        transition: 0.3s;
    }

    .btn-primary:hover {
        background-color: #cfa955;
        transform: translateY(-2px);
    }

    .alert {
        margin-bottom: 1rem;
    }

    .password-wrapper {
        position: relative;
    }

    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #ddd;
        font-size: 1.1rem;
        transition: color 0.3s;
        z-index: 10;
    }

    .toggle-password:hover {
        color: var(--primary);
    }

    .password-wrapper .form-control {
        padding-right: 40px;
    }

    @media (max-width: 576px) {
        .login-card {
            width: 90%;
            padding: 1.5rem;
        }
    }
    </style>
</head>

<body>
    <div class="login-card">
        <h2>Đăng nhập quản lý khách sạn</h2>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <div class="password-wrapper">
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Nhập mật khẩu" required />
                    <span class="toggle-password" onclick="togglePassword()">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember" />
                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Đăng nhập</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }
    </script>
</body>

</html>