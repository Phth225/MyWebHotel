<?php
// Phân quyền module Phòng
$canViewRoom   = function_exists('checkPermission') ? checkPermission('room.view')   : true;
$canCreateRoom = function_exists('checkPermission') ? checkPermission('room.create') : true;
$canEditRoom   = function_exists('checkPermission') ? checkPermission('room.edit')   : true;
$canDeleteRoom = function_exists('checkPermission') ? checkPermission('room.delete') : true;

if (!$canViewRoom) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem trang phòng.</div></div>';
    return;
}

// Xử lý CRUD
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$messageType = '';

// Xử lý Room
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_room'])) {
        $room_number = trim($_POST['room_number']);
        $floor = intval($_POST['floor']);
        $room_type_id = intval($_POST['room_type_id']);
        $status = $_POST['status'] ?? 'Available';
        
        // Kiểm tra số phòng đã tồn tại chưa
        $checkStmt = $mysqli->prepare("SELECT room_id FROM room WHERE room_number = ? AND deleted IS NULL");
        $checkStmt->bind_param("s", $room_number);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $message = 'Số phòng ' . h($room_number) . ' đã tồn tại! Vui lòng chọn số phòng khác.';
            $messageType = 'danger';
            $checkStmt->close();
        } else {
            $checkStmt->close();

            // Thêm phòng - ảnh hiển thị theo loại phòng (room_type / roomtype_images)
            $stmt = $mysqli->prepare("INSERT INTO room (room_number, floor, room_type_id, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siis", $room_number, $floor, $room_type_id, $status);

            if ($stmt->execute()) {
                $message = 'Thêm phòng thành công!';
                $messageType = 'success';
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }

    if (isset($_POST['update_room'])) {
        $room_id = intval($_POST['room_id']);
        $room_number = trim($_POST['room_number']);
        $floor = intval($_POST['floor']);
        $room_type_id = intval($_POST['room_type_id']);
        $status = $_POST['status'] ?? 'Available';
        
        // Kiểm tra số phòng đã tồn tại chưa (trừ phòng hiện tại đang sửa)
        $checkStmt = $mysqli->prepare("SELECT room_id FROM room WHERE room_number = ? AND room_id != ? AND deleted IS NULL");
        $checkStmt->bind_param("si", $room_number, $room_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $message = 'Số phòng ' . h($room_number) . ' đã tồn tại! Vui lòng chọn số phòng khác.';
            $messageType = 'danger';
            $checkStmt->close();
        } else {
            $checkStmt->close();

            // Cập nhật thông tin cơ bản của phòng; ảnh lấy theo loại phòng
            $stmt = $mysqli->prepare("UPDATE room SET room_number=?, floor=?, room_type_id=?, status=? WHERE room_id=? AND deleted IS NULL");
            $stmt->bind_param("siisi", $room_number, $floor, $room_type_id, $status, $room_id);

            if ($stmt->execute()) {
                $message = 'Cập nhật phòng thành công!';
                $messageType = 'success';
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }

    if (isset($_POST['delete_room'])) {
        $room_id = intval($_POST['room_id']);
        $stmt = $mysqli->prepare("UPDATE room SET deleted = NOW() WHERE room_id = ?");
        $stmt->bind_param("i", $room_id);

        if ($stmt->execute()) {
            $message = 'Xóa phòng thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Lấy thông tin phòng để edit
$editRoom = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM room WHERE room_id = ? AND deleted IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editRoom = $result->fetch_assoc();
    $stmt->close();
}

// Phân trang và tìm kiếm
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$type_filter = intval($_GET['type'] ?? 0);
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'desc';

$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum);
$perPage = 5;
$offset = ($pageNum - 1) * $perPage;

// Xây dựng query
$where = "WHERE r.deleted IS NULL";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (r.room_number LIKE ? OR rt.room_type_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
    $types .= 'ss';
}

if ($status_filter) {
    $where .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($type_filter) {
    $where .= " AND r.room_type_id = ?";
    $params[] = $type_filter;
    $types .= 'i';
}

// Đếm tổng số
$countQuery = "SELECT COUNT(*) as total FROM room r 
    LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id 
    $where";
$countStmt = $mysqli->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$countStmt->close();

// Lấy dữ liệu - ảnh hiển thị theo loại phòng (roomtype_images)
$query = "SELECT 
            r.*,
            rt.room_type_name,
            rt.base_price,
            rt.capacity,
            rt.area,
            rt.amenities,
            rt.description,
            (
                SELECT image_url 
                FROM roomtype_images rti 
                WHERE rti.room_type_id = r.room_type_id 
                ORDER BY rti.is_primary DESC, rti.display_order ASC, rti.id ASC 
                LIMIT 1
            ) AS primary_image
          FROM room r 
          LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id 
          $where
          ORDER BY r.room_number ASC 
          LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($query);
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes = $types . 'ii';
if (!empty($allParams)) {
    $stmt->bind_param($allTypes, ...$allParams);
}
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $rooms = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Lỗi query: " . $stmt->error);
}
$stmt->close();

// Lấy TOÀN BỘ danh sách phòng (không phân trang) để hiển thị trong Grid View
$queryAll = "SELECT 
                r.*,
                rt.room_type_name,
                rt.base_price,
                rt.capacity,
                rt.area,
                rt.amenities,
                rt.description
              FROM room r 
              LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id 
              $where
              ORDER BY r.floor ASC, r.room_number ASC";
$stmtAll = $mysqli->prepare($queryAll);
if (!empty($params)) {
    $stmtAll->bind_param($types, ...$params);
}
if ($stmtAll->execute()) {
    $allRooms = $stmtAll->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmtAll->close();

$roomTypesResult = $mysqli->query("SELECT * FROM room_type WHERE deleted IS NULL");
$roomTypes = $roomTypesResult->fetch_all(MYSQLI_ASSOC);

// Xây dựng where clause cho ROOM_TYPE
$whereRoomType = "WHERE rt.deleted IS NULL";
$paramsRoomType = [];
$typesRoomType = '';


if ($search) {
    $whereRoomType .= " AND (rt.room_type_name LIKE ?)";
    $searchParam = "%$search%";
    $paramsRoomType[] = $searchParam;
    $typesRoomType .= 's';
}

if ($status_filter) {
    $whereRoomType .= " AND rt.status = ?";
    $paramsRoomType[] = $status_filter;
    $typesRoomType .= 's';
}

$orderBy = " ORDER BY rt.area DESC";
switch ($sort) {
    case 'desc':
        $orderBy = " ORDER BY rt.area DESC";
        break;
    case 'asc':
        $orderBy = " ORDER BY rt.area ASC";
        break;
    default:
        $orderBy = " ";
        break;
}

// Đếm tổng số room_type
$countQueryRoomTypes = "SELECT COUNT(*) as total FROM room_type rt $whereRoomType";
$countStmtRoomTypes = $mysqli->prepare($countQueryRoomTypes);
if (!empty($paramsRoomType)) {
    $countStmtRoomTypes->bind_param($typesRoomType, ...$paramsRoomType);
}
$countStmtRoomTypes->execute();
$totalResultRoomTypes = $countStmtRoomTypes->get_result();
$totalRoomTypes = $totalResultRoomTypes->fetch_assoc()['total'];
$countStmtRoomTypes->close();

// Lấy danh sách room_type với phân trang
$queryRoomTypes = "SELECT rt.* FROM room_type rt $whereRoomType $orderBy LIMIT ? OFFSET ?";
$stmtRoomTypes = $mysqli->prepare($queryRoomTypes);

// QUAN TRỌNG: Thêm $perPage và $offset vào array params
$allParamsRoomType = array_merge($paramsRoomType, [$perPage, $offset]);
$allTypesRoomType = $typesRoomType . 'ii';

if (!empty($allParamsRoomType)) {
    $stmtRoomTypes->bind_param($allTypesRoomType, ...$allParamsRoomType);
}

if ($stmtRoomTypes->execute()) {
    $result = $stmtRoomTypes->get_result();
    $roomTypesAll = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Lỗi query: " . $stmtRoomTypes->error);
}
$stmtRoomTypes->close();

?>

<div class="main-content">
    <div class="content-header">
        <h1>Quản Lý Phòng</h1>
        <?php
        $current_panel = isset($panel) ? $panel : (isset($_GET['panel']) ? $_GET['panel'] : 'room-panel');
        ?>
        <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel == 'room-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="/My-Web-Hotel/admin/index.php?page=room-manager&panel=room-panel">
                    <span>Phòng</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel == 'roomType-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="/My-Web-Hotel/admin/index.php?page=room-manager&panel=roomType-panel">
                    <span>Loại Phòng</span>
                </a>
            </li>
        </ul>
    </div>
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo h($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- content -->
    <div class="tab-content">
        <?php
        $panel = isset($_GET['panel']) ? trim($_GET['panel']) : 'room-panel';
        $panelAllowed = [
            'room-panel' => 'pages/room-panel.php',
            'roomType-panel' => 'pages/roomType-panel.php',
        ];
        if (isset($panelAllowed[$panel])) {
            include $panelAllowed[$panel];
        } else {
            include 'pages/404.php';
        }
        ?>

    </div>
    <script>
    function editRoom(id) {
        window.location.href = 'index.php?page=room-manager&action=edit&id=' + id;
    }

    <?php if ($editRoom): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = new bootstrap.Modal(document.getElementById('addRoomModal'));
        modal.show();
    });
    <?php endif; ?>
    </script>
    </script>