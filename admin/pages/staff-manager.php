<?php
// Phân quyền module Nhân Viên
$canViewStaff = function_exists('checkPermission') ? checkPermission('employee.view') : true;

if (!$canViewStaff) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem trang nhân viên.</div></div>';
    return;
}

// Xử lý panel
$panel = isset($_GET['panel']) ? trim($_GET['panel']) : 'staff-panel';
$panelAllowed = [
    'staff-panel' => 'pages/staff-panel.php',
    'permission-panel' => 'pages/permission-manager.php',
    'task-panel' => 'pages/task-manager.php',
];

// Kiểm tra quyền cho từng panel
if ($panel == 'permission-panel') {
    if (!function_exists('checkPermission') || !checkPermission('employee.set_permission')) {
        http_response_code(403);
        echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền truy cập trang phân quyền.</div></div>';
        return;
    }
}

if ($panel == 'task-panel') {
    if (!function_exists('checkPermission') || !checkPermission('task.view')) {
        http_response_code(403);
        echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền truy cập trang giao nhiệm vụ.</div></div>';
        return;
    }
}
?>

<div class="main-content">
    <div class="content-header">
        <h1>Quản Lý Nhân Viên</h1>
        <?php
        $current_panel = isset($panel) ? $panel : (isset($_GET['panel']) ? $_GET['panel'] : 'staff-panel');
        ?>
        <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel == 'staff-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="/My-Web-Hotel/admin/index.php?page=staff-manager&panel=staff-panel">
                    <span>Quản Lý Nhân Viên</span>
                </a>
            </li>
            <?php if (function_exists('checkPermission') && checkPermission('employee.set_permission')): ?>
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel == 'permission-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="/My-Web-Hotel/admin/index.php?page=staff-manager&panel=permission-panel">
                    <span>Phân Quyền</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (function_exists('checkPermission') && checkPermission('task.view')): ?>
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel == 'task-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="/My-Web-Hotel/admin/index.php?page=staff-manager&panel=task-panel">
                    <span>Giao Nhiệm Vụ</span>
                </a>
            </li>
    <?php endif; ?>
        </ul>
    </div>

    <div class="tab-content">
        <?php 
        $panel = isset($panel) ? $panel : (isset($_GET['panel']) ? trim($_GET['panel']) : 'staff-panel');
        $panelAllowed = [
            'staff-panel' => 'staff-panel.php',
            'permission-panel' => 'permission-manager.php',
            'task-panel' => 'task-manager.php',
        ];
        
        if (isset($panelAllowed[$panel])) {
            // Ensure $mysqli is available
            if (!isset($mysqli)) {
                global $mysqli;
                if (!isset($mysqli)) {
                    require_once __DIR__ . '/../includes/connect.php';
                }
            }
            $panelFile = __DIR__ . DIRECTORY_SEPARATOR . $panelAllowed[$panel];
            if (file_exists($panelFile)) {
                include $panelFile;
            } else {
                echo '<div class="alert alert-danger">File not found: ' . htmlspecialchars($panelFile) . '</div>';
            }
        } else {
            $defaultFile = __DIR__ . DIRECTORY_SEPARATOR . 'staff-panel.php';
            if (file_exists($defaultFile)) {
                include $defaultFile;
            } else {
                echo '<div class="alert alert-danger">Default panel not found</div>';
            }
        }
        ?>
    </div>
</div>
