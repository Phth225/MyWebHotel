<?php
// Include helper function để tự động tạo hóa đơn
require_once __DIR__ . '/../includes/invoice_helper.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$messageType = '';

function getServiceInfo($mysqli, $service_id) {
    if (!$service_id) return null;
    $stmt = $mysqli->prepare("SELECT price, unit FROM service WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Thêm booking dịch vụ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_booking_service'])) {
        $customer_id = intval($_POST['customer_id']);
        $service_id = intval($_POST['service_id']);
        $quantity = intval($_POST['quantity']);
        $usage_date = $_POST['usage_date'];
        $usage_time = $_POST['usage_time'];
        $booking_id = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null; // Cho phép NULL
        $amount = floatval($_POST['amount']); // Nên dùng floatval thay vì intval cho tiền
        $notes = is_array($_POST['note'] ?? '') ? '' : trim($_POST['note'] ?? '');
        $status = $_POST['status'] ?? 'confirmed';

        // Xử lý validate dữ liệu
        $errors = [];
        if (empty($customer_id) || $customer_id <= 0) {
            $errors[] = "Khách hàng không được để trống";
        }
        if (empty($service_id) || $service_id <= 0) {
            $errors[] = "Dịch vụ không được để trống";
        }
        if (empty($quantity) || $quantity <= 0) {
            $errors[] = "Số lượng phải lớn hơn 0";
        }
        if (empty($usage_date)) {
            $errors[] = "Ngày sử dụng không được để trống";
        }
        if (empty($usage_time)) {
            $errors[] = "Giờ sử dụng không được để trống";
        }
        if (empty($amount) || $amount <= 0) {
            $errors[] = "Số tiền phải lớn hơn 0";
        }

        // Nếu có booking_id, kiểm tra xem booking có tồn tại không
        if ($booking_id) {
            $check_booking = $mysqli->prepare("SELECT booking_id FROM booking WHERE booking_id = ?");
            $check_booking->bind_param("i", $booking_id);
            $check_booking->execute();
            $result = $check_booking->get_result();
            if ($result->num_rows == 0) {
                $errors[] = "Booking ID không tồn tại";
            }
            $check_booking->close();
        }

        // Xử lý nhiều dịch vụ: service_id có thể là array
        $service_ids = [];
        if (isset($_POST['service_id']) && is_array($_POST['service_id'])) {
            $service_ids = array_map('intval', $_POST['service_id']);
        } elseif (isset($_POST['service_id']) && !empty($_POST['service_id'])) {
            $service_ids = [intval($_POST['service_id'])];
        }
        
        // Lấy các thông tin khác (có thể là array nếu có nhiều dịch vụ)
        $quantities = isset($_POST['quantity']) && is_array($_POST['quantity']) ? $_POST['quantity'] : [$_POST['quantity'] ?? 1];
        $usage_dates = isset($_POST['usage_date']) && is_array($_POST['usage_date']) ? $_POST['usage_date'] : [$_POST['usage_date'] ?? ''];
        $usage_times = isset($_POST['usage_time']) && is_array($_POST['usage_time']) ? $_POST['usage_time'] : [$_POST['usage_time'] ?? ''];
        $amounts = isset($_POST['amount']) && is_array($_POST['amount']) ? $_POST['amount'] : [$_POST['amount'] ?? 1];
        $notes_array = isset($_POST['note']) && is_array($_POST['note']) ? $_POST['note'] : [$_POST['note'] ?? ''];
        $statuses = isset($_POST['status']) && is_array($_POST['status']) ? $_POST['status'] : [$_POST['status'] ?? 'pending'];
        
        // Đảm bảo tất cả arrays có cùng số lượng phần tử
        $max_count = max(count($service_ids), count($quantities), count($usage_dates), count($usage_times), count($amounts));
        $service_ids = array_pad($service_ids, $max_count, 0);
        $quantities = array_pad($quantities, $max_count, 1);
        $usage_dates = array_pad($usage_dates, $max_count, '');
        $usage_times = array_pad($usage_times, $max_count, '');
        $amounts = array_pad($amounts, $max_count, 1);
        $notes_array = array_pad($notes_array, $max_count, '');
        $statuses = array_pad($statuses, $max_count, 'pending');
        
        if (empty($errors) && !empty($service_ids)) {
            $success_count = 0;
            $error_count = 0;
            $created_booking_services = [];
            
            // Tạo booking_service cho từng dịch vụ
            for ($i = 0; $i < $max_count; $i++) {
                if (empty($service_ids[$i]) || $service_ids[$i] <= 0) {
                    continue; // Bỏ qua nếu không có service_id
                }
                
                $current_service_id = $service_ids[$i];
                $current_quantity = intval($quantities[$i] ?? 1);
                $current_usage_date = $usage_dates[$i] ?? '';
                $current_usage_time = $usage_times[$i] ?? '';
                $current_amount = floatval($amounts[$i] ?? 1);
                $current_notes = trim($notes_array[$i] ?? '');
                $current_status = $statuses[$i] ?? 'pending';
                
                // Validate từng dịch vụ
                if (empty($current_usage_date) || empty($current_usage_time) || $current_amount <= 0) {
                    $error_count++;
                    continue;
                }
                
                // Lấy unit_price từ service
                $service_data = getServiceInfo($mysqli, $current_service_id);
                
                if (!$service_data) {
                    $error_count++;
                    continue;
                }
                
                $unit_price = $service_data['price'] ?? 0;
                $unit = $service_data['unit'] ?? '';
                
                // INSERT booking_service
                if ($booking_id) {
                    $stmt = $mysqli->prepare("INSERT INTO booking_service (customer_id, service_id, quantity, usage_date, usage_time, booking_id, amount, unit_price, unit, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iiissiddsss", $customer_id, $current_service_id, $current_quantity, $current_usage_date, $current_usage_time, $booking_id, $current_amount, $unit_price, $unit, $current_notes, $current_status);
                } else {
                    $stmt = $mysqli->prepare("INSERT INTO booking_service (customer_id, service_id, quantity, usage_date, usage_time, amount, unit_price, unit, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iiissddsss", $customer_id, $current_service_id, $current_quantity, $current_usage_date, $current_usage_time, $current_amount, $unit_price, $unit, $current_notes, $current_status);
                }
                
                if ($stmt->execute()) {
                    $booking_service_id = $stmt->insert_id;
                    $created_booking_services[] = $booking_service_id;
                    $success_count++;
                    
                    // Tự động tạo hóa đơn nếu status = confirmed
                    if ($current_status === 'confirmed') {
                        $invoice_id = createInvoiceForServiceBooking($mysqli, $booking_service_id);
                    }
                } else {
                    $error_count++;
                }
                $stmt->close();
            }
            
            if ($success_count > 0) {
                $message = 'Đã tạo thành công ' . $success_count . ' booking dịch vụ';
                if ($success_count > 1) {
                    $message .= ' (IDs: ' . implode(', ', $created_booking_services) . ')';
                } else {
                    $message .= ' (ID: ' . $created_booking_services[0] . ')';
                }
                if ($error_count > 0) {
                    $message .= '. Có ' . $error_count . ' dịch vụ không thể tạo';
                }
                $messageType = 'success';
                $action = '';
            } else {
                $message = 'Không thể tạo booking dịch vụ. Vui lòng kiểm tra lại thông tin.';
                $messageType = 'danger';
            }
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'danger';
        }
    }
    if (isset($_POST['update_service_booking'])) {
        $booking_service_id = intval($_POST['booking_service_id']);
        $customer_id = intval($_POST['customer_id']);
        // Khi update, chỉ có 1 dịch vụ (không phải array)
        $service_id = isset($_POST['service_id']) && is_array($_POST['service_id']) ? intval($_POST['service_id'][0]) : intval($_POST['service_id'] ?? 0);
        $quantity = isset($_POST['quantity']) && is_array($_POST['quantity']) ? intval($_POST['quantity'][0]) : intval($_POST['quantity'] ?? 1);
        $usage_date = isset($_POST['usage_date']) && is_array($_POST['usage_date']) ? $_POST['usage_date'][0] : ($_POST['usage_date'] ?? '');
        $usage_time = isset($_POST['usage_time']) && is_array($_POST['usage_time']) ? $_POST['usage_time'][0] : ($_POST['usage_time'] ?? '');
        $booking_id = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
        $amount = isset($_POST['amount']) && is_array($_POST['amount']) ? floatval($_POST['amount'][0]) : floatval($_POST['amount'] ?? 1);
        $notes = isset($_POST['note']) && is_array($_POST['note']) ? trim($_POST['note'][0] ?? '') : trim($_POST['note'] ?? '');
        $status = isset($_POST['status']) && is_array($_POST['status']) ? ($_POST['status'][0] ?? 'confirmed') : ($_POST['status'] ?? 'confirmed');

        // Validate dữ liệu
        $errors = [];
        if (empty($customer_id) || $customer_id <= 0) {
            $errors[] = "Khách hàng không được để trống";
        }
        if (empty($service_id) || $service_id <= 0) {
            $errors[] = "Dịch vụ không được để trống";
        }
        if (empty($quantity) || $quantity <= 0) {
            $errors[] = "Số lượng phải lớn hơn 0";
        }
        if (empty($usage_date)) {
            $errors[] = "Ngày sử dụng không được để trống";
        }
        if (empty($usage_time)) {
            $errors[] = "Giờ sử dụng không được để trống";
        }
        if (empty($amount) || $amount <= 0) {
            $errors[] = "Số tiền phải lớn hơn 0";
        }

        // Nếu có booking_id, kiểm tra xem booking có tồn tại không
        if ($booking_id) {
            $check_booking = $mysqli->prepare("SELECT booking_id FROM booking WHERE booking_id = ?");
            $check_booking->bind_param("i", $booking_id);
            $check_booking->execute();
            $result = $check_booking->get_result();
            if ($result->num_rows == 0) {
                $errors[] = "Booking ID không tồn tại";
            }
            $check_booking->close();
        }

        if (empty($errors)) {
            // Lấy unit_price từ service để cập nhật vào booking_service
            $service_data = getServiceInfo($mysqli, $service_id);

            $unit_price = $service_data['price'] ?? 0;
            $unit = $service_data['unit'] ?? '';

            // UPDATE - không thay đổi booking_id nếu đã có, chỉ cập nhật các trường khác
            // Nếu booking_id đã tồn tại trong record, giữ nguyên; nếu không có thì không set
            $stmt = $mysqli->prepare("UPDATE booking_service SET customer_id=?, service_id=?, quantity=?, usage_date=?, usage_time=?, amount=?, unit_price=?, unit=?, notes=?, status=? WHERE booking_service_id=? AND deleted IS NULL");
            $stmt->bind_param("iiissddsssi", $customer_id, $service_id, $quantity, $usage_date, $usage_time, $amount, $unit_price, $unit, $notes, $status, $booking_service_id);

            if ($stmt->execute()) {
                $message = 'Cập nhật booking dịch vụ thành công!';
                $messageType = 'success';
                
                // Tự động tạo hóa đơn nếu status = confirmed và chưa có hóa đơn
                // Đặc biệt: Nếu booking dịch vụ không có booking_id (chỉ booking dịch vụ), luôn tạo hóa đơn khi confirmed
                if ($status === 'confirmed') {
                    $invoice_id = createInvoiceForServiceBooking($mysqli, $booking_service_id);
                    if ($invoice_id) {
                        $message .= ' Hóa đơn đã được tạo tự động (ID: ' . $invoice_id . ')';
                    } else {
                        // Log lỗi nếu không tạo được hóa đơn
                        error_log("Failed to create invoice for service booking ID: " . $booking_service_id);
                        $message .= ' (Lưu ý: Không thể tạo hóa đơn tự động, vui lòng tạo thủ công)';
                    }
                } else {
                    // Nếu status không phải confirmed, thông báo cho user
                    if (!$booking_id) {
                        $message .= ' (Lưu ý: Để tự động tạo hóa đơn, vui lòng đặt trạng thái là "Đã xác nhận")';
                    }
                }
                
                $action = '';
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'danger';
        }
    }
    if (isset($_POST['delete_booking_service'])) {
        $booking_service_id = intval($_POST['booking_service_id']);
        // Kiểm tra booking_service có liên kết đến invoice hay ko
        $check_stmt = $mysqli->prepare("
        SELECT i.status, i.invoice_id
        FROM booking_service bs
        LEFT JOIN invoice i ON(i.booking_id=bs.booking_id OR i.customer_id=bs.customer_id)
        WHERE bs.booking_service_id = ? AND i.deleted IS NULL
        ");
        $check_stmt->bind_param("i", $booking_service_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $invoice_data = $result->fetch_assoc();
        $check_stmt->close();

        if ($invoice_data && $invoice_data['status'] == 'Paid') {
            $message = 'Không thể xóa!Dịch vụ này đã có hóa đơn thanh toán';
            $messageType = 'warning';
        } else {
            // Soft delete booking_service
            $stmt = $mysqli->prepare("UPDATE booking_service SET deleted=NOW() WHERE booking_service_id=?");
            $stmt->bind_param("i", $booking_service_id);
            if ($stmt->execute()) {
                $message = 'Xóa booking dịch vụ thành công';
                $messageType = 'success';

                if ($invoice_data && $invoice_data['status'] == 'Unpaid') {
                    $invoice_id = $invoice_data['invoice_id'];
                    $update_invoice = $mysqli->prepare("
                UPDATE invoice i 
                SET i.service_charge = (
                SELECT COALESCE(SUM(bs.amount),0)
                FROM booking_service bs
                WHERE (bs.booking_id=i.booking_id OR bs.customer_id=i.customer_id)
                AND bs.deleted IS NULL
                ),
                i.total_amount = i.room_charge + (
                 SELECT COALESCE(SUM(bs.amount),0)
                 FROM booking_service bs
                 WHERE (bs.booking_id=i.booking_id OR bs.customer_id=i.customer_id)
                 AND bs.deleted IS NULL
                ) + i.vat +i.other_fees
                 WHERE i.invoice_id=?
                ");
                    $update_invoice->bind_param("i", $invoice_id);
                    $update_invoice->execute();
                    $update_invoice->close();

                    $message = 'Hóa đơn đã được cập nhật lại tổng tiền';
                }
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }
}
// Lấy ra thông tin booking service để edit
$editBookingService = null;
$editError = '';

if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id <= 0) {
        $editError = 'ID không hợp lệ';
        $action = '';
    } else {
        $stmt = $mysqli->prepare(
            "SELECT bs.*,
            COALESCE(c.customer_id, 0) as customer_id,
            COALESCE(c.phone, w.phone, w3.phone) as phone,
            COALESCE(c.full_name, w.full_name, w3.full_name) as full_name,
            COALESCE(c.email, w.email, w3.email) as email,
            s.service_id,s.service_name,s.price as price ,s.description,
            b.booking_id,b.check_in_date,b.check_out_date,
            r.room_number
            FROM booking_service bs
            LEFT JOIN customer c ON bs.customer_id=c.customer_id AND bs.customer_id IS NOT NULL
            LEFT JOIN booking b ON bs.booking_id=b.booking_id
            LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
            LEFT JOIN walk_in_guest w3 ON bs.walk_in_guest_id = w3.id
            LEFT JOIN service s ON bs.service_id=s.service_id
            LEFT JOIN room r ON r.room_id=b.room_id
            WHERE bs.booking_service_id=? AND bs.deleted IS NULL
            "
        );
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $editBookingService = $result->fetch_assoc();

                    if ($editBookingService['booking_id']) {
                        // Trường hợp có booking phòng
                        $invoice_stmt = $mysqli->prepare(
                            "SELECT invoice_id, status, total_amount
                            FROM invoice 
                            WHERE booking_id=? AND deleted IS NULL
                            LIMIT 1
                            "
                        );
                        $invoice_stmt->bind_param("i", $editBookingService['booking_id']);
                    } else {
                        // Trường hợp chỉ có dịch vụ không có booking phòng
                        $invoice_stmt = $mysqli->prepare(
                            "SELECT invoice_id,status, total_amount
                            FROM invoice 
                            WHERE customer_id=?  AND booking_id IS NULL AND deleted IS NULL
                            LIMIT 1"
                        );
                        $invoice_stmt->bind_param("i", $editBookingService['customer_id']);
                    }
                    $invoice_stmt->execute();
                    $invoice_result = $invoice_stmt->get_result();
                    $editBookingService['invoice'] = $invoice_result->fetch_assoc();
                    $invoice_stmt->close();

                    // Thêm flag để biết có phòng hay không
                    $editBookingService['has_room_booking'] = !empty($editBookingService['booking_id']);
                } else {
                    $editError = 'Không tìm thấy booking service này hoặc đã bị xóa';
                    $action = '';
                }
            } else {
                $editError = 'Lỗi thực thi query: ' . $stmt->error;
                $action = '';
            }
            $stmt->close();
        } else {
            $editError = 'Lỗi chuẩn bị query: ';
            $action = '';
        }
    }
}
if ($editError && empty($message)) {
    $message = $editError;
    $messageType = 'danger';
}

// Lấy danh sách customer 
$customersResult = $mysqli->query(
    "SELECT customer_id, full_name, phone, email 
     FROM customer 
     WHERE deleted IS NULL 
     ORDER BY full_name ASC"
);
$customers = $customersResult->fetch_all(MYSQLI_ASSOC);

// Lấy ra danh sách các dịch vụ 
$serviceResult = $mysqli->query(
    "SELECT service_id, service_name,price
    FROM service
    WHERE deleted IS NULL
    "
);
$services = $serviceResult->fetch_all(MYSQLI_ASSOC);

// Phân trang và tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$type_filter = intval($_GET['type'] ?? 0);
$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum);
$perPage = 5;
$offset = ($pageNum - 1) * $perPage;

// Xây dụng query

$where = "WHERE bs.deleted IS NULL";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (COALESCE(c.full_name, w.full_name, w3.full_name) LIKE ? OR bs.booking_service_id LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
    $types .= 'ss';
}

if ($status_filter) {
    $where .= " AND bs.status=?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($type_filter) {
    $where .= " AND s.service_id = ?";
    $params[] = $type_filter;
    $types .= 'i';
}
// Đếm tổng số các booking dịch vụ
$counstSql = "SELECT COUNT(*) as total
          FROM booking_service bs
          LEFT JOIN customer c ON bs.customer_id=c.customer_id AND bs.customer_id IS NOT NULL
          LEFT JOIN booking b ON b.booking_id=bs.booking_id
          LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
          LEFT JOIN walk_in_guest w3 ON bs.walk_in_guest_id = w3.id
          LEFT JOIN service s ON s.service_id=bs.service_id
          $where
          ";
$countStmt = $mysqli->prepare($counstSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalBookingService = $totalResult->fetch_assoc()['total'];
$countStmt->close();

// Get booking service data
$sql = "SELECT 
       bs.booking_service_id,
       bs.booking_id,
       bs.customer_id,
       bs.service_id,
       bs.quantity,
       bs.unit_price,
       bs.amount,
       bs.usage_date,
       bs.usage_time,
       bs.notes,
       bs.status,
       bs.unit,
       bs.created_at,
       bs.deleted,
       -- Lấy thông tin khách hàng: ưu tiên customer, sau đó walk_in_guest từ booking, cuối cùng từ walk_in_guest trực tiếp
       COALESCE(c.full_name, w.full_name, w3.full_name) as full_name,
       COALESCE(c.phone, w.phone, w3.phone) as phone,
       COALESCE(c.email, w.email, w3.email) as email,
       CASE WHEN bs.customer_id IS NULL THEN 'Walk-in' ELSE 'Registered' END as guest_type,
       s.service_name,
       s.price,
       s.service_type,
       s.description,
       b.booking_id,
       b.check_in_date,
       b.check_out_date,
       b.status as booking_status,
       r.room_id,
       r.room_number,
       rt.room_type_name,
       -- Subquery để lấy invoice_id phù hợp nhất
       (SELECT i.invoice_id 
        FROM invoice i 
        WHERE ((i.booking_id = bs.booking_id AND bs.booking_id IS NOT NULL)
               OR (i.customer_id = bs.customer_id AND i.booking_id IS NULL))
          AND i.deleted IS NULL
        ORDER BY i.created_at DESC
        LIMIT 1) as invoice_id,
       (SELECT i.status 
        FROM invoice i 
        WHERE ((i.booking_id = bs.booking_id AND bs.booking_id IS NOT NULL)
               OR (i.customer_id = bs.customer_id AND i.booking_id IS NULL))
          AND i.deleted IS NULL
        ORDER BY i.created_at DESC
        LIMIT 1) as invoice_status,
       (SELECT i.total_amount 
        FROM invoice i 
        WHERE ((i.booking_id = bs.booking_id AND bs.booking_id IS NOT NULL)
               OR (i.customer_id = bs.customer_id AND i.booking_id IS NULL))
          AND i.deleted IS NULL
        ORDER BY i.created_at DESC
        LIMIT 1) as total_amount,
       CASE
          WHEN bs.booking_id IS NOT NULL THEN 1
          ELSE 0
        END as has_room_booking
        FROM booking_service bs
        INNER JOIN service s ON bs.service_id=s.service_id
        LEFT JOIN customer c ON bs.customer_id=c.customer_id AND bs.customer_id IS NOT NULL
        LEFT JOIN booking b ON b.booking_id=bs.booking_id AND b.deleted IS NULL
        LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
        LEFT JOIN walk_in_guest w3 ON bs.walk_in_guest_id = w3.id
        LEFT JOIN room r ON b.room_id=r.room_id
        LEFT JOIN room_type rt ON r.room_type_id=rt.room_type_id
        $where
        ORDER BY bs.created_at DESC
        LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$booking_service = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Build baase URL for pagination
$baseUrl = "index.php?page=booking-manager&panel=serviceBooking-panel";
if ($search) $baseUrl .= "&search=" . urlencode($search);
if ($status_filter) $baseUrl .= "&status=" . urldecode($status_filter);
if ($type_filter) $baseUrl .= "&type=" . $type_filter;
?>

<div class="content-card">
    <div class="card-header-custom">
        <h3 class="card-title">Danh Sách Booking Dịch Vụ</h3>
        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addServiceBookingModal">
            <i class="fas fa-plus"></i> Thêm Booking
        </button>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo h($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <!-- Filter -->
    <div class="filter-section">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="booking-manager">
            <input type="hidden" name="panel" value="serviceBooking-panel">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Tìm tên khách hoặc mã booking dịch vụ..." />
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Confirmed" <?php echo $status_filter == 'Confirmed' ? 'selected' : ''; ?>>Đã xác
                            nhận</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Chưa thanh
                            toán</option>
                        <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Đã hủy
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type" id="typeFilter">
                        <option value="0">Tất cả các dịch vụ</option>
                        <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['service_id']; ?>"
                            <?php echo $type_filter == $service['service_id'] ? 'selected' : ''; ?>>
                            <?php echo h($service['service_name']); ?>
                        </option>
                        <?php endforeach; ?>
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
                    <th>ID</th>
                    <th>Tên Khách</th>
                    <th>Tên dịch vụ</th>
                    <th>Ngày</th>
                    <th>Giờ</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($booking_service)): ?>
                <tr>
                    <td colspan="9" class="text-center">Không có dữ liệu</td>
                </tr>
                <?php else: ?>
                <?php foreach ($booking_service as $bk): ?>
                <tr>
                    <td><?php echo h($bk['booking_service_id']); ?></td>
                    <td>
                        <?php if (!empty($bk['full_name'])): ?>
                            <strong><?php echo h($bk['full_name']); ?></strong><br><small><?php echo h($bk['phone'] ?? ''); ?></small>
                        <?php else: ?>
                            <span class="text-muted">Chưa có thông tin</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo h($bk['service_name']); ?></td>
                    <td><?php echo h($bk['usage_date'] ?? ''); ?></td>
                    <td><?php echo h($bk['usage_time'] ?? ''); ?></td>
                    </td>
                    <td>
                        <?php
                        $status = strtolower(trim($bk['status']));
                        $statusClass = 'bg-secondary';
                        $statusText = $bk['status'];
                        switch ($status) {
                            case 'confirmed':
                                $statusClass = 'bg-success';
                                $statusText = 'Đã xác nhận';
                                break;
                            case 'pending':
                                $statusClass = 'bg-warning';
                                $statusText = 'Chờ xử lý';
                                break;
                            case 'cancelled':
                                $statusClass = 'bg-danger';
                                $statusText = 'Đã hủy';
                                break;
                            case 'completed':
                                $statusClass = 'bg-info';
                                $statusText = 'Hoàn thành';
                                break;
                        }
                        ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" title="Xem chi tiết" data-bs-toggle="modal"
                            data-bs-target="#viewBookingServiceModal<?php echo $bk['booking_service_id']; ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" title="Sửa"
                            onclick="editServiceBooking(<?php echo $bk['booking_service_id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" title="Xóa"
                            onclick="deleteServiceBooking(<?php echo $bk['booking_service_id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>

                <!-- View Detail Modal -->
                <div class="modal fade" id="viewBookingServiceModal<?php echo $bk['booking_service_id']; ?>"
                    tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header">
                                <h5 class="modal-title">Chi tiết Booking Dịch Vụ
                                    #<?php echo h($bk['booking_service_id']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body pt-2 px-4 pb-4">
                                <!-- Status Badge & Created Date -->
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="text-muted small">
                                        Ngày tạo: <?php echo date('d/m/Y H:i', strtotime($bk['created_at'])); ?>
                                    </div>
                                    <span class="badge rounded-pill <?php echo $statusClass; ?> px-3 py-2 fs-6">
                                        <?php echo $statusText; ?>
                                    </span>
                                </div>

                                <div class="row g-4">
                                    <!-- Customer Info Column -->
                                    <div class="col-md-6 border-end">
                                        <h6 class="text-uppercase text-secondary fw-bold small mb-3">
                                            <i class="fas fa-user me-2"></i>Thông tin khách hàng
                                        </h6>
                                        <div class="ps-3">
                                            <div class="mb-2">
                                                <span class="d-block text-muted small">Khách hàng</span>
                                                <span class="fw-bold text-dark">
                                                    <?php echo !empty($bk['full_name']) ? h($bk['full_name']) : '<span class="text-muted">Chưa có thông tin</span>'; ?>
                                                </span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="d-block text-muted small">Số điện thoại</span>
                                                <span class="fw-bold text-dark">
                                                    <?php echo !empty($bk['phone']) ? h($bk['phone']) : '<span class="text-muted">-</span>'; ?>
                                                </span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="d-block text-muted small">Email</span>
                                                <span class="fw-bold text-dark">
                                                    <?php echo !empty($bk['email']) ? h($bk['email']) : '<span class="text-muted">-</span>'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Service Info Column -->
                                    <div class="col-md-6">
                                        <h6 class="text-uppercase text-secondary fw-bold small mb-3">
                                            <i class="fas fa-concierge-bell me-2"></i>Thông tin dịch vụ
                                        </h6>
                                        <div class="ps-3">
                                            <div class="mb-2">
                                                <span class="d-block text-muted small">Dịch vụ</span>
                                                <span
                                                    class="fw-bold text-primary"><?php echo h($bk['service_name']); ?></span>
                                                <?php
                                                $typeClass = 'bg-secondary';
                                                switch ($bk['service_type']) {
                                                    case 'Wellness': $typeClass = 'bg-info text-white'; break;
                                                    case 'Food & Beverage': $typeClass = 'bg-success text-white'; break;
                                                    case 'Transportation': $typeClass = 'bg-primary text-white'; break;
                                                    case 'Tour': $typeClass = 'bg-warning text-dark'; break;
                                                }
                                                ?>
                                                <span
                                                    class="badge <?php echo $typeClass; ?> ms-2"><?php echo h($bk['service_type']); ?></span>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <span class="d-block text-muted small">Ngày sử dụng</span>
                                                    <span
                                                        class="fw-bold text-dark"><?php echo h($bk['usage_date']); ?></span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="d-block text-muted small">Giờ sử dụng</span>
                                                    <span
                                                        class="fw-bold text-dark"><?php echo h($bk['usage_time']); ?></span>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <span class="d-block text-muted small">Số người</span>
                                                <span class="fw-bold text-dark"><?php echo h($bk['quantity']); ?>
                                                    người</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4 text-muted opacity-25">

                                <!-- Payment Info -->
                                <div class="bg-light rounded-3 p-3">
                                    <h6 class="text-uppercase text-secondary fw-bold small mb-3">
                                        <i class="fas fa-money-bill-wave me-2"></i>Chi tiết thanh toán
                                    </h6>
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <div class="small text-muted">Đơn giá</div>
                                            <div class="fw-bold"><?php echo number_format($bk['price'], 0, ',', '.'); ?>
                                                VNĐ / <?php echo h($bk['unit']); ?></div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div class="small text-muted">Số lượng</div>
                                            <div class="fw-bold">x <?php echo h($bk['amount']); ?></div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="small text-muted">Tổng thành tiền</div>
                                            <div class="fw-bold text-primary fs-5">
                                                <?php echo number_format($bk['price'] * $bk['amount'], 0, ',', '.'); ?>
                                                VNĐ</div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($bk['notes'])): ?>
                                <div class="mt-4">
                                    <h6 class="text-uppercase text-secondary fw-bold small mb-2">
                                        <i class="fas fa-sticky-note me-2"></i>Ghi chú
                                    </h6>
                                    <div class="bg-light border rounded p-3 text-muted fst-italic">
                                        <?php echo nl2br(h($bk['notes'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="modal-footer border-top-0 pt-0 pb-4 px-4 bg-transparent">
                                <button type="button" class="btn bg-secondary text-light px-4"
                                    data-bs-dismiss="modal">Đóng</button>
                                <a href="index.php?page=booking-manager&panel=serviceBooking-panel&action=edit&id=<?php echo $bk['booking_service_id']; ?>"
                                    class="btn btn-primary px-4 shadow-sm">
                                    <i class="fas fa-edit me-2"></i>Chỉnh sửa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <?php echo getPagination($totalBookingService, $perPage, $pageNum, $baseUrl); ?>
</div>
<!-- Modal Thêm Booking Dịch Vụ -->
<div class="modal fade" id="addServiceBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?php echo $editBookingService ? 'Sửa' : 'Thêm'; ?> Booking Dịch Vụ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" id="bookingServiceForm">
                <?php if ($editBookingService): ?>
                <input type="hidden" name="booking_service_id"
                    value="<?php echo $editBookingService['booking_service_id']; ?>">
                <?php endif; ?>

                <div class="modal-body px-4 pt-3 pb-4">

                    <!-- Customer Info Section -->
                    <div class="bg-light rounded-3 p-3 mb-4">
                        <label class="text-uppercase text-secondary fw-bold small mb-3">
                            <i class="fas fa-user me-2"></i>Thông tin khách hàng
                        </label>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-muted">Khách hàng <span
                                        class="text-danger">*</span></label>
                                <select class="form-select customer-search shadow-sm rounded-3" name="customer_id"
                                    required id="customerSelect">
                                    <option value="">-- Chọn khách hàng --</option>
                                    <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['customer_id']; ?>"
                                        <?php echo ($editBookingService && $editBookingService['customer_id'] == $customer['customer_id']) ? 'selected' : ''; ?>
                                        data-phone="<?php echo h($customer['phone']); ?>"
                                        data-email="<?php echo h($customer['email']); ?>">
                                        <?php echo h($customer['full_name']); ?> - <?php echo h($customer['phone']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-muted">Số điện thoại <span
                                        class="text-danger">*</span></label>
                                <input type="tel" class="form-control shadow-sm rounded-3" name="phone"
                                    id="customerPhone"
                                    value="<?php echo $editBookingService ? $editBookingService['phone'] : ''; ?>"
                                    required readonly placeholder="Tự động điền...">
                            </div>
                        </div>
                    </div>

                    <?php if ($editBookingService && $editBookingService['has_room_booking']): ?>
                    <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-bed me-3 fs-4"></i>
                        <div>
                            <strong>Liên kết với booking phòng:</strong><br>
                            Phòng: <?php echo h($editBookingService['room_number']); ?> |
                            Check-in: <?php echo date('d/m/Y', strtotime($editBookingService['check_in_date'])); ?>
                        </div>
                    </div>
                    <input type="hidden" name="booking_id" value="<?php echo $editBookingService['booking_id']; ?>">
                    <?php else: ?>
                    <input type="hidden" name="booking_id" value="">
                    <?php endif; ?>

                    <!-- Services Section -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="text-uppercase text-secondary fw-bold small mb-0">
                                <i class="fas fa-concierge-bell me-2"></i>Danh sách dịch vụ
                            </label>
                            <?php if (!$editBookingService): ?>
                            <button type="button" class="btn btn-sm btn-success shadow-sm" onclick="addServiceRow()">
                                <i class="fas fa-plus me-1"></i> Thêm Dịch Vụ
                            </button>
                            <?php endif; ?>
                        </div>

                        <div id="servicesContainer">
                            <!-- Service Row Template -->
                            <div class="service-row card shadow-sm border-0 mb-3 bg-light" data-row-index="0">
                                <div class="card-body p-3">
                                    <div
                                        class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                        <strong class="text-primary"><i class="fas fa-receipt me-2"></i>Dịch Vụ
                                            #1</strong>
                                        <?php if (!$editBookingService): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0"
                                            onclick="removeServiceRow(this)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">Chọn dịch vụ <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select service-select shadow-sm rounded-3"
                                                name="service_id[]" required>
                                                <option value="">-- Chọn dịch vụ --</option>
                                                <?php foreach ($services as $service): ?>
                                                <option value="<?php echo $service['service_id']; ?>"
                                                    <?php echo ($editBookingService && $editBookingService['service_id'] == $service['service_id']) ? 'selected' : ''; ?>
                                                    data-price="<?php echo $service['price']; ?>">
                                                    <?php echo $service['service_name']; ?> -
                                                    <?php echo number_format($service['price'], 0, ',', '.'); ?> VNĐ
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold small text-muted">Số người <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" name="quantity[]"
                                                class="form-control shadow-sm rounded-3" min="1"
                                                value="<?php echo $editBookingService ? h($editBookingService['quantity']) : '1'; ?>"
                                                required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold small text-muted">Số lượng <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" name="amount[]" min="1"
                                                class="form-control shadow-sm rounded-3"
                                                value="<?php echo $editBookingService ? $editBookingService['amount'] : '1'; ?>"
                                                required>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Ngày sử dụng <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="usage_date[]"
                                                class="form-control shadow-sm rounded-3" required
                                                value="<?php echo $editBookingService ? $editBookingService['usage_date'] : ''; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Giờ sử dụng <span
                                                    class="text-danger">*</span></label>
                                            <input type="time" name="usage_time[]"
                                                class="form-control shadow-sm rounded-3" required
                                                value="<?php echo $editBookingService ? $editBookingService['usage_time'] : ''; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Trạng thái <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select shadow-sm rounded-3" name="status[]" required>
                                                <option value="pending" class="text-danger"
                                                    <?php echo ($editBookingService && $editBookingService['status'] == 'pending') ? 'selected' : ''; ?>>
                                                    Chưa thanh toán
                                                </option>
                                                <option value="confirmed" class="text-success"
                                                    <?php echo ($editBookingService && $editBookingService['status'] == 'confirmed') ? 'selected' : ''; ?>>
                                                    Đã thanh toán (Tạo hóa đơn)
                                                </option>
                                                <option value="cancelled" class="text-secondary"
                                                    <?php echo ($editBookingService && $editBookingService['status'] == 'cancelled') ? 'selected' : ''; ?>>
                                                    Đã hủy
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold small text-muted">Ghi chú</label>
                                            <textarea class="form-control form-control-sm shadow-sm" name="note[]"
                                                rows="2"
                                                placeholder="Ghi chú thêm..."><?php echo $editBookingService ? h($editBookingService['notes']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn text-light bg-secondary px-4" data-bs-dismiss="modal">Hủy
                        bỏ</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm"
                        name="<?php echo $editBookingService ? 'update_service_booking' : 'add_booking_service'; ?>">
                        <i class="fas fa-save me-2"></i><?php echo $editBookingService ? 'Cập nhật' : 'Thêm'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Đảm bảo input Select2 có thể gõ được */
.select2-search__field {
    width: 100% !important;
    border: none !important;
    outline: none !important;
    padding: 5px !important;
    margin: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
}

.select2-search__field:focus {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da !important;
    border-radius: 0.375rem !important;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
    border-color: #86b7fe !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}
</style>

<script>
// Tự động mở modal edit khi có action=edit
<?php if ($editBookingService): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Populate form with edit data
    const form = document.querySelector('#addServiceBookingModal form');
    if (form) {
        form.querySelector('select[name="customer_id"]').value =
            '<?php echo $editBookingService['customer_id']; ?>';
        form.querySelector('input[name="phone"]').value = '<?php echo h($editBookingService['phone']); ?>';
        form.querySelector('select[name="service_id[]"]').value =
            '<?php echo $editBookingService['service_id']; ?>';
        form.querySelector('input[name="quantity[]"]').value =
            '<?php echo h($editBookingService['quantity']); ?>';
        form.querySelector('input[name="amount[]"]').value = '<?php echo h($editBookingService['amount']); ?>';
        form.querySelector('input[name="usage_date[]"]').value =
            '<?php echo $editBookingService['usage_date']; ?>';
        form.querySelector('input[name="usage_time[]"]').value =
            '<?php echo $editBookingService['usage_time']; ?>';
        form.querySelector('select[name="status[]"]').value = '<?php echo h($editBookingService['status']); ?>';
        form.querySelector('textarea[name="note[]"]').value =
            '<?php echo h($editBookingService['notes'] ?? ''); ?>';

        // Update modal title and button
        const modalTitle = document.querySelector('#addServiceBookingModal .modal-title');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (modalTitle) modalTitle.textContent = 'Sửa Booking dịch vụ';
        if (submitBtn) {
            submitBtn.name = 'update_service_booking';
            submitBtn.textContent = 'Cập nhật';
        }
    }

    const modal = new bootstrap.Modal(document.getElementById('addServiceBookingModal'));
    modal.show();
    // Khởi tạo lại Select2 sau khi modal mở hoàn toàn
    const modalEl = document.getElementById('addServiceBookingModal');
    modalEl.addEventListener('shown.bs.modal', function() {
        setTimeout(initCustomerSelect2, 200);
    }, {
        once: true
    });
});
<?php endif; ?>

// Khởi tạo lại Select2 khi modal mở


// Hàm khởi tạo Select2 cho customer
function initCustomerSelect2() {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
        return false;
    }

    const $customerSelect = jQuery('#customerSelect');
    if (!$customerSelect.length) {
        return false;
    }

    // Destroy nếu đã khởi tạo trước đó
    if ($customerSelect.hasClass('select2-hidden-accessible')) {
        $customerSelect.select2('destroy');
    }

    // Lấy modal để set dropdownParent
    const $modal = jQuery('#addServiceBookingModal');
    const dropdownParent = $modal.length ? $modal : jQuery('body');

    $customerSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Chọn khách hàng --',
        allowClear: true,
        minimumInputLength: 0,
        width: '100%',
        dropdownParent: dropdownParent,
        language: {
            noResults: function() {
                return "Không tìm thấy khách hàng";
            },
            searching: function() {
                return "Đang tìm kiếm...";
            }
        }
    });

    // Tự động điền số điện thoại khi chọn khách hàng
    $customerSelect.off('change.select2-customer').on('change.select2-customer', function() {
        const selectedOption = jQuery(this).find('option:selected');
        const phone = selectedOption.data('phone') || '';
        jQuery('#customerPhone').val(phone);
    });

    // Đảm bảo input tìm kiếm có thể gõ được
    $customerSelect.on('select2:open', function() {
        setTimeout(function() {
            const $searchField = jQuery('.select2-search__field');
            $searchField.attr('placeholder', 'Gõ để tìm kiếm...');
            $searchField.prop('readonly', false);
            $searchField.prop('disabled', false);
            $searchField.focus();
        }, 100);
    });

    return true;
}
// Tự động tính tổng tiền


// Thêm row dịch vụ mới
let serviceRowIndex = 1;

function addServiceRow() {
    const container = document.getElementById('servicesContainer');
    const firstRow = container.querySelector('.service-row');
    if (!firstRow) return;

    const newRow = firstRow.cloneNode(true);
    const rowIndex = serviceRowIndex++;

    // Cập nhật số thứ tự
    newRow.querySelector('strong').innerHTML = '<i class="fas fa-receipt me-2"></i>Dịch Vụ #' + (rowIndex + 1);
    newRow.setAttribute('data-row-index', rowIndex);

    // Reset các giá trị
    newRow.querySelector('.service-select').value = '';
    newRow.querySelector('input[name="quantity[]"]').value = '1';
    newRow.querySelector('input[name="amount[]"]').value = '1';
    newRow.querySelector('input[name="usage_date[]"]').value = '';
    newRow.querySelector('input[name="usage_time[]"]').value = '';
    newRow.querySelector('select[name="status[]"]').value = 'pending';
    newRow.querySelector('textarea[name="note[]"]').value = '';

    // Đảm bảo nút xóa luôn hiển thị (trừ khi edit)
    const removeBtn = newRow.querySelector('button[onclick*="removeServiceRow"]');
    if (removeBtn) {
        removeBtn.style.display = 'block';
    }

    container.appendChild(newRow);

    // Re-initialize Select2 cho select mới (nếu có)
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery(newRow.querySelector('.service-select')).select2({
            placeholder: '-- Chọn dịch vụ --',
            allowClear: true,
            width: '100%',
            dropdownParent: jQuery('#addServiceBookingModal')
        });
    }
}

// Xóa row dịch vụ
function removeServiceRow(button) {
    const container = document.getElementById('servicesContainer');
    const rows = container.querySelectorAll('.service-row');

    // Không cho xóa nếu chỉ còn 1 row
    if (rows.length <= 1) {
        alert('Phải có ít nhất một dịch vụ');
        return;
    }

    const row = button.closest('.service-row');
    if (row) {
        row.remove();

        // Cập nhật lại số thứ tự
        const remainingRows = container.querySelectorAll('.service-row');
        remainingRows.forEach((r, index) => {
            r.querySelector('strong').innerHTML = '<i class="fas fa-receipt me-2"></i>Dịch Vụ #' + (index + 1);
        });
    }
}

function editServiceBooking(id) {
    window.location.href = 'index.php?page=booking-manager&panel=serviceBooking-panel&action=edit&id=' + id;
}

function deleteServiceBooking(id) {
    if (typeof showDeleteModal === 'function') {
        showDeleteModal(id, 'service');
    } else {
        // Fallback if modal function is not available
        if (confirm('Bạn có chắc chắn muốn xóa booking dịch vụ này?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="booking_service_id" value="' + id + '">' +
                '<input type="hidden" name="delete_booking_service" value="1">';
            document.body.appendChild(form);
            form.submit();
        }
    }
}

// Hàm reset form booking dịch vụ
function resetServiceBookingForm() {
    const modal = document.getElementById('addServiceBookingModal');
    if (!modal) return;

    const form = modal.querySelector('form');
    if (!form) return;

    // Clear URL params
    const url = new URL(window.location);
    url.searchParams.delete('action');
    url.searchParams.delete('id');
    window.history.replaceState({}, '', url);

    form.reset();

    // Reset specific inputs
    form.querySelectorAll(
        'input, textarea, select'
    ).forEach(input => {
        if (input.name !== 'page' && input.name !== 'panel' && input.id !== 'customerSelect') {
            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else {
                if (input.name === 'quantity[]' || input.name === 'amount[]') {
                    input.value = '1';
                } else if (input.name === 'status[]') {
                    input.value = 'pending';
                } else {
                    input.value = '';
                }
            }
        }
    });

    // Reset Select2 for customer
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        const $customerSelect = jQuery('#customerSelect');
        if ($customerSelect.length) {
            $customerSelect.val(null).trigger('change');
        }
    }

    // Reset service rows
    const container = document.getElementById('servicesContainer');
    if (container) {
        const rows = container.querySelectorAll('.service-row');
        rows.forEach((row, index) => {
            if (index > 0) {
                row.remove();
            } else {
                // Reset first row
                const serviceSelect = row.querySelector('.service-select');
                if (serviceSelect) {
                    serviceSelect.value = '';
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                        jQuery(serviceSelect).val('').trigger('change');
                    }
                }

                const quantityInput = row.querySelector('input[name="quantity[]"]');
                if (quantityInput) quantityInput.value = '1';

                const amountInput = row.querySelector('input[name="amount[]"]');
                if (amountInput) amountInput.value = '1';

                const usageDateInput = row.querySelector('input[name="usage_date[]"]');
                if (usageDateInput) usageDateInput.value = '';

                const usageTimeInput = row.querySelector('input[name="usage_time[]"]');
                if (usageTimeInput) usageTimeInput.value = '';

                const statusSelect = row.querySelector('select[name="status[]"]');
                if (statusSelect) statusSelect.value = 'pending';

                const noteTextarea = row.querySelector('textarea[name="note[]"]');
                if (noteTextarea) noteTextarea.value = '';
            }
        });
    }

    // Reset modal title and button
    const modalTitle = modal.querySelector('.modal-title');
    const submitBtn = form.querySelector('button[type="submit"]');
    if (modalTitle) modalTitle.textContent = 'Thêm Booking dịch vụ';
    if (submitBtn) {
        submitBtn.name = 'add_booking_service';
        submitBtn.textContent = 'Thêm Booking';
    }
}

// Auto-reset when modal is closed or when "Add" button is clicked
// Consolidated DOMContentLoaded listener
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addServiceBookingModal');
    
    // Initialize Select2 if not in modal (page load)
    if (typeof jQuery !== 'undefined' && typeof initCustomerSelect2 === 'function') {
        jQuery(document).ready(function() {
            setTimeout(initCustomerSelect2, 300);
        });
    }

    if (modal) {
        // Initialize Select2 when modal is shown
        modal.addEventListener('shown.bs.modal', function() {
             // Re-init select2 with small delay to ensure modal is fully visible
            setTimeout(initCustomerSelect2, 200);
        });

        // Clear URL and reset form when modal is closed (no reload needed)
        modal.addEventListener('hidden.bs.modal', function() {
            resetServiceBookingForm();
        });

        // Reset form when modal opens if not in edit mode
        modal.addEventListener('show.bs.modal', function() {
            const isEditMode = window.location.search.includes('action=edit');
            if (!isEditMode) {
                resetServiceBookingForm();
            }
        });
    }

    // Reset form when "Add" button is clicked
    const addButton = document.querySelector('[data-bs-target="#addServiceBookingModal"]');
    if (addButton) {
        addButton.addEventListener('click', function() {
            resetServiceBookingForm();
        });
    }
});
</script>