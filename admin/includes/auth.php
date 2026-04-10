<?php
// Kiểm tra đăng nhập cho nhân viên
// ĐƠN GIẢN HÓA: chỉ dùng session, KHÔNG dùng remember_token/login_tokens
if (!isset($_SESSION['id_nhan_vien']) || !isset($_SESSION['is_staff'])) {
    $currentPage = $_SERVER['PHP_SELF'];
    $queryString = $_SERVER['QUERY_STRING'];
    $redirect = urlencode($currentPage . ($queryString ? '?' . $queryString : ''));
    header("Location: /My-Web-Hotel/admin/pages/logIn.php?redirect=" . $redirect);
    exit;
}

// Kiểm tra quyền truy cập (nếu cần)
function checkPermission($permissionName) {
    if (!isset($_SESSION['chuc_vu']) || $permissionName === '') {
        return false;
    }
    
    $permissions = getStaffPermissions();
    return in_array($permissionName, $permissions, true);
}

// Lấy tất cả quyền của nhân viên hiện tại (cache theo request)
function getStaffPermissions() {
    static $cachedPermissions = null;

    if ($cachedPermissions !== null) {
        return $cachedPermissions;
    }
    
    if (!isset($_SESSION['chuc_vu']) || !isset($_SESSION['id_nhan_vien'])) {
        $cachedPermissions = [];
        return $cachedPermissions;
    }
    
    global $mysqli;
    $staffId = (int)$_SESSION['id_nhan_vien'];
    $chuc_vu = $_SESSION['chuc_vu'];
    
    // Quyền theo chức vụ
    $stmt = $mysqli->prepare("
        SELECT q.ten_quyen 
        FROM quyen q
        INNER JOIN quyen_chuc_vu qcv ON q.id_quyen = qcv.id_quyen
        WHERE qcv.chuc_vu = ? AND qcv.trang_thai = 1
    ");
    $stmt->bind_param("s", $chuc_vu);
    $stmt->execute();
    $result = $stmt->get_result();
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['ten_quyen'];
    }
    $stmt->close();
    
    // Quyền RIÊNG của nhân viên
    $stmt = $mysqli->prepare("
        SELECT q.ten_quyen
        FROM quyen q
        INNER JOIN quyen_nhan_vien qnv ON q.id_quyen = qnv.id_quyen
        WHERE qnv.id_nhan_vien = ? AND qnv.trang_thai = 1
    ");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['ten_quyen'];
}
    $stmt->close();

    // Loại bỏ trùng
    $cachedPermissions = array_values(array_unique($permissions));
    return $cachedPermissions;
}

function canAccessSection($sectionKey) {
    $map = getPagePermissionMap();
    if (!isset($map[$sectionKey]) || empty($map[$sectionKey])) {
        return true;
    }

    $permissions = getStaffPermissions();
    if (empty($permissions)) {
        return false;
    }

    foreach ($map[$sectionKey] as $rule) {
        if (permissionRuleSatisfied($permissions, $rule)) {
            return true;
        }
    }

    return false;
}

function getPagePermissionMap() {
    static $map = null;

    if ($map !== null) {
        return $map;
    }

    $configPath = __DIR__ . '/permission_map.php';
    if (file_exists($configPath)) {
        $loaded = include $configPath;
        $map = is_array($loaded) ? $loaded : [];
    } else {
        $map = [];
    }

    return $map;
}

function permissionRuleSatisfied(array $permissions, array $rule) {
    $type = strtolower($rule['type'] ?? 'exact');
    $value = $rule['value'] ?? '';

    if ($value === '') {
        return false;
    }

    foreach ($permissions as $permission) {
        switch ($type) {
            case 'prefix':
                if (startsWithInsensitive($permission, $value)) {
                    return true;
                }
                break;
            case 'regex':
                $result = @preg_match($value, $permission);
                if ($result === 1) {
                    return true;
                }
                break;
            case 'exact':
            default:
                if ($permission === $value) {
                    return true;
                }
                break;
        }
    }

    return false;
}

function startsWithInsensitive($haystack, $needle) {
    if ($needle === '') {
        return false;
    }

    if (function_exists('mb_substr') && function_exists('mb_strtolower') && function_exists('mb_strlen')) {
        $length = mb_strlen($needle, 'UTF-8');
        $segment = mb_substr($haystack, 0, $length, 'UTF-8');
        return mb_strtolower($segment, 'UTF-8') === mb_strtolower($needle, 'UTF-8');
    }

    return stripos($haystack, $needle) === 0;
}
?>
