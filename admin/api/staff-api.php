<?php
session_start();
require '../includes/connect.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Xem chi tiết nhân viên
if ($action == 'view' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM nhan_vien WHERE id_nhan_vien = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $nhanVien = $result->fetch_assoc();
    $stmt->close();

    if ($nhanVien) {
        // URL ảnh từ database đã là Cloudinary URL, không cần xử lý
        echo json_encode(['success' => true, 'data' => $nhanVien]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên']);
    }
    exit;
}

// Lấy danh sách quyền và quyền của nhân viên
if ($action == 'permissions' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Lấy thông tin nhân viên
    $stmt = $mysqli->prepare("SELECT * FROM nhan_vien WHERE id_nhan_vien = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();
    $stmt->close();

    if (!$staff) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên']);
        exit;
    }

    // Lấy tất cả quyền
    $permissionsResult = $mysqli->query("SELECT * FROM quyen ORDER BY ten_quyen");
    $permissions = $permissionsResult->fetch_all(MYSQLI_ASSOC);

    // Lấy quyền theo chức vụ
    $chucVu = $staff['chuc_vu'];
    $stmt = $mysqli->prepare("SELECT id_quyen FROM quyen_chuc_vu WHERE chuc_vu = ? AND trang_thai = 1");
    $stmt->bind_param("s", $chucVu);
    $stmt->execute();
    $result = $stmt->get_result();
    $rolePermissions = [];
    while ($row = $result->fetch_assoc()) {
        $rolePermissions[] = intval($row['id_quyen']);
    }
    $stmt->close();

    // Lấy quyền riêng của nhân viên
    $stmt = $mysqli->prepare("
        SELECT id_quyen 
        FROM quyen_nhan_vien 
        WHERE id_nhan_vien = ? AND trang_thai = 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $personalPermissions = [];
    while ($row = $result->fetch_assoc()) {
        $personalPermissions[] = intval($row['id_quyen']);
    }
    $stmt->close();

    // URL ảnh từ database đã là Cloudinary URL, không cần xử lý
    echo json_encode([
        'success' => true,
        'staff' => $staff,
        'permissions' => $permissions,
        'rolePermissions' => $rolePermissions,
        'personalPermissions' => $personalPermissions
    ]);
    exit;
}

// Lưu quyền RIÊNG cho nhân viên
if ($action == 'save_permissions' && isset($_POST['id_nhan_vien'])) {
    $id_nhan_vien = intval($_POST['id_nhan_vien']);
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    // Xóa toàn bộ quyền riêng cũ
    $stmt = $mysqli->prepare("DELETE FROM quyen_nhan_vien WHERE id_nhan_vien = ?");
    $stmt->bind_param("i", $id_nhan_vien);
    $stmt->execute();
    $stmt->close();

    // Thêm quyền riêng mới
    if (!empty($permissions)) {
        $stmt = $mysqli->prepare("INSERT INTO quyen_nhan_vien (id_nhan_vien, id_quyen, trang_thai) VALUES (?, ?, 1)");
        foreach ($permissions as $permId) {
            $permId = intval($permId);
            $stmt->bind_param("ii", $id_nhan_vien, $permId);
            $stmt->execute();
        }
        $stmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Lưu quyền riêng cho nhân viên thành công']);
    exit;
}

// Lấy danh sách nhiệm vụ của nhân viên
if ($action == 'tasks' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Lấy thông tin nhân viên
    $stmt = $mysqli->prepare("SELECT * FROM nhan_vien WHERE id_nhan_vien = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();
    $stmt->close();

    if (!$staff) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên']);
        exit;
    }

    // Lấy nhiệm vụ của nhân viên
    $stmt = $mysqli->prepare("
        SELECT nv.*, 
               nv1.ho_ten as nguoi_gan_ten
        FROM nhiem_vu nv
        LEFT JOIN nhan_vien nv1 ON nv.id_nhan_vien_gan_phien = nv1.id_nhan_vien
        WHERE nv.id_nhan_vien_duoc_gan = ?
        ORDER BY nv.created_at DESC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // URL ảnh từ database đã là Cloudinary URL, không cần xử lý
    echo json_encode([
        'success' => true,
        'staff' => $staff,
        'tasks' => $tasks
    ]);
    exit;
}

// Giao nhiệm vụ
if ($action == 'assign_task' && isset($_POST['id_nhan_vien_duoc_gan'])) {
    // Kiểm tra quyền
    require '../includes/auth.php';
    if (!function_exists('checkPermission') || !checkPermission('task.create')) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền giao nhiệm vụ']);
        exit;
    }

    $id_nhan_vien_duoc_gan = intval($_POST['id_nhan_vien_duoc_gan']);
    $id_nhan_vien_gan_phien = isset($_SESSION['id_nhan_vien']) ? intval($_SESSION['id_nhan_vien']) : null;
    $ten_nhiem_vu = trim($_POST['ten_nhiem_vu']);
    $mo_ta_chi_tiet = trim($_POST['mo_ta_chi_tiet'] ?? '');
    $muc_do_uu_tien = $_POST['muc_do_uu_tien'] ?? 'Trung bình';
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $han_hoan_thanh = $_POST['han_hoan_thanh'];
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');

    if (empty($ten_nhiem_vu) || empty($ngay_bat_dau) || empty($han_hoan_thanh)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }

    if (strtotime($han_hoan_thanh) < strtotime($ngay_bat_dau)) {
        echo json_encode(['success' => false, 'message' => 'Hạn hoàn thành phải sau ngày bắt đầu']);
        exit;
    }

    $stmt = $mysqli->prepare("
        INSERT INTO nhiem_vu (ten_nhiem_vu, mo_ta_chi_tiet, id_nhan_vien_duoc_gan, id_nhan_vien_gan_phien, 
                             muc_do_uu_tien, ngay_bat_dau, han_hoan_thanh, ghi_chu, trang_thai)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Chưa bắt đầu')
    ");
    $stmt->bind_param(
        "ssiissss",
        $ten_nhiem_vu,
        $mo_ta_chi_tiet,
        $id_nhan_vien_duoc_gan,
        $id_nhan_vien_gan_phien,
        $muc_do_uu_tien,
        $ngay_bat_dau,
        $han_hoan_thanh,
        $ghi_chu
    );

    if ($stmt->execute()) {
        // Ghi lịch sử
        $nhiem_vu_id = $stmt->insert_id;
        $loai_thay_doi = 'Giao nhiệm vụ';
        $noi_dung = "Giao nhiệm vụ: $ten_nhiem_vu";
        $historyStmt = $mysqli->prepare("
            INSERT INTO lich_su_thay_doi (id_nhan_vien, id_nhiem_vu, loai_thay_doi, noi_dung_thay_doi, nguoi_thay_doi)
            VALUES (?, ?, ?, ?, ?)
        ");
        $historyStmt->bind_param("iissi", $id_nhan_vien_duoc_gan, $nhiem_vu_id, $loai_thay_doi, $noi_dung, $id_nhan_vien_gan_phien);
        $historyStmt->execute();
        $historyStmt->close();

        echo json_encode(['success' => true, 'message' => 'Giao nhiệm vụ thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Xem chi tiết nhiệm vụ
if ($action == 'view_task' && isset($_GET['id'])) {
    require '../includes/auth.php';
    if (!function_exists('checkPermission') || !checkPermission('task.view_detail')) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem chi tiết nhiệm vụ']);
        exit;
    }

    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("
        SELECT nv.*, 
               nv1.ho_ten AS ten_nhan_vien,
               nv1.ma_nhan_vien,
               nv2.ho_ten AS nguoi_gan_ten
        FROM nhiem_vu nv
        INNER JOIN nhan_vien nv1 ON nv.id_nhan_vien_duoc_gan = nv1.id_nhan_vien
        LEFT JOIN nhan_vien nv2 ON nv.id_nhan_vien_gan_phien = nv2.id_nhan_vien
        WHERE nv.id_nhiem_vu = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    $stmt->close();

    if ($task) {
        echo json_encode(['success' => true, 'task' => $task]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhiệm vụ']);
    }
    exit;
}

// Sửa nhiệm vụ
if ($action == 'update_task' && isset($_POST['id_nhiem_vu'])) {
    require '../includes/auth.php';
    if (!function_exists('checkPermission') || !checkPermission('task.edit')) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa nhiệm vụ']);
        exit;
    }

    $id_nhiem_vu = intval($_POST['id_nhiem_vu']);
    $id_nhan_vien_duoc_gan = intval($_POST['id_nhan_vien_duoc_gan']);
    $ten_nhiem_vu = trim($_POST['ten_nhiem_vu']);
    $mo_ta_chi_tiet = trim($_POST['mo_ta_chi_tiet'] ?? '');
    $muc_do_uu_tien = $_POST['muc_do_uu_tien'] ?? 'Trung bình';
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $han_hoan_thanh = $_POST['han_hoan_thanh'];
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');
    $trang_thai = $_POST['trang_thai'] ?? 'Chưa bắt đầu';
    $tien_do_hoan_thanh = intval($_POST['tien_do_hoan_thanh'] ?? 0);

    if (empty($ten_nhiem_vu) || empty($ngay_bat_dau) || empty($han_hoan_thanh)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }

    if (strtotime($han_hoan_thanh) < strtotime($ngay_bat_dau)) {
        echo json_encode(['success' => false, 'message' => 'Hạn hoàn thành phải sau ngày bắt đầu']);
        exit;
    }

    $stmt = $mysqli->prepare("
        UPDATE nhiem_vu 
        SET ten_nhiem_vu = ?, mo_ta_chi_tiet = ?, id_nhan_vien_duoc_gan = ?, 
            muc_do_uu_tien = ?, ngay_bat_dau = ?, han_hoan_thanh = ?, 
            ghi_chu = ?, trang_thai = ?, tien_do_hoan_thanh = ?
        WHERE id_nhiem_vu = ?
    ");
    $stmt->bind_param(
        "ssissssiii",
        $ten_nhiem_vu,
        $mo_ta_chi_tiet,
        $id_nhan_vien_duoc_gan,
        $muc_do_uu_tien,
        $ngay_bat_dau,
        $han_hoan_thanh,
        $ghi_chu,
        $trang_thai,
        $tien_do_hoan_thanh,
        $id_nhiem_vu
    );

    if ($stmt->execute()) {
        // Ghi lịch sử
        $loai_thay_doi = 'Cập nhật nhiệm vụ';
        $noi_dung = "Cập nhật nhiệm vụ: $ten_nhiem_vu";
        $id_nhan_vien_gan_phien = isset($_SESSION['id_nhan_vien']) ? intval($_SESSION['id_nhan_vien']) : null;
        $historyStmt = $mysqli->prepare("
            INSERT INTO lich_su_thay_doi (id_nhan_vien, id_nhiem_vu, loai_thay_doi, noi_dung_thay_doi, nguoi_thay_doi)
            VALUES (?, ?, ?, ?, ?)
        ");
        $historyStmt->bind_param("iissi", $id_nhan_vien_duoc_gan, $id_nhiem_vu, $loai_thay_doi, $noi_dung, $id_nhan_vien_gan_phien);
        $historyStmt->execute();
        $historyStmt->close();

        echo json_encode(['success' => true, 'message' => 'Cập nhật nhiệm vụ thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Xóa nhiệm vụ
if ($action == 'delete_task' && isset($_POST['id_nhiem_vu'])) {
    require '../includes/auth.php';
    if (!function_exists('checkPermission') || !checkPermission('task.delete')) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa nhiệm vụ']);
        exit;
    }

    $id_nhiem_vu = intval($_POST['id_nhiem_vu']);

    // Lấy thông tin nhiệm vụ trước khi xóa để ghi lịch sử
    $stmt = $mysqli->prepare("SELECT * FROM nhiem_vu WHERE id_nhiem_vu = ?");
    $stmt->bind_param("i", $id_nhiem_vu);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    $stmt->close();

    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhiệm vụ']);
        exit;
    }

    // Use soft delete if deleted column exists, otherwise hard delete
    $checkDeleted = $mysqli->query("SHOW COLUMNS FROM nhiem_vu LIKE 'deleted'");
    if ($checkDeleted && $checkDeleted->num_rows > 0) {
        // Soft delete
        $stmt = $mysqli->prepare("UPDATE nhiem_vu SET deleted = NOW() WHERE id_nhiem_vu = ?");
    } else {
        // Hard delete
        $stmt = $mysqli->prepare("DELETE FROM nhiem_vu WHERE id_nhiem_vu = ?");
    }
    $stmt->bind_param("i", $id_nhiem_vu);

    if ($stmt->execute()) {
        // Ghi lịch sử (chỉ nếu bảng tồn tại)
        try {
            $loai_thay_doi = 'Xóa nhiệm vụ';
            $noi_dung = "Xóa nhiệm vụ: " . $task['ten_nhiem_vu'];
            $id_nhan_vien_gan_phien = isset($_SESSION['id_nhan_vien']) ? intval($_SESSION['id_nhan_vien']) : null;
            $historyStmt = $mysqli->prepare("
                INSERT INTO lich_su_thay_doi (id_nhan_vien, id_nhiem_vu, loai_thay_doi, noi_dung_thay_doi, nguoi_thay_doi)
                VALUES (?, ?, ?, ?, ?)
            ");
            if ($historyStmt) {
                $historyStmt->bind_param("iissi", $task['id_nhan_vien_duoc_gan'], $id_nhiem_vu, $loai_thay_doi, $noi_dung, $id_nhan_vien_gan_phien);
                $historyStmt->execute();
                $historyStmt->close();
            }
        } catch (Exception $e) {
            // Ignore history logging errors
            error_log("History logging error: " . $e->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Xóa nhiệm vụ thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
