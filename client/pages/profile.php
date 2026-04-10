<?php
session_start();
require_once '../includes/connect.php';
$isLoggedIn = isset($_SESSION['user_id']);
$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'] )) {
    header("Location: /My-Web-Hotel/client/pages/login.php");
    exit();
}

$stmt = $mysqli->prepare("SELECT * FROM customer WHERE customer_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    header("Location: /My-Web-Hotel/client/pages/login.php");
    exit();
}

$stmt->close();
$avatarPath = !empty($user['avatar']) ? $user['avatar'] : '/My-Web-Hotel/client/assets/images/275f99923b080b18e7b474ed6155a17f.jpg';

// Query lấy lịch sử đặt phòng của khách hàng
$sql_room_booking = "
SELECT 
    b.booking_id,
    b.booking_date,
    b.check_in_date,
    b.check_out_date,
    b.quantity,
    b.special_request,
    b.booking_method,
    b.deposit,
    b.status as booking_status,
    r.room_number,
    r.floor,
    rt.room_type_name,
    rt.base_price,
    rt.capacity,
    i.invoice_id,
    i.room_charge,
    i.service_charge,
    i.vat,
    i.other_fees,
    i.total_amount,
    i.payment_method,
    i.status as payment_status,
    i.payment_time,
    i.note,
    DATEDIFF(b.check_out_date, b.check_in_date) as nights
FROM booking b
LEFT JOIN room r ON b.room_id = r.room_id
LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
LEFT JOIN invoice i ON b.booking_id = i.booking_id
WHERE b.customer_id = ? AND b.deleted IS NULL
ORDER BY b.booking_date DESC
";

$stmt_room = $mysqli->prepare($sql_room_booking);
$stmt_room->bind_param("i", $_SESSION['user_id']);
$stmt_room->execute();
$result = $stmt_room->get_result();

// Chuyển kết quả thành mảng và lấy thêm dịch vụ cho mỗi booking
$room_bookings = [];
while ($row = $result->fetch_assoc()) {
    // Lấy danh sách dịch vụ kèm theo booking này
    $sql_services = "
        SELECT 
            bs.booking_service_id,
            bs.unit_price,
            s.service_id,
            s.service_name,
            s.service_type,
            s.unit,
            s.description
        FROM booking_service bs
        JOIN service s ON bs.service_id = s.service_id
        WHERE bs.booking_id = ? AND s.deleted IS NULL
    ";
    
    $stmt_services = $mysqli->prepare($sql_services);
    $stmt_services->bind_param("i", $row['booking_id']);
    $stmt_services->execute();
    $services_result = $stmt_services->get_result();
    
    $services = [];
    while ($service = $services_result->fetch_assoc()) {
        $services[] = $service;
    }
    $stmt_services->close();
    
    // Thêm danh sách dịch vụ vào booking
    $row['services'] = $services;
    $room_bookings[] = $row;
}
// ========== QUERY LỊCH SỬ ĐẶT DỊCH VỤ ==========
$sql_service_booking = "
SELECT 
    bs.booking_service_id,
    bs.usage_date,
    bs.usage_time,
    bs.quantity,
    bs.unit_price,
    bs.status as booking_status,
    bs.created_at,
    s.service_id,
    s.service_name,
    s.service_type,
    s.description,
    s.unit,
    i.invoice_id,
    i.total_amount,
    i.vat,
    i.discount,
    i.payment_method,
    i.note,
    i.created_at as invoice_date
FROM booking_service bs
JOIN service s ON bs.service_id = s.service_id
JOIN invoice_service invs ON bs.booking_service_id = invs.booking_service_id
JOIN invoice i ON invs.invoice_id = i.invoice_id
WHERE bs.customer_id = ? 
AND bs.booking_id IS NULL
AND bs.deleted IS NULL
ORDER BY bs.created_at DESC
";

$stmt_service = $mysqli->prepare($sql_service_booking);
$stmt_service->bind_param("i", $_SESSION['user_id']);
$stmt_service->execute();
$service_result = $stmt_service->get_result();

$service_bookings = [];
while ($row = $service_result->fetch_assoc()) {
    $service_bookings[] = $row;
}
$stmt_service->close();
// Hàm format trạng thái booking
function getBookingStatusBadge( $status) {
    $status = strtolower($status);
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark">Chờ xác nhận</span>',
        'confirmed' => '<span class="badge bg-success">Đã xác nhận</span>',
        'cancelled' => '<span class="badge bg-danger">Đã hủy</span>',
        'completed' => '<span class="badge bg-primary">Hoàn thành</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="/My-Web-Hotel/client/assets/images/favicon.png" type="image/x-icon" />
    <title>Profile - OceanPearl Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/loading.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include '../includes/loading.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="profile-section">
                    <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Profile Avatar" class="profile-img"
                        id="sidebar-avatar" />
                    <div class="profile-name" id="username-display"><?= htmlspecialchars($user['full_name']) ?></div>
                </div>

                <nav class="nav flex-column nav-pills px-3 mt-3" role="tablist">
                    <a class="nav-link active" data-bs-toggle="pill" href="#personal" role="tab">
                        <i class="fa fa-id-card"></i> Thông tin cá nhân
                    </a>
                    <a class="nav-link" data-bs-toggle="pill" href="#changePassword" role="tab">
                        <i class="fa fa-key"></i> Đổi mật khẩu
                    </a>
                    <!-- Dropdown Menu for History -->
                    <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#historyDropdown" role="button"
                        aria-expanded="false">
                        <span><i class="fa fa-history"></i> Lịch sử đặt</span>
                    </a>
                    <div class="collapse dropdown-menu-custom" id="historyDropdown">
                        <a class="dropdown-item-custom" data-bs-toggle="pill" href="#historyRoom" role="tab">
                            <i class="fas fa-bed"></i> Phòng
                        </a>
                        <a class="dropdown-item-custom" data-bs-toggle="pill" href="#historyService" role="tab">
                            <i class="fas fa-concierge-bell"></i> Dịch vụ
                        </a>
                    </div>
                </nav>

                <div class="logout-section">
                    <button id="logout-btn" class="btn logout-btn">
                        <i class="fa fa-sign-out-alt"></i> Đăng xuất
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="content-area">
                    <div class="tab-content">
                        <!-- Personal Info Tab -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="content-card">
                                <h2 class="content-title">Thông tin cá nhân</h2>

                                <?php if (isset($_GET['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fa fa-check-circle me-2"></i> Cập nhật thông tin thành công!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php elseif (isset($_GET['error'])): ?>
                                <?php 
                                    $error_messages = [
                                        '1' => 'Có lỗi xảy ra khi cập nhật. Vui lòng thử lại.',
                                        'missing' => 'Vui lòng nhập đầy đủ thông tin bắt buộc!',
                                        'avatar' => 'Lỗi khi tải ảnh lên. Vui lòng thử lại!',
                                        'invalid_file_type' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)!',
                                        'file_too_large' => 'Kích thước file quá lớn (tối đa 5MB)!'
                                    ];
                                    $error = $_GET['error'];
                                    $message = $error_messages[$error] ?? 'Có lỗi xảy ra!';
                                    ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fa fa-times-circle me-2"></i> <?= $message ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>

                                <form method="post" action="../controller/update-profile.php"
                                    enctype="multipart/form-data" id="profile-form">
                                    <!-- Avatar Upload Section -->
                                    <div class="avatar-upload-section">
                                        <div class="avatar-preview-container"
                                            onclick="document.getElementById('avatar-input').click()">
                                            <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Avatar Preview"
                                                class="avatar-preview" id="avatar-preview">
                                            <div class="camera-overlay">
                                                <i class="fas fa-camera"></i>
                                            </div>
                                            <input type="file" id="avatar-input" name="avatar"
                                                accept="image/jpeg,image/png,image/jpg,image/gif"
                                                style="display: none;">
                                        </div>
                                        <div class="avatar-upload-controls">
                                            <h5><i class="fas fa-user-circle me-2"></i>Ảnh đại diện</h5>
                                            <p class="text-muted mb-2">Nhấn vào ảnh để thay đổi</p>
                                            <p class="text-muted small mb-3">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Định dạng: JPG, PNG, GIF | Tối đa: 5MB
                                            </p>
                                            <span class="file-name-display" id="file-name">
                                                <i class="fas fa-file-image me-1"></i>Chưa chọn file
                                            </span>
                                            <button type="button" class="btn-remove-avatar" id="remove-avatar"
                                                style="display:none;">
                                                <i class="fas fa-trash-alt"></i> Xóa ảnh đã chọn
                                            </button>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tên người dùng <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="full_name"
                                                placeholder="Nhập họ và tên"
                                                value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Ngày sinh</label>
                                            <input type="date" class="form-control" name="birth_day"
                                                value="<?= !empty($user['date_of_birth']) ? date('Y-m-d', strtotime($user['date_of_birth'])) : '' ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Địa chỉ</label>
                                            <input type="text" class="form-control" name="address"
                                                placeholder="Nhập địa chỉ đầy đủ"
                                                value="<?= htmlspecialchars($user['address']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Giới tính</label>
                                            <select class="form-select" name="gender">
                                                <option value="">Chọn giới tính</option>
                                                <option value="Male" <?= $user['gender'] == 'Male' ? 'selected' : '' ?>>
                                                    Nam</option>
                                                <option value="Female"
                                                    <?= $user['gender'] == 'Female' ? 'selected' : '' ?>>Nữ</option>
                                                <option value="Other"
                                                    <?= $user['gender'] == 'Other' ? 'selected' : '' ?>>Khác</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control"
                                                placeholder="example@email.com"
                                                value="<?= htmlspecialchars($user['email']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Số điện thoại</label>
                                            <input type="tel" class="form-control" name="phone"
                                                placeholder="Nhập số điện thoại"
                                                value="<?= htmlspecialchars($user['phone']) ?>">
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-save">
                                            <i class="fa fa-save me-2"></i>Cập nhật
                                        </button>
                                        <button type="reset" class="btn btn-cancel">
                                            <i class="fa fa-times me-2"></i>Hủy
                                        </button>
                                    </div>
                                </form>

                                <button class="btn btn-home" onclick="goHome()">
                                    <i class="fa fa-arrow-left me-2"></i>Quay về trang chủ
                                </button>
                            </div>
                        </div>

                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="changePassword" role="tabpanel">
                            <div class="content-card">
                                <h2 class="content-title">Đổi mật khẩu</h2>

                                <?php if (isset($_GET['pw_success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fa fa-check-circle me-2"></i> Mật khẩu đã được thay đổi thành công!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php elseif (isset($_GET['pw_error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fa fa-times-circle me-2"></i> <?= htmlspecialchars($_GET['pw_error']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>

                                <form class="change-pass" method="post" action="../controller/update-password.php">
                                    <div class="row mb-4">
                                        <div class="col-md-6 position-relative">
                                            <label class="form-label">Mật khẩu hiện tại <span
                                                    class="text-danger">*</span></label>
                                            <input type="password" class="form-control password-field"
                                                name="current_password" placeholder="Nhập mật khẩu cũ" required>
                                            <i class="fa fa-eye toggle-password"></i>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-6 position-relative">
                                            <label class="form-label">Mật khẩu mới <span
                                                    class="text-danger">*</span></label>
                                            <input type="password" class="form-control password-field"
                                                name="new_password" placeholder="Nhập mật khẩu mới" required>
                                            <i class="fa fa-eye toggle-password"></i>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-6 position-relative">
                                            <label class="form-label">Xác nhận mật khẩu mới <span
                                                    class="text-danger">*</span></label>
                                            <input type="password" class="form-control password-field"
                                                name="confirm_password" placeholder="Nhập lại mật khẩu mới" required>
                                            <i class="fa fa-eye toggle-password"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-save">
                                            <i class="fa fa-key me-2"></i>Lưu thay đổi
                                        </button>
                                        <button type="reset" class="btn btn-cancel">
                                            <i class="fa fa-times me-2"></i>Hủy
                                        </button>
                                    </div>
                                </form>

                                <button class="btn btn-home" onclick="goHome()">
                                    <i class="fa fa-arrow-left me-2"></i>Quay về trang chủ
                                </button>
                            </div>
                        </div>

                        <!-- History Room Tab -->
                        <div class="tab-pane fade" id="historyRoom" role="tabpanel">
                            <div class="content-card">
                                <h2 class="content-title">Lịch sử đặt Phòng</h2>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle text-center" id="tableRoomInvoice">
                                        <thead>
                                            <tr>
                                                <th>Mã HĐ</th>
                                                <th>Phòng</th>
                                                <th>Check-in</th>
                                                <th>Check-out</th>
                                                <th>Tổng Tiền</th>
                                                <th>Tình Trạng</th>
                                                <th>Hành Động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($room_bookings)): ?>
                                            <?php foreach ($room_bookings as $booking): ?>
                                            <tr>
                                                <td><strong>#<?= htmlspecialchars($booking['booking_id']) ?></strong>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($booking['room_number']) ?>
                                                    <br>
                                                    <small
                                                        class="text-muted"><?= htmlspecialchars($booking['room_type_name']) ?></small>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($booking['check_in_date'])) ?></td>
                                                <td><?= date('d/m/Y', strtotime($booking['check_out_date'])) ?></td>
                                                <td><?= number_format($booking['total_amount'], 0, ',', '.') ?> VNĐ</td>
                                                <td>
                                                    <?= getBookingStatusBadge($booking['booking_status']) ?>
                                                </td>
                                                <td>
                                                    <!-- FIX: Sử dụng base64 encode để tránh lỗi parse JSON -->
                                                    <button class="btn btn-sm btn-outline-info view-detail-btn"
                                                        data-booking-base64="<?= base64_encode(json_encode($booking)) ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                                        <p class="mb-0">Bạn chưa có đơn đặt phòng nào</p>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <nav aria-label="Booking history navigation" class="mt-4" id="paginationContainer"
                                        style="display: none;">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item">
                                                <button class="page-link" id="prevPage" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </button>
                                            </li>
                                            <li class="page-item">
                                                <span class="page-link" id="pageInfo">Trang 1 / 1</span>
                                            </li>
                                            <li class="page-item">
                                                <button class="page-link" id="nextPage" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </button>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>

                                <button class="btn btn-home" onclick="goHome()">
                                    <i class="fa fa-arrow-left me-2"></i>Quay về trang chủ
                                </button>
                            </div>
                        </div>

                        <!-- History Service Tab -->
                        <div class="tab-pane fade" id="historyService" role="tabpanel">
                            <div class="content-card">
                                <h2 class="content-title">Lịch sử đặt Dịch vụ</h2>
                                <div class="table-container">
                                    <table class="table table-hover text-center align-middle" id="tableServiceInvoice">
                                        <thead>
                                            <tr>
                                                <th>Mã HĐ</th>
                                                <th>Dịch vụ</th>
                                                <th>Ngày sử dụng</th>
                                                <th>Thời gian</th>
                                                <th>Tổng Tiền</th>
                                                <th>Tình Trạng</th>
                                                <th>Hành Động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($service_bookings)): ?>
                                            <?php foreach ($service_bookings as $service): ?>
                                            <tr>
                                                <td><strong>#<?= htmlspecialchars($service['invoice_id']) ?></strong>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($service['service_name']) ?>
                                                    <br><small
                                                        class="text-muted"><?= htmlspecialchars($service['service_type']) ?></small>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($service['usage_date'])) ?></td>
                                                <td><?= htmlspecialchars($service['usage_time']) ?></td>
                                                <td><?= number_format($service['total_amount'], 0, ',', '.') ?> VNĐ</td>
                                                <td><?= getBookingStatusBadge($service['booking_status']) ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info view-service-detail-btn"
                                                        data-service-base64="<?= base64_encode(json_encode($service)) ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                                        <p class="mb-0">Bạn chưa có đơn đặt dịch vụ nào</p>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <nav aria-label="Service booking history navigation" class="mt-4"
                                        id="servicePaginationContainer" style="display: none;">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item">
                                                <button class="page-link" id="servicePrevPage" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </button>
                                            </li>
                                            <li class="page-item">
                                                <span class="page-link" id="servicePageInfo">Trang 1 / 1</span>
                                            </li>
                                            <li class="page-item">
                                                <button class="page-link" id="serviceNextPage" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </button>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>

                                <button class="btn btn-home" onclick="goHome()">
                                    <i class="fa fa-arrow-left me-2"></i>Quay về trang chủ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content detail">
                <div class="modal-header ">
                    <h5 class="modal-title" id="logoutModalLabel">Xác nhận đăng xuất</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Bạn có chắc chắn muốn đăng xuất không?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmLogout">
                        <i class="fa fa-sign-out-alt me-2"></i>Đăng xuất
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Chi tiết đặt phòng -->
    <div class="modal fade modal-detail" id="roomDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-invoice me-2"></i>Chi tiết đặt phòng #<span id="modal-booking-id"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Thông tin phòng và thời gian -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-bed me-2"></i>Thông tin phòng</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">Số phòng:</td>
                                <td class="fw-bold text-end" id="modal-room-number"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Loại phòng:</td>
                                <td class="text-end" id="modal-room-type"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tầng:</td>
                                <td class="text-end" id="modal-floor"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Số lượng khách:</td>
                                <td class="text-end" id="modal-quantity"></td>
                            </tr>
                        </table>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-calendar me-2"></i>Thời gian</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">Ngày đặt:</td>
                                <td class="text-end" id="modal-booking-date"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Check-in:</td>
                                <td class="fw-bold text-end" id="modal-checkin"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Check-out:</td>
                                <td class="fw-bold text-end" id="modal-checkout"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Số đêm:</td>
                                <td class="text-end" id="modal-nights"></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Thanh toán và trạng thái -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-money-bill-wave me-2"></i>Chi tiết thanh toán</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Tiền phòng:</td>
                                <td class="text-end fw-bold" id="modal-room-charge"></td>
                            </tr>
                            <tr id="modal-service-charge-row">
                                <td>Tiền dịch vụ:</td>
                                <td class="text-end fw-bold" id="modal-service-charge"></td>
                            </tr>
                            <tr id="modal-discount-row" style="display:none;">
                                <td>Giảm giá:</td>
                                <td class="text-end text-danger fw-bold" id="modal-discount"></td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Tạm tính:</strong></td>
                                <td class="text-end fw-bold" id="modal-subtotal"></td>
                            </tr>
                            <tr>
                                <td>VAT (10%):</td>
                                <td class="text-end" id="modal-vat"></td>
                            </tr>
                            <tr id="modal-other-fees-row" style="display:none;">
                                <td>Phí khác:</td>
                                <td class="text-end" id="modal-other-fees"></td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Tổng cộng:</strong></td>
                                <td class="text-end fs-5 fw-bold" id="modal-total"></td>
                            </tr>
                            <tr class="bg-light">
                                <td><strong>Đặt cọc (30%):</strong></td>
                                <td class="text-end text-success fs-6 fw-bold" id="modal-deposit"></td>
                            </tr>
                        </table>
                    </div>
                    <!-- Danh sách dịch vụ kèm theo -->
                    <div class="mb-4" id="modal-services-section" style="display:none;">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-concierge-bell me-2"></i>Dịch vụ kèm theo
                            <span class="badge bg-primary" id="modal-service-count">0</span>
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>STT</th>
                                        <th>Tên dịch vụ</th>
                                        <th>Đơn vị</th>
                                        <th class="text-end">Giá</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-services-list">
                                    <!-- Dịch vụ sẽ được thêm vào đây bằng JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Yêu cầu đặc biệt -->
                    <div class="alert alert-info mb-0" id="modal-special-request" style="display:none;">
                        <strong><i class="fas fa-comment-dots me-2"></i>Yêu cầu đặc biệt:</strong>
                        <p class="mb-0 mt-2" id="modal-special-request-text"></p>
                    </div>

                    <!-- Ghi chú từ invoice -->
                    <div class="alert alert-warning mb-0 mt-3" id="modal-invoice-note" style="display:none;">
                        <strong><i class="fas fa-info-circle me-2"></i>Ghi chú:</strong>
                        <p class="mb-0 mt-2" id="modal-invoice-note-text"></p>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <p class="mb-2"><strong>Phương thức đặt:</strong> <span id="modal-booking-method"></span>
                            </p>
                            <p class="mb-2"><strong>Phương thức thanh toán:</strong> <span
                                    id="modal-payment-method"></span>
                            </p>
                            <p class="mb-2" id="modal-payment-time-row" style="display:none;">
                                <strong>Thời gian thanh toán:</strong> <span id="modal-payment-time"></span>
                            </p>
                        </div>
                        <div class="col-6">
                            <p class="mb-2"><strong>Trạng thái đặt phòng:</strong> <span
                                    id="modal-booking-status"></span>
                            </p>
                            <p class="mb-2"><strong>Trạng thái thanh toán:</strong> <span
                                    id="modal-payment-status"></span>
                            </p>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Chi tiết đặt dịch vụ -->
    <div class="modal fade modal-detail" id="serviceDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-concierge-bell me-2"></i>Chi tiết đặt dịch vụ #<span
                            id="modal-service-invoice-id"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Thông tin dịch vụ -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-concierge-bell me-2"></i>Thông tin dịch vụ</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">Tên dịch vụ:</td>
                                <td class="fw-bold text-end" id="modal-service-name"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Loại dịch vụ:</td>
                                <td class="text-end" id="modal-service-type"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mô tả:</td>
                                <td class="text-end" id="modal-service-description"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Số lượng:</td>
                                <td class="text-end" id="modal-service-quantity"></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Thời gian -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-calendar me-2"></i>Thời gian sử dụng</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">Ngày đặt:</td>
                                <td class="text-end" id="modal-service-booking-date"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Ngày sử dụng:</td>
                                <td class="fw-bold text-end" id="modal-service-usage-date"></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Giờ sử dụng:</td>
                                <td class="fw-bold text-end" id="modal-service-usage-time"></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Thanh toán -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-money-bill-wave me-2"></i>Chi tiết thanh toán</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Giá dịch vụ:</td>
                                <td class="text-end fw-bold" id="modal-service-price"></td>
                            </tr>
                            <tr id="modal-service-discount-row" style="display:none;">
                                <td>Giảm giá:</td>
                                <td class="text-end text-danger fw-bold" id="modal-service-discount"></td>
                            </tr>
                            <tr>
                                <td>VAT (10%):</td>
                                <td class="text-end" id="modal-service-vat"></td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Tổng thanh toán:</strong></td>
                                <td class="text-end fs-5 fw-bold text-success" id="modal-service-total"></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Ghi chú -->
                    <div class="alert alert-info mb-0" id="modal-service-note" style="display:none;">
                        <strong><i class="fas fa-comment-dots me-2"></i>Ghi chú:</strong>
                        <p class="mb-0 mt-2" id="modal-service-note-text"></p>
                    </div>

                    <div class="row mt-3">
                        <div class="col-6">
                            <p class="mb-2"><strong>Phương thức đặt:</strong> <span
                                    id="modal-booking-service-method"></span>
                            </p>
                            <p class="mb-2"><strong>Phương thức thanh toán:</strong> <span
                                    id="modal-payment-service-method"></span>
                            </p>
                            <p class="mb-2" id="modal-payment-service-time-row" style="display:none;">
                                <strong>Thời gian thanh toán:</strong> <span id="modal-payment-service-time"></span>
                            </p>
                        </div>
                        <div class="col-6">
                            <p class="mb-2"><strong>Trạng thái đặt:</strong> <span
                                    id="modal-booking-service-status"></span>
                            </p>
                            <p class="mb-2"><strong>Trạng thái thanh toán:</strong> <span
                                    id="modal-payment-service-status"></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/My-Web-Hotel/client/assets/js/loading.js?v=<?php echo time(); ?>"></script>
    <script src="/My-Web-Hotel/client/assets/js/profile.js?v=<?php echo time(); ?>"></script>
    <script>
    window.IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
    window.CURRENT_USER_NAME = <?= $username ? json_encode($username) : 'null' ?>;
    window.DEFAULT_AVATAR = "<?= htmlspecialchars($avatarPath) ?>";
    </script>
</body>

</html