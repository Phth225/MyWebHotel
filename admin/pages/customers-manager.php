<?php
// Kiểm tra quyền module Khách Hàng
$canViewCustomers   = function_exists('checkPermission') ? checkPermission('customer.view')   : true;
$canCreateCustomers = function_exists('checkPermission') ? checkPermission('customer.create') : true;
$canEditCustomers   = function_exists('checkPermission') ? checkPermission('customer.edit')   : true;
$canDeleteCustomers = function_exists('checkPermission') ? checkPermission('customer.delete') : true;

if (!$canViewCustomers) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền truy cập trang khách hàng.</div></div>';
    return;
}

// Xử lý CRUD
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$messageType = '';

// Xử lý cập nhật hạng cho tất cả khách hàng
if (isset($_POST['update_all_ranks']) && $canEditCustomers) {
    require_once __DIR__ . '/../includes/customer_rank_helper.php';
    $result = updateAllCustomerRanks($mysqli);
    
    if ($result['updated'] > 0) {
        $message = "Đã cập nhật hạng cho {$result['updated']} khách hàng (tổng {$result['total']} khách hàng).";
        $messageType = 'success';
    } else {
        $message = "Không có khách hàng nào cần cập nhật hạng (tổng {$result['total']} khách hàng).";
        $messageType = 'info';
    }
    
    if (!empty($result['errors'])) {
        $message .= " Có " . count($result['errors']) . " lỗi xảy ra.";
        $messageType = 'warning';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_customer'])) {
        if (!$canCreateCustomers) {
            $message = 'Bạn không có quyền thêm khách hàng.';
            $messageType = 'danger';
        } else {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $username = trim($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $gender = $_POST['gender'] ?? 'Other';
            $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            $address = trim($_POST['address'] ?? '');
            $id_card = trim($_POST['id_card'] ?? '');
            $customer_type = $_POST['customer_type'] ?? 'Regular';
            $account_status = $_POST['account_status'] ?? 'Active';

            $stmt = $mysqli->prepare("INSERT INTO customer (full_name, email, phone, username, password, gender, date_of_birth, address, id_card, customer_type, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $full_name, $email, $phone, $username, $password, $gender, $date_of_birth, $address, $id_card, $customer_type, $account_status);

            if ($stmt->execute()) {
                $message = 'Thêm khách hàng thành công!';
                $messageType = 'success';
                $action = '';
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }

    if (isset($_POST['update_customer'])) {
        if (!$canEditCustomers) {
            $message = 'Bạn không có quyền chỉnh sửa khách hàng.';
            $messageType = 'danger';
        } else {
            $customer_id = intval($_POST['customer_id']);
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $gender = $_POST['gender'] ?? 'Other';
            $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            $address = trim($_POST['address'] ?? '');
            $id_card = trim($_POST['id_card'] ?? '');
            $customer_type = $_POST['customer_type'] ?? 'Regular';
            $account_status = $_POST['account_status'] ?? 'Active';

            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE customer SET full_name=?, email=?, phone=?, password=?, gender=?, date_of_birth=?, address=?, id_card=?, customer_type=?, account_status=? WHERE customer_id=? AND deleted IS NULL");
                $stmt->bind_param("ssssssssssi", $full_name, $email, $phone, $password, $gender, $date_of_birth, $address, $id_card, $customer_type, $account_status, $customer_id);
            } else {
                $stmt = $mysqli->prepare("UPDATE customer SET full_name=?, email=?, phone=?, gender=?, date_of_birth=?, address=?, id_card=?, customer_type=?, account_status=? WHERE customer_id=? AND deleted IS NULL");
                $stmt->bind_param("sssssssssi", $full_name, $email, $phone, $gender, $date_of_birth, $address, $id_card, $customer_type, $account_status, $customer_id);
            }

            if ($stmt->execute()) {
                $message = 'Cập nhật khách hàng thành công!';
                $messageType = 'success';
                $action = '';
                header("Location: index.php?page=customers-manager&success=1");
                exit;
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }

    if (isset($_POST['delete_customer'])) {
        if (!$canDeleteCustomers) {
            $message = 'Bạn không có quyền xóa khách hàng.';
            $messageType = 'danger';
        } else {
            $customer_id = intval($_POST['customer_id']);
            $stmt = $mysqli->prepare("UPDATE customer SET deleted = NOW() WHERE customer_id = ?");
            $stmt->bind_param("i", $customer_id);

            if ($stmt->execute()) {
                $message = 'Xóa khách hàng thành công!';
                $messageType = 'success';
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }
}

// Lấy thông tin khách hàng để edit
$editCustomer = null;
if ($action == 'edit' && isset($_GET['id'])) {
    if (!$canEditCustomers) {
        $message = 'Bạn không có quyền chỉnh sửa khách hàng.';
        $messageType = 'danger';
        $action = '';
    } else {
        $id = intval($_GET['id']);
        $stmt = $mysqli->prepare("SELECT * FROM customer WHERE customer_id = ? AND deleted IS NULL");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $editCustomer = $result->fetch_assoc();
        $stmt->close();
    }
}

// Phân trang và tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum);
$perPage = 5;
$offset = ($pageNum - 1) * $perPage;

// Xây dựng WHERE clause
$where = "WHERE c.deleted IS NULL";

if ($search) {
    $search = $mysqli->real_escape_string($search);
    $where .= " AND (c.full_name LIKE '%$search%' OR c.email LIKE '%$search%' OR c.phone LIKE '%$search%' OR c.username LIKE '%$search%')";
}

if ($type_filter) {
    $type_filter = $mysqli->real_escape_string($type_filter);
    $where .= " AND c.customer_type = '$type_filter'";
}

// Xây dựng ORDER BY
$orderBy = "ORDER BY c.created_at DESC";
switch ($sort) {
    case 'oldest':
        $orderBy = "ORDER BY c.created_at ASC";
        break;
    case 'name_asc':
        $orderBy = "ORDER BY c.full_name ASC";
        break;
    case 'name_desc':
        $orderBy = "ORDER BY c.full_name DESC";
        break;
    case 'newest':
    default:
        $orderBy = "ORDER BY c.created_at DESC";
        break;
}

// Đếm tổng số - KHÔNG DÙNG JOIN VÀO ĐÂY
$countQuery = "SELECT COUNT(*) as total FROM customer c $where";
$countResult = $mysqli->query($countQuery);
$total = 0;

if ($countResult) {
    $totalRow = $countResult->fetch_assoc();
    $total = $totalRow['total'] ?? 0;
} else {
    error_log("Count Error: " . $mysqli->error);
    $message = "Lỗi đếm dữ liệu: " . $mysqli->error;
    $messageType = 'danger';
}

// Lấy dữ liệu - NỐI STRING TRỰC TIẾP (tránh vấn đề GROUP BY)
$customers = [];
if ($total > 0) {
    // Đảm bảo $perPage và $offset là số nguyên dương
    $perPage = intval($perPage);
    $offset = intval($offset);

    $query = "SELECT c.*, 
        COUNT(DISTINCT b.booking_id) as booking_count,
        COALESCE(SUM(i.total_amount), 0) as total_spending
        FROM customer c 
        LEFT JOIN booking b ON b.customer_id = c.customer_id AND b.deleted IS NULL
        LEFT JOIN invoice i ON i.booking_id = b.booking_id AND i.deleted IS NULL
        $where 
        GROUP BY c.customer_id
        $orderBy 
        LIMIT $perPage OFFSET $offset";

    $result = $mysqli->query($query);
    if (!$result) {
        error_log("Query Error: " . $mysqli->error);
        $message = "Lỗi truy vấn: " . $mysqli->error;
        $messageType = 'danger';
    } else {
        $customers = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Build base URL for pagination
$baseUrl = "index.php?page=customers-manager";
if ($search) $baseUrl .= "&search=" . urlencode($search);
if ($type_filter) $baseUrl .= "&type=" . urlencode($type_filter);
if ($sort) $baseUrl .= "&sort=" . urlencode($sort);
?>
<div class="main-content">
    <div class="content-header">
        <h1>Quản Lý Khách Hàng</h1>
        <?php if ($canCreateCustomers || $canEditCustomers): ?>
        <div class="row m-3">
            <?php if ($canCreateCustomers): ?>
            <div class="col-md-6">
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addCustomerModal" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Thêm Khách Hàng
                </button>
            </div>
            <?php endif; ?>
            <?php if ($canEditCustomers): ?>
            <div class="col-md-6">
                <form method="POST" action="" style="display: inline;"
                    onsubmit="return confirm('Bạn có chắc muốn cập nhật hạng cho tất cả khách hàng?');">
                    <button type="submit" name="update_all_ranks" class="btn-update">
                        <i class="fas fa-sync-alt"></i> Cập Nhật Hạng
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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
            <input type="hidden" name="page" value="customers-manager">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Tìm kiếm khách hàng..."
                            value="<?php echo h($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type">
                        <option value="">Tất cả hạng</option>
                        <option value="Regular" <?php echo $type_filter == 'Regular' ? 'selected' : ''; ?>>Regular
                        </option>
                        <option value="VIP" <?php echo $type_filter == 'VIP' ? 'selected' : ''; ?>>VIP</option>
                        <option value="Corporate" <?php echo $type_filter == 'Corporate' ? 'selected' : ''; ?>>Corporate
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="sort">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                        <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Cũ nhất</option>
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Tên (A → Z)
                        </option>
                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Tên (Z → A)
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
    <div class="table-container">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Họ Tên</th>
                    <th>Hạng</th>
                    <th>Số Điện Thoại</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="6" class="text-center">Không có dữ liệu</td>
                </tr>
                <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td>
                        <div class="employee-info">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($customer['full_name']); ?>&background=d4b896&color=fff"
                                alt="Avatar" class="employee-avatar">
                            <div>
                                <p class="employee-name"><?php echo h($customer['full_name']); ?></p>
                                <p class="employee-id">ID: <?php echo $customer['customer_id']; ?></p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php
                                $badgeClass = 'badge';
                                if ($customer['customer_type'] == 'VIP') $badgeClass = 'badge badge-diamond';
                                elseif ($customer['customer_type'] == 'Corporate') $badgeClass = 'badge badge-gold';
                                ?>
                        <span class="<?php echo $badgeClass; ?>"><?php echo h($customer['customer_type']); ?></span>
                    </td>
                    <td><?php echo h($customer['phone'] ?: '-'); ?></td>
                    <td><?php echo h($customer['email']); ?></td>
                    <td>
                        <span
                            class="badge <?php echo $customer['account_status'] == 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo h($customer['account_status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($canViewCustomers): ?>
                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                            data-bs-target="#viewCustomerModal<?php echo $customer['customer_id']; ?>"
                            title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($canEditCustomers): ?>
                        <button class="btn btn-sm btn-outline-warning"
                            onclick="editCustomer(<?php echo $customer['customer_id']; ?>)" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($canDeleteCustomers): ?>
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="deleteCustomer(<?php echo $customer['customer_id']; ?>)" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ($canViewCustomers): ?>
                <!-- View Modal for each customer -->
                <div class="modal fade" id="viewCustomerModal<?php echo $customer['customer_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-user"></i> Thông Tin Khách Hàng</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-4">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($customer['full_name']); ?>&background=d4b896&color=fff&size=120"
                                        alt="Avatar" class="avatar-preview">
                                    <h5 class="mt-2"><?php echo h($customer['full_name']); ?></h5>
                                    <p class="text-muted">ID: <?php echo $customer['customer_id']; ?></p>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6"><strong>Email:</strong> <?php echo h($customer['email']); ?>
                                    </div>
                                    <div class="col-md-6"><strong>Số điện thoại:</strong>
                                        <?php echo h($customer['phone'] ?: '-'); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6"><strong>Ngày sinh:</strong>
                                        <?php echo formatDate($customer['date_of_birth']); ?></div>
                                    <div class="col-md-6"><strong>Giới tính:</strong>
                                        <?php echo h($customer['gender']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6"><strong>Địa chỉ:</strong>
                                        <?php echo h($customer['address'] ?: '-'); ?></div>
                                    <div class="col-md-6"><strong>CMND/CCCD:</strong>
                                        <?php echo h($customer['id_card'] ?: '-'); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6"><strong>Hạng:</strong>
                                        <?php echo h($customer['customer_type']); ?></div>
                                    <div class="col-md-6"><strong>Trạng thái:</strong>
                                        <?php echo h($customer['account_status']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6"><strong>Ngày tạo:</strong>
                                        <?php echo formatDateTime($customer['created_at']); ?></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button type="button" class="btn btn-primary"
                                    onclick="editCustomer(<?php echo $customer['customer_id']; ?>)">
                                    <i class="fas fa-edit"></i> Chỉnh Sửa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php echo getPagination($total, $perPage, $pageNum, $baseUrl); ?>

    <?php if ($canCreateCustomers || $canEditCustomers): ?>
    <!-- Modal: Thêm/Sửa khách hàng -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> <span id="modalTitle">Thêm Khách
                            Hàng</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="customerForm">
                    <div class="modal-body">
                        <input type="hidden" name="customer_id" id="customer_id" value="">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và Tên *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số Điện Thoại</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu <span id="passwordRequired">*</span></label>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Nhập mật khẩu" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày Sinh</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giới Tính</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="Male">Nam</option>
                                    <option value="Female">Nữ</option>
                                    <option value="Other">Khác</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Quốc tịch</label>
                                <input type="text" class="form-control" id="address" name="address">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CMND/CCCD</label>
                                <input type="text" class="form-control" id="id_card" name="id_card">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hạng khách hàng</label>
                                <select class="form-select" id="customer_type" name="customer_type">
                                    <option value="Regular">Regular</option>
                                    <option value="VIP">VIP</option>
                                    <option value="Corporate">Corporate</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trạng thái tài khoản</label>
                            <select class="form-select" id="account_status" name="account_status">
                                <option value="Active">Active</option>
                                <option value="Locked">Locked</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Thêm Khách Hàng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Modal Xác nhận xóa khách hàng -->
    <div class="modal fade" id="confirmDeleteCustomerModal" tabindex="-1" aria-labelledby="confirmDeleteCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteCustomerModalLabel">Xác nhận xóa</h5>
                </div>
                <div class="modal-body text-center">
                    <p class="mt-3 mb-0">Bạn có chắc muốn xóa khách hàng này?<br>Hành động này không thể hoàn tác.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">Hủy</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDeleteCustomer" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
<?php if ($canCreateCustomers || $canEditCustomers): ?>

function resetForm() {
    // Clear URL parameters first
    const url = new URL(window.location);
    url.searchParams.delete('action');
    url.searchParams.delete('id');
    window.history.replaceState({}, '', url);

    // Reset form
    const form = document.getElementById('customerForm');
    if (form) {
        form.reset();
        document.getElementById('customer_id').value = '';
        document.getElementById('username').readOnly = false;
        document.getElementById('password').required = true;
        document.getElementById('passwordRequired').style.display = 'inline';
        document.getElementById('modalTitle').textContent = 'Thêm Khách Hàng';
        document.getElementById('submitBtn').textContent = 'Thêm Khách Hàng';
        document.getElementById('submitBtn').name = 'add_customer';
    }
}

function editCustomer(id) {
    window.location.href = 'index.php?page=customers-manager&action=edit&id=' + id;
}

// Auto-reset when modal is closed
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addCustomerModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            // Clear URL parameters when modal is closed
            const url = new URL(window.location);
            url.searchParams.delete('action');
            url.searchParams.delete('id');
            window.history.replaceState({}, '', url);
        });

        // Reset form when "Add" button is clicked
        const addButton = document.querySelector('[data-bs-target="#addCustomerModal"]');
        if (addButton) {
            addButton.addEventListener('click', function() {
                // Clear URL first
                const url = new URL(window.location);
                url.searchParams.delete('action');
                url.searchParams.delete('id');
                window.history.replaceState({}, '', url);
                // Then reset form
                setTimeout(resetForm, 100);
            });
        }
    }
});
<?php else: ?>

function resetForm() {
    alert('Bạn không có quyền thêm khách hàng.');
}

function editCustomer() {
    alert('Bạn không có quyền chỉnh sửa khách hàng.');
}
<?php endif; ?>

<?php if ($canDeleteCustomers): ?>

// Biến lưu ID khách hàng cần xóa
let customerIdToDelete = null;

function deleteCustomer(id) {
    customerIdToDelete = id;
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteCustomerModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const btnConfirmDelete = document.getElementById('btnConfirmDeleteCustomer');
    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', function() {
            if (customerIdToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="customer_id" value="' + customerIdToDelete + '">' +
                    '<input type="hidden" name="delete_customer" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});
<?php else: ?>

function deleteCustomer() {
    alert('Bạn không có quyền xóa khách hàng.');
}
<?php endif; ?>

<?php if ($editCustomer && $canEditCustomers): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Điền dữ liệu vào form
    document.getElementById('customer_id').value = '<?php echo $editCustomer['customer_id']; ?>';
    document.getElementById('full_name').value = '<?php echo h($editCustomer['full_name']); ?>';
    document.getElementById('email').value = '<?php echo h($editCustomer['email']); ?>';
    document.getElementById('phone').value = '<?php echo h($editCustomer['phone']); ?>';
    document.getElementById('username').value = '<?php echo h($editCustomer['username']); ?>';
    document.getElementById('username').readOnly = true;
    document.getElementById('date_of_birth').value = '<?php echo $editCustomer['date_of_birth']; ?>';
    document.getElementById('gender').value = '<?php echo $editCustomer['gender']; ?>';
    document.getElementById('address').value = '<?php echo h($editCustomer['address']); ?>';
    document.getElementById('id_card').value = '<?php echo h($editCustomer['id_card']); ?>';
    document.getElementById('customer_type').value = '<?php echo $editCustomer['customer_type']; ?>';
    document.getElementById('account_status').value = '<?php echo $editCustomer['account_status']; ?>';

    // Cập nhật UI
    document.getElementById('password').required = false;
    document.getElementById('passwordRequired').style.display = 'none';
    document.getElementById('modalTitle').textContent = 'Sửa Khách Hàng';
    document.getElementById('submitBtn').textContent = 'Cập Nhật Khách Hàng';
    document.getElementById('submitBtn').name = 'update_customer';

    // Hiện modal
    const modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
    modal.show();
});
<?php endif; ?>
</script>