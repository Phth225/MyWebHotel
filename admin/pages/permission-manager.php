<?php
// Trang phân quyền riêng
if (function_exists('checkPermission') && !checkPermission('employee.set_permission')) {
    echo '<div class="main-content"><div class="alert alert-danger mt-3">Bạn không có quyền truy cập trang phân quyền.</div></div>';
    return;
}

$message = '';
$messageType = '';

// Danh sách chức vụ (lấy từ bảng nhân viên để đảm bảo thực tế)
$rolesResult = $mysqli->query("SELECT DISTINCT chuc_vu FROM nhan_vien ORDER BY chuc_vu");
$roles = $rolesResult ? $rolesResult->fetch_all(MYSQLI_ASSOC) : [];

// Xử lý lưu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chuc_vu'])) {
    $selectedRole = trim($_POST['chuc_vu']);
    $permissions = isset($_POST['permissions']) ? array_map('intval', $_POST['permissions']) : [];

    if ($selectedRole === '') {
        $message = 'Vui lòng chọn chức vụ hợp lệ.';
        $messageType = 'danger';
    } else {
        $mysqli->begin_transaction();
        try {
            $deleteStmt = $mysqli->prepare("DELETE FROM quyen_chuc_vu WHERE chuc_vu = ?");
            $deleteStmt->bind_param("s", $selectedRole);
            $deleteStmt->execute();
            $deleteStmt->close();

            if (!empty($permissions)) {
                $insertStmt = $mysqli->prepare("INSERT INTO quyen_chuc_vu (chuc_vu, id_quyen, trang_thai) VALUES (?, ?, 1)");
                foreach ($permissions as $permId) {
                    $insertStmt->bind_param("si", $selectedRole, $permId);
                    $insertStmt->execute();
                }
                $insertStmt->close();
            }

            $mysqli->commit();
            $message = 'Cập nhật phân quyền cho chức vụ "' . h($selectedRole) . '" thành công!';
            $messageType = 'success';
            // Set flag để JavaScript redirect
            $redirectAfterSave = true;
        } catch (Throwable $e) {
            $mysqli->rollback();
            error_log('Permission update error: ' . $e->getMessage());
            $message = 'Có lỗi xảy ra khi lưu phân quyền. Vui lòng thử lại.';
            $messageType = 'danger';
        }
    }
}

// Lấy selectedRole từ GET (sau khi redirect) hoặc từ roles
if (!isset($selectedRole)) {
    $selectedRole = isset($_GET['chuc_vu']) ? trim($_GET['chuc_vu']) : ($roles[0]['chuc_vu'] ?? '');
}

// Hiển thị thông báo thành công nếu có
if (isset($_GET['saved']) && $_GET['saved'] == '1') {
    $message = 'Cập nhật phân quyền cho chức vụ "' . h($selectedRole) . '" thành công!';
    $messageType = 'success';
}

// Lấy danh sách quyền
$permissionsResult = $mysqli->query("SELECT id_quyen, ten_quyen, mo_ta FROM quyen ORDER BY ten_quyen");
$permissions = $permissionsResult ? $permissionsResult->fetch_all(MYSQLI_ASSOC) : [];

// Nhóm quyền theo prefix (trước dấu '.')
$groupedPermissions = [];
foreach ($permissions as $perm) {
    $parts = explode('.', $perm['ten_quyen']);
    $group = strtoupper($parts[0] ?? 'KHAC');
    $groupedPermissions[$group][] = $perm;
}
ksort($groupedPermissions);

// Quyền hiện tại của chức vụ
$assignedIds = [];
if ($selectedRole !== '') {
    $stmt = $mysqli->prepare("SELECT id_quyen FROM quyen_chuc_vu WHERE chuc_vu = ? AND trang_thai = 1");
    $stmt->bind_param("s", $selectedRole);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignedIds[] = (int)$row['id_quyen'];
    }
    $stmt->close();
}
?>

<div class="content-card">
    <div class="card-header-custom">
        <h3 class="card-title">Phân Quyền Nhân Viên</h3>
        <div style="width: 250px;">
            <label class="form-label fw-bold">Chọn chức vụ</label>
            <select class="form-select " name="chuc_vu"
                onchange="window.location.href='index.php?page=staff-manager&panel=permission-panel&chuc_vu=' + encodeURIComponent(this.value)">
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo h($role['chuc_vu']); ?>"
                        <?php echo $selectedRole === $role['chuc_vu'] ? 'selected' : ''; ?>>
                        <?php echo h($role['chuc_vu']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <form method="POST" id="permissionForm">
        <input type="hidden" name="chuc_vu" value="<?php echo h($selectedRole); ?>">
        <?php if (empty($permissions)): ?>
            <div class="alert alert-warning">Chưa có dữ liệu quyền trong hệ thống.</div>
        <?php else: ?>
            <div class="permission-groups">
                <?php foreach ($groupedPermissions as $group => $perms): ?>
                    <div class="permission-group mb-4 border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 text-uppercase"><?php echo h($group); ?></h5>
                            <button type="button" class="btn btn-primary-outline"
                                onclick="toggleGroup(this, '<?php echo h($group); ?>')">
                                Chọn/Bỏ tất cả
                            </button>
                        </div>
                        <div class="row">
                            <?php foreach ($perms as $perm): ?>
                                <?php $checked = in_array((int)$perm['id_quyen'], $assignedIds); ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input perm-checkbox" type="checkbox"
                                            data-group="<?php echo h($group); ?>" name="permissions[]"
                                            value="<?php echo (int)$perm['id_quyen']; ?>"
                                            id="perm<?php echo (int)$perm['id_quyen']; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="perm<?php echo (int)$perm['id_quyen']; ?>">
                                            <strong><?php echo h($perm['ten_quyen']); ?></strong><br>
                                            <small class="text-muted"><?php echo h($perm['mo_ta'] ?? ''); ?></small>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>



        <div class="text-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Lưu phân quyền
            </button>
        </div>
    </form>

</div>

<script>
    function toggleGroup(button, groupName) {
        const checkboxes = document.querySelectorAll('.perm-checkbox[data-group="' + groupName + '"]');
        if (!checkboxes.length) return;
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
    }

    <?php if (isset($redirectAfterSave) && $redirectAfterSave): ?>
        // Redirect sau khi lưu thành công để tránh resubmit form khi refresh
        window.location.href = 'index.php?page=staff-manager&panel=permission-panel&chuc_vu=<?php echo urlencode($selectedRole); ?>';
    <?php endif; ?>
</script>