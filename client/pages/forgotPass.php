<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quên Mật Khẩu - OceanPearl Hotel</title>
    <link rel="icon" href="/My-Web-Hotel/client/assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/loading.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/forgotPass.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php 
    include '../includes/loading.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Reset email nếu chưa verify thành công (chỉ giữ email nếu đã verify)
    // Nếu có reset_verified trong session thì giữ email, nếu không thì xóa
    if (isset($_SESSION['reset_email']) && !isset($_SESSION['reset_verified'])) {
        // Xóa email khỏi session nếu chưa verify
        unset($_SESSION['reset_email']);
    }
    
    // Lấy email từ session (chỉ nếu đã verify mã thành công)
    $email = (isset($_SESSION['reset_email']) && isset($_SESSION['reset_verified'])) ? $_SESSION['reset_email'] : '';
    $hasEmail = !empty($email);
    
    // Ẩn một phần email để hiển thị (ví dụ: dt*****@gmail.com)
    $hiddenEmail = '';
    if ($hasEmail) {
        $emailParts = explode('@', $email);
        $emailPrefix = $emailParts[0];
        $emailDomain = $emailParts[1] ?? '';
        if (strlen($emailPrefix) > 2) {
            $hiddenEmail = substr($emailPrefix, 0, 2) . str_repeat('*', strlen($emailPrefix) - 2) . '@' . $emailDomain;
        } else {
            $hiddenEmail = substr($emailPrefix, 0, 1) . '*' . '@' . $emailDomain;
        }
    }
    ?>

    <div class="login-box">
        <h2>Quên Mật Khẩu</h2>

        <!-- Step 1: Nhập Email -->
        <div id="step1" class="step" style="<?php echo $hasEmail ? 'display: none;' : 'display: block;'; ?>">
            <p class="text-center mb-3 text-muted">
                Nhập email của bạn để nhận mã OTP
            </p>
            <form id="emailForm">
                <div class="input-box">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required autofocus />
                </div>
                <button type="submit" class="btn text-light">
                    <i class="fas fa-paper-plane me-2"></i>Gửi Mã OTP
                </button>
            </form>
        </div>

        <!-- Step 2: Nhập Mã OTP -->
        <div id="step2" class="step" style="<?php echo $hasEmail ? 'display: block;' : 'display: none;'; ?>">
            <div class="alert alert-info text-center mb-3">
                <i class="fas fa-envelope me-2"></i>
                <strong>Chúng tôi đã gửi mã OTP 6 chữ số đến email:</strong><br>
                <span class="text-primary fw-bold"
                    id="hiddenEmailDisplay"><?php echo htmlspecialchars($hiddenEmail); ?></span>
            </div>

            <form id="codeForm">
                <div class="input-box">
                    <i class="fas fa-shield-alt"></i>
                    <input type="text" id="code" name="code" placeholder="Nhập mã OTP 6 chữ số" maxlength="6"
                        pattern="[0-9]{6}" required autofocus />
                </div>

                <!-- Bộ đếm ngược -->
                <div class="text-center mb-3">
                    <p class="mb-1 text-muted">Mã OTP có hiệu lực trong:</p>
                    <div id="countdown" class="countdown-timer">
                        <span id="timer">02:00</span>
                    </div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-check me-2"></i>Xác Nhận Mã
                </button>

                <button type="button" class="btn btn-secondary mt-2" id="resendBtn" onclick="resendCode()"
                    style="display: none;">
                    <i class="fas fa-redo me-2"></i>Gửi Lại Mã
                </button>
            </form>
        </div>

        <!-- Step 3: Đặt Lại Mật Khẩu -->
        <div id="step3" class="step" style="display: none;">
            <form id="passwordForm">
                <div class="input-box">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new_password" name="new_password"
                        placeholder="Mật khẩu mới (tối thiểu 6 ký tự)" required />
                    <span class="toggle-password" onclick="togglePassword('new_password', this)">
                        <i class="fa-solid fa-eye"></i>
                    </span>
                </div>
                <div class="input-box">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password"
                        placeholder="Xác nhận mật khẩu mới" required />
                    <span class="toggle-password" onclick="togglePassword('confirm_password', this)">
                        <i class="fa-solid fa-eye"></i>
                    </span>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-save me-2"></i>Đặt Lại Mật Khẩu
                </button>
            </form>
        </div>

        <!-- Success Message -->
        <div id="successMessage" class="alert alert-success" style="display: none;">
            <i class="fas fa-check-circle me-2"></i>
            <span id="successText"></span>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="alert alert-danger" style="display: none;">
            <i class="fas fa-exclamation-circle me-2"></i>
            <span id="errorText"></span>
        </div>

        <div class="options">
            <a href="/My-Web-Hotel/client/pages/logIn.php">
                <i class="fas fa-arrow-left me-1"></i>Quay lại đăng nhập
            </a>
            <a href="/My-Web-Hotel/client/pages/signUp.php">Đăng ký</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/My-Web-Hotel/client/assets/js/loading.js?v=<?php echo time(); ?>"></script>
    <script>
    function togglePassword(inputId, toggleSpan) {
        const passwordInput = document.getElementById(inputId);
        const eyeIcon = toggleSpan.querySelector('i');

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
    let currentEmail = '<?php echo htmlspecialchars($email); ?>';
    let countdownInterval = null;
    let timeLeft = 120; // 2 phút = 120 giây

    // Bộ đếm ngược
    function startCountdown() {
        const timerElement = document.getElementById('timer');
        const resendBtn = document.getElementById('resendBtn');
        const submitBtn = document.getElementById('submitBtn');

        if (!timerElement) return;

        countdownInterval = setInterval(function() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent =
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                timerElement.textContent = '00:00';
                timerElement.parentElement.innerHTML = '<p class="text-danger mb-1">Mã OTP đã hết hạn</p>';
                if (resendBtn) resendBtn.style.display = 'block';
                if (submitBtn) submitBtn.disabled = true;
            } else {
                timeLeft--;
            }
        }, 1000);
    }

    // Step 1: Gửi mã khi nhập email
    const emailForm = document.getElementById('emailForm');
    if (emailForm) {
        emailForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();

            if (!email) {
                showMessage('Vui lòng nhập email', 'error');
                return;
            }

            showMessage('', '');
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang kiểm tra...';

            try {
                const response = await fetch('/My-Web-Hotel/client/controller/forgot-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=send_code&email=${encodeURIComponent(email)}`
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Phản hồi từ server không hợp lệ');
                }

                if (data.success) {
                    currentEmail = email;

                    // Chuyển sang step 2
                    document.getElementById('step1').style.display = 'none';
                    document.getElementById('step2').style.display = 'block';

                    // Cập nhật email hiển thị (ẩn một phần)
                    const emailParts = email.split('@');
                    const emailPrefix = emailParts[0];
                    const emailDomain = emailParts[1] || '';
                    let hiddenEmail = '';
                    if (emailPrefix.length > 2) {
                        hiddenEmail = emailPrefix.substring(0, 2) + '*'.repeat(emailPrefix.length - 2) +
                            '@' + emailDomain;
                    } else {
                        hiddenEmail = emailPrefix.substring(0, 1) + '*' + '@' + emailDomain;
                    }
                    document.getElementById('hiddenEmailDisplay').textContent = hiddenEmail;

                    // Bắt đầu đếm ngược
                    timeLeft = 120;
                    startCountdown();

                    // Focus vào input mã
                    document.getElementById('code').focus();

                    showMessage('Mã OTP đã được gửi đến email của bạn', 'success');
                } else {
                    showMessage(data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (error) {
                showMessage('Lỗi kết nối: ' + error.message +
                    '. Vui lòng kiểm tra console để xem chi tiết.', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }

    // Tự động gửi mã khi trang load (nếu đã có email trong session)
    window.addEventListener('DOMContentLoaded', async function() {
        if (currentEmail) {
            // Đã có email trong session, tự động gửi mã
            try {
                const response = await fetch('/My-Web-Hotel/client/controller/forgot-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=send_code&email=${encodeURIComponent(currentEmail)}`
                });

                const data = await response.json();
                if (data.success) {
                    startCountdown();
                } else {
                    // Nếu không gửi được, quay lại step 1
                    document.getElementById('step2').style.display = 'none';
                    document.getElementById('step1').style.display = 'block';
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                // Quay lại step 1 nếu có lỗi
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step1').style.display = 'block';
            }
        }
    });

    // Xác nhận mã OTP
    document.getElementById('codeForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const code = document.getElementById('code').value.trim();

        if (code.length !== 6) {
            showMessage('Vui lòng nhập đầy đủ 6 chữ số', 'error');
            return;
        }

        showMessage('', '');
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xác nhận...';

        try {
            const response = await fetch('/My-Web-Hotel/client/controller/forgot-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=verify_code&email=${encodeURIComponent(currentEmail)}&code=${encodeURIComponent(code)}`
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Phản hồi từ server không hợp lệ');
            }

            if (data.success) {
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';
                showMessage(data.message, 'success');
            } else {
                showMessage(data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Lỗi kết nối: ' + error.message + '. Vui lòng kiểm tra console để xem chi tiết.',
                'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // Step 3: Đặt lại mật khẩu
    document.getElementById('passwordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword !== confirmPassword) {
            showMessage('Mật khẩu xác nhận không khớp', 'error');
            return;
        }

        if (newPassword.length < 6) {
            showMessage('Mật khẩu phải có ít nhất 6 ký tự', 'error');
            return;
        }

        showMessage('', '');
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';

        try {
            const response = await fetch('/My-Web-Hotel/client/controller/forgot-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=reset_password&email=${encodeURIComponent(currentEmail)}&new_password=${encodeURIComponent(newPassword)}&confirm_password=${encodeURIComponent(confirmPassword)}`
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Phản hồi từ server không hợp lệ');
            }

            if (data.success) {
                document.getElementById('step3').style.display = 'none';
                showMessage(data.message +
                    ' <a href="/My-Web-Hotel/client/pages/logIn.php">Đăng nhập ngay</a>', 'success');
            } else {
                showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Lỗi kết nối: ' + error.message + '. Vui lòng kiểm tra console để xem chi tiết.',
                'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // Gửi lại mã
    async function resendCode() {
        if (!currentEmail) return;

        showMessage('', '');
        const resendBtn = document.getElementById('resendBtn');
        const submitBtn = document.getElementById('submitBtn');
        const originalText = resendBtn.innerHTML;
        resendBtn.disabled = true;
        resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang gửi...';

        try {
            const response = await fetch('/My-Web-Hotel/client/controller/forgot-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=send_code&email=${encodeURIComponent(currentEmail)}`
            });

            const data = await response.json();

            if (data.success) {
                // Reset countdown
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }
                timeLeft = 120;
                const countdownDiv = document.querySelector('.text-center.mb-3');
                countdownDiv.innerHTML = `
                        <p class="mb-1 text-muted">Mã OTP có hiệu lực trong:</p>
                        <div id="countdown" class="countdown-timer">
                            <span id="timer">02:00</span>
                        </div>
                    `;
                startCountdown();

                // Reset form
                document.getElementById('code').value = '';
                resendBtn.style.display = 'none';
                submitBtn.disabled = false;

                showMessage('Mã OTP mới đã được gửi đến email của bạn', 'success');
            } else {
                showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Lỗi kết nối: ' + error.message + '. Vui lòng kiểm tra console để xem chi tiết.', 'error');
        } finally {
            resendBtn.disabled = false;
            resendBtn.innerHTML = originalText;
        }
    }

    // Hiển thị thông báo
    function showMessage(message, type) {
        const successDiv = document.getElementById('successMessage');
        const errorDiv = document.getElementById('errorMessage');

        successDiv.style.display = 'none';
        errorDiv.style.display = 'none';

        if (message) {
            if (type === 'success') {
                document.getElementById('successText').innerHTML = message;
                successDiv.style.display = 'block';
            } else {
                document.getElementById('errorText').textContent = message;
                errorDiv.style.display = 'block';
            }
        }
    }

    // Chỉ cho phép nhập số cho mã và tự động loại bỏ khoảng trắng
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function(e) {
            // Loại bỏ tất cả ký tự không phải số
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Khi paste, tự động loại bỏ khoảng trắng
        codeInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            // Loại bỏ tất cả ký tự không phải số
            const cleaned = pastedText.replace(/[^0-9]/g, '');
            this.value = cleaned.substring(0, 6); // Chỉ lấy 6 chữ số đầu
        });
    }
    </script>
</body>

</html>