<?php
// Lấy message từ session nếu có
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$messageType = isset($_SESSION['messageType']) ? $_SESSION['messageType'] : '';
unset($_SESSION['message']);
unset($_SESSION['messageType']);

// Lấy thông tin nhân viên hiện tại
$id_nhan_vien = $_SESSION['id_nhan_vien'];
$stmt = $mysqli->prepare("SELECT * FROM nhan_vien WHERE id_nhan_vien = ?");
$stmt->bind_param("i", $id_nhan_vien);
$stmt->execute();
$result = $stmt->get_result();
$nhanVien = $result->fetch_assoc();
$stmt->close();

// Cập nhật thông tin cá nhân
// Chỉ xử lý nếu không được gọi từ ProfileController (ProfileController đã xử lý POST)
$processedByController = isset($_SESSION['message']) && isset($_SESSION['messageType']);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile']) && !$processedByController) {
    $ho_ten = trim($_POST['ho_ten']);
    $dien_thoai = trim($_POST['dien_thoai'] ?? '');
    $ngay_sinh = !empty($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : null;
    $gioi_tinh = $_POST['gioi_tinh'] ?? 'Nam';
    $cmnd_cccd = trim($_POST['cmnd_cccd'] ?? '');
    $dia_chi = trim($_POST['dia_chi'] ?? '');
    
    // Validation mật khẩu
    $passwordError = '';
    if (!empty($_POST['mat_khau_moi']) || !empty($_POST['mat_khau_moi_confirm'])) {
        if (empty($_POST['mat_khau_moi'])) {
            $passwordError = 'Vui lòng nhập mật khẩu mới';
        } elseif (strlen($_POST['mat_khau_moi']) < 8) {
            $passwordError = 'Mật khẩu phải có ít nhất 8 ký tự';
        } elseif ($_POST['mat_khau_moi'] !== $_POST['mat_khau_moi_confirm']) {
            $passwordError = 'Mật khẩu nhập lại không khớp';
        }
    }
    
    // Upload ảnh nếu có
    $anh_dai_dien = $nhanVien['anh_dai_dien'] ?? '';
    $uploadError = '';
    
    if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] == UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Kiểm tra lỗi upload
        if ($_FILES['anh_dai_dien']['error'] != UPLOAD_ERR_OK) {
            $uploadError = 'Lỗi upload: ' . $_FILES['anh_dai_dien']['error'];
        } elseif (!in_array($_FILES['anh_dai_dien']['type'], $allowedTypes)) {
            $uploadError = 'Định dạng file không hợp lệ. Chỉ chấp nhận: JPG, PNG, GIF, WEBP';
        } elseif ($_FILES['anh_dai_dien']['size'] > $maxSize) {
            $uploadError = 'File quá lớn. Kích thước tối đa: 5MB';
        } else {
            require_once __DIR__ . '/../includes/cloudinary_helper.php';
            
            // Upload lên Cloudinary
            $cloudinaryUrl = CloudinaryHelper::upload($_FILES['anh_dai_dien']['tmp_name'], 'staff');
            
            if ($cloudinaryUrl !== false) {
                // Xóa ảnh cũ trên Cloudinary nếu có
                if (!empty($anh_dai_dien)) {
                    CloudinaryHelper::deleteByUrl($anh_dai_dien);
                }
                $anh_dai_dien = $cloudinaryUrl;
            }
        }
    } elseif (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] != UPLOAD_ERR_NO_FILE) {
        $uploadError = 'Lỗi upload file. Mã lỗi: ' . $_FILES['anh_dai_dien']['error'];
    }
    
    // Chỉ tiếp tục update nếu không có lỗi mật khẩu
    if (empty($passwordError)) {
        // Cập nhật mật khẩu nếu có
        $updatePassword = '';
        $mat_khau_moi = null;
        if (!empty($_POST['mat_khau_moi'])) {
            $mat_khau_moi = password_hash($_POST['mat_khau_moi'], PASSWORD_DEFAULT);
            $updatePassword = ", mat_khau = ?";
        }
        
        if ($updatePassword) {
            $stmt = $mysqli->prepare("UPDATE nhan_vien SET ho_ten=?, dien_thoai=?, ngay_sinh=?, gioi_tinh=?, cmnd_cccd=?, dia_chi=?, anh_dai_dien=?" . $updatePassword . " WHERE id_nhan_vien=?");
            $stmt->bind_param("ssssssssi", $ho_ten, $dien_thoai, $ngay_sinh, $gioi_tinh, $cmnd_cccd, $dia_chi, $anh_dai_dien, $mat_khau_moi, $id_nhan_vien);
        } else {
            $stmt = $mysqli->prepare("UPDATE nhan_vien SET ho_ten=?, dien_thoai=?, ngay_sinh=?, gioi_tinh=?, cmnd_cccd=?, dia_chi=?, anh_dai_dien=? WHERE id_nhan_vien=?");
            $stmt->bind_param("sssssssi", $ho_ten, $dien_thoai, $ngay_sinh, $gioi_tinh, $cmnd_cccd, $dia_chi, $anh_dai_dien, $id_nhan_vien);
        }
        
        if ($stmt->execute()) {
            $_SESSION['ho_ten'] = $ho_ten;
            $_SESSION['anh_dai_dien'] = $anh_dai_dien;
            
            $stmt->close();
            
            $stmt = $mysqli->prepare("SELECT * FROM nhan_vien WHERE id_nhan_vien = ?");
            $stmt->bind_param("i", $id_nhan_vien);
            $stmt->execute();
            $result = $stmt->get_result();
            $nhanVien = $result->fetch_assoc();
            $stmt->close();
            
            if ($uploadError) {
                $message = 'Cập nhật thông tin thành công! Tuy nhiên: ' . $uploadError;
                $messageType = 'warning';
            } else {
                $message = 'Cập nhật thông tin thành công!';
                $messageType = 'success';
            }
            $updateAvatar = true;
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            if ($uploadError) {
                $message .= ' | ' . $uploadError;
            }
            $messageType = 'danger';
            $stmt->close();
        }
    } else {
        // Có lỗi mật khẩu
        $message = $passwordError;
        if ($uploadError) {
            $message .= ' | ' . $uploadError;
        }
        $messageType = 'danger';
    }
}

// Tạo URL avatar - URL từ database đã là Cloudinary URL
$avatarUrl = '';
if (!empty($nhanVien['anh_dai_dien'])) {
        $avatarUrl = $nhanVien['anh_dai_dien'];
} else {
    $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($nhanVien['ho_ten']) . '&background=deb666&color=fff&size=200';
}
?>

<div class="main-content">
    <!-- Banner Header -->
    <div class="profile-banner">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="avatar-wrapper">
                    <img src="<?php echo h($avatarUrl); ?>" alt="Avatar" class="profile-avatar" id="profileAvatar"
                        data-original-src="<?php echo h($avatarUrl); ?>"
                        onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($nhanVien['ho_ten']); ?>&background=deb666&color=fff&size=200'">
                    <button type="button" class="avatar-upload-btn"
                        onclick="document.getElementById('avatarInput').click()">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
            </div>
            <div class="col">
                <h2 class="mb-2 fw-bold"><?php echo h($nhanVien['ho_ten']); ?></h2>
                <p class="mb-2 opacity-75">
                    <i class="fas fa-id-badge me-2"></i><?php echo h($nhanVien['ma_nhan_vien']); ?>
                    <span class="mx-2">•</span>
                    <i class="fas fa-envelope me-2"></i><?php echo h($nhanVien['email']); ?>
                </p>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-modern alert-dismissible fade show" role="alert">
        <i
            class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : ($messageType == 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'); ?> me-2"></i>
        <strong><?php echo $messageType == 'success' ? 'Thành công!' : ($messageType == 'warning' ? 'Cảnh báo!' : 'Lỗi!'); ?></strong>
        <?php echo h($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php if (isset($updateAvatar) && $updateAvatar): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const timestamp = new Date().getTime();
        const avatarImg = document.getElementById('profileAvatar');
        if (avatarImg) {
            let currentSrc = avatarImg.getAttribute('data-original-src') || avatarImg.src;
            if (currentSrc && currentSrc.startsWith('data:')) {
                return;
            }
            let baseUrl = currentSrc;
            const queryIndex = baseUrl.indexOf('?');
            if (queryIndex !== -1) {
                baseUrl = baseUrl.substring(0, queryIndex);
            }
            if (baseUrl && baseUrl.length > 0 && baseUrl !== '?v' && !baseUrl.startsWith('data:')) {
                avatarImg.src = baseUrl + '?v=' + timestamp;
            }
        }
        const sidebarAvatar = document.querySelector('.sidebar .header img');
        if (sidebarAvatar) {
            let sidebarSrc = sidebarAvatar.src;
            if (sidebarSrc && !sidebarSrc.startsWith('data:')) {
                const sidebarQueryIndex = sidebarSrc.indexOf('?');
                if (sidebarQueryIndex !== -1) {
                    sidebarSrc = sidebarSrc.substring(0, sidebarQueryIndex);
                }
                if (sidebarSrc && sidebarSrc.length > 0 && sidebarSrc !== '?v') {
                    sidebarAvatar.src = sidebarSrc + '?v=' + timestamp;
                }
            }
        }
    });
    </script>
    <?php endif; ?>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="profileForm">
        <input type="file" id="avatarInput" name="anh_dai_dien"
            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display: none;">

        <div class="row">
            <!-- Cột trái -->
            <div class="col-lg-8">
                <!-- Thông Tin Cơ Bản -->
                <div class="card card-modern mb-4">
                    <div class="card-header card-header-gold">
                        <h5><i class="fas fa-user-edit"></i> Thông Tin Cá Nhân</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-user form-label-icon"></i>Họ và Tên *
                                </label>
                                <input type="text" class="form-control form-control-modern" name="ho_ten"
                                    value="<?php echo h($nhanVien['ho_ten']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-phone form-label-icon"></i>Số Điện Thoại
                                </label>
                                <input type="text" class="form-control form-control-modern" name="dien_thoai"
                                    value="<?php echo h($nhanVien['dien_thoai']); ?>" placeholder="Nhập số điện thoại">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-envelope form-label-icon"></i>Email
                                </label>
                                <input type="email" class="form-control form-control-modern"
                                    value="<?php echo h($nhanVien['email']); ?>" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-id-card form-label-icon"></i>CMND/CCCD
                                </label>
                                <input type="text" class="form-control form-control-modern" name="cmnd_cccd"
                                    value="<?php echo h($nhanVien['cmnd_cccd']); ?>" placeholder="Nhập số CMND/CCCD">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-birthday-cake form-label-icon"></i>Ngày Sinh
                                </label>
                                <input type="date" class="form-control form-control-modern" name="ngay_sinh"
                                    value="<?php echo $nhanVien['ngay_sinh'] ? h($nhanVien['ngay_sinh']) : ''; ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-venus-mars form-label-icon"></i>Giới Tính
                                </label>
                                <select class="form-select form-control-modern" name="gioi_tinh">
                                    <option value="Nam"
                                        <?php echo $nhanVien['gioi_tinh'] == 'Nam' ? 'selected' : ''; ?>>Nam</option>
                                    <option value="Nữ" <?php echo $nhanVien['gioi_tinh'] == 'Nữ' ? 'selected' : ''; ?>>
                                        Nữ</option>
                                    <option value="Khác"
                                        <?php echo $nhanVien['gioi_tinh'] == 'Khác' ? 'selected' : ''; ?>>Khác</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-map-marker-alt form-label-icon"></i>Địa Chỉ
                                </label>
                                <textarea class="form-control form-control-modern" name="dia_chi" rows="3"
                                    placeholder="Nhập địa chỉ"><?php echo h($nhanVien['dia_chi']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải -->
            <div class="col-lg-4">
                <!-- Thông Tin Công Việc -->
                <div class="card card-modern mb-4">
                    <div class="card-header card-header-gold">
                        <h5><i class="fas fa-briefcase"></i> Thông Tin Công Việc</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="info-box mb-3">
                            <div class="info-box-label">Mã Nhân Viên</div>
                            <div class="info-box-value"><?php echo h($nhanVien['ma_nhan_vien']); ?></div>
                        </div>

                        <div class="info-box mb-3">
                            <div class="info-box-label">Chức Vụ</div>
                            <div class="info-box-value"><?php echo h($nhanVien['chuc_vu']); ?></div>
                        </div>

                        <div class="info-box mb-3">
                            <div class="info-box-label">Phòng Ban</div>
                            <div class="info-box-value"><?php echo h($nhanVien['phong_ban'] ?: 'Chưa phân công'); ?>
                            </div>
                        </div>

                        <div class="info-box">
                            <div class="info-box-label">Ngày Vào Làm</div>
                            <div class="info-box-value"><?php echo formatDate($nhanVien['ngay_vao_lam']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Đổi Mật Khẩu -->
        <div class="card card-modern mb-4">
            <div class="card-header card-header-gold">
                <h5><i class="fas fa-key"></i> Bảo Mật & Mật Khẩu</h5>
            </div>
            <div class="card-body p-4">
                <div class="security-alert mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-shield-alt fs-3 text-danger me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold text-danger">Lưu ý bảo mật</h6>
                            <p class="mb-0 small text-muted">Chỉ nhập mật khẩu mới nếu bạn muốn thay đổi. Để
                                trống nếu không muốn đổi mật khẩu.</p>
                        </div>
                    </div>
                </div>

                <div class="mb-0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mật Khẩu Mới</label>
                            <input type="password" class="form-control form-control-modern" name="mat_khau_moi"
                                id="mat_khau_moi" placeholder="Nhập mật khẩu mới (tối thiểu 8 ký tự)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nhập Lại Mật Khẩu Mới</label>
                            <input type="password" class="form-control form-control-modern" name="mat_khau_moi_confirm"
                                id="mat_khau_moi_confirm" placeholder="Nhập lại mật khẩu mới">
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>Mật khẩu nên có ít nhất 8 ký tự, bao gồm chữ hoa,
                        chữ thường và số
                    </small>
                </div>
            </div>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                <i class="fas fa-times me-2"></i>Hủy Bỏ
            </button>
            <button type="submit" name="update_profile" class="btn btn-gold">
                <i class="fas fa-save me-2"></i>Lưu Thay Đổi
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatarInput');
    const profileAvatar = document.getElementById('profileAvatar');

    if (avatarInput && profileAvatar) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('⚠️ Kích thước file không được vượt quá 5MB');
                    this.value = '';
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                    'image/webp'
                ];
                if (!allowedTypes.includes(file.type)) {
                    alert('⚠️ Chỉ chấp nhận file ảnh định dạng JPG, PNG, GIF hoặc WEBP');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    profileAvatar.src = e.target.result;
                    profileAvatar.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        profileAvatar.style.transition = 'transform 0.3s ease';
                        profileAvatar.style.transform = 'scale(1)';
                    }, 100);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Validation mật khẩu khi submit
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const matKhauMoi = document.getElementById('mat_khau_moi').value;
            const matKhauConfirm = document.getElementById('mat_khau_moi_confirm').value;

            // Chỉ validate nếu có nhập mật khẩu
            if (matKhauMoi || matKhauConfirm) {
                if (matKhauMoi.length < 8) {
                    e.preventDefault();
                    alert('⚠️ Mật khẩu phải có ít nhất 8 ký tự');
                    return false;
                }

                if (matKhauMoi !== matKhauConfirm) {
                    e.preventDefault();
                    alert('⚠️ Mật khẩu nhập lại không khớp');
                    return false;
                }
            }
        });
    }

    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    if (window.location.search.includes('t=')) {
        const url = new URL(window.location);
        url.searchParams.delete('t');
        window.history.replaceState({}, '', url);
    }
});
</script>