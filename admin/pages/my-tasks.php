<?php
// Lấy thông tin nhân viên hiện tại
$id_nhan_vien = $_SESSION['id_nhan_vien'];

// Cập nhật tiến độ nhiệm vụ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_progress'])) {
    $id_nhiem_vu = intval($_POST['id_nhiem_vu']);
    $tien_do_hoan_thanh = intval($_POST['tien_do_hoan_thanh']);
    $trang_thai = $_POST['trang_thai'];

    // Kiểm tra quyền sở hữu nhiệm vụ
    $checkStmt = $mysqli->prepare("SELECT id_nhiem_vu FROM nhiem_vu WHERE id_nhiem_vu = ? AND id_nhan_vien_duoc_gan = ?");
    $checkStmt->bind_param("ii", $id_nhiem_vu, $id_nhan_vien);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $stmt = $mysqli->prepare("UPDATE nhiem_vu SET tien_do_hoan_thanh = ?, trang_thai = ? WHERE id_nhiem_vu = ?");
        $stmt->bind_param("isi", $tien_do_hoan_thanh, $trang_thai, $id_nhiem_vu);

        if ($stmt->execute()) {
            // Ghi lịch sử
            $loai_thay_doi = 'Cập nhật tiến độ';
            $noi_dung = "Cập nhật tiến độ: $tien_do_hoan_thanh%, Trạng thái: $trang_thai";
            $historyStmt = $mysqli->prepare("
                INSERT INTO lich_su_thay_doi (id_nhan_vien, id_nhiem_vu, loai_thay_doi, noi_dung_thay_doi, nguoi_thay_doi)
                VALUES (?, ?, ?, ?, ?)
            ");
            $historyStmt->bind_param("iissi", $id_nhan_vien, $id_nhiem_vu, $loai_thay_doi, $noi_dung, $id_nhan_vien);
            $historyStmt->execute();
            $historyStmt->close();

            $message = 'Cập nhật tiến độ thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } else {
        $message = 'Bạn không có quyền cập nhật nhiệm vụ này!';
        $messageType = 'danger';
    }
    $checkStmt->close();
}

// Phân trang và lọc
$trang_thai_filter = isset($_GET['trang_thai']) ? trim($_GET['trang_thai']) : '';
$muc_do_filter = isset($_GET['muc_do']) ? trim($_GET['muc_do']) : '';
$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum);
$perPage = 10;
$offset = ($pageNum - 1) * $perPage;

// Xây dựng query
$where = "WHERE nv.id_nhan_vien_duoc_gan = ?";
$params = [$id_nhan_vien];
$types = 'i';

if ($trang_thai_filter) {
    $where .= " AND nv.trang_thai = ?";
    $params[] = $trang_thai_filter;
    $types .= 's';
}

if ($muc_do_filter) {
    $where .= " AND nv.muc_do_uu_tien = ?";
    $params[] = $muc_do_filter;
    $types .= 's';
}

// Đếm tổng số nhiệm vụ
$countSql = "SELECT COUNT(*) as total 
             FROM nhiem_vu nv
             $where";
$countStmt = $mysqli->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalTasks = $totalResult->fetch_assoc()['total'];
$countStmt->close();

// Lấy danh sách nhiệm vụ
$sql = "SELECT nv.*, 
               nv1.ho_ten as nguoi_gan_ten,
               nv1.ma_nhan_vien as nguoi_gan_ma
        FROM nhiem_vu nv
        LEFT JOIN nhan_vien nv1 ON nv.id_nhan_vien_gan_phien = nv1.id_nhan_vien
        $where
        ORDER BY nv.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Build base URL for pagination
$baseUrl = "index.php?page=my-tasks";
if ($trang_thai_filter) $baseUrl .= "&trang_thai=" . urlencode($trang_thai_filter);
if ($muc_do_filter) $baseUrl .= "&muc_do=" . urlencode($muc_do_filter);

// Thống kê nhiệm vụ
$statsQuery = "SELECT 
    COUNT(*) as tong_so,
    SUM(CASE WHEN trang_thai = 'Hoàn thành' THEN 1 ELSE 0 END) as hoan_thanh,
    SUM(CASE WHEN trang_thai = 'Đang thực hiện' THEN 1 ELSE 0 END) as dang_thuc_hien,
    SUM(CASE WHEN trang_thai = 'Chưa bắt đầu' THEN 1 ELSE 0 END) as chua_bat_dau
FROM nhiem_vu WHERE id_nhan_vien_duoc_gan = ?";
$statsStmt = $mysqli->prepare($statsQuery);
$statsStmt->bind_param("i", $id_nhan_vien);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();
?>

<div class="main-content">
    <!-- Header với thống kê -->
    <div class="tasks-header">
        <h2 class=" fw-bold mb-4">
            Nhiệm Vụ Của Tôi
        </h2>
        <div class="row g-3">
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['tong_so']; ?></div>
                    <div class="stats-label">Tổng Nhiệm Vụ</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['hoan_thanh']; ?></div>
                    <div class="stats-label">Hoàn Thành</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['dang_thuc_hien']; ?></div>
                    <div class="stats-label">Đang Thực Hiện</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['chua_bat_dau']; ?></div>
                    <div class="stats-label">Chưa Bắt Đầu</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
        <strong><?php echo $messageType == 'success' ? 'Thành công!' : 'Lỗi!'; ?></strong> <?php echo h($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Bộ lọc -->
    <div class="filter-card">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="my-tasks">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Trạng Thái
                    </label>
                    <select class="form-select" name="trang_thai">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Chưa bắt đầu"
                            <?php echo $trang_thai_filter == 'Chưa bắt đầu' ? 'selected' : ''; ?>>Chưa bắt đầu</option>
                        <option value="Đang thực hiện"
                            <?php echo $trang_thai_filter == 'Đang thực hiện' ? 'selected' : ''; ?>>Đang thực hiện
                        </option>
                        <option value="Hoàn thành" <?php echo $trang_thai_filter == 'Hoàn thành' ? 'selected' : ''; ?>>
                            Hoàn thành</option>
                        <option value="Hủy" <?php echo $trang_thai_filter == 'Hủy' ? 'selected' : ''; ?>>Hủy</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Mức Độ Ưu Tiên
                    </label>
                    <select class="form-select" name="muc_do">
                        <option value="">Tất cả mức độ</option>
                        <option value="Thấp" <?php echo $muc_do_filter == 'Thấp' ? 'selected' : ''; ?>>Thấp</option>
                        <option value="Trung bình" <?php echo $muc_do_filter == 'Trung bình' ? 'selected' : ''; ?>>Trung
                            bình</option>
                        <option value="Cao" <?php echo $muc_do_filter == 'Cao' ? 'selected' : ''; ?>>Cao</option>
                        <option value="Khẩn cấp" <?php echo $muc_do_filter == 'Khẩn cấp' ? 'selected' : ''; ?>>Khẩn cấp
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold d-block" style="visibility: hidden;">Action</label>
                    <button type="submit" class="btn btn-gold w-100">
                        <i class="fas fa-search me-2"></i>Lọc Nhiệm Vụ
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Danh sách nhiệm vụ -->
    <?php if (empty($tasks)): ?>
    <div class="card text-center p-5">
        <div class="card-body">
            <i class="fas fa-clipboard-list display-1 mb-3" style="color: #deb666;"></i>
            <h3 class="fw-bold mb-2">Chưa Có Nhiệm Vụ Nào</h3>
            <p class="text-muted">Bạn chưa được giao nhiệm vụ nào hoặc không có kết quả phù hợp với bộ lọc.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($tasks as $task): ?>
        <?php
                // Xác định class cho priority badge
                $priorityClass = 'priority-low';
                switch ($task['muc_do_uu_tien']) {
                    case 'Khẩn cấp':
                        $priorityClass = 'priority-urgent';
                        break;
                    case 'Cao':
                        $priorityClass = 'priority-high';
                        break;
                    case 'Trung bình':
                        $priorityClass = 'priority-medium';
                        break;
                }

                // Xác định class cho status badge
                $statusClass = 'bg-secondary';
                $statusIcon = 'fa-pause-circle';
                switch ($task['trang_thai']) {
                    case 'Hoàn thành':
                        $statusClass = 'bg-success';
                        $statusIcon = 'fa-check-circle';
                        break;
                    case 'Đang thực hiện':
                        $statusClass = 'bg-primary';
                        $statusIcon = 'fa-spinner';
                        break;
                    case 'Hủy':
                        $statusClass = 'bg-danger';
                        $statusIcon = 'fa-times-circle';
                        break;
                }
                ?>
        <div class="col-lg-6">
            <div class="card task-card h-100">
                <!-- Task Header -->
                <div class="card-header task-card-header d-flex justify-content-between align-items-start">
                    <h5 class="mb-0 fw-bold flex-grow-1"><?php echo h($task['ten_nhiem_vu']); ?></h5>
                    <span class="badge <?php echo $priorityClass; ?> text-white ms-2">
                        <?php echo h($task['muc_do_uu_tien']); ?>
                    </span>
                </div>

                <!-- Task Body -->
                <div class="card-body">
                    <!-- Mô tả -->
                    <?php if ($task['mo_ta_chi_tiet']): ?>
                    <p class="text-muted mb-3">
                        <?php echo nl2br(h($task['mo_ta_chi_tiet'])); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Thông tin meta -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-calendar me-2" style="color: #deb666; width: 20px;"></i>
                            <small class="text-muted"><strong>Bắt đầu:</strong>
                                <?php echo formatDate($task['ngay_bat_dau']); ?></small>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-calendar-check me-2" style="color: #deb666; width: 20px;"></i>
                            <small class="text-muted"><strong>Hạn chót:</strong>
                                <?php echo formatDate($task['han_hoan_thanh']); ?></small>
                        </div>
                        <?php if ($task['nguoi_gan_ten']): ?>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user me-2" style="color: #deb666; width: 20px;"></i>
                            <small class="text-muted"><strong>Người giao:</strong>
                                <?php echo h($task['nguoi_gan_ten']); ?>
                                (<?php echo h($task['nguoi_gan_ma']); ?>)</small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tiến độ -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small">Tiến độ hoàn thành</span>
                            <span class="fw-bold"
                                style="color: #deb666;"><?php echo $task['tien_do_hoan_thanh']; ?>%</span>
                        </div>
                        <div class="progress progress-custom">
                            <div class="progress-bar progress-bar-gold" role="progressbar"
                                style="width: <?php echo $task['tien_do_hoan_thanh']; ?>%"
                                aria-valuenow="<?php echo $task['tien_do_hoan_thanh']; ?>" aria-valuemin="0"
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <!-- Trạng thái -->
                    <div class="mb-3">
                        <span class="badge <?php echo $statusClass; ?> px-3 py-2">
                            <i class="fas <?php echo $statusIcon; ?> me-1"></i>
                            <?php echo h($task['trang_thai']); ?>
                        </span>
                    </div>

                    <!-- Ghi chú -->
                    <?php if ($task['ghi_chu']): ?>
                    <div class="task-note mb-3">
                        <div class="fw-bold mb-2 text-uppercase small" style="color: #c9a555; letter-spacing: 0.5px;">
                            Ghi Chú
                        </div>
                        <div class="text-muted small"><?php echo nl2br(h($task['ghi_chu'])); ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Nút cập nhật -->
                    <button type="button" class="btn btn-gold w-100" data-bs-toggle="modal"
                        data-bs-target="#updateTaskModal<?php echo $task['id_nhiem_vu']; ?>">
                        <i class="fas fa-edit me-2"></i>Cập Nhật Tiến Độ
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Cập Nhật Tiến Độ -->
        <div class="modal fade" id="updateTaskModal<?php echo $task['id_nhiem_vu']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border: none; border-radius: 15px;">
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #deb666 0%, #f5d899 100%); border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title fw-bold text-white">
                            <i class="fas fa-tasks me-2"></i>Cập Nhật Tiến Độ Nhiệm Vụ
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body p-4">
                            <input type="hidden" name="id_nhiem_vu" value="<?php echo $task['id_nhiem_vu']; ?>">

                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-tasks me-2" style="color: #deb666;"></i>Tên Nhiệm Vụ
                                </label>
                                <input type="text" class="form-control" value="<?php echo h($task['ten_nhiem_vu']); ?>"
                                    disabled style="background: #f8f9fa;">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-percentage me-2" style="color: #deb666;"></i>Tiến Độ Hoàn Thành (%)
                                </label>
                                <input type="number" class="form-control" name="tien_do_hoan_thanh" min="0" max="100"
                                    value="<?php echo $task['tien_do_hoan_thanh']; ?>" required>
                                <small class="text-muted">Nhập giá trị từ 0 đến 100</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-flag me-2" style="color: #deb666;"></i>Trạng Thái
                                </label>
                                <select class="form-select" name="trang_thai" required>
                                    <option value="Chưa bắt đầu"
                                        <?php echo $task['trang_thai'] == 'Chưa bắt đầu' ? 'selected' : ''; ?>>Chưa bắt
                                        đầu</option>
                                    <option value="Đang thực hiện"
                                        <?php echo $task['trang_thai'] == 'Đang thực hiện' ? 'selected' : ''; ?>>Đang
                                        thực hiện</option>
                                    <option value="Hoàn thành"
                                        <?php echo $task['trang_thai'] == 'Hoàn thành' ? 'selected' : ''; ?>>Hoàn thành
                                    </option>
                                    <option value="Hủy" <?php echo $task['trang_thai'] == 'Hủy' ? 'selected' : ''; ?>>
                                        Hủy</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Hủy
                            </button>
                            <button type="submit" name="update_progress" class="btn btn-gold">
                                <i class="fas fa-save me-2"></i>Cập Nhật
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <div class="mt-4">
        <?php echo getPagination($totalTasks, $perPage, $pageNum, $baseUrl); ?>
    </div>
</div>