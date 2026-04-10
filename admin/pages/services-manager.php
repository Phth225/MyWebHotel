<?php
// Phân quyền module Dịch Vụ
$canViewService   = function_exists('checkPermission') ? checkPermission('service.view')   : true;
$canCreateService = function_exists('checkPermission') ? checkPermission('service.create') : true;
$canEditService   = function_exists('checkPermission') ? checkPermission('service.edit')   : true;
$canDeleteService = function_exists('checkPermission') ? checkPermission('service.delete') : true;

if (!$canViewService) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem trang dịch vụ.</div></div>';
    return;
}

// Xử lý CRUD
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$messageType = '';

// Hàm upload ảnh cho dịch vụ lên Cloudinary
if (!function_exists('uploadServiceImage')) {
    function uploadServiceImage($file, $oldImage = '') {
        if (!isset($file['name']) || empty($file['name'])) {
            return $oldImage;
        }
        
        require_once __DIR__ . '/../includes/cloudinary_helper.php';
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Upload lên Cloudinary
        $cloudinaryUrl = CloudinaryHelper::upload($file['tmp_name'], 'service');
        
        if ($cloudinaryUrl !== false) {
            // Xóa ảnh cũ trên Cloudinary nếu có
            if (!empty($oldImage)) {
                CloudinaryHelper::deleteByUrl($oldImage);
            }
            return $cloudinaryUrl;
        }
        
        return $oldImage;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_service'])) {
        if (!$canCreateService) {
            $message = 'Bạn không có quyền thêm dịch vụ.';
            $messageType = 'danger';
        } else {
        $service_name = trim($_POST['service_name']);
        $description = trim($_POST['description'] ?? '');
        $service_type = trim($_POST['service_type']);
        $price = floatval($_POST['price']);
        $unit = trim($_POST['unit'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $uploadResult = uploadServiceImage($_FILES['image']);
            if ($uploadResult !== false) {
                $image = $uploadResult;
            }
        }

        $stmt = $mysqli->prepare("INSERT INTO service (service_name, description, service_type, price, unit, status, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdsss", $service_name, $description, $service_type, $price, $unit, $status, $image);

        if ($stmt->execute()) {
            $message = 'Thêm dịch vụ thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
        }
    }

    if (isset($_POST['update_service'])) {
        if (!$canEditService) {
            $message = 'Bạn không có quyền chỉnh sửa dịch vụ.';
            $messageType = 'danger';
        } else {
        $service_id = intval($_POST['service_id']);
        $service_name = trim($_POST['service_name']);
        $description = trim($_POST['description'] ?? '');
        $service_type = trim($_POST['service_type']);
        $price = floatval($_POST['price']);
        $unit = trim($_POST['unit'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        
        // Lấy ảnh cũ
        $oldImageStmt = $mysqli->prepare("SELECT image FROM service WHERE service_id = ?");
        $oldImageStmt->bind_param("i", $service_id);
        $oldImageStmt->execute();
        $oldImageResult = $oldImageStmt->get_result();
        $oldImage = $oldImageResult->fetch_assoc()['image'] ?? '';
        $oldImageStmt->close();

        $image = $oldImage;

        // Nếu tick xóa ảnh
        if (!empty($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            if (!empty($oldImage)) {
                require_once __DIR__ . '/../includes/cloudinary_helper.php';
                CloudinaryHelper::deleteByUrl($oldImage);
            }
            $image = '';
        }

        // Nếu upload ảnh mới
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $uploadResult = uploadServiceImage($_FILES['image'], $oldImage);
            if ($uploadResult !== false) {
                $image = $uploadResult;
            }
        }

        $stmt = $mysqli->prepare("UPDATE service SET service_name=?, description=?, service_type=?, price=?, unit=?, status=?, image=? WHERE service_id=? AND deleted IS NULL");
        $stmt->bind_param("sssdsssi", $service_name, $description, $service_type, $price, $unit, $status, $image, $service_id);

        if ($stmt->execute()) {
            $message = 'Cập nhật dịch vụ thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
        }
    }

    if (isset($_POST['delete_service'])) {
        if (!$canDeleteService) {
            $message = 'Bạn không có quyền xóa dịch vụ.';
            $messageType = 'danger';
        } else {
        $service_id = intval($_POST['service_id']);
        $stmt = $mysqli->prepare("UPDATE service SET deleted = NOW() WHERE service_id = ?");
        $stmt->bind_param("i", $service_id);

        if ($stmt->execute()) {
            $message = 'Xóa dịch vụ thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
        }
    }
}

// Lấy thông tin dịch vụ để edit
$editService = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM service WHERE service_id = ? AND deleted IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editService = $result->fetch_assoc();
    $stmt->close();
}

// Phân trang và tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';
$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum); // Đảm bảo pageNum >= 1
$perPage = 5;
$offset = ($pageNum - 1) * $perPage;

// Xây dựng WHERE clause
$where = "WHERE s.deleted IS NULL";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (s.service_name LIKE ? OR s.service_type LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
    $types .= 'ss';
}

if ($status_filter) {
    $where .= " AND s.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($type_filter) {
    $where .= " AND s.service_type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

// Đếm tổng số
$countQuery = "SELECT COUNT(*) as total FROM service s $where";
$countStmt = $mysqli->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$countStmt->close();

// Lấy dữ liệu - FIX: Áp dụng $where, $params, $offset, $perPage
$query = "SELECT * FROM service s 
    $where
    ORDER BY s.service_id ASC 
    LIMIT $perPage OFFSET $offset";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $services = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Lỗi query: " . $stmt->error);
}
$stmt->close();

// Lấy danh sách service types
$typesResult = $mysqli->query("SELECT DISTINCT service_type FROM service WHERE deleted IS NULL ORDER BY service_type");
$serviceTypes = $typesResult->fetch_all(MYSQLI_ASSOC);

// Build base URL for pagination
$baseUrl = "index.php?page=services-manager";
if ($search) $baseUrl .= "&search=" . urlencode($search);
if ($status_filter) $baseUrl .= "&status=" . urlencode($status_filter);
if ($type_filter) $baseUrl .= "&type=" . urlencode($type_filter);
?>

<div class="main-content">
    <div class="content-header">
        <h1>Quản Lý Dịch Vụ</h1>
        <div class="row m-3">
            <div class="col-md-12">
                <?php if ($canCreateService): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="fas fa-plus"></i> Thêm Dịch Vụ
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo h($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="services-manager">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Tìm kiếm dịch vụ..."
                            value="<?php echo h($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Đang hoạt
                            động</option>
                        <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Tạm dừng
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="service_type">
                        <option value="">Tất cả loại</option>
                        <?php foreach ($serviceTypes as $st): ?>
                        <option value="<?php echo h($st['service_type']); ?>"
                            <?php echo $type_filter == $st['service_type'] ? 'selected' : ''; ?>>
                            <?php echo h($st['service_type']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên Dịch Vụ</th>
                    <th>Loại</th>
                    <th>Đơn Vị</th>
                    <th>Giá</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                <tr>
                    <td colspan="7" class="text-center">Không có dữ liệu</td>
                </tr>
                <?php else: ?>
                <?php foreach ($services as $service): ?>
                <tr>
                    <td><?php echo $service['service_id']; ?></td>
                    <td><?php echo h($service['service_name']); ?></td>
                    <td>
                        <span class="badge bg-info"><?php echo h($service['service_type']); ?></span>
                    </td>
                    <td><?php echo h($service['unit'] ?: '-'); ?></td>
                    <td><?php echo formatCurrency($service['price']); ?></td>
                    <td>
                        <span class="badge <?php echo $service['status'] == 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo h($service['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                            data-bs-target="#viewServiceModal<?php echo $service['service_id']; ?>"
                            title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning"
                            onclick="editService(<?php echo $service['service_id']; ?>)" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                            data-bs-target="#confirmDeleteServiceModal"
                            data-service-id="<?php echo $service['service_id']; ?>" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Modal Xem Chi Tiết Dịch Vụ -->
    <?php if (!empty($services)): ?>
    <?php foreach ($services as $service): ?>
    <?php
        // Xác định trạng thái
        $statusClass = $service['status'] == 'Active' ? 'bg-success' : 'bg-danger';
        $statusText = $service['status'] == 'Active' ? 'Đang hoạt động' : 'Tạm dừng';
        ?>
    <div class="modal fade" id="viewServiceModal<?php echo $service['service_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Dịch Vụ</h5>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Cột Ảnh -->
                        <div class="col-md-6">
                            <?php if (!empty($service['image'])): ?>
                            <div class="text-center">
                                <img src="<?php echo h($service['image']); ?>"
                                    alt="<?php echo h($service['service_name']); ?>" class="img-fluid rounded"
                                    style="height: 250px; width: 100%; object-fit: cover; border: 2px solid #ddd;">
                            </div>
                            <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light rounded"
                                style="width: 100%; height: 300px;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-image fa-3x mb-2"></i>
                                    <p class="mb-0">Chưa có ảnh</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Cột Thông Tin -->
                        <div class="col-md-6">
                            <p><strong>Mã dịch vụ:</strong> #<?php echo $service['service_id']; ?></p>
                            <p><strong>Tên dịch vụ:</strong> <?php echo h($service['service_name']); ?></p>
                            <p><strong>Loại dịch vụ:</strong>
                                <span class="badge bg-info"><?php echo h($service['service_type']); ?></span>
                            </p>
                            <p><strong>Giá:</strong>
                                <span class="fw-bold"
                                    style="color: #b69854;"><?php echo formatCurrency($service['price']); ?></span>
                            </p>
                            <p><strong>Đơn vị:</strong> <?php echo h($service['unit'] ?: '-'); ?></p>
                            <p><strong>Trạng thái:</strong>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <?php if (!empty($service['description'])): ?>
                        <p><strong>Mô tả:</strong></p>
                        <div class="p-3 desc">
                            <?php echo nl2br(h($service['description'])); ?>
                        </div>
                        <?php else: ?>
                        <p><strong>Mô tả:</strong> <span class="text-muted">Không có mô tả</span></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <?php if ($canEditService): ?>
                    <button type="button" class="btn btn-primary"
                        onclick="editServiceFromView(<?php echo $service['service_id']; ?>)">
                        <i class="fas fa-edit"></i> Chỉnh Sửa
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <!-- Pagination -->
    <?php echo getPagination($total, $perPage, $pageNum, $baseUrl); ?>
</div>

<!-- Modal Xác nhận xóa dịch vụ -->
<div class="modal fade" id="confirmDeleteServiceModal" tabindex="-1" aria-labelledby="confirmDeleteServiceModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteServiceModalLabel">
                    Xác nhận xóa
                </h5>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3 mb-0">Bạn có chắc muốn xóa dịch vụ này?<br>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteServiceButton">
                    <i class="fas fa-trash-alt me-2"></i>Xóa dịch vụ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm/Sửa Dịch Vụ -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $editService ? 'Sửa' : 'Thêm'; ?> Dịch Vụ</h5>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php if ($editService): ?>
                    <input type="hidden" name="service_id" value="<?php echo $editService['service_id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tên Dịch Vụ *</label>
                            <input type="text" class="form-control" name="service_name"
                                value="<?php echo h($editService['service_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Loại Dịch Vụ *</label>
                            <select class="form-select" name="service_type">
                                <option value="" disabled>Tất cả loại</option>
                                <?php foreach ($serviceTypes as $st): ?>
                                <option value="<?php echo h($st['service_type']); ?>"
                                    <?php echo (isset($editService) && $editService['service_type'] == $st['service_type']) ? 'selected' : ''; ?>>
                                    <?php echo h($st['service_type']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giá (VNĐ) *</label>
                            <input type="number" class="form-control" name="price" step="0.01"
                                value="<?php echo $editService['price'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Đơn Vị</label>
                            <input type="text" class="form-control" name="unit"
                                value="<?php echo h($editService['unit'] ?? ''); ?>"
                                placeholder="VD: Suất, Gói, Lần...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô Tả</label>
                        <textarea class="form-control" name="description"
                            rows="3"><?php echo h($editService['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ảnh Dịch Vụ</label>
                        <div class="image-upload-area" onclick="document.getElementById('serviceImage').click()"
                            style="border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Click để chọn ảnh</p>
                            <small class="text-muted">hoặc kéo thả ảnh vào đây</small>
                        </div>
                        <input type="file" id="serviceImage" name="image" accept="image/*" style="display: none"
                            onchange="previewImage(this, 'servicePreview')" />
                        <input type="hidden" name="remove_image" id="removeServiceImage" value="0">
                        <div id="serviceImageWrapper" class="image-preview-item position-relative d-inline-block mt-3">
                            <?php
                                $hasImage = $editService && !empty($editService['image']);
                                $serviceImageUrl = $hasImage ? $editService['image'] : '';
                            ?>
                            <img id="servicePreview" class="image-preview" src="<?php echo h($serviceImageUrl); ?>"
                                style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; <?php echo $hasImage ? 'display: block;' : 'display: none;'; ?>" />
                            <button type="button" id="serviceImageRemoveBtn"
                                class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                style="<?php echo $hasImage ? '' : 'display: none;'; ?>"
                                onclick="clearServiceImage(this)">
                                ×
                            </button>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Định dạng: JPG, PNG, GIF, WEBP. Kích thước tối đa: 5MB</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Trạng Thái *</label>
                        <select class="form-select" name="status" required>
                            <option value="Active"
                                <?php echo ($editService['status'] ?? 'Active') == 'Active' ? 'selected' : ''; ?>>Đang
                                hoạt động</option>
                            <option value="Inactive"
                                <?php echo ($editService['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Tạm dừng
                            </option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"
                        name="<?php echo $editService ? 'update_service' : 'add_service'; ?>">
                        <?php echo $editService ? 'Cập nhật' : 'Thêm'; ?> Dịch Vụ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview image function
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = "block";

            // Khi chọn ảnh mới thì không xóa ảnh nữa
            const removeInput = document.getElementById('removeServiceImage');
            if (removeInput) {
                removeInput.value = '0';
            }

            // Hiển thị lại nút X nếu có
            const removeBtn = document.getElementById('serviceImageRemoveBtn');
            if (removeBtn) {
                removeBtn.style.display = 'flex';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Xóa ảnh dịch vụ hiện tại (set cờ remove_image = 1)
function clearServiceImage(button) {
    const preview = document.getElementById('servicePreview');
    const removeInput = document.getElementById('removeServiceImage');
    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
    }
    if (removeInput) {
        removeInput.value = '1';
    }
    if (button && button.parentElement) {
        button.parentElement.remove(); // Xóa luôn cả khung image-preview-item giống loại phòng
    }
}

// ==================== HELPER FUNCTIONS ====================

// Hàm xóa query string edit - PHẢI ở ngoài
function clearEditQueryString() {
    const url = new URL(window.location);
    url.searchParams.delete('action');
    url.searchParams.delete('id');
    window.history.replaceState({}, '', url.toString());
}

// Hàm force cleanup backdrop
function forceCleanupBackdrop() {
    const openModals = document.querySelectorAll('.modal.show');
    if (openModals.length === 0) {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
}

// Hàm reset modal về trạng thái "Thêm mới"
function resetModalToAddMode(modalElement, form) {
    if (!modalElement || !form) return;

    const modalId = modalElement.id;
    const modalTitle = modalElement.querySelector('.modal-title');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Config cho từng modal
    const modalConfig = {
        'addServiceModal': {
            title: 'Thêm dịch vụ',
            buttonName: 'add_service',
            buttonHTML: '<i class="fas fa-save"></i> Thêm dịch vụ'
        }
    };

    const config = modalConfig[modalId];
    if (config) {
        if (modalTitle) modalTitle.textContent = config.title;
        if (submitBtn) {
            submitBtn.name = config.buttonName;
            submitBtn.innerHTML = config.buttonHTML;
        }
    }
}

// Hàm reset form tổng quát
function resetFormFields(form) {
    if (!form) return;
    form.reset();

    // Xóa input hidden (trừ page và panel)
    form.querySelectorAll('input[type="hidden"]').forEach(input => {
        if (input.name !== 'page' && input.name !== 'panel') {
            input.remove();
        }
    });

    // Reset text/number/tel/email inputs
    form.querySelectorAll('input[type="text"], input[type="number"],select').forEach(input => {
        input.value = '';
    });

    // Reset textarea
    form.querySelectorAll('textarea').forEach(textarea => {
        textarea.value = '';
    });

    // Reset date về hôm nay
    const today = new Date().toISOString().split('T')[0];
    form.querySelectorAll('input[type="date"]').forEach(input => {
        input.value = today;
    });

    // Clear readonly fields
    form.querySelectorAll('input[readonly]').forEach(input => {
        input.value = '';
    });

    // Reset image preview và file input
    form.querySelectorAll('input[type="file"]').forEach(input => {
        input.value = '';
    });

    // Reset image preview
    const previewImg = form.querySelector('#servicePreview');
    if (previewImg) {
        previewImg.src = '';
        previewImg.style.display = 'none';
    }

    // Ẩn nút xóa ảnh (dấu X đỏ) - QUAN TRỌNG
    // Tìm trong form trước, nếu không có thì tìm trong document
    let removeImageBtn = form.querySelector('#serviceImageRemoveBtn');
    if (!removeImageBtn) {
        removeImageBtn = document.getElementById('serviceImageRemoveBtn');
    }
    if (removeImageBtn) {
        removeImageBtn.style.display = 'none';
    }

    // Reset remove image input về 0
    let removeImageInput = form.querySelector('#removeServiceImage');
    if (!removeImageInput) {
        removeImageInput = document.getElementById('removeServiceImage');
    }
    if (removeImageInput) {
        removeImageInput.value = '0';
    }

    // Đảm bảo image wrapper vẫn hiển thị (không ẩn hoàn toàn)
    let imageWrapper = form.querySelector('#serviceImageWrapper');
    if (!imageWrapper) {
        imageWrapper = document.getElementById('serviceImageWrapper');
    }
    if (imageWrapper) {
        imageWrapper.style.display = 'block';
    }
}

// ==================== SERVICE FUNCTIONS ====================
function editService(id) {
    const url = new URL(window.location.href);
    url.searchParams.set('action', 'edit');
    url.searchParams.set('id', id);
    window.location.href = url.toString();
}

// Hàm xóa dịch vụ (cũ - giữ lại để tương thích ngược)
function deleteService(id) {
    currentServiceId = id;
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteServiceModal'));
    modal.show();
}

// ==================== MODAL AUTO-RESET ====================

document.addEventListener('DOMContentLoaded', function() {

    // Tự động mở modal edit nếu có action=edit
    <?php if ($editService): ?>
    const editModal = new bootstrap.Modal(document.getElementById('addServiceModal'));
    editModal.show();
    <?php endif; ?>

    // Danh sách modal cần auto-reset
    const resettableModals = ['addServiceModal'];

    // Xử lý TỔNG QUÁT cho TẤT CẢ modal
    document.querySelectorAll('.modal').forEach(modalElement => {

        // Event: Khi modal đã đóng hoàn toàn
        modalElement.addEventListener('hidden.bs.modal', function() {
            const form = modalElement.querySelector('form');
            const modalId = modalElement.id;

            // Chỉ xử lý modal trong danh sách
            if (resettableModals.includes(modalId)) {
                const isEditMode = window.location.search.includes('action=edit');

                if (isEditMode) {
                    // Xóa query string edit
                    clearEditQueryString();
                }

                // Reset form về trạng thái "Thêm mới"
                if (form) {
                    resetFormFields(form);
                    resetModalToAddMode(modalElement, form);
                }
            }

            // Cleanup backdrop
            setTimeout(forceCleanupBackdrop, 100);
        });

        // Event: Khi modal sắp mở
        modalElement.addEventListener('show.bs.modal', function() {
            const form = modalElement.querySelector('form');
            const isEditMode = window.location.search.includes('action=edit');

            // Nếu KHÔNG phải edit mode, reset form
            if (!isEditMode && form && resettableModals.includes(modalElement.id)) {
                resetFormFields(form);
            }
        });
    });

    // Xử lý nút "Thêm mới" - xóa query string edit và reset form
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            const isEditMode = window.location.search.includes('action=edit');
            if (isEditMode) {
                clearEditQueryString();
            }
            // Reset form when opening add modal
            const modalId = button.getAttribute('data-bs-target');
            if (modalId && modalId.includes('addServiceModal')) {
                setTimeout(function() {
                    const form = document.querySelector(modalId + ' form');
                    if (form && !window.location.search.includes('action=edit')) {
                        resetFormFields(form);
                        resetModalToAddMode(document.querySelector(modalId), form);
                    }
                }, 200);
            }
        });
    });

    // Xử lý ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            setTimeout(forceCleanupBackdrop, 150);
        }
    });
});

// Hàm xử lý sửa dịch vụ từ modal xem chi tiết
function editServiceFromView(id) {
    // Đóng modal xem chi tiết
    const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewServiceModal' + id));
    if (viewModal) {
        viewModal.hide();
    }

    // Chuyển hướng đến trang chỉnh sửa
    window.location.href = 'index.php?page=services-manager&action=edit&id=' + id;
}

// Biến lưu trữ ID dịch vụ hiện tại
let currentServiceId = null;

// Khởi tạo sự kiện khi DOM đã tải xong
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý sự kiện khi modal xác nhận xóa được hiển thị
    const deleteModal = document.getElementById('confirmDeleteServiceModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.hasAttribute('data-service-id')) {
                currentServiceId = button.getAttribute('data-service-id');
            }
        });
    }

    // Xử lý sự kiện khi nhấn nút xác nhận xóa
    const confirmDeleteBtn = document.getElementById('confirmDeleteServiceButton');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (currentServiceId) {
                performDeleteService(currentServiceId);
            }
        });
    }
});

// Hàm thực hiện xóa dịch vụ
function performDeleteService(id) {
    if (!id) return;

    // Tạo form ẩn để gửi yêu cầu xóa
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;
    form.style.display = 'none';

    // Thêm input ẩn chứa ID dịch vụ cần xóa
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'service_id';
    idInput.value = id;
    form.appendChild(idInput);

    // Thêm input ẩn cho action delete
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'delete_service';
    actionInput.value = '1';
    form.appendChild(actionInput);

    // Thêm CSRF token nếu có
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }

    // Thêm form vào body
    document.body.appendChild(form);

    // Gửi form
    form.submit();
}

// Hàm xóa dịch vụ (giữ lại để tương thích ngược)
function deleteService(id) {
    currentServiceId = id;
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteServiceModal'));
    modal.show();
}
</script>