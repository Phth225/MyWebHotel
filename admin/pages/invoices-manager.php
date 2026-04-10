<?php
// Phân quyền module Hóa Đơn
$canViewInvoice   = function_exists('checkPermission') ? checkPermission('invoice.view')   : true;
$canCreateInvoice = function_exists('checkPermission') ? checkPermission('invoice.create') : true;
$canEditInvoice   = function_exists('checkPermission') ? checkPermission('invoice.edit')   : true;
$canDeleteInvoice = function_exists('checkPermission') ? checkPermission('invoice.delete') : true;

if (!$canViewInvoice) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem trang hóa đơn.</div></div>';
    return;
}

// Xử lý CRUD trong invoice-manager
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Thêm hóa đơn
    if (isset($_POST['add_invoice'])) {
        $booking_id = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $room_charge = floatval($_POST['room_charge']);
        $service_charge = floatval($_POST['service_charge']);
        $vat = floatval($_POST['vat']);
        $other_fees = floatval($_POST['other_fees']);
        $total_amount = $room_charge + $service_charge + $vat + $other_fees;
        $deposit_amount = floatval($_POST['deposit_amount'] ?? 0);
        $remaining_amount = $total_amount - $deposit_amount;
        $payment_method = trim($_POST['payment_method']);
        $status = $_POST['status'] ?? 'Unpaid';
        $payment_time = !empty($_POST['payment_time']) ? $_POST['payment_time'] : null;
        $note = trim($_POST['note'] ?? '');

        // Nếu có booking_id, lấy customer_id từ booking
        if ($booking_id) {
            $stmt_get_customer = $mysqli->prepare("SELECT customer_id FROM booking WHERE booking_id = ?");
            $stmt_get_customer->bind_param("i", $booking_id);
            $stmt_get_customer->execute();
            $result = $stmt_get_customer->get_result();
            $booking_data = $result->fetch_assoc();
            $customer_id = $booking_data['customer_id'] ?? $customer_id;
            $stmt_get_customer->close();
        }

        // Kiểm tra customer_id
        if (!$customer_id || $customer_id <= 0) {
            $message = 'Lỗi: Khách hàng không được để trống!';
            $messageType = 'danger';
        } else {
            // INSERT với booking_id có thể NULL
            if ($booking_id) {
                $stmt = $mysqli->prepare("INSERT INTO invoice (booking_id, customer_id, room_charge, service_charge, vat, other_fees, total_amount, deposit_amount, remaining_amount, payment_method, status, payment_time, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiddddddssss", $booking_id, $customer_id, $room_charge, $service_charge, $vat, $other_fees, $total_amount, $deposit_amount, $remaining_amount, $payment_method, $status, $payment_time, $note);
            } else {
                $stmt = $mysqli->prepare("INSERT INTO invoice (booking_id, customer_id, room_charge, service_charge, vat, other_fees, total_amount, deposit_amount, remaining_amount, payment_method, status, payment_time, note) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iddddddssss", $customer_id, $room_charge, $service_charge, $vat, $other_fees, $total_amount, $deposit_amount, $remaining_amount, $payment_method, $status, $payment_time, $note);
            }

            if ($stmt->execute()) {
                // Tự động cập nhật hạng khách hàng nếu invoice được tạo với status = Paid
                if ($status === 'Paid' && $customer_id > 0) {
                    require_once __DIR__ . '/../includes/customer_rank_helper.php';
                    $rankResult = updateCustomerRank($mysqli, $customer_id);
                    if ($rankResult['success'] && $rankResult['old_rank'] !== $rankResult['new_rank']) {
                        error_log("Customer rank auto-updated: Customer ID {$customer_id} - {$rankResult['old_rank']} -> {$rankResult['new_rank']}");
                    }
                }
                
                $message = 'Thêm hóa đơn thành công!';
                $messageType = 'success';
                $action = '';
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }

    // Cập nhật hóa đơn
    if (isset($_POST['update_invoice'])) {
        $invoice_id = intval($_POST['invoice_id']);
        $booking_id = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $room_charge = floatval($_POST['room_charge']);
        $service_charge = floatval($_POST['service_charge']);
        $vat = floatval($_POST['vat']);
        $other_fees = floatval($_POST['other_fees']);
        $total_amount = $room_charge + $service_charge + $vat + $other_fees;
        $deposit_amount = floatval($_POST['deposit_amount'] ?? 0);
        $remaining_amount = $total_amount - $deposit_amount;
        $payment_method = trim($_POST['payment_method']);
        $status = $_POST['status'] ?? 'Unpaid';
        $payment_time = !empty($_POST['payment_time']) ? $_POST['payment_time'] : null;
        $note = trim($_POST['note'] ?? '');

        // Nếu không có customer_id từ POST, lấy từ invoice hiện tại (cho Service Only)
        if (!$customer_id || $customer_id <= 0) {
            $stmt_get_invoice = $mysqli->prepare("SELECT customer_id FROM invoice WHERE invoice_id = ? AND deleted IS NULL");
            $stmt_get_invoice->bind_param("i", $invoice_id);
            $stmt_get_invoice->execute();
            $invoice_result = $stmt_get_invoice->get_result();
            if ($invoice_data = $invoice_result->fetch_assoc()) {
                $customer_id = $invoice_data['customer_id'] ?? 0;
            }
            $stmt_get_invoice->close();
        }

        // Nếu có booking_id, lấy customer_id từ booking (ưu tiên booking)
        if ($booking_id) {
            $stmt_get_customer = $mysqli->prepare("SELECT customer_id FROM booking WHERE booking_id = ? AND deleted IS NULL");
            $stmt_get_customer->bind_param("i", $booking_id);
            $stmt_get_customer->execute();
            $result = $stmt_get_customer->get_result();
            $booking_data = $result->fetch_assoc();
            if ($booking_data && !empty($booking_data['customer_id'])) {
                $customer_id = $booking_data['customer_id'];
            }
            $stmt_get_customer->close();
        }

        // Kiểm tra customer_id
        if (!$customer_id || $customer_id <= 0) {
            $message = 'Lỗi: Khách hàng không được để trống!';
            $messageType = 'danger';
        } else {
            // UPDATE với booking_id có thể NULL
            $stmt = $mysqli->prepare("UPDATE invoice SET booking_id=?, customer_id=?, room_charge=?, service_charge=?, vat=?, other_fees=?, total_amount=?, deposit_amount=?, remaining_amount=?, payment_method=?, status=?, payment_time=?, note=? WHERE invoice_id=? AND deleted IS NULL");
            $stmt->bind_param("iiddddddssssi", $booking_id, $customer_id, $room_charge, $service_charge, $vat, $other_fees, $total_amount, $deposit_amount, $remaining_amount, $payment_method, $status, $payment_time, $note, $invoice_id);

            if ($stmt->execute()) {
                // Tự động cập nhật hạng khách hàng nếu invoice được thanh toán
                if ($status === 'Paid' && $customer_id > 0) {
                    require_once __DIR__ . '/../includes/customer_rank_helper.php';
                    $rankResult = updateCustomerRank($mysqli, $customer_id);
                    if ($rankResult['success'] && $rankResult['old_rank'] !== $rankResult['new_rank']) {
                        error_log("Customer rank auto-updated: Customer ID {$customer_id} - {$rankResult['old_rank']} -> {$rankResult['new_rank']}");
                    }
                }
                
                $message = 'Cập nhật hóa đơn thành công!';
                $messageType = 'success';
                $action = '';
                header("Location: index.php?page=invoices-manager&success=1");
                exit;
            } else {
                $message = 'Lỗi: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }

    // Xóa hóa đơn
    if (isset($_POST['delete_invoice'])) {
        $invoice_id = intval($_POST['invoice_id']);
        $stmt = $mysqli->prepare("UPDATE invoice SET deleted = NOW() WHERE invoice_id = ?");
        $stmt->bind_param("i", $invoice_id);

        if ($stmt->execute()) {
            $message = 'Xóa hóa đơn thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Lấy thông tin hóa đơn để edit
$editInvoice = null;
$editBookingInfo = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM invoice WHERE invoice_id = ? AND deleted IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editInvoice = $result->fetch_assoc();
    $stmt->close();

    // Nếu có booking_id, lấy thông tin booking để hiển thị
    if ($editInvoice && $editInvoice['booking_id']) {
        $booking_stmt = $mysqli->prepare("
            SELECT b.booking_id, b.check_in_date, b.check_out_date,
                   COALESCE(c.full_name, w.full_name) as full_name,
                   COALESCE(c.phone, w.phone) as phone,
                   r.room_number, rt.room_type_name
            FROM booking b
            LEFT JOIN customer c ON b.customer_id = c.customer_id
            LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
            LEFT JOIN room r ON b.room_id = r.room_id
            LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
            WHERE b.booking_id = ? AND b.deleted IS NULL
        ");
        $booking_stmt->bind_param("i", $editInvoice['booking_id']);
        $booking_stmt->execute();
        $booking_result = $booking_stmt->get_result();
        $editBookingInfo = $booking_result->fetch_assoc();
        $booking_stmt->close();
    }
}

// Lấy danh sách booking chưa có hóa đơn (chỉ booking phòng)
// Cũng lấy cả booking đã có hóa đơn để có thể tạo hóa đơn mới nếu cần
$bookingsWithoutInvoice = [];
$bookingsQuery = $mysqli->query("
    SELECT b.booking_id, b.check_in_date, b.check_out_date, 
           COALESCE(c.full_name, w.full_name) as full_name,
           COALESCE(c.phone, w.phone) as phone,
           COALESCE(c.email, w.email) as email,
           r.room_number,
           rt.room_type_name,
           CASE WHEN i.invoice_id IS NULL THEN 0 ELSE 1 END as has_invoice
    FROM booking b
    LEFT JOIN customer c ON b.customer_id = c.customer_id
    LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
    INNER JOIN room r ON b.room_id = r.room_id
    INNER JOIN room_type rt ON r.room_type_id = rt.room_type_id
    LEFT JOIN invoice i ON b.booking_id = i.booking_id AND i.deleted IS NULL
    WHERE b.deleted IS NULL 
    AND b.status NOT IN ('Cancelled')
    ORDER BY has_invoice ASC, b.check_in_date DESC
    LIMIT 100
");
if ($bookingsQuery) {
    $bookingsWithoutInvoice = $bookingsQuery->fetch_all(MYSQLI_ASSOC);
}

// Phân trang và tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum);
$perPage = 5;
$offset = ($pageNum - 1) * $perPage;

// Xây dựng WHERE clause
$where = "WHERE i.deleted IS NULL";
$params = [];
$types = '';
if ($search) {
    $where .= " AND (b.booking_id LIKE ? OR c.full_name LIKE ? OR i.invoice_id LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= 'isi';
}

if ($status_filter) {
    $where .= " AND i.status=?";
    $params[] = $status_filter;
    $types .= 's';
}

// Xây dựng ORDER BY
$orderBy = "ORDER BY i.created_at DESC";
switch ($sort) {
    case 'oldest':
        $orderBy = "ORDER BY i.created_at ASC";
        break;
    case 'amount_high':
        $orderBy = "ORDER BY i.total_amount DESC";
        break;
    case 'amount_low':
        $orderBy = "ORDER BY i.total_amount ASC";
        break;
    case 'newest':
    default:
        $orderBy = "ORDER BY i.created_at DESC";
        break;
}

// Đếm tổng số
$countQuery = "SELECT COUNT(DISTINCT i.invoice_id) as total 
    FROM invoice i 
    LEFT JOIN booking b ON i.booking_id = b.booking_id
    LEFT JOIN customer c ON i.customer_id = c.customer_id
    $where";
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

// Lấy dữ liệu
$invoices = [];
if ($total > 0) {
    // Lấy đầy đủ thông tin invoice, customer, booking, room
    // Với Service Only: lấy tên từ invoice.customer_id hoặc walk_in_guest thông qua booking_service
    // Với invoice có booking: ưu tiên lấy từ customer hoặc walk_in_guest thông qua booking
    $query = "SELECT i.invoice_id, i.booking_id, i.customer_id, i.discount,
               i.room_charge, i.service_charge, i.vat, i.other_fees,
               i.total_amount, i.deposit_amount, i.remaining_amount,
               i.payment_method, i.status, i.payment_time, i.note, i.created_at,
               COALESCE(
                   NULLIF(c.full_name, ''), 
                   NULLIF(bc.full_name, ''), 
                   NULLIF(w.full_name, ''), 
                   NULLIF(w2.full_name, ''),
                   (SELECT w3.full_name 
                    FROM invoice_service isv3 
                    INNER JOIN booking_service bs3 ON isv3.booking_service_id = bs3.booking_service_id AND bs3.deleted IS NULL
                    LEFT JOIN customer c3 ON bs3.customer_id = c3.customer_id AND bs3.customer_id IS NOT NULL
                    LEFT JOIN walk_in_guest w3 ON bs3.walk_in_guest_id = w3.id
                    WHERE isv3.invoice_id = i.invoice_id
                    LIMIT 1),
                   ''
               ) as full_name,
               COALESCE(
                   NULLIF(c.phone, ''), 
                   NULLIF(bc.phone, ''), 
                   NULLIF(w.phone, ''), 
                   NULLIF(w2.phone, ''),
                   (SELECT COALESCE(c3.phone, w3.phone)
                    FROM invoice_service isv3 
                    INNER JOIN booking_service bs3 ON isv3.booking_service_id = bs3.booking_service_id AND bs3.deleted IS NULL
                    LEFT JOIN customer c3 ON bs3.customer_id = c3.customer_id AND bs3.customer_id IS NOT NULL
                    LEFT JOIN walk_in_guest w3 ON bs3.walk_in_guest_id = w3.id
                    WHERE isv3.invoice_id = i.invoice_id
                    LIMIT 1),
                   ''
               ) as phone,
               COALESCE(
                   NULLIF(c.email, ''), 
                   NULLIF(bc.email, ''), 
                   NULLIF(w.email, ''), 
                   NULLIF(w2.email, ''),
                   (SELECT COALESCE(c3.email, w3.email)
                    FROM invoice_service isv3 
                    INNER JOIN booking_service bs3 ON isv3.booking_service_id = bs3.booking_service_id AND bs3.deleted IS NULL
                    LEFT JOIN customer c3 ON bs3.customer_id = c3.customer_id AND bs3.customer_id IS NOT NULL
                    LEFT JOIN walk_in_guest w3 ON bs3.walk_in_guest_id = w3.id
                    WHERE isv3.invoice_id = i.invoice_id
                    LIMIT 1),
                   ''
               ) as email,
               CASE 
                   WHEN (i.customer_id IS NULL OR i.customer_id = 0) OR b.walk_in_guest_id IS NOT NULL OR bs.walk_in_guest_id IS NOT NULL 
                   THEN 'Walk-in' 
                   ELSE 'Registered' 
               END as guest_type,
               b.check_in_date, b.check_out_date,
               r.room_number, rt.room_type_name
        FROM invoice i 
        LEFT JOIN customer c ON i.customer_id = c.customer_id AND i.customer_id IS NOT NULL AND i.customer_id > 0
        LEFT JOIN booking b ON i.booking_id = b.booking_id AND (b.deleted IS NULL OR b.deleted = '')
        LEFT JOIN customer bc ON b.customer_id = bc.customer_id
        LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
        -- JOIN với walk_in_guest thông qua invoice_service -> booking_service cho service-only invoices
        LEFT JOIN invoice_service isv ON isv.invoice_id = i.invoice_id
        LEFT JOIN booking_service bs ON isv.booking_service_id = bs.booking_service_id AND bs.deleted IS NULL
        LEFT JOIN walk_in_guest w2 ON bs.walk_in_guest_id = w2.id
        LEFT JOIN room r ON b.room_id = r.room_id
        LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
        $where 
        $orderBy 
        LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $mysqli->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $invoices = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Đảm bảo tất cả invoice đều có tên khách hàng (fallback nếu JOIN không lấy được)
    foreach ($invoices as &$inv) {
        // Bước 1: Nếu không có tên nhưng có customer_id, thử lấy lại từ customer (không kiểm tra deleted)
        if (empty($inv['full_name']) && !empty($inv['customer_id'])) {
            $customer_stmt = $mysqli->prepare("SELECT full_name, phone, email FROM customer WHERE customer_id = ?");
            $customer_stmt->bind_param("i", $inv['customer_id']);
            $customer_stmt->execute();
            $customer_result = $customer_stmt->get_result();
            if ($customer = $customer_result->fetch_assoc()) {
                if (!empty($customer['full_name'])) {
                    $inv['full_name'] = $customer['full_name'];
                    $inv['phone'] = $customer['phone'] ?? '';
                    $inv['email'] = $customer['email'] ?? '';
                }
            }
            $customer_stmt->close();
        }
        
        // Bước 2: Nếu vẫn không có tên và có booking_id, thử lấy từ booking's customer hoặc walk_in_guest
        if (empty($inv['full_name']) && !empty($inv['booking_id'])) {
            $booking_customer_stmt = $mysqli->prepare("
                SELECT COALESCE(c.full_name, w.full_name) as full_name,
                       COALESCE(c.phone, w.phone) as phone,
                       COALESCE(c.email, w.email) as email
                FROM booking b
                LEFT JOIN customer c ON b.customer_id = c.customer_id
                LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
                WHERE b.booking_id = ? AND b.deleted IS NULL
            ");
            $booking_customer_stmt->bind_param("i", $inv['booking_id']);
            $booking_customer_stmt->execute();
            $booking_customer_result = $booking_customer_stmt->get_result();
            if ($booking_customer = $booking_customer_result->fetch_assoc()) {
                if (!empty($booking_customer['full_name'])) {
                    $inv['full_name'] = $booking_customer['full_name'];
                    $inv['phone'] = $booking_customer['phone'] ?? '';
                    $inv['email'] = $booking_customer['email'] ?? '';
                }
            }
            $booking_customer_stmt->close();
        }
        
        // Bước 3: Nếu vẫn không có tên và không có booking_id (service-only invoice), thử lấy từ booking_service
        if (empty($inv['full_name']) && empty($inv['booking_id'])) {
            $service_customer_stmt = $mysqli->prepare("
                SELECT COALESCE(c.full_name, w.full_name) as full_name,
                       COALESCE(c.phone, w.phone) as phone,
                       COALESCE(c.email, w.email) as email
                FROM invoice_service isv
                INNER JOIN booking_service bs ON isv.booking_service_id = bs.booking_service_id AND bs.deleted IS NULL
                LEFT JOIN customer c ON bs.customer_id = c.customer_id AND bs.customer_id IS NOT NULL
                LEFT JOIN walk_in_guest w ON bs.walk_in_guest_id = w.id
                WHERE isv.invoice_id = ?
                LIMIT 1
            ");
            $service_customer_stmt->bind_param("i", $inv['invoice_id']);
            $service_customer_stmt->execute();
            $service_customer_result = $service_customer_stmt->get_result();
            if ($service_customer = $service_customer_result->fetch_assoc()) {
                if (!empty($service_customer['full_name'])) {
                    $inv['full_name'] = $service_customer['full_name'];
                    $inv['phone'] = $service_customer['phone'] ?? '';
                    $inv['email'] = $service_customer['email'] ?? '';
                }
            }
            $service_customer_stmt->close();
        }
        
        // Debug log (có thể xóa sau)
        if (empty($inv['full_name'])) {
            error_log("Invoice ID: " . $inv['invoice_id'] . " - No customer name found. Customer ID: " . ($inv['customer_id'] ?? 'NULL') . ", Booking ID: " . ($inv['booking_id'] ?? 'NULL'));
        }
    }
    unset($inv);
    
    // Lấy danh sách dịch vụ cho mỗi invoice
    foreach ($invoices as &$invoice) {
        $invoice_id = $invoice['invoice_id'];
        $services_stmt = $mysqli->prepare("
            SELECT bs.booking_service_id,
                   s.service_name,
                   s.service_type,
                   bs.quantity,
                   bs.unit_price,
                   bs.amount,
                   bs.unit,
                   (bs.amount * bs.unit_price) as total_price
            FROM invoice_service isv
            INNER JOIN booking_service bs ON isv.booking_service_id = bs.booking_service_id AND bs.deleted IS NULL
            INNER JOIN service s ON bs.service_id = s.service_id
            WHERE isv.invoice_id = ?
            ORDER BY bs.booking_service_id
        ");
        $services_stmt->bind_param("i", $invoice_id);
        $services_stmt->execute();
        $services_result = $services_stmt->get_result();
        $invoice['services'] = [];
        while ($service = $services_result->fetch_assoc()) {
            $invoice['services'][] = $service;
        }
        $services_stmt->close();
    }
    unset($invoice); // Unset reference
}

// Helper functions cho format
function formatInvoiceDate($dateStr) {
    if (!$dateStr || $dateStr === '0000-00-00 00:00:00' || $dateStr === '0000-00-00' || $dateStr === null) {
        return '-';
    }
    try {
        $date = new DateTime($dateStr);
        return $date->format('d/m/Y H:i');
    } catch (Exception $e) {
        return '-';
    }
}

function formatInvoiceMoney($amount) {
    return number_format($amount ?? 0, 0, ',', '.') . ' VNĐ';
}

// Build base URL for pagination
$baseUrl = "index.php?page=invoices-manager";
if ($search) $baseUrl .= "&search=" . urlencode($search);
if ($status_filter) $baseUrl .= "&status=" . urlencode($status_filter);
if ($sort) $baseUrl .= "&sort=" . urlencode($sort);

function formatPaymentMethod($method){
    switch ($method) {
        case 'Credit Card':
            return 'Thẻ tín dụng';
        case 'Cash':
            return 'Tiền mặt';
        case 'Bank Transfer':
            return 'Chuyển khoản';
        case 'E-Wallet':
            return 'Ví điện tử';
        default:
            return '-';
    }
}
?>

<div class="main-content">
    <div class="content-header">
        <h1>Quản lý hóa đơn</h1>
        <div class="row m-3">
            <div class="col-md-12">
                <button class="btn-add " data-bs-toggle="modal" data-bs-target="#addInvoiceModal"
                    onclick="resetInvoiceForm()">
                    <i class="fas fa-plus"></i> Thêm Hóa Đơn
                </button>
            </div>
        </div>
        <!-- Tabs loại hóa đơn -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo h($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    </div>

    <div class="tab-content">
        <!-- Bảng: Hóa đơn phòng -->
        <div class="filter-section ">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="invoices-manager">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" name="search" placeholder="Tìm kiếm hóa đơn..."
                                value="<?php echo h($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter" name="status">
                            <option value="">Tất cả tình trạng</option>
                            <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Đã thanh toán
                            </option>
                            <option value="Unpaid" <?php echo $status_filter == 'Unpaid' ? 'selected' : ''; ?>>Chưa thanh toán</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select">
                            <option value="all-invoices">Tất cả hóa đơn</option>
                            <option value="room-invoices">Hóa đơn phòng</option>
                            <option value="service-invoice">Hóa đơn dịch vụ</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="table table-hover" id="invoiceTable">
                <thead>
                    <tr>
                        <th>Mã HĐ</th>
                        <th>Booking ID</th>
                        <th>Khách Hàng</th>
                        <th>Tổng Tiền</th>
                        <th>Hình Thức TT</th>
                        <th>Tình Trạng</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="9" class="text-center">Không có dữ liệu</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($invoices as $invoice): ?>
                    <tr data-invoice-id="<?php echo $invoice['invoice_id']; ?>">
                        <td><?php echo $invoice['invoice_id']; ?></td>
                        <td>
                            <?php
                                    if ($invoice['booking_id']) {
                                        echo $invoice['booking_id'];
                                    } else {
                                        echo '<span class="text-muted">Service Only</span>';
                                    }
                                    ?>
                        </td>
                        <td><?php echo h($invoice['full_name'] ?? ''); ?></td>
                        <td><strong><?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?> VNĐ</strong>
                        </td>
                        <td><?php echo formatPaymentMethod(h($invoice['payment_method'])); ?></td>
                        <td>
                            <?php
                                    $badgeClass = 'badge';
                                    if ($invoice['status'] == 'Paid') $badgeClass = 'badge bg-success';
                                    elseif ($invoice['status'] == 'Unpaid') $badgeClass = 'badge bg-danger';
                                    // Removed Partial status handling
                                    ?>
                            <span class="<?php echo $badgeClass; ?>">
                                <?php
                                        $statusText = [
                                            'Paid' => 'Đã thanh toán',
                                            'Unpaid' => 'Chưa thanh toán',
                                            'Partial' => 'Thanh toán một phần'
                                        ];
                                        echo $statusText[$invoice['status']] ?? $invoice['status'];
                                        ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                                data-bs-target="#viewInvoiceModal<?php echo $invoice['invoice_id']; ?>"
                                title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a
                                href="index.php?page=invoices-manager&action=edit&id=<?php echo $invoice['invoice_id']; ?>">
                                <button class="btn btn-sm btn-outline-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </a>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteInvoice(<?php echo $invoice['invoice_id']; ?>)" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals cho từng invoice - Đặt bên ngoài table -->
    <?php if (!empty($invoices)): ?>
    <?php foreach ($invoices as $invoice): ?>
    <?php
            // Format trạng thái
            $statusMap = [
                'Paid' => 'Đã thanh toán',
                'Unpaid' => 'Chưa thanh toán',
                'Refunded' => 'Đã hoàn tiền'
            ];
            $statusText = $statusMap[$invoice['status']] ?? $invoice['status'];
            ?>
    <!-- Modal Xem Chi Tiết Hóa Đơn -->
    <div class="modal fade" id="viewInvoiceModal<?php echo $invoice['invoice_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- Header -->
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Chi Tiết Hóa Đơn</h5>
                        <small class="text-muted">#<?php echo $invoice['invoice_id']; ?> -
                            <?php echo formatInvoiceDate($invoice['created_at']); ?></small>
                    </div>
                </div>

                <!-- Body -->
                <div class="modal-body">

                    <!-- Thông tin khách hàng -->
                    <div class="info-card mb-3">
                        <div class="info-card-header">
                            <i class="fas fa-user"></i> Thông Tin Khách Hàng
                        </div>
                        <div class="info-card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <span class="info-label">Họ Tên:</span>
                                        <span class="info-value"><?php echo h($invoice['full_name'] ?? '-'); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <span class="info-label">Điện Thoại:</span>
                                        <span class="info-value"><?php echo h($invoice['phone'] ?? '-'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php 
        // Kiểm tra loại hóa đơn
        $hasRoom = !empty($invoice['room_number']) && !empty($invoice['room_type_name']);
        $hasServices = !empty($invoice['services']) && count($invoice['services']) > 0;
        $hasRoomCharge = isset($invoice['room_charge']) && $invoice['room_charge'] > 0;
        
        // Tính tổng tiền dịch vụ thuần từ bảng services
        $pureServiceTotal = 0;
        if ($hasServices) {
            foreach ($invoice['services'] as $service) {
                $pureServiceTotal += ($service['total_price'] ?? 0);
            }
        }
        ?>

                    <!-- Thông tin phòng - chỉ hiện khi có đặt phòng -->
                    <?php if ($hasRoom): ?>
                    <div class="info-card mb-3">
                        <div class="info-card-header">
                            <i class="fas fa-bed"></i> Thông Tin Phòng
                        </div>
                        <div class="info-card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <span class="info-label">Số Phòng:</span>
                                        <span
                                            class="info-value fw-bold text-primary"><?php echo h($invoice['room_number']); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <span class="info-label">Loại Phòng:</span>
                                        <span class="info-value"><?php echo h($invoice['room_type_name']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <span class="info-label">Check-in:</span>
                                        <span
                                            class="info-value"><?php echo formatInvoiceDate($invoice['check_in_date']); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <span class="info-label">Check-out:</span>
                                        <span
                                            class="info-value"><?php echo formatInvoiceDate($invoice['check_out_date']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Dịch vụ -->
                    <?php if ($hasServices): ?>
                    <div class="info-card mb-3">
                        <div class="info-card-header">
                            <i class="fas fa-concierge-bell"></i> Dịch Vụ Sử Dụng
                        </div>
                        <div class="info-card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tên Dịch Vụ</th>
                                            <th class="text-center" width="120">Số Lượng</th>
                                            <th class="text-end" width="130">Đơn Giá</th>
                                            <th class="text-end" width="140">Thành Tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invoice['services'] as $service): ?>
                                        <tr>
                                            <td><?php echo h($service['service_name'] ?? '-'); ?></td>
                                            <td class="text-center">
                                                <?php echo h($service['quantity'] ?? 0); ?>
                                                <small
                                                    class="text-muted"><?php echo h($service['unit'] ?? ''); ?></small>
                                            </td>
                                            <td class="text-end">
                                                <?php echo formatInvoiceMoney($service['unit_price'] ?? 0); ?></td>
                                            <td class="text-end fw-semibold">
                                                <?php echo formatInvoiceMoney($service['total_price'] ?? 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Bảng tính tiền -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fas fa-calculator"></i> Chi Tiết Thanh Toán
                        </div>
                        <div class="info-card-body">
                            <table class="table table-borderless calculation-table mb-0">
                                <?php if ($hasRoomCharge): ?>
                                <!-- Hóa đơn có phòng - hiển thị chi tiết -->
                                <tr>
                                    <td><i class="fas fa-bed text-muted me-2"></i>Phí Phòng:</td>
                                    <td class="text-end"><?php echo formatInvoiceMoney($invoice['room_charge']); ?></td>
                                </tr>
                                <!-- Luôn hiển thị phí dịch vụ cho hóa đơn phòng (có thể = 0) -->
                                <tr>
                                    <td><i class="fas fa-concierge-bell text-muted me-2"></i>Phí Dịch Vụ:</td>
                                    <td class="text-end"><?php echo formatInvoiceMoney($invoice['service_charge']); ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <!-- Hóa đơn chỉ dịch vụ - chỉ hiển thị khi có dịch vụ -->
                                <?php if ($pureServiceTotal > 0): ?>
                                <tr>
                                    <td><i class="fas fa-concierge-bell text-muted me-2"></i>Tiền Dịch Vụ:</td>
                                    <td class="text-end"><?php echo formatInvoiceMoney($pureServiceTotal); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php endif; ?>

                                <!-- Phí khác nếu có -->
                                <?php if (isset($invoice['other_fees']) && $invoice['other_fees'] > 0): ?>
                                <tr>
                                    <td><i class="fas fa-plus-circle text-muted me-2"></i>Phí Khác:</td>
                                    <td class="text-end"><?php echo formatInvoiceMoney($invoice['other_fees']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <!-- Giảm giá - chỉ hiện khi có giảm giá -->
                                <?php if (isset($invoice['discount']) && $invoice['discount'] > 0): ?>
                                <tr class="discount-row">
                                    <td><i class="fas fa-tag text-success me-2"></i>Giảm Giá:</td>
                                    <td class="text-end text-success">
                                        -<?php echo formatInvoiceMoney($invoice['discount']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><i class="fas fa-percent text-muted me-2"></i>VAT:</td>
                                    <td class="text-end"><?php echo formatInvoiceMoney($invoice['vat']); ?></td>
                                </tr>

                                <tr class="total-row">
                                    <td class="fw-bold fs-6"><i class="fas fa-receipt text-warning me-2"></i>Tổng Cộng:
                                    </td>
                                    <td class="text-end fw-bold fs-5 text-primary">
                                        <?php echo formatInvoiceMoney($invoice['total_amount']); ?></td>
                                </tr>

                                <?php if ($invoice['deposit_amount'] > 0): ?>
                                <tr>
                                    <td><i class="fas fa-hand-holding-usd text-muted me-2"></i>Tiền Cọc:</td>
                                    <td class="text-end text-success">
                                        -<?php echo formatInvoiceMoney($invoice['deposit_amount']); ?></td>
                                </tr>

                                <tr class="remaining-row">
                                    <td class="fw-bold"><i class="fas fa-money-bill-wave text-danger me-2"></i>Còn Phải
                                        Trả:</td>
                                    <td class="text-end fw-bold fs-5 text-danger">
                                        <?php echo formatInvoiceMoney($invoice['remaining_amount']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Thông tin thanh toán -->
                    <div class="payment-info mt-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="payment-detail-box">
                                    <div class="payment-detail-label">Hình Thức Thanh Toán</div>
                                    <div class="payment-detail-value">
                                        <?php 
                  $paymentIcons = [
                    'Cash' => 'fa-money-bill-wave',
                    'Card' => 'fa-credit-card',
                    'Transfer' => 'fa-exchange-alt'
                  ];
                  $icon = $paymentIcons[$invoice['payment_method']] ?? 'fa-wallet';
                  ?>
                                        <i class="fas <?php echo $icon; ?> me-2"></i>
                                        <?php echo formatPaymentMethod(h($invoice['payment_method'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="payment-detail-box">
                                    <div class="payment-detail-label">Tình Trạng</div>
                                    <div class="payment-detail-value">
                                        <?php
                  $statusBadge = [
                    'Paid' => '<span class="badge-custom badge-success"><i class="fas fa-check-circle me-1"></i>Đã Thanh Toán</span>',
                    'Unpaid' => '<span class="badge-custom badge-danger"><i class="fas fa-times-circle me-1"></i>Chưa Thanh Toán</span>',
                    'Partial' => '<span class="badge-custom badge-warning"><i class="fas fa-clock me-1"></i>Thanh Toán Một Phần</span>'
                  ];
                  echo $statusBadge[$invoice['status']] ?? '<span class="badge-custom badge-secondary">-</span>';
                  ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="payment-detail-box">
                                    <div class="payment-detail-label">Ngày Thanh Toán</div>
                                    <div class="payment-detail-value">
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?php echo formatInvoiceDate($invoice['payment_time']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ghi chú -->
                    <?php if (!empty($invoice['note'])): ?>
                    <div class="note-section mt-3">
                        <div class="note-header">
                            <i class="fas fa-sticky-note"></i> Ghi Chú
                        </div>
                        <div class="note-body">
                            <?php echo nl2br(h($invoice['note'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Đóng
                    </button>
                    <button type="button" class="btn btn-success"
                        onclick="exportInvoiceToWord(<?php echo $invoice['invoice_id']; ?>)">
                        <i class="fas fa-file-word me-1"></i> Xuất Word
                    </button>
                    <a href="index.php?page=invoices-manager&action=edit&id=<?php echo $invoice['invoice_id']; ?>"
                        class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Chỉnh Sửa
                    </a>
                </div>

            </div>
        </div>
    </div>

    <?php endforeach; ?>
    <?php endif; ?>

    <!-- pagination -->
    <?php echo getPagination($total, $perPage, $pageNum, $baseUrl); ?>


</div>
</div>

<!-- Modal: Thêm Hóa Đơn -->
<div class="modal fade" id="addInvoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-invoice"></i> <span id="modalTitle">Thêm Hóa Đơn</span>
                </h5>
            </div>
            <form method="POST" id="invoiceForm">
                <div class="modal-body">
                    <input type="hidden" name="invoice_id" id="invoice_id" value="">
                    <input type="hidden" name="customer_id" id="customer_id"
                        value="<?php echo $editInvoice['customer_id'] ?? ''; ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Booking ID
                                <?php echo $editInvoice ? '(Không thể thay đổi)' : '*'; ?></label>
                            <?php if ($editInvoice): ?>
                            <!-- Khi edit: hiển thị readonly -->
                            <?php if ($editInvoice['booking_id']): ?>
                            <!-- Có booking_id: hiển thị thông tin booking -->
                            <select class="form-select" id="booking_id" name="booking_id" readonly disabled
                                style="background-color: #e9ecef; cursor: not-allowed;">
                                <option value="<?php echo $editInvoice['booking_id']; ?>" selected>
                                    <?php if ($editBookingInfo): ?>
                                    Booking #<?php echo $editInvoice['booking_id']; ?> -
                                    <?php echo h($editBookingInfo['full_name']); ?> -
                                    Phòng <?php echo h($editBookingInfo['room_number']); ?> -
                                    <?php echo date('d/m/Y', strtotime($editBookingInfo['check_in_date'])); ?>
                                    <?php else: ?>
                                    Booking #<?php echo $editInvoice['booking_id']; ?>
                                    <?php endif; ?>
                                </option>
                            </select>
                            <!-- Hidden input để gửi booking_id khi submit -->
                            <input type="hidden" name="booking_id" value="<?php echo $editInvoice['booking_id']; ?>">
                            <?php else: ?>
                            <!-- Không có booking_id: hóa đơn chỉ dịch vụ -->
                            <input type="text" class="form-control" value="Không có (Hóa đơn chỉ dịch vụ)" readonly
                                disabled style="background-color: #e9ecef; cursor: not-allowed;">
                            <input type="hidden" name="booking_id" value="">
                            <?php endif; ?>
                            <small class="text-muted">Booking ID không thể thay đổi khi chỉnh sửa hóa đơn</small>
                            <?php else: ?>
                            <!-- Khi thêm mới: select bình thường -->
                            <select class="form-select booking-search" id="booking_id" name="booking_id">
                                <option value="">-- Chọn booking (không bắt buộc) --</option>
                                <?php foreach ($bookingsWithoutInvoice as $booking): ?>
                                <option value="<?php echo $booking['booking_id']; ?>"
                                    data-customer-name="<?php echo h($booking['full_name']); ?>"
                                    data-checkin="<?php echo $booking['check_in_date']; ?>"
                                    data-checkout="<?php echo $booking['check_out_date']; ?>"
                                    data-room="<?php echo h($booking['room_number']); ?>">
                                    Booking #<?php echo $booking['booking_id']; ?> -
                                    <?php echo h($booking['full_name']); ?> -
                                    Phòng <?php echo h($booking['room_number']); ?> -
                                    <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>
                                    <?php if (isset($booking['has_invoice']) && $booking['has_invoice']): ?>
                                    (Đã có hóa đơn)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Có thể tạo hóa đơn không cần booking ID (chỉ dịch vụ). Hoặc chọn
                                booking để tự động điền thông tin.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hình Thức Thanh Toán *</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Chọn hình thức</option>
                                <option value="Cash">Tiền mặt</option>
                                <option value="Bank Transfer">Chuyển khoản</option>
                                <option value="Credit Card">Thẻ tín dụng</option>
                                <option value="E-Wallet">Ví điện tử</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phí Phòng (VNĐ) *</label>
                            <input type="number" class="form-control" id="room_charge" name="room_charge" step="0.01"
                                min="0" value="0" required onchange="calculateTotal()">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phí Dịch Vụ (VNĐ) *</label>
                            <input type="number" class="form-control" id="service_charge" name="service_charge"
                                step="0.01" min="0" value="0" required onchange="calculateTotal()">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">VAT (VNĐ) *</label>
                            <input type="number" class="form-control" id="vat" name="vat" step="0.01" min="0" value="0"
                                required onchange="calculateTotal()">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phí Khác (VNĐ)</label>
                            <input type="number" class="form-control" id="other_fees" name="other_fees" step="0.01"
                                min="0" value="0" onchange="calculateTotal()">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tổng Tiền (VNĐ) *</label>
                        <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01"
                            min="0" readonly required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tiền Cọc (VNĐ)</label>
                            <input type="number" class="form-control" id="deposit_amount" name="deposit_amount"
                                step="0.01" min="0" value="0" onchange="calculateRemaining()">
                            <small class="text-muted">Số tiền đã cọc (30% = tổng tiền × 0.3)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Còn Lại (VNĐ)</label>
                            <input type="number" class="form-control" id="remaining_amount" name="remaining_amount"
                                step="0.01" min="0" value="0" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tình Trạng *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Unpaid">Chưa thanh toán</option>
                                <option value="Paid">Đã thanh toán</option>
                                <option value="Refunded">Đã hoàn tiền</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày Thanh Toán</label>
                            <input type="datetime-local" class="form-control" id="payment_time" name="payment_time">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ghi Chú</label>
                        <textarea class="form-control" id="note" name="note" rows="3"
                            placeholder="Nhập ghi chú..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Thêm Hóa Đơn</button>
                </div>
            </form>
        </div>
    </div>
</div>





                </div> <!-- End table-container -->
            </div> <!-- End tab-pane -->
        </div> <!-- End tab-content -->
    </div> <!-- End container-fluid or main-content -->
</div> <!-- End main-content -->

<script>
<?php if ($editInvoice): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('invoice_id').value = '<?php echo $editInvoice['invoice_id']; ?>';
    document.getElementById('customer_id').value = '<?php echo $editInvoice['customer_id'] ?? ''; ?>';
    document.getElementById('room_charge').value = '<?php echo $editInvoice['room_charge']; ?>';
    document.getElementById('service_charge').value = '<?php echo $editInvoice['service_charge']; ?>';
    document.getElementById('vat').value = '<?php echo $editInvoice['vat']; ?>';
    document.getElementById('other_fees').value = '<?php echo $editInvoice['other_fees']; ?>';
    document.getElementById('total_amount').value = '<?php echo $editInvoice['total_amount']; ?>';
    document.getElementById('deposit_amount').value = '<?php echo $editInvoice['deposit_amount'] ?? 0; ?>';
    document.getElementById('remaining_amount').value =
        '<?php echo $editInvoice['remaining_amount'] ?? $editInvoice['total_amount']; ?>';
    document.getElementById('payment_method').value = '<?php echo $editInvoice['payment_method']; ?>';
    document.getElementById('status').value = '<?php echo $editInvoice['status']; ?>';
    document.getElementById('payment_time').value = '<?php echo $editInvoice['payment_time']; ?>';
    document.getElementById('note').value = '<?php echo h($editInvoice['note']); ?>';

    document.getElementById('modalTitle').textContent = 'Sửa Hóa Đơn';
    document.getElementById('submitBtn').textContent = 'Cập Nhật Hóa Đơn';
    document.getElementById('submitBtn').name = 'update_invoice';

    const modal = new bootstrap.Modal(document.getElementById('addInvoiceModal'));
    modal.show();
});
<?php endif; ?>
</script>


<!-- Modal Xác nhận xóa hóa đơn -->
<div class="modal fade" id="confirmDeleteInvoiceModal" tabindex="-1" aria-labelledby="confirmDeleteInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteInvoiceModalLabel">Xác nhận xóa</h5>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3 mb-0">Bạn có chắc muốn xóa hóa đơn này?<br>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">Hủy</button>
                <form method="POST" id="deleteInvoiceForm">
                     <input type="hidden" name="invoice_id" id="delete_invoice_id_input">
                     <button type="submit" name="delete_invoice" class="btn btn-danger" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>