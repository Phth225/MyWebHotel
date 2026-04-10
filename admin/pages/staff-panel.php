<?php
// Phân quyền module Nhân Viên
$canViewStaff   = function_exists('checkPermission') ? checkPermission('employee.view')   : true;
$canCreateStaff = function_exists('checkPermission') ? checkPermission('employee.create') : true;
$canEditStaff   = function_exists('checkPermission') ? checkPermission('employee.edit')   : true;
$canDeleteStaff = function_exists('checkPermission') ? checkPermission('employee.delete') : true;

if (!$canViewStaff) {
    http_response_code(403);
    echo '<div class="alert alert-danger m-4">Bạn không có quyền xem trang nhân viên.</div>';
    return;
}

// Xử lý CRUD
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$messageType = '';

// Xử lý upload ảnh lên Cloudinary
function uploadAvatar($file, $oldAvatar = '') {
    if (!isset($file['name']) || empty($file['name'])) {
        return $oldAvatar;
    }
    
    require_once __DIR__ . '/../includes/cloudinary_helper.php';
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Upload lên Cloudinary
    $cloudinaryUrl = CloudinaryHelper::upload($file['tmp_name'], 'staff');
    
    if ($cloudinaryUrl !== false) {
        // Xóa ảnh cũ trên Cloudinary nếu có
        if (!empty($oldAvatar)) {
            CloudinaryHelper::deleteByUrl($oldAvatar);
        }
        return $cloudinaryUrl;
            }
    
    return $oldAvatar;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_nhan_vien']) && $canCreateStaff) {
        $ma_nhan_vien = trim($_POST['ma_nhan_vien']);
        $ho_ten = trim($_POST['ho_ten']);
        $email = trim($_POST['email']);
        $dien_thoai = trim($_POST['dien_thoai']);
        $ngay_sinh = $_POST['ngay_sinh'];
        $gioi_tinh = $_POST['gioi_tinh'];
        $chuc_vu = $_POST['chuc_vu'];
        $phong_ban = trim($_POST['phong_ban'] ?? '');
        $ngay_vao_lam = $_POST['ngay_vao_lam'];
        $luong_co_ban = isset($_POST['luong_co_ban']) ? floatval($_POST['luong_co_ban']) : 0;
        $trang_thai = $_POST['trang_thai'];
        $dia_chi = trim($_POST['dia_chi'] ?? '');
        $ghi_chu = trim($_POST['ghi_chu'] ?? '');
        $mat_khau = password_hash(trim($_POST['mat_khau']), PASSWORD_DEFAULT);
        
        $anh_dai_dien = '';
        if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] == 0) {
            $uploadResult = uploadAvatar($_FILES['anh_dai_dien']);
            if ($uploadResult !== false) {
                $anh_dai_dien = $uploadResult;
            }
        }
        
        $stmt = $mysqli->prepare("INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, email, dien_thoai, ngay_sinh, gioi_tinh, chuc_vu, phong_ban, ngay_vao_lam, luong_co_ban, trang_thai, dia_chi, ghi_chu, mat_khau, anh_dai_dien) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssdsssss", $ma_nhan_vien, $ho_ten, $email, $dien_thoai, $ngay_sinh, $gioi_tinh, $chuc_vu, $phong_ban, $ngay_vao_lam, $luong_co_ban, $trang_thai, $dia_chi, $ghi_chu, $mat_khau, $anh_dai_dien);
        
        if ($stmt->execute()) {
            $message = 'Thêm nhân viên thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
    
    if (isset($_POST['update_nhan_vien']) && $canEditStaff) {
        $id_nhan_vien = intval($_POST['id_nhan_vien']);
        $ma_nhan_vien = trim($_POST['ma_nhan_vien']);
        $ho_ten = trim($_POST['ho_ten']);
        $email = trim($_POST['email']);
        $dien_thoai = trim($_POST['dien_thoai']);
        $ngay_sinh = $_POST['ngay_sinh'];
        $gioi_tinh = $_POST['gioi_tinh'];
        $chuc_vu = $_POST['chuc_vu'];
        $phong_ban = trim($_POST['phong_ban'] ?? '');
        $ngay_vao_lam = $_POST['ngay_vao_lam'];
        $luong_co_ban = isset($_POST['luong_co_ban']) ? floatval($_POST['luong_co_ban']) : 0;
        $trang_thai = $_POST['trang_thai'];
        $dia_chi = trim($_POST['dia_chi'] ?? '');
        $ghi_chu = trim($_POST['ghi_chu'] ?? '');
        
        // Lấy ảnh cũ
        $oldAvatarStmt = $mysqli->prepare("SELECT anh_dai_dien FROM nhan_vien WHERE id_nhan_vien = ?");
        $oldAvatarStmt->bind_param("i", $id_nhan_vien);
        $oldAvatarStmt->execute();
        $oldAvatarResult = $oldAvatarStmt->get_result();
        $oldAvatar = $oldAvatarResult->fetch_assoc()['anh_dai_dien'] ?? '';
        $oldAvatarStmt->close();
        
        $anh_dai_dien = $oldAvatar;
        if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] == 0) {
            $uploadResult = uploadAvatar($_FILES['anh_dai_dien'], $oldAvatar);
            if ($uploadResult !== false) {
                $anh_dai_dien = $uploadResult;
            }
        }
        
        // Cập nhật mật khẩu nếu có
        if (!empty($_POST['mat_khau'])) {
            $mat_khau = password_hash(trim($_POST['mat_khau']), PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE nhan_vien SET ma_nhan_vien=?, ho_ten=?, email=?, dien_thoai=?, ngay_sinh=?, gioi_tinh=?, chuc_vu=?, phong_ban=?, ngay_vao_lam=?, luong_co_ban=?, trang_thai=?, dia_chi=?, ghi_chu=?, mat_khau=?, anh_dai_dien=? WHERE id_nhan_vien=?");
            $stmt->bind_param("sssssssssdsssssi", $ma_nhan_vien, $ho_ten, $email, $dien_thoai, $ngay_sinh, $gioi_tinh, $chuc_vu, $phong_ban, $ngay_vao_lam, $luong_co_ban, $trang_thai, $dia_chi, $ghi_chu, $mat_khau, $anh_dai_dien, $id_nhan_vien);
        } else {
            $stmt = $mysqli->prepare("UPDATE nhan_vien SET ma_nhan_vien=?, ho_ten=?, email=?, dien_thoai=?, ngay_sinh=?, gioi_tinh=?, chuc_vu=?, phong_ban=?, ngay_vao_lam=?, luong_co_ban=?, trang_thai=?, dia_chi=?, ghi_chu=?, anh_dai_dien=? WHERE id_nhan_vien=?");
            $stmt->bind_param("sssssssssdssssi", $ma_nhan_vien, $ho_ten, $email, $dien_thoai, $ngay_sinh, $gioi_tinh, $chuc_vu, $phong_ban, $ngay_vao_lam, $luong_co_ban, $trang_thai, $dia_chi, $ghi_chu, $anh_dai_dien, $id_nhan_vien);
        }
        
        if ($stmt->execute()) {
            $message = 'Cập nhật nhân viên thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
    
    if (isset($_POST['delete_nhan_vien']) && $canDeleteStaff) {
        $id_nhan_vien = intval($_POST['id_nhan_vien']);
        $stmt = $mysqli->prepare("UPDATE nhan_vien SET trang_thai='Thôi việc' WHERE id_nhan_vien=?");
        $stmt->bind_param("i", $id_nhan_vien);
        if ($stmt->execute()) {
            $message = 'Xóa nhân viên thành công!';
            $messageType = 'success';
            if (function_exists('safe_redirect')) {
                safe_redirect("index.php?page=staff-manager&panel=staff-panel");
            } else {
                echo "<script>window.location.href = 'index.php?page=staff-manager&panel=staff-panel';</script>";
                exit;
            }
        } else {
            $message = 'Xóa nhân viên thất bại';
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Lấy thông tin nhân viên để edit
$editNhanVien = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM nhan_vien WHERE id_nhan_vien = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editNhanVien = $result->fetch_assoc();
    $stmt->close();
}

// Phân trang và tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$chuc_vu_filter = isset($_GET['chuc_vu']) ? trim($_GET['chuc_vu']) : '';
$phong_ban_filter = isset($_GET['phong_ban']) ? trim($_GET['phong_ban']) : '';
$trang_thai_filter = isset($_GET['trang_thai']) ? trim($_GET['trang_thai']) : '';
$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum);
$perPage = 10;
$offset = ($pageNum - 1) * $perPage;

// Xây dựng query
$where = "WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (ho_ten LIKE ? OR ma_nhan_vien LIKE ? OR email LIKE ? OR dien_thoai LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= 'ssss';
}

if ($chuc_vu_filter) {
    $where .= " AND chuc_vu = ?";
    $params[] = $chuc_vu_filter;
    $types .= 's';
}

if ($phong_ban_filter) {
    $where .= " AND phong_ban = ?";
    $params[] = $phong_ban_filter;
    $types .= 's';
}

if ($trang_thai_filter) {
    $where .= " AND trang_thai = ?";
    $params[] = $trang_thai_filter;
    $types .= 's';
}

// Đếm tổng số
$countQuery = "SELECT COUNT(*) as total FROM nhan_vien $where";
$countStmt = $mysqli->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$countStmt->close();

// Lấy dữ liệu
$query = "SELECT * FROM nhan_vien $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$nhanVienList = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lấy danh sách chức vụ và phòng ban để filter
$chucVuList = $mysqli->query("SELECT DISTINCT chuc_vu FROM nhan_vien WHERE chuc_vu IS NOT NULL ORDER BY chuc_vu")->fetch_all(MYSQLI_ASSOC);
$phongBanList = $mysqli->query("SELECT DISTINCT phong_ban FROM nhan_vien WHERE phong_ban IS NOT NULL AND phong_ban != '' ORDER BY phong_ban")->fetch_all(MYSQLI_ASSOC);

// Build base URL for pagination
$baseUrl = "index.php?page=staff-manager&panel=staff-panel";
if ($search) $baseUrl .= "&search=" . urlencode($search);
if ($chuc_vu_filter) $baseUrl .= "&chuc_vu=" . urlencode($chuc_vu_filter);
if ($phong_ban_filter) $baseUrl .= "&phong_ban=" . urlencode($phong_ban_filter);
if ($trang_thai_filter) $baseUrl .= "&trang_thai=" . urlencode($trang_thai_filter);
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
    <?php echo h($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="content-card">
    <div class="card-header-custom">
        <h3 class="card-title">Danh Sách Nhân Viên</h3>
        <?php if ($canCreateStaff): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-plus"></i> Thêm Nhân Viên
        </button>
        <?php endif; ?>
    </div>

    <div class="filter-section">
        <form method="GET" action="">
            <input type="hidden" name="page" value="staff-manager">
            <input type="hidden" name="panel" value="staff-panel">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Tìm kiếm nhân viên..."
                            value="<?php echo h($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="chuc_vu">
                        <option value="">Tất cả chức vụ</option>
                        <?php foreach ($chucVuList as $cv): ?>
                        <option value="<?php echo h($cv['chuc_vu']); ?>"
                            <?php echo $chuc_vu_filter == $cv['chuc_vu'] ? 'selected' : ''; ?>>
                            <?php echo h($cv['chuc_vu']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="phong_ban">
                        <option value="">Tất cả phòng ban</option>
                        <?php foreach ($phongBanList as $pb): ?>
                        <option value="<?php echo h($pb['phong_ban']); ?>"
                            <?php echo $phong_ban_filter == $pb['phong_ban'] ? 'selected' : ''; ?>>
                            <?php echo h($pb['phong_ban']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="trang_thai">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Đang làm việc"
                            <?php echo $trang_thai_filter == 'Đang làm việc' ? 'selected' : ''; ?>>Đang làm việc
                        </option>
                        <option value="Nghỉ" <?php echo $trang_thai_filter == 'Nghỉ' ? 'selected' : ''; ?>>Nghỉ</option>
                        <option value="Thôi việc" <?php echo $trang_thai_filter == 'Thôi việc' ? 'selected' : ''; ?>>
                            Thôi việc</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã NV</th>
                    <th>Họ Tên</th>
                    <th>Chức Vụ</th>
                    <th>Phòng Ban</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($nhanVienList)): ?>
                <tr>
                    <td colspan="9" class="text-center">Không có dữ liệu</td>
                </tr>
                <?php else: ?>
                <?php foreach ($nhanVienList as $nv): ?>
                <tr>
                    <td><?php echo $nv['id_nhan_vien']; ?></td>
                    <td><?php echo h($nv['ma_nhan_vien']); ?></td>
                    <td><?php echo h($nv['ho_ten']); ?></td>
                    <td><?php echo h($nv['chuc_vu']); ?></td>
                    <td><?php echo h($nv['phong_ban'] ?? '-'); ?></td>
                    <td>
                        <?php
                                $statusClass = 'bg-secondary';
                                switch ($nv['trang_thai']) {
                                    case 'Đang làm việc':
                                        $statusClass = 'bg-success';
                                        break;
                                    case 'Nghỉ':
                                        $statusClass = 'bg-warning';
                                        break;
                                    case 'Thôi việc':
                                        $statusClass = 'bg-danger';
                                        break;
                                }
                                ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo h($nv['trang_thai']); ?></span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info"
                            onclick="viewEmployee(<?php echo $nv['id_nhan_vien']; ?>)" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($canEditStaff): ?>
                        <button class="btn btn-sm btn-outline-warning"
                            onclick="editEmployee(<?php echo $nv['id_nhan_vien']; ?>)" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($canDeleteStaff): ?>
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="confirmDeleteStaff(<?php echo $nv['id_nhan_vien']; ?>)" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <?php if (function_exists('checkPermission') && checkPermission('employee.set_permission')): ?>
                        <button class="btn btn-sm btn-outline-primary"
                            onclick="openPermissionModal(<?php echo $nv['id_nhan_vien']; ?>)" title="Phân quyền">
                            <i class="fas fa-key"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php echo getPagination($total, $perPage, $pageNum, $baseUrl); ?>
</div>

<!-- Modal: Thêm/Sửa Nhân Viên -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $editNhanVien ? 'Sửa' : 'Thêm'; ?> Nhân Viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php if ($editNhanVien): ?>
                    <input type="hidden" name="id_nhan_vien" value="<?php echo $editNhanVien['id_nhan_vien']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mã Nhân Viên *</label>
                            <input type="text" class="form-control" name="ma_nhan_vien" required
                                value="<?php echo $editNhanVien ? h($editNhanVien['ma_nhan_vien']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Họ Tên *</label>
                            <input type="text" class="form-control" name="ho_ten" required
                                value="<?php echo $editNhanVien ? h($editNhanVien['ho_ten']) : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required
                                value="<?php echo $editNhanVien ? h($editNhanVien['email']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Điện Thoại *</label>
                            <input type="text" class="form-control" name="dien_thoai" required
                                value="<?php echo $editNhanVien ? h($editNhanVien['dien_thoai']) : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ngày Sinh</label>
                            <input type="date" class="form-control" name="ngay_sinh"
                                value="<?php echo $editNhanVien && $editNhanVien['ngay_sinh'] ? h($editNhanVien['ngay_sinh']) : ''; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Giới Tính</label>
                            <select class="form-select" name="gioi_tinh">
                                <option value="Nam"
                                    <?php echo ($editNhanVien && $editNhanVien['gioi_tinh'] == 'Nam') ? 'selected' : ''; ?>>
                                    Nam</option>
                                <option value="Nữ"
                                    <?php echo ($editNhanVien && $editNhanVien['gioi_tinh'] == 'Nữ') ? 'selected' : ''; ?>>
                                    Nữ</option>
                                <option value="Khác"
                                    <?php echo ($editNhanVien && $editNhanVien['gioi_tinh'] == 'Khác') ? 'selected' : ''; ?>>
                                    Khác</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ảnh Đại Diện</label>
                            <input type="file" class="form-control" name="anh_dai_dien" accept="image/*">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mật Khẩu
                            <?php echo $editNhanVien ? '(Để trống nếu không đổi)' : '*'; ?></label>
                        <input type="password" class="form-control" name="mat_khau"
                            <?php echo $editNhanVien ? '' : 'required'; ?>>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Địa Chỉ</label>
                        <textarea class="form-control" name="dia_chi" rows="2"
                            placeholder="Nhập địa chỉ..."><?php echo $editNhanVien ? h($editNhanVien['dia_chi']) : ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Chức Vụ *</label>
                            <select class="form-select" name="chuc_vu" required>
                                <option value="Quản lý"
                                    <?php echo ($editNhanVien && $editNhanVien['chuc_vu'] == 'Quản lý') ? 'selected' : ''; ?>>
                                    Quản lý</option>
                                <option value="Lễ tân"
                                    <?php echo ($editNhanVien && $editNhanVien['chuc_vu'] == 'Lễ tân') ? 'selected' : ''; ?>>
                                    Lễ tân</option>
                                <option value="Nhân viên"
                                    <?php echo ($editNhanVien && $editNhanVien['chuc_vu'] == 'Nhân viên') ? 'selected' : ''; ?>>
                                    Nhân viên</option>
                                <option value="Buông phòng"
                                    <?php echo ($editNhanVien && $editNhanVien['chuc_vu'] == 'Buông phòng') ? 'selected' : ''; ?>>
                                    Buồng phòng</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phòng Ban</label>
                            <input type="text" class="form-control" name="phong_ban"
                                value="<?php echo $editNhanVien ? h($editNhanVien['phong_ban']) : ''; ?>"
                                placeholder="Nhập phòng ban">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ngày Vào Làm *</label>
                            <input type="date" class="form-control" name="ngay_vao_lam"
                                value="<?php echo $editNhanVien && $editNhanVien['ngay_vao_lam'] ? h($editNhanVien['ngay_vao_lam']) : date('Y-m-d'); ?>"
                                required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lương Cơ Bản</label>
                            <input type="number" class="form-control" name="luong_co_ban"
                                value="<?php echo $editNhanVien && $editNhanVien['luong_co_ban'] ? h($editNhanVien['luong_co_ban']) : ''; ?>"
                                placeholder="5000000" step="1000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trạng Thái *</label>
                            <select class="form-select" name="trang_thai" required>
                                <option value="Đang làm việc"
                                    <?php echo ($editNhanVien && $editNhanVien['trang_thai'] == 'Đang làm việc') ? 'selected' : ''; ?>>
                                    Đang làm việc</option>
                                <option value="Nghỉ"
                                    <?php echo ($editNhanVien && $editNhanVien['trang_thai'] == 'Nghỉ') ? 'selected' : ''; ?>>
                                    Nghỉ</option>
                                <option value="Thôi việc"
                                    <?php echo ($editNhanVien && $editNhanVien['trang_thai'] == 'Thôi việc') ? 'selected' : ''; ?>>
                                    Thôi việc</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ghi Chú</label>
                        <textarea class="form-control" name="ghi_chu" rows="2"
                            placeholder="Nhập ghi chú..."><?php echo $editNhanVien ? h($editNhanVien['ghi_chu']) : ''; ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        onclick="clearEditMode()">Hủy</button>
                    <button type="submit" name="<?php echo $editNhanVien ? 'update_nhan_vien' : 'add_nhan_vien'; ?>"
                        class="btn btn-primary">
                        <?php echo $editNhanVien ? 'Cập nhật' : 'Thêm'; ?> Nhân Viên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Xem Chi Tiết Nhân Viên -->
<div class="modal fade" id="viewEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user"></i> Thông Tin Nhân Viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewEmployeeContent">
                <!-- Nội dung sẽ được load bằng AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Phân Quyền -->
<div class="modal fade" id="permissionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key"></i> Phân Quyền Nhân Viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="permissionContent">
                <!-- Nội dung sẽ được load bằng AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="savePermissions()">Lưu Quyền</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận xóa nhân viên -->
<div class="modal fade" id="confirmDeleteStaffModal" tabindex="-1" aria-labelledby="confirmDeleteStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteStaffModalLabel">Xác nhận xóa</h5>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3 mb-0">Bạn có chắc muốn xóa nhân viên này?<br>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">Hủy</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteStaff" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function editEmployee(id) {
    window.location.href = 'index.php?page=staff-manager&panel=staff-panel&action=edit&id=' + id;
}

// Auto-reset when modal is closed or when "Add" button is clicked (không cần reload)
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addEmployeeModal');
    if (modal) {
        // Clear URL and reset form when modal is closed
        modal.addEventListener('hidden.bs.modal', function() {
            const url = new URL(window.location);
            url.searchParams.delete('action');
            url.searchParams.delete('id');
            window.history.replaceState({}, '', url);
            // Reset form completely ngay lập tức
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                // Clear all input values
                form.querySelectorAll(
                    'input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="number"], textarea, select'
                ).forEach(input => {
                    if (input.name !== 'page' && input.name !== 'panel' && input.name !==
                        'id_nhan_vien') {
                        if (input.type === 'select-one' || input.tagName === 'SELECT') {
                            input.selectedIndex = 0;
                        } else {
                            input.value = '';
                        }
                    } else if (input.name === 'id_nhan_vien') {
                        input.value = '';
                    }
                });
                // Reset modal title and button
                const modalTitle = modal.querySelector('.modal-title');
                const submitBtn = form.querySelector('button[type="submit"]');
                if (modalTitle) modalTitle.textContent = 'Thêm Nhân Viên';
                if (submitBtn) {
                    submitBtn.name = 'add_nhan_vien';
                    submitBtn.textContent = 'Thêm Nhân Viên';
                }
            }
        });

        // Reset form when "Add" button is clicked
        const addButton = document.querySelector('[data-bs-target="#addEmployeeModal"]');
        if (addButton) {
            addButton.addEventListener('click', function() {
                const url = new URL(window.location);
                url.searchParams.delete('action');
                url.searchParams.delete('id');
                window.history.replaceState({}, '', url);
                // Reset form
                setTimeout(function() {
                    const form = modal.querySelector('form');
                    if (form) {
                        form.reset();
                        // Clear all input values
                        form.querySelectorAll(
                            'input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="number"], textarea, select'
                        ).forEach(input => {
                            if (input.name !== 'page' && input.name !== 'panel' && input
                                .name !== 'id_nhan_vien') {
                                if (input.type === 'select-one' || input.tagName ===
                                    'SELECT') {
                                    input.selectedIndex = 0;
                                } else {
                                    input.value = '';
                                }
                            } else if (input.name === 'id_nhan_vien') {
                                input.value = '';
                            }
                        });
                        // Reset modal title and button
                        const modalTitle = modal.querySelector('.modal-title');
                        const submitBtn = form.querySelector('button[type="submit"]');
                        if (modalTitle) modalTitle.textContent = 'Thêm Nhân Viên';
                        if (submitBtn) {
                            submitBtn.name = 'add_nhan_vien';
                            submitBtn.textContent = 'Thêm Nhân Viên';
                        }
                    }
                }, 200);
            });
        }

        // Reset form when modal opens if not in edit mode
        modal.addEventListener('show.bs.modal', function() {
            const isEditMode = window.location.search.includes('action=edit');
            if (!isEditMode) {
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                    // Clear all input values
                    form.querySelectorAll(
                        'input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="number"], textarea, select'
                    ).forEach(input => {
                        if (input.name !== 'page' && input.name !== 'panel' && input.name !==
                            'id_nhan_vien') {
                            if (input.type === 'select-one' || input.tagName === 'SELECT') {
                                input.selectedIndex = 0;
                            } else {
                                input.value = '';
                            }
                        } else if (input.name === 'id_nhan_vien') {
                            input.value = '';
                        }
                    });
                    // Reset modal title and button
                    const modalTitle = modal.querySelector('.modal-title');
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (modalTitle) modalTitle.textContent = 'Thêm Nhân Viên';
                    if (submitBtn) {
                        submitBtn.name = 'add_nhan_vien';
                        submitBtn.textContent = 'Thêm Nhân Viên';
                    }
                }
            }
        });
    }
});

// Biến lưu ID nhân viên cần xóa
let staffIdToDelete = null;

// Rename function to avoid conflict with old cached versions
function confirmDeleteStaff(id) {
    staffIdToDelete = id;
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteStaffModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const btnConfirmDelete = document.getElementById('btnConfirmDeleteStaff');
    if (btnConfirmDelete) {
        // Remove existing listeners to avoid duplicates if any (though cloneNode is cleaner, a simple check suffices here)
        btnConfirmDelete.replaceWith(btnConfirmDelete.cloneNode(true));
        
        // Re-select after replacement
        document.getElementById('btnConfirmDeleteStaff').addEventListener('click', function() {
            if (staffIdToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="id_nhan_vien" value="' + staffIdToDelete + '">' +
                    '<input type="hidden" name="delete_nhan_vien" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});

function viewEmployee(id) {
    fetch(`api/staff-api.php?action=view&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const nv = data.staff;
                const content = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img src="${nv.anh_dai_dien ? nv.anh_dai_dien : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(nv.ho_ten || 'Staff') + '&background=d4b896&color=fff&size=150'}" 
                                class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" alt="Avatar"
                                onerror="if(this.src.indexOf('ui-avatars.com') === -1) { this.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent('${nv.ho_ten || 'Staff'}') + '&background=d4b896&color=fff&size=150'; }">
                        </div>
                        <div class="col-md-8">
                            <h5>${nv.ho_ten}</h5>
                            <p><strong>Mã NV:</strong> ${nv.ma_nhan_vien}</p>
                            <p><strong>Email:</strong> ${nv.email}</p>
                            <p><strong>Điện thoại:</strong> ${nv.dien_thoai}</p>
                            <p><strong>Ngày sinh:</strong> ${nv.ngay_sinh || '-'}</p>
                            <p><strong>Giới tính:</strong> ${nv.gioi_tinh || '-'}</p>
                            <p><strong>Chức vụ:</strong> ${nv.chuc_vu}</p>
                            <p><strong>Phòng ban:</strong> ${nv.phong_ban || '-'}</p>
                            <p><strong>Ngày vào làm:</strong> ${nv.ngay_vao_lam || '-'}</p>
                            <p><strong>Lương cơ bản:</strong> ${nv.luong_co_ban ? new Intl.NumberFormat('vi-VN').format(nv.luong_co_ban) + ' VNĐ' : '-'}</p>
                            <p><strong>Trạng thái:</strong> <span class="badge ${nv.trang_thai == 'Đang làm việc' ? 'bg-success' : (nv.trang_thai == 'Nghỉ' ? 'bg-warning' : 'bg-danger')}">${nv.trang_thai}</span></p>
                            <p><strong>Địa chỉ:</strong> ${nv.dia_chi || '-'}</p>
                            <p><strong>Ghi chú:</strong> ${nv.ghi_chu || '-'}</p>
                        </div>
                    </div>
                `;
                document.getElementById('viewEmployeeContent').innerHTML = content;
                const modal = new bootstrap.Modal(document.getElementById('viewEmployeeModal'));
                modal.show();
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra khi tải thông tin nhân viên');
        });
}

function openPermissionModal(id) {
    fetch(`api/staff-api.php?action=permissions&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Load permission content (giữ nguyên logic cũ từ staff-manager.js)
                document.getElementById('permissionContent').innerHTML = data.html || '';
                const modal = new bootstrap.Modal(document.getElementById('permissionModal'));
                modal.show();
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra khi tải quyền');
        });
}

function savePermissions() {
    const form = document.getElementById('permissionForm');
    if (!form) return;

    const formData = new FormData(form);
    formData.append('action', 'save_permissions');

    fetch('api/staff-api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Lưu quyền thành công!');
                bootstrap.Modal.getInstance(document.getElementById('permissionModal')).hide();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể lưu quyền'));
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra khi lưu quyền');
        });
}

function clearEditMode() {
    window.location.href = 'index.php?page=staff-manager&panel=staff-panel';
}

<?php if ($editNhanVien): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Populate form with edit data
    const form = document.querySelector('#addEmployeeModal form');
    if (form) {
        const maNV = form.querySelector('input[name="ma_nhan_vien"]');
        const hoTen = form.querySelector('input[name="ho_ten"]');
        const email = form.querySelector('input[name="email"]');
        const dienThoai = form.querySelector('input[name="dien_thoai"]');
        const ngaySinh = form.querySelector('input[name="ngay_sinh"]');
        const gioiTinh = form.querySelector('select[name="gioi_tinh"]');
        const chucVu = form.querySelector('select[name="chuc_vu"]');
        const phongBan = form.querySelector('input[name="phong_ban"]');
        const ngayVaoLam = form.querySelector('input[name="ngay_vao_lam"]');
        const luongCoBan = form.querySelector('input[name="luong_co_ban"]');
        const trangThai = form.querySelector('select[name="trang_thai"]');
        const diaChi = form.querySelector('input[name="dia_chi"]');
        const ghiChu = form.querySelector('textarea[name="ghi_chu"]');

        if (maNV) maNV.value = '<?php echo h($editNhanVien['ma_nhan_vien']); ?>';
        if (hoTen) hoTen.value = '<?php echo h($editNhanVien['ho_ten']); ?>';
        if (email) email.value = '<?php echo h($editNhanVien['email']); ?>';
        if (dienThoai) dienThoai.value = '<?php echo h($editNhanVien['dien_thoai']); ?>';
        if (ngaySinh) ngaySinh.value = '<?php echo $editNhanVien['ngay_sinh']; ?>';
        if (gioiTinh) gioiTinh.value = '<?php echo h($editNhanVien['gioi_tinh']); ?>';
        if (chucVu) chucVu.value = '<?php echo h($editNhanVien['chuc_vu']); ?>';
        if (phongBan) phongBan.value = '<?php echo h($editNhanVien['phong_ban'] ?? ''); ?>';
        if (ngayVaoLam) ngayVaoLam.value = '<?php echo $editNhanVien['ngay_vao_lam']; ?>';
        if (luongCoBan) luongCoBan.value = '<?php echo $editNhanVien['luong_co_ban']; ?>';
        if (trangThai) trangThai.value = '<?php echo h($editNhanVien['trang_thai']); ?>';
        if (diaChi) diaChi.value = '<?php echo h($editNhanVien['dia_chi'] ?? ''); ?>';
        if (ghiChu) ghiChu.value = '<?php echo h($editNhanVien['ghi_chu'] ?? ''); ?>';

        // Update modal title and button
        const modalTitle = document.querySelector('#addEmployeeModal .modal-title');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (modalTitle) modalTitle.textContent = 'Sửa Nhân Viên';
        if (submitBtn) {
            submitBtn.name = 'update_nhan_vien';
            submitBtn.textContent = 'Cập nhật Nhân Viên';
        }
    }

    const modal = new bootstrap.Modal(document.getElementById('addEmployeeModal'));
    modal.show();
});
<?php endif; ?>
</script>