<?php
// Phân quyền module Nhiệm Vụ
$canViewTask      = function_exists('checkPermission') ? checkPermission('task.view')      : true;
$canViewDetailTask = function_exists('checkPermission') ? checkPermission('task.view_detail') : true;
$canCreateTask    = function_exists('checkPermission') ? checkPermission('task.create')     : true;
$canEditTask      = function_exists('checkPermission') ? checkPermission('task.edit')       : true;
$canDeleteTask    = function_exists('checkPermission') ? checkPermission('task.delete')     : true;

if (!$canViewTask) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem trang nhiệm vụ.</div></div>';
    return;
}

// Lọc & phân trang
$nhan_vien_id = isset($_GET['nhan_vien_id']) ? intval($_GET['nhan_vien_id']) : 0;
$trang_thai_filter = isset($_GET['trang_thai']) ? trim($_GET['trang_thai']) : '';
$muc_do_filter = isset($_GET['muc_do']) ? trim($_GET['muc_do']) : '';
$pageNum = isset($_GET['pageNum']) ? max(1, intval($_GET['pageNum'])) : 1;
$perPage = 10;
$offset = ($pageNum - 1) * $perPage;

// Lấy danh sách nhân viên để filter & giao nhiệm vụ
$nvResult = $mysqli->query("SELECT id_nhan_vien, ma_nhan_vien, ho_ten, chuc_vu 
                            FROM nhan_vien 
                            WHERE trang_thai = 'Đang làm việc'
                            ORDER BY ho_ten");
$nhanVienList = $nvResult ? $nvResult->fetch_all(MYSQLI_ASSOC) : [];

// Xây where cho nhiệm vụ
$where = "WHERE 1=1";
$params = [];
$types = '';

if ($nhan_vien_id > 0) {
    $where .= " AND nv.id_nhan_vien_duoc_gan = ?";
    $params[] = $nhan_vien_id;
    $types .= 'i';
}

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

// Đếm tổng nhiệm vụ
$countSql = "SELECT COUNT(*) AS total FROM nhiem_vu nv $where";
$countStmt = $mysqli->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalTasks = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$countStmt->close();

// Lấy danh sách nhiệm vụ
$sql = "SELECT nv.*, 
               nv1.ho_ten AS ten_nhan_vien,
               nv1.ma_nhan_vien,
               nv2.ho_ten AS nguoi_gan_ten
        FROM nhiem_vu nv
        INNER JOIN nhan_vien nv1 ON nv.id_nhan_vien_duoc_gan = nv1.id_nhan_vien
        LEFT JOIN nhan_vien nv2 ON nv.id_nhan_vien_gan_phien = nv2.id_nhan_vien
        $where
        ORDER BY nv.created_at DESC
        LIMIT ? OFFSET ?";

$params2 = $params;
$types2 = $types . 'ii';
$params2[] = $perPage;
$params2[] = $offset;

$stmt = $mysqli->prepare($sql);
if (!empty($params2)) {
    $stmt->bind_param($types2, ...$params2);
}
$stmt->execute();
$tasksResult = $stmt->get_result();
$tasks = $tasksResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Build base URL cho phân trang
$baseUrl = "index.php?page=task-manager";
if ($nhan_vien_id) $baseUrl .= "&nhan_vien_id=" . urlencode($nhan_vien_id);
if ($trang_thai_filter) $baseUrl .= "&trang_thai=" . urlencode($trang_thai_filter);
if ($muc_do_filter) $baseUrl .= "&muc_do=" . urlencode($muc_do_filter);
?>

<div class="content-card">
    <div class="card-header-custom">
        <h3 class="card-title">Quản Lý Nhiệm Vụ</h3>
        <?php if ($canCreateTask): ?>
        <button class="btn btn-primary" onclick="openGlobalTaskModal()">
            <i class="fas fa-plus"></i> Giao Nhiệm Vụ Mới
        </button>
        <?php endif; ?>
    </div>

    <!-- Bộ lọc -->
    <div class="filter-section">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="task-manager">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nhân Viên</label>
                    <select class="form-select" name="nhan_vien_id">
                        <option value="0">Tất cả nhân viên</option>
                        <?php foreach ($nhanVienList as $nv): ?>
                        <option value="<?php echo (int)$nv['id_nhan_vien']; ?>"
                            <?php echo $nhan_vien_id == $nv['id_nhan_vien'] ? 'selected' : ''; ?>>
                            <?php echo h($nv['ho_ten']); ?> (<?php echo h($nv['ma_nhan_vien']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Trạng Thái</label>
                    <select class="form-select" name="trang_thai">
                        <option value="">Tất cả</option>
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
                <div class="col-md-3">
                    <label class="form-label">Mức độ ưu tiên</label>
                    <select class="form-select" name="muc_do">
                        <option value="">Tất cả</option>
                        <option value="Thấp" <?php echo $muc_do_filter == 'Thấp' ? 'selected' : ''; ?>>Thấp</option>
                        <option value="Trung bình" <?php echo $muc_do_filter == 'Trung bình' ? 'selected' : ''; ?>>Trung
                            bình</option>
                        <option value="Cao" <?php echo $muc_do_filter == 'Cao' ? 'selected' : ''; ?>>Cao</option>
                        <option value="Khẩn cấp" <?php echo $muc_do_filter == 'Khẩn cấp' ? 'selected' : ''; ?>>Khẩn cấp
                        </option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Danh sách nhiệm vụ -->
    <div class="table-container">
        <?php if (empty($tasks)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> Không có nhiệm vụ nào.
        </div>
        <?php else: ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nhiệm Vụ</th>
                    <th>Nhân Viên</th>
                    <th>Mức Độ</th>
                    <th>Tiến Độ</th>
                    <th>Thời Gian</th>
                    <th>Trạng Thái</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <?php
                            $priorityClass = 'bg-secondary';
                            switch ($task['muc_do_uu_tien']) {
                                case 'Khẩn cấp': $priorityClass = 'bg-danger'; break;
                                case 'Cao': $priorityClass = 'bg-warning'; break;
                                case 'Trung bình': $priorityClass = 'bg-info'; break;
                                case 'Thấp': $priorityClass = 'bg-success'; break;
                            }

                            $statusClass = 'bg-secondary';
                            switch ($task['trang_thai']) {
                                case 'Hoàn thành': $statusClass = 'bg-success'; break;
                                case 'Đang thực hiện': $statusClass = 'bg-primary'; break;
                                case 'Hủy': $statusClass = 'bg-danger'; break;
                            }
                        ?>
                <tr>
                    <td>
                        <strong><?php echo h($task['ten_nhiem_vu']); ?></strong><br>
                        <small class="text-muted">
                            Người giao: <?php echo h($task['nguoi_gan_ten'] ?: 'N/A'); ?>
                        </small>
                    </td>
                    <td>
                        <?php echo h($task['ten_nhan_vien']); ?><br>
                        <small class="text-muted"><?php echo h($task['ma_nhan_vien']); ?></small>
                    </td>
                    <td><span
                            class="badge <?php echo $priorityClass; ?>"><?php echo h($task['muc_do_uu_tien']); ?></span>
                    </td>
                    <td>
                        <small><?php echo (int)$task['tien_do_hoan_thanh']; ?>%</small>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar"
                                style="width: <?php echo (int)$task['tien_do_hoan_thanh']; ?>%"></div>
                        </div>
                    </td>
                    <td>
                        <small class="text-muted">
                            Bắt đầu: <?php echo formatDate($task['ngay_bat_dau']); ?><br>
                            Hạn: <?php echo formatDate($task['han_hoan_thanh']); ?>
                        </small>
                    </td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo h($task['trang_thai']); ?></span>
                    </td>
                    <td>
                        <?php if ($canViewDetailTask): ?>
                        <button class="btn btn-sm btn-outline-info"
                            onclick="viewTaskDetail(<?php echo $task['id_nhiem_vu']; ?>)" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($canEditTask): ?>
                        <button class="btn btn-sm btn-outline-warning"
                            onclick="editTask(<?php echo $task['id_nhiem_vu']; ?>)" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($canDeleteTask): ?>
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="deleteTask(<?php echo $task['id_nhiem_vu']; ?>)" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php echo getPagination($totalTasks, $perPage, $pageNum, $baseUrl); ?>
</div>

<!-- Modal giao nhiệm vụ toàn hệ thống -->
<div class="modal fade" id="globalTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tasks"></i> Giao Nhiệm Vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="globalTaskForm">
                    <div class="mb-3">
                        <label class="form-label">Nhân viên được giao *</label>
                        <select class="form-select" name="id_nhan_vien_duoc_gan" required>
                            <option value="">-- Chọn nhân viên --</option>
                            <?php foreach ($nhanVienList as $nv): ?>
                            <option value="<?php echo (int)$nv['id_nhan_vien']; ?>">
                                <?php echo h($nv['ho_ten']); ?> (<?php echo h($nv['ma_nhan_vien']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tên Nhiệm Vụ *</label>
                        <input type="text" class="form-control" name="ten_nhiem_vu" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô Tả Chi Tiết</label>
                        <textarea class="form-control" name="mo_ta_chi_tiet" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Mức Độ Ưu Tiên *</label>
                            <select class="form-select" name="muc_do_uu_tien" required>
                                <option value="Thấp">Thấp</option>
                                <option value="Trung bình" selected>Trung bình</option>
                                <option value="Cao">Cao</option>
                                <option value="Khẩn cấp">Khẩn cấp</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ngày Bắt Đầu *</label>
                            <input type="date" class="form-control" name="ngay_bat_dau" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hạn Hoàn Thành *</label>
                            <input type="date" class="form-control" name="han_hoan_thanh" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi Chú</label>
                        <textarea class="form-control" name="ghi_chu" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="submitGlobalTask()">Giao Nhiệm Vụ</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Xem chi tiết nhiệm vụ -->
<div class="modal fade" id="viewTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Chi Tiết Nhiệm Vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewTaskContent">
                <!-- Nội dung sẽ được load bằng AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Sửa nhiệm vụ -->
<?php if ($canEditTask): ?>
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Sửa Nhiệm Vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editTaskForm">
                    <input type="hidden" name="id_nhiem_vu" value="">
                    <div class="mb-3">
                        <label class="form-label">Nhân viên được giao *</label>
                        <select class="form-select" name="id_nhan_vien_duoc_gan" required>
                            <option value="">-- Chọn nhân viên --</option>
                            <?php foreach ($nhanVienList as $nv): ?>
                            <option value="<?php echo (int)$nv['id_nhan_vien']; ?>">
                                <?php echo h($nv['ho_ten']); ?> (<?php echo h($nv['ma_nhan_vien']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tên Nhiệm Vụ *</label>
                        <input type="text" class="form-control" name="ten_nhiem_vu" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô Tả Chi Tiết</label>
                        <textarea class="form-control" name="mo_ta_chi_tiet" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Mức Độ Ưu Tiên *</label>
                            <select class="form-select" name="muc_do_uu_tien" required>
                                <option value="Thấp">Thấp</option>
                                <option value="Trung bình">Trung bình</option>
                                <option value="Cao">Cao</option>
                                <option value="Khẩn cấp">Khẩn cấp</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ngày Bắt Đầu *</label>
                            <input type="date" class="form-control" name="ngay_bat_dau" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hạn Hoàn Thành *</label>
                            <input type="date" class="form-control" name="han_hoan_thanh" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trạng Thái *</label>
                            <select class="form-select" name="trang_thai" required>
                                <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                                <option value="Đang thực hiện">Đang thực hiện</option>
                                <option value="Hoàn thành">Hoàn thành</option>
                                <option value="Hủy">Hủy</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tiến Độ Hoàn Thành (%)</label>
                            <input type="number" class="form-control" name="tien_do_hoan_thanh" min="0" max="100"
                                value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi Chú</label>
                        <textarea class="form-control" name="ghi_chu" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="submitEditTask()">Cập Nhật</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Xác nhận xóa nhiệm vụ -->
<div class="modal fade" id="confirmDeleteTaskModal" tabindex="-1" aria-labelledby="confirmDeleteTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteTaskModalLabel">Xác nhận xóa</h5>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3 mb-0">Bạn có chắc muốn xóa nhiệm vụ này?<br>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">Hủy</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteTask" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                </button>
            </div>
        </div>
    </div>
</div>