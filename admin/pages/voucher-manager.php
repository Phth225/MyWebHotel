<?php
// Phân quyền module Voucher
$canViewVoucher   = function_exists('checkPermission') ? checkPermission('voucher.view')   : true;
$canCreateVoucher = function_exists('checkPermission') ? checkPermission('voucher.create') : true;
$canEditVoucher   = function_exists('checkPermission') ? checkPermission('voucher.edit')   : true;
$canDeleteVoucher = function_exists('checkPermission') ? checkPermission('voucher.delete') : true;

if (!$canViewVoucher) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem trang voucher.</div></div>';
    return;
}

// Xử lý CRUD
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$messageType = '';

// Hàm upload ảnh cho voucher lên Cloudinary
if (!function_exists('uploadVoucherImage')) {
function uploadVoucherImage($file, $oldImage = '') {
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
    $cloudinaryUrl = CloudinaryHelper::upload($file['tmp_name'], 'voucher');
    
    if ($cloudinaryUrl !== false) {
        // Xóa ảnh cũ trên Cloudinary nếu có
        if (!empty($oldImage)) {
            CloudinaryHelper::deleteByUrl($oldImage);
        }
        return $cloudinaryUrl;
    }
    
    return $oldImage;
}
} // End function_exists check

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_voucher'])) {
        if (!$canCreateVoucher) {
            $message = 'Bạn không có quyền thêm voucher.';
            $messageType = 'danger';
        } else {
            $code = trim($_POST['code']);
            $name = trim($_POST['name']);
            $description = trim($_POST['description'] ?? '');
            
            // Xử lý upload ảnh
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $uploadResult = uploadVoucherImage($_FILES['image']);
                if ($uploadResult !== false) {
                    $image = $uploadResult;
                }
            } else {
                $image = trim($_POST['image'] ?? '');
            }
            
            $discount_type = $_POST['discount_type'] ?? 'percent';
            $discount_value = floatval($_POST['discount_value']);
            $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
            
            $min_order = !empty($_POST['min_order']) ? floatval($_POST['min_order']) : 0;
            $apply_to = $_POST['apply_to'] ?? 'all';
            $customer_types = !empty($_POST['customer_types']) ? implode(',', $_POST['customer_types']) : null;
            $min_nights = !empty($_POST['min_nights']) ? intval($_POST['min_nights']) : null;
            $min_rooms = !empty($_POST['min_rooms']) ? intval($_POST['min_rooms']) : null;
            $room_types = !empty($_POST['room_types']) ? implode(',', $_POST['room_types']) : null;
            $service_ids = !empty($_POST['service_ids']) ? implode(',', $_POST['service_ids']) : null;
            
            $total_uses = intval($_POST['total_uses'] ?? 100);
            $per_customer = intval($_POST['per_customer'] ?? 1);
            
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $valid_days = !empty($_POST['valid_days']) ? implode(',', $_POST['valid_days']) : null;
            $valid_hours = trim($_POST['valid_hours'] ?? '');
            
            $status = $_POST['status'] ?? 'active';
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $is_public = isset($_POST['is_public']) ? 1 : 0;
            $auto_apply = isset($_POST['auto_apply']) ? 1 : 0;
            $priority = intval($_POST['priority'] ?? 0);
            $is_stackable = isset($_POST['is_stackable']) ? 1 : 0;
            $payment_methods = !empty($_POST['payment_methods']) ? implode(',', $_POST['payment_methods']) : null;
            
            $created_by = isset($_SESSION['id_nhan_vien']) ? intval($_SESSION['id_nhan_vien']) : null;
            
            // Validate
            if (empty($code) || empty($name) || empty($discount_value) || empty($start_date) || empty($end_date)) {
                $message = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
                $messageType = 'danger';
            } elseif (strtotime($end_date) < strtotime($start_date)) {
                $message = 'Ngày kết thúc phải sau ngày bắt đầu.';
                $messageType = 'danger';
            } else {
                // Check code unique
                $checkStmt = $mysqli->prepare("SELECT voucher_id FROM voucher WHERE code = ? AND deleted IS NULL");
                $checkStmt->bind_param("s", $code);
                $checkStmt->execute();
                if ($checkStmt->get_result()->num_rows > 0) {
                    $message = 'Mã voucher đã tồn tại.';
                    $messageType = 'danger';
                    $checkStmt->close();
                } else {
                    $checkStmt->close();
                    
                    $stmt = $mysqli->prepare("INSERT INTO voucher (
                        code, name, description, image, discount_type, discount_value, max_discount,
                        min_order, apply_to, customer_types, min_nights, min_rooms, room_types, service_ids,
                        total_uses, per_customer, start_date, end_date, valid_days, valid_hours,
                        status, is_featured, is_public, auto_apply, priority, is_stackable, payment_methods, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->bind_param("sssssdddsisssssiisssssiisssi",
                        $code, $name, $description, $image, $discount_type, $discount_value, $max_discount,
                        $min_order, $apply_to, $customer_types, $min_nights, $min_rooms, $room_types, $service_ids,
                        $total_uses, $per_customer, $start_date, $end_date, $valid_days, $valid_hours,
                        $status, $is_featured, $is_public, $auto_apply, $priority, $is_stackable, $payment_methods, $created_by
                    );
                    
                    if ($stmt->execute()) {
                        $message = 'Thêm voucher thành công!';
                        $messageType = 'success';
                        $action = '';
                        if (function_exists('safe_redirect')) {
                            safe_redirect("index.php?page=voucher-manager");
                        } else {
                            echo "<script>window.location.href = 'index.php?page=voucher-manager';</script>";
                            exit;
                        }
                    } else {
                        $message = 'Lỗi: ' . $stmt->error;
                        $messageType = 'danger';
                    }
                    $stmt->close();
                }
            }
        }
    }
    
    if (isset($_POST['update_voucher'])) {
        if (!$canEditVoucher) {
            $message = 'Bạn không có quyền chỉnh sửa voucher.';
            $messageType = 'danger';
        } else {
            $voucher_id = intval($_POST['voucher_id']);
            $code = trim($_POST['code']);
            $name = trim($_POST['name']);
            $description = trim($_POST['description'] ?? '');
            
            // Lấy ảnh cũ
            $oldImageStmt = $mysqli->prepare("SELECT image FROM voucher WHERE voucher_id = ?");
            $oldImageStmt->bind_param("i", $voucher_id);
            $oldImageStmt->execute();
            $oldImageResult = $oldImageStmt->get_result();
            $oldImage = $oldImageResult->fetch_assoc()['image'] ?? '';
            $oldImageStmt->close();
            
            // Xử lý upload ảnh
            $image = $oldImage;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $uploadResult = uploadVoucherImage($_FILES['image'], $oldImage);
                if ($uploadResult !== false) {
                    $image = $uploadResult;
                }
            } else {
                $image = trim($_POST['image'] ?? $oldImage);
            }
            
            $discount_type = $_POST['discount_type'] ?? 'percent';
            $discount_value = floatval($_POST['discount_value']);
            $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
            
            $min_order = !empty($_POST['min_order']) ? floatval($_POST['min_order']) : 0;
            $apply_to = $_POST['apply_to'] ?? 'all';
            $customer_types = !empty($_POST['customer_types']) ? implode(',', $_POST['customer_types']) : null;
            $min_nights = !empty($_POST['min_nights']) ? intval($_POST['min_nights']) : null;
            $min_rooms = !empty($_POST['min_rooms']) ? intval($_POST['min_rooms']) : null;
            $room_types = !empty($_POST['room_types']) ? implode(',', $_POST['room_types']) : null;
            $service_ids = !empty($_POST['service_ids']) ? implode(',', $_POST['service_ids']) : null;
            
            $total_uses = intval($_POST['total_uses'] ?? 100);
            $per_customer = intval($_POST['per_customer'] ?? 1);
            
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $valid_days = !empty($_POST['valid_days']) ? implode(',', $_POST['valid_days']) : null;
            $valid_hours = trim($_POST['valid_hours'] ?? '');
            
            $status = $_POST['status'] ?? 'active';
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $is_public = isset($_POST['is_public']) ? 1 : 0;
            $auto_apply = isset($_POST['auto_apply']) ? 1 : 0;
            $priority = intval($_POST['priority'] ?? 0);
            $is_stackable = isset($_POST['is_stackable']) ? 1 : 0;
            $payment_methods = !empty($_POST['payment_methods']) ? implode(',', $_POST['payment_methods']) : null;
            
            // Check code unique (trừ chính nó)
            $checkStmt = $mysqli->prepare("SELECT voucher_id FROM voucher WHERE code = ? AND voucher_id != ? AND deleted IS NULL");
            $checkStmt->bind_param("si", $code, $voucher_id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = 'Mã voucher đã tồn tại.';
                $messageType = 'danger';
                $checkStmt->close();
            } else {
                $checkStmt->close();
                
                $stmt = $mysqli->prepare("UPDATE voucher SET 
                    code=?, name=?, description=?, image=?, discount_type=?, discount_value=?, max_discount=?,
                    min_order=?, apply_to=?, customer_types=?, min_nights=?, min_rooms=?, room_types=?, service_ids=?,
                    total_uses=?, per_customer=?, start_date=?, end_date=?, valid_days=?, valid_hours=?,
                    status=?, is_featured=?, is_public=?, auto_apply=?, priority=?, is_stackable=?, payment_methods=?
                    WHERE voucher_id=? AND deleted IS NULL");
                
                $stmt->bind_param("sssssdddsisssssiisssssiisssi",
                    $code, $name, $description, $image, $discount_type, $discount_value, $max_discount,
                    $min_order, $apply_to, $customer_types, $min_nights, $min_rooms, $room_types, $service_ids,
                    $total_uses, $per_customer, $start_date, $end_date, $valid_days, $valid_hours,
                    $status, $is_featured, $is_public, $auto_apply, $priority, $is_stackable, $payment_methods, $voucher_id
                );
                
                if ($stmt->execute()) {
                    $message = 'Cập nhật voucher thành công!';
                    $messageType = 'success';
                    if (function_exists('safe_redirect')) {
                        safe_redirect("index.php?page=voucher-manager");
                    } else {
                        echo "<script>window.location.href = 'index.php?page=voucher-manager';</script>";
                        exit;
                    }
                } else {
                    $message = 'Lỗi: ' . $stmt->error;
                    $messageType = 'danger';
                }
                $stmt->close();
            }
        }
    }
    
    if (isset($_POST['delete_voucher'])) {
        if (!$canDeleteVoucher) {
            $message = 'Bạn không có quyền xóa voucher.';
            $messageType = 'danger';
        } else {
            $voucher_id = intval($_POST['voucher_id']);
            $stmt = $mysqli->prepare("UPDATE voucher SET deleted = NOW() WHERE voucher_id = ?");
            $stmt->bind_param("i", $voucher_id);
            
            if ($stmt->execute()) {
                $message = 'Xóa voucher thành công!';
                $messageType = 'success';
                if (function_exists('safe_redirect')) {
                    safe_redirect("index.php?page=voucher-manager");
                } else {
                    echo "<script>window.location.href = 'index.php?page=voucher-manager';</script>";
                    exit;
                }
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }
    
    // Gán voucher cho khách hàng
    if (isset($_POST['assign_voucher_customer'])) {
        $voucher_id = intval($_POST['voucher_id'] ?? 0);
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $note = trim($_POST['note'] ?? '');
        $assigned_by = isset($_SESSION['id_nhan_vien']) ? intval($_SESSION['id_nhan_vien']) : null;
        
        // Kiểm tra voucher_id và customer_id có hợp lệ không
        if ($voucher_id <= 0) {
            $message = 'Voucher ID không hợp lệ! Vui lòng thử lại.';
            $messageType = 'danger';
        } elseif ($customer_id <= 0) {
            $message = 'Vui lòng chọn khách hàng!';
            $messageType = 'danger';
        } else {
            // Kiểm tra voucher có tồn tại và chưa bị xóa không
            $voucherCheckStmt = $mysqli->prepare("SELECT voucher_id, code, name FROM voucher WHERE voucher_id = ? AND deleted IS NULL");
            if (!$voucherCheckStmt) {
                $message = 'Lỗi kết nối database: ' . $mysqli->error;
                $messageType = 'danger';
            } else {
                $voucherCheckStmt->bind_param("i", $voucher_id);
                $voucherCheckStmt->execute();
                $voucherResult = $voucherCheckStmt->get_result();
                
                if ($voucherResult->num_rows == 0) {
                    $message = 'Voucher không tồn tại hoặc đã bị xóa! (ID: ' . $voucher_id . ')';
                    $messageType = 'danger';
                    $voucherCheckStmt->close();
                } else {
                    $voucherCheckStmt->close();
                    
                    // Kiểm tra customer có tồn tại không
                    $customerCheckStmt = $mysqli->prepare("SELECT customer_id, full_name FROM customer WHERE customer_id = ? AND deleted IS NULL");
                    if (!$customerCheckStmt) {
                        $message = 'Lỗi kết nối database: ' . $mysqli->error;
                        $messageType = 'danger';
                    } else {
                        $customerCheckStmt->bind_param("i", $customer_id);
                        $customerCheckStmt->execute();
                        $customerResult = $customerCheckStmt->get_result();
                        
                        if ($customerResult->num_rows == 0) {
                            $message = 'Khách hàng không tồn tại hoặc đã bị xóa! (ID: ' . $customer_id . ')';
                            $messageType = 'danger';
                            $customerCheckStmt->close();
                        } else {
                            $customerCheckStmt->close();
                            
                            // Check xem đã gán chưa (chỉ check những voucher chưa dùng)
                            $checkStmt = $mysqli->prepare("SELECT id FROM voucher_customer WHERE voucher_id = ? AND customer_id = ? AND is_used = 0");
                            if (!$checkStmt) {
                                $message = 'Lỗi kết nối database: ' . $mysqli->error;
                                $messageType = 'danger';
                            } else {
                                $checkStmt->bind_param("ii", $voucher_id, $customer_id);
                                $checkStmt->execute();
                                if ($checkStmt->get_result()->num_rows > 0) {
                                    $message = 'Voucher đã được gán cho khách hàng này rồi (chưa sử dụng).';
                                    $messageType = 'danger';
                                    $checkStmt->close();
                                } else {
                                    $checkStmt->close();
                                    $stmt = $mysqli->prepare("INSERT INTO voucher_customer (voucher_id, customer_id, expires_at, note, assigned_by) VALUES (?, ?, ?, ?, ?)");
                                    if (!$stmt) {
                                        $message = 'Lỗi kết nối database: ' . $mysqli->error;
                                        $messageType = 'danger';
                                    } else {
                                        $stmt->bind_param("iissi", $voucher_id, $customer_id, $expires_at, $note, $assigned_by);
                                        
                                        if ($stmt->execute()) {
                                            $message = 'Gán voucher cho khách hàng thành công!';
                                            $messageType = 'success';
                                        } else {
                                            $message = 'Lỗi khi gán voucher: ' . $stmt->error;
                                            $messageType = 'danger';
                                        }
                                        $stmt->close();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Lấy thông tin voucher để edit
$editVoucher = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM voucher WHERE voucher_id = ? AND deleted IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editVoucher = $result->fetch_assoc();
    $stmt->close();
    
    // Parse các trường dạng string thành array
    if ($editVoucher) {
        $editVoucher['customer_types_array'] = !empty($editVoucher['customer_types']) ? explode(',', $editVoucher['customer_types']) : [];
        $editVoucher['room_types_array'] = !empty($editVoucher['room_types']) ? explode(',', $editVoucher['room_types']) : [];
        $editVoucher['service_ids_array'] = !empty($editVoucher['service_ids']) ? explode(',', $editVoucher['service_ids']) : [];
        $editVoucher['valid_days_array'] = !empty($editVoucher['valid_days']) ? explode(',', $editVoucher['valid_days']) : [];
        $editVoucher['payment_methods_array'] = !empty($editVoucher['payment_methods']) ? explode(',', $editVoucher['payment_methods']) : [];
    }
}

// Phân trang và tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum);
$perPage = 10;
$offset = ($pageNum - 1) * $perPage;

// Xây dựng WHERE clause
$where = "WHERE v.deleted IS NULL";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (v.code LIKE ? OR v.name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
    $types .= 'ss';
}

if ($status_filter) {
    $where .= " AND v.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Đếm tổng số
$countQuery = "SELECT COUNT(*) as total FROM voucher v $where";
$countStmt = $mysqli->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$countStmt->close();

// Lấy dữ liệu
$query = "SELECT v.*, 
    nv.ho_ten as created_by_name,
    (SELECT COUNT(*) FROM voucher_usage vu WHERE vu.voucher_id = v.voucher_id) as usage_count
    FROM voucher v
    LEFT JOIN nhan_vien nv ON v.created_by = nv.id_nhan_vien
    $where
    ORDER BY v.created_at DESC 
    LIMIT $perPage OFFSET $offset";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $vouchers = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Lỗi query: " . $stmt->error);
}
$stmt->close();

// Lấy danh sách room types, services, customers cho form
$roomTypesResult = $mysqli->query("SELECT room_type_id, room_type_name FROM room_type WHERE deleted IS NULL ORDER BY room_type_name");
$roomTypes = $roomTypesResult->fetch_all(MYSQLI_ASSOC);

$servicesResult = $mysqli->query("SELECT service_id, service_name FROM service WHERE deleted IS NULL ORDER BY service_name");
$services = $servicesResult->fetch_all(MYSQLI_ASSOC);

$customersResult = $mysqli->query("SELECT customer_id, full_name, phone FROM customer WHERE deleted IS NULL ORDER BY full_name");
$customers = $customersResult->fetch_all(MYSQLI_ASSOC);

// Build base URL for pagination
$baseUrl = "index.php?page=voucher-manager";
if ($search) $baseUrl .= "&search=" . urlencode($search);
if ($status_filter) $baseUrl .= "&status=" . urlencode($status_filter);
?>

<div class="main-content">
    <div class="content-header">
        <h1>Quản Lý Voucher</h1>
        <div class="row m-3">
            <div class="col-md-12">
                <?php if ($canCreateVoucher): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVoucherModal">
                    <i class="fas fa-plus"></i> Thêm Voucher
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
            <input type="hidden" name="page" value="voucher-manager">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Tìm mã hoặc tên voucher..."
                            value="<?php echo h($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Đang hoạt
                            động</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Tạm dừng
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên Voucher</th>
                    <th>Loại Giảm</th>
                    <th>Đã Dùng</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vouchers)): ?>
                <tr>
                    <td colspan="9" class="text-center">Không có dữ liệu</td>
                </tr>
                <?php else: ?>
                <?php foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><strong><?php echo h($voucher['code']); ?></strong></td>
                    <td><?php echo h($voucher['name']); ?></td>
                    <td>
                        <span class="badge bg-info">
                            <?php echo $voucher['discount_type'] == 'percent' ? '%' : 'VNĐ'; ?>
                        </span>
                    </td>
                    <td>
                        <?php echo $voucher['usage_count'] ?? 0; ?> / <?php echo $voucher['total_uses']; ?>
                    </td>
                    <td>
                        <span
                            class="badge <?php echo $voucher['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $voucher['status'] == 'active' ? 'Hoạt động' : 'Tạm dừng'; ?>
                        </span>
                        <?php if ($voucher['is_featured']): ?>
                        <span class="badge bg-warning">Nổi bật</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info"
                            onclick="viewVoucherDetails(<?php echo $voucher['voucher_id']; ?>)" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($canEditVoucher): ?>
                        <button class="btn btn-sm btn-outline-warning"
                            onclick="editVoucher(<?php echo $voucher['voucher_id']; ?>)" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($canDeleteVoucher): ?>
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="deleteVoucher(<?php echo $voucher['voucher_id']; ?>)" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-secondary"
                            onclick="viewVoucherUsage(<?php echo $voucher['voucher_id']; ?>)" title="Lịch sử sử dụng">
                            <i class="fas fa-history"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php 
    if (function_exists('getPagination')) {
        echo getPagination($total, $perPage, $pageNum, $baseUrl);
    }
    ?>
</div>

<!-- Modal Thêm/Sửa Voucher -->
<div class="modal fade" id="addVoucherModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $editVoucher ? 'Sửa' : 'Thêm'; ?> Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="voucherForm" enctype="multipart/form-data">
                <?php if ($editVoucher): ?>
                <input type="hidden" name="voucher_id" value="<?php echo $editVoucher['voucher_id']; ?>">
                <?php endif; ?>

                <div class="modal-body px-4 pt-3 pb-4">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-pills nav-fill mb-4 gap-2" id="voucherTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold" id="basic-tab" data-bs-toggle="tab"
                                data-bs-target="#basic" type="button" role="tab">
                                <i class="fas fa-info-circle me-2"></i>Thông tin
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold" id="discount-tab" data-bs-toggle="tab"
                                data-bs-target="#discount" type="button" role="tab">
                                <i class="fas fa-tags me-2"></i>Giảm giá
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold" id="conditions-tab" data-bs-toggle="tab"
                                data-bs-target="#conditions" type="button" role="tab">
                                <i class="fas fa-clipboard-check me-2"></i>Điều kiện
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold" id="validity-tab" data-bs-toggle="tab"
                                data-bs-target="#validity" type="button" role="tab">
                                <i class="fas fa-clock me-2"></i>Thời hạn
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold" id="settings-tab" data-bs-toggle="tab"
                                data-bs-target="#settings" type="button" role="tab">
                                <i class="fas fa-cog me-2"></i>Cấu hình
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="voucherTabContent">
                        <!-- 1. Basic Info -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel">
                            <div class="bg-light rounded-3 p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted">Mã Voucher <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control shadow-sm rounded-3" name="code"
                                            value="<?php echo h($editVoucher['code'] ?? ''); ?>" required maxlength="20"
                                            placeholder="VD: SUMMER2024">
                                        <div class="form-text">Mã phải duy nhất, tối đa 20 ký tự</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted">Tên Voucher <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control shadow-sm rounded-3" name="name"
                                            value="<?php echo h($editVoucher['name'] ?? ''); ?>" required
                                            maxlength="100">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-muted">Mô Tả</label>
                                        <textarea class="form-control shadow-sm rounded-3" name="description" rows="2"
                                            maxlength="300"><?php echo h($editVoucher['description'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-muted">Hình Ảnh</label>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php 
                                                    $imgSrc = !empty($editVoucher['image']) ? $editVoucher['image'] : 'assets/img/placeholder-image.png';
                                                    $display = !empty($editVoucher['image']) ? 'block' : 'none'; 
                                                ?>
                                                <img id="voucherPreview" src="<?php echo h($imgSrc); ?>"
                                                    class="rounded-3 shadow-sm object-fit-cover"
                                                    style="width: 100px; height: 100px; display: <?php echo $display; ?>;"
                                                    alt="Preview">
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="file" class="form-control shadow-sm rounded-3"
                                                    id="voucherImage" name="image" accept="image/*"
                                                    onchange="previewImage(this, 'voucherPreview')">
                                                <input type="hidden" name="image"
                                                    value="<?php echo h($editVoucher['image'] ?? ''); ?>"
                                                    id="voucherImageHidden">
                                                <div class="form-text small mt-1">Định dạng: JPG, PNG, GIF. Tối đa 5MB.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Discount Value -->
                        <div class="tab-pane fade" id="discount" role="tabpanel">
                            <div class="bg-light rounded-3 p-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted">Loại Giảm <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select shadow-sm rounded-3" name="discount_type"
                                            id="discount_type" required>
                                            <option value="percent"
                                                <?php echo ($editVoucher['discount_type'] ?? 'percent') == 'percent' ? 'selected' : ''; ?>>
                                                Phần trăm (%)</option>
                                            <option value="fixed"
                                                <?php echo ($editVoucher['discount_type'] ?? '') == 'fixed' ? 'selected' : ''; ?>>
                                                Số tiền cố định (VNĐ)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted">Giá Trị <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group shadow-sm">
                                            <input type="number" class="form-control rounded-3" name="discount_value"
                                                id="discount_value_input"
                                                value="<?php echo $editVoucher['discount_value'] ?? ''; ?>" required
                                                step="0.01" min="0">
                                        </div>
                                        <div class="form-text" id="discount_hint">Nhập số phần trăm (0-100)</div>
                                    </div>
                                    <div class="col-md-4" id="max_discount_container"
                                        style="<?php echo ($editVoucher['discount_type'] ?? 'percent') == 'fixed' ? 'display: none;' : ''; ?>">
                                        <label class="form-label fw-bold text-muted">Giảm Tối Đa (VNĐ)</label>
                                        <input type="number" class="form-control shadow-sm rounded-3"
                                            name="max_discount"
                                            value="<?php echo $editVoucher['max_discount'] ?? ''; ?>" step="0.01"
                                            min="0" placeholder="0 = Không giới hạn">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Conditions -->
                        <div class="tab-pane fade" id="conditions" role="tabpanel">
                            <div class="bg-light rounded-3 p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted">Đơn Hàng Tối Thiểu
                                            (VNĐ)</label>
                                        <input type="number" class="form-control shadow-sm rounded-3" name="min_order"
                                            value="<?php echo $editVoucher['min_order'] ?? '0'; ?>" step="1000" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted">Áp Dụng Cho</label>
                                        <select class="form-select shadow-sm rounded-3" name="apply_to">
                                            <option value="all"
                                                <?php echo ($editVoucher['apply_to'] ?? 'all') == 'all' ? 'selected' : ''; ?>>
                                                Tất cả</option>
                                            <option value="room"
                                                <?php echo ($editVoucher['apply_to'] ?? '') == 'room' ? 'selected' : ''; ?>>
                                                Chỉ phòng</option>
                                            <option value="service"
                                                <?php echo ($editVoucher['apply_to'] ?? '') == 'service' ? 'selected' : ''; ?>>
                                                Chỉ dịch vụ</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="card bg-white border shadow-sm rounded-3">
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label
                                                            class="form-label fw-bold text-muted d-block mb-2">Loại
                                                            Khách Hàng</label>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="customer_types[]" value="VIP"
                                                                <?php echo (isset($editVoucher['customer_types_array']) && in_array('VIP', $editVoucher['customer_types_array'])) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label text-muted">VIP</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="customer_types[]" value="Corporate"
                                                                <?php echo (isset($editVoucher['customer_types_array']) && in_array('Corporate', $editVoucher['customer_types_array'])) ? 'checked' : ''; ?>>
                                                            <label
                                                                class="form-check-label text-muted">Corporate</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="customer_types[]" value="Regular"
                                                                <?php echo (isset($editVoucher['customer_types_array']) && in_array('Regular', $editVoucher['customer_types_array'])) ? 'checked' : ''; ?>>
                                                            <label
                                                                class="form-check-label text-muted">Regular</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold text-muted">Giới hạn thời
                                                            gian</label>
                                                        <input type="number"
                                                            class="form-control shadow-sm rounded-3 mb-2"
                                                            name="min_nights" placeholder="Số đêm tối thiểu"
                                                            value="<?php echo $editVoucher['min_nights'] ?? ''; ?>">
                                                        <input type="number"
                                                            class="form-control shadow-sm rounded-3"
                                                            name="min_rooms" placeholder="Số phòng tối thiểu"
                                                            value="<?php echo $editVoucher['min_rooms'] ?? ''; ?>">
                                                    </div>
                                                </div>

                                                <!-- Object Limits Section (Moved down & Expanded) -->
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <label class="form-label fw-bold text-muted">Giới hạn đối
                                                            tượng (Chọn nhiều)</label>
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label text-muted">Loại
                                                                    phòng</label>
                                                                <select
                                                                    class="form-select shadow-sm rounded-3"
                                                                    name="room_types[]" multiple style="height: 150px;">
                                                                    <option disabled>-- Chọn loại phòng --</option>
                                                                    <?php foreach ($roomTypes as $rt): ?>
                                                                    <option value="<?php echo $rt['room_type_id']; ?>"
                                                                        <?php echo (isset($editVoucher['room_types_array']) && in_array($rt['room_type_id'], $editVoucher['room_types_array'])) ? 'selected' : ''; ?>>
                                                                        <?php echo h($rt['room_type_name']); ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-muted">Dịch
                                                                    vụ</label>
                                                                <select
                                                                    class="form-select shadow-sm rounded-3"
                                                                    name="service_ids[]" multiple
                                                                    style="height: 150px;">
                                                                    <option disabled>-- Chọn dịch vụ --</option>
                                                                    <?php foreach ($services as $sv): ?>
                                                                    <option value="<?php echo $sv['service_id']; ?>"
                                                                        <?php echo (isset($editVoucher['service_ids_array']) && in_array($sv['service_id'], $editVoucher['service_ids_array'])) ? 'selected' : ''; ?>>
                                                                        <?php echo h($sv['service_name']); ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-text mt-1">Giữ
                                                            phím <b>Ctrl</b> (Windows) hoặc <b>Cmd</b> (Mac) để chọn
                                                            nhiều mục.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Validity -->
                        <div class="tab-pane fade" id="validity" role="tabpanel">
                            <div class="bg-light rounded-3 p-4">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold text-muted">Từ ngày <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control shadow-sm rounded-3" name="start_date"
                                            value="<?php echo $editVoucher['start_date'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold text-muted">Đến ngày <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control shadow-sm rounded-3" name="end_date"
                                            value="<?php echo $editVoucher['end_date'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-muted d-block mb-2">Ngày trong
                                            tuần</label>
                                        <div>
                                            <?php $days = ['Mon' => 'T2', 'Tue' => 'T3', 'Wed' => 'T4', 'Thu' => 'T5', 'Fri' => 'T6', 'Sat' => 'T7', 'Sun' => 'CN']; 
                                            foreach ($days as $code => $label): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="valid_days[]"
                                                    value="<?php echo $code; ?>"
                                                    <?php echo (isset($editVoucher['valid_days_array']) && in_array($code, $editVoucher['valid_days_array'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label"><?php echo $label; ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-muted">Khung giờ
                                            (HH:MM-HH:MM)</label>
                                        <input type="text" class="form-control shadow-sm rounded-3" name="valid_hours"
                                            value="<?php echo h($editVoucher['valid_hours'] ?? ''); ?>"
                                            placeholder="VD: 14:00-18:00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Settings -->
                        <div class="tab-pane fade" id="settings" role="tabpanel">
                            <div class="bg-light rounded-3 p-4">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold text-muted">Tổng số lượng</label>
                                        <input type="number" class="form-control shadow-sm rounded-3" name="total_uses"
                                            value="<?php echo $editVoucher['total_uses'] ?? '100'; ?>" required min="1">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold text-muted">Giới hạn / khách</label>
                                        <input type="number" class="form-control shadow-sm rounded-3"
                                            name="per_customer"
                                            value="<?php echo $editVoucher['per_customer'] ?? '1'; ?>" min="1">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold text-muted">Trạng thái</label>
                                        <select class="form-select shadow-sm rounded-3" name="status" required>
                                            <option value="active" class="text-success"
                                                <?php echo ($editVoucher['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>
                                                Hoạt động</option>
                                            <option value="inactive" class="text-secondary"
                                                <?php echo ($editVoucher['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>
                                                Tạm dừng</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold text-muted">Ưu tiên</label>
                                        <input type="number" class="form-control shadow-sm rounded-3" name="priority"
                                            value="<?php echo $editVoucher['priority'] ?? '0'; ?>" min="0">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                                id="is_featured"
                                                <?php echo ($editVoucher['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_featured">Voucher nổi bật
                                                (hiển thị đầu)</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="is_public" value="1"
                                                id="is_public"
                                                <?php echo ($editVoucher['is_public'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_public">Công khai trên
                                                website</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_stackable"
                                                value="1" id="is_stackable"
                                                <?php echo ($editVoucher['is_stackable'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_stackable">Cho phép dùng chung
                                                voucher khác</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"
                        name="<?php echo $editVoucher ? 'update_voucher' : 'add_voucher'; ?>">
                        <?php echo $editVoucher ? 'Cập nhật' : 'Thêm'; ?> Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Xem Chi Tiết Voucher -->
<div class="modal fade" id="viewVoucherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi Tiết Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="voucherDetailsContent">
                <!-- Nội dung sẽ được load bằng AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lịch Sử Sử Dụng Voucher -->
<div class="modal fade" id="voucherUsageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lịch Sử Sử Dụng Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="voucherUsageContent">
                <!-- Nội dung sẽ được load bằng AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận xóa voucher -->
<div class="modal fade" id="confirmDeleteVoucherModal" tabindex="-1" aria-labelledby="confirmDeleteVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteVoucherModalLabel">Xác nhận xóa</h5>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3 mb-0">Bạn có chắc muốn xóa voucher này?<br>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">Hủy</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteVoucher" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle max_discount field based on discount_type
document.addEventListener('DOMContentLoaded', function() {
    const discountType = document.getElementById('discount_type');
    const maxDiscountContainer = document.getElementById('max_discount_container');
    const discountHint = document.getElementById('discount_hint');

    if (discountType && maxDiscountContainer) {
        discountType.addEventListener('change', function() {
            if (this.value === 'percent') {
                maxDiscountContainer.style.display = 'block';
                if (discountHint) discountHint.textContent = 'Nhập số phần trăm (0-100)';
            } else {
                maxDiscountContainer.style.display = 'none';
                if (discountHint) discountHint.textContent = 'Nhập số tiền (VNĐ)';
            }
        });
    }

    // Auto-open edit modal
    <?php if ($editVoucher): ?>
    const editModal = new bootstrap.Modal(document.getElementById('addVoucherModal'));
    editModal.show();
    <?php endif; ?>
});

function editVoucher(id) {
    window.location.href = 'index.php?page=voucher-manager&action=edit&id=' + id;
}

let voucherIdToDelete = null;

function deleteVoucher(id) {
    voucherIdToDelete = id;
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteVoucherModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const btnConfirmDelete = document.getElementById('btnConfirmDeleteVoucher');
    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', function() {
            if (voucherIdToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="voucher_id" value="' + voucherIdToDelete + '">' +
                    '<input type="hidden" name="delete_voucher" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});



function assignVoucher(id) {
    if (!id || id <= 0) {
        alert('Lỗi: Voucher ID không hợp lệ!');
        return;
    }
    const voucherIdInput = document.getElementById('assign_voucher_id');
    if (!voucherIdInput) {
        alert('Lỗi: Không tìm thấy form gán voucher!');
        return;
    }
    voucherIdInput.value = id;
    const modal = new bootstrap.Modal(document.getElementById('assignVoucherModal'));
    modal.show();
}

function viewVoucherDetails(id) {
    fetch('api/voucher-api.php?action=view&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('voucherDetailsContent').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('viewVoucherModal'));
                modal.show();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra khi tải dữ liệu.');
        });
}

function viewVoucherUsage(id) {
    fetch('api/voucher-api.php?action=usage&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('voucherUsageContent').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('voucherUsageModal'));
                modal.show();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra khi tải dữ liệu.');
        });
}

// function resetVoucherForm
function resetVoucherForm() {
    const modal = document.getElementById('addVoucherModal');
    if (!modal) return;

    const url = new URL(window.location);
    url.searchParams.delete('action');
    url.searchParams.delete('id');
    window.history.replaceState({}, '', url);

    const form = document.getElementById('voucherForm');
    if (!form) return;

    form.reset();

    // Reset all input values
    form.querySelectorAll(
        'input[type="text"], input[type="number"], input[type="date"], textarea'
    ).forEach(input => {
        if (input.name !== 'page' && input.name !== 'panel') {
            if (input.type === 'date') {
                input.value = '';
            } else if (input.type === 'number') {
                if (input.name === 'total_uses') input.value = '100';
                else if (input.name === 'per_customer') input.value = '1';
                else if (input.name === 'priority') input.value = '0';
                else input.value = '';
            } else {
                input.value = '';
            }
        }
    });

    // Reset all selects
    form.querySelectorAll('select').forEach(select => {
        if (select.name === 'discount_type') select.value = 'percent';
        else if (select.name === 'status') select.value = 'active';
        else if (select.name === 'apply_to') select.value = 'all';
        else select.selectedIndex = 0;
    });

    // Handle Discount Type Change (Trigger event to update UI)
    const discountType = document.getElementById('discount_type');
    if (discountType) {
        // Manually trigger change event logic
        const event = new Event('change');
        discountType.dispatchEvent(event);
    }

    // Reset all checkboxes
    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        // Default values for specific checkboxes
        if (checkbox.name === 'is_public') checkbox.checked = true;
        else checkbox.checked = false;
    });

    // Reset image preview
    const imagePreview = document.getElementById('voucherPreview');
    if (imagePreview) {
        imagePreview.style.display = 'none';
        imagePreview.src = '';
    }
    const imageInput = document.getElementById('voucherImage');
    if (imageInput) imageInput.value = '';
    const imageInputHidden = document.getElementById('voucherImageHidden');
    if (imageInputHidden) imageInputHidden.value = '';



    // Reset modal title
    const modalTitle = modal.querySelector('.modal-title');
    if (modalTitle) modalTitle.textContent = 'Thêm Voucher';

    // Reset submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.name = 'add_voucher';
        submitBtn.textContent = 'Thêm Voucher';
    }

    // Clear hidden voucher_id if exists (remove if dynamically added, or value reset)
    // The PHP loop renders input type hidden name=voucher_id only if editVoucher.
    // In JS we might need to handle it if we switch from edit to add without reload.
    // But since this implementation reloads for edit, safe to just reset form.
    const voucherIdInput = form.querySelector('input[name="voucher_id"]');
    if (voucherIdInput) voucherIdInput.remove();

    // Reset Tabs
    const firstTabTrigger = document.querySelector('#voucherTab button[data-bs-target="#basic"]');
    if (firstTabTrigger) {
        const tab = new bootstrap.Tab(firstTabTrigger);
        tab.show();
    }
}

// Reset form when modal closes
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addVoucherModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            resetVoucherForm();
        });

        // Reset form when modal opens if not in edit mode
        modal.addEventListener('show.bs.modal', function() {
            const isEditMode = window.location.search.includes('action=edit');
            if (!isEditMode) {
                resetVoucherForm();
            }
        });
    }

    // Reset form when "Add" button is clicked
    document.querySelectorAll('[data-bs-target="#addVoucherModal"]').forEach(button => {
        button.addEventListener('click', function() {
            // Small delay to ensure event propagation
            setTimeout(function() {
                resetVoucherForm();
            }, 100);
        });
    });
});
</script>