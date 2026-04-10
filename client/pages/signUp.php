<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register Page</title>
    <!-- favicon -->
    <link rel="icon" href="/My-Web-Hotel/client/assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/loading.css?v=<?php echo time(); ?>">
    <link href="/My-Web-Hotel/lib/Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/my-web-hotel/client/assets/css/signUp.css?v=<?php echo time(); ?>" />

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>

<body>
    <?php
    include '../includes/loading.php';
    ?>
    <!-- Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="modalHeader">
                    <h5 class="modal-title" id="notificationModalLabel">Thông Báo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"
                        id="closeBtn"></button>
                </div>
                <div class="modal-body text-center" id="notificationMessage">
                    <!-- Notification content will be inserted here -->
                </div>
                <div class="modal-footer" id="modalFooter">
                    <!-- Buttons will be inserted here dynamically -->
                </div>
            </div>
        </div>
    </div>

    <div class="login-box">
        <h2>Tạo Tài Khoản</h2>
        <form method="POST" action="../controller/register.php">
            <div class="input-box">
                <input type="text" name="full_name" placeholder="Họ và tên" required />
            </div>
            <div class="input-box">
                <input type="text" name="username" placeholder="Tên người dùng" required />
            </div>
            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required />
            </div>
            <div class="input-box">
                <input type="text" name="phone" placeholder="Số điện thoại" required />
            </div>
            <div class="input-box">
                <input type="password" name="password" id="password" placeholder="Mật khẩu" required />
                <span class="toggle-password" onclick="togglePassword('password', 'eyeIcon1')">
                    <i class="fa-solid fa-eye" id="eyeIcon1"></i>
                </span>
            </div>
            <div class="input-box">
                <input type="password" name="confirm_password" id="confirmPassword" placeholder="Nhập lại mật khẩu"
                    required />
                <span class="toggle-password" onclick="togglePassword('confirmPassword', 'eyeIcon2')">
                    <i class="fa-solid fa-eye" id="eyeIcon2"></i>
                </span>
            </div>
            <button type="submit" class="btn btn-primary">Đăng Ký</button>
            <div class="options">
                <p style="color: #fff;">Bạn đã có tài khoản?</p>
                <a href="/my-web-hotel/client/pages/login.php">Đăng nhập ngay</a>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS for modal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/My-Web-Hotel/client/assets/js/loading.js?v=<?php echo time(); ?>"></script>
    <script>
    // Show modal with message if there's a success or error message
    document.addEventListener('DOMContentLoaded', function() {
        const modalFooter = document.getElementById('modalFooter');
        const modalHeader = document.getElementById('modalHeader');
        const closeBtn = document.getElementById('closeBtn');

        <?php if (isset($_SESSION['success'])): ?>
        // Success case - show success modal with login button
        document.getElementById('notificationMessage').innerHTML =
            '<p class="mb-0"><?= addslashes($_SESSION['success']) ?></p>';


        // Add login button for success
        modalFooter.innerHTML =
            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><a href="/my-web-hotel/client/pages/login.php" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket me-2"></i>Đăng nhập</a>';

        const successModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        successModal.show();

        <?php unset($_SESSION['success']); ?>

        <?php elseif (isset($_SESSION['error'])): ?>
        // Error case - show error modal with close button only
        document.getElementById('notificationMessage').innerHTML =
            '<p class="mb-0"><?= addslashes($_SESSION['error']) ?></p>';


        // Add only close button for error
        modalFooter.innerHTML =
            '<button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Đóng</button>';

        const errorModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        errorModal.show();

        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    });

    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
    </script>
</body>

</html>