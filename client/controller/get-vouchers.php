<?php
session_start();
require_once __DIR__ . '/../includes/connect.php';

header('Content-Type: application/json');

// Lấy thông tin khách hàng từ session
$customer_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$customer_type = 'Regular'; // Mặc định

if ($customer_id > 0) {
    $customerStmt = $mysqli->prepare("SELECT customer_type FROM customer WHERE customer_id = ? AND deleted IS NULL");
    $customerStmt->bind_param("i", $customer_id);
    $customerStmt->execute();
    $customerResult = $customerStmt->get_result();
    if ($customer = $customerResult->fetch_assoc()) {
        $customer_type = $customer['customer_type'] ?? 'Regular';
    }
    $customerStmt->close();
}

// Lấy thông tin booking từ request (nếu có)
$apply_to = $_GET['apply_to'] ?? 'all'; // all, room, service
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : null;
$room_type_id = null;
$service_ids = isset($_GET['service_ids']) ? explode(',', $_GET['service_ids']) : [];
$service_ids = array_filter(array_map('intval', $service_ids));
$total_amount = isset($_GET['total_amount']) ? floatval($_GET['total_amount']) : 0;
$nights = isset($_GET['nights']) ? intval($_GET['nights']) : 0;
$num_rooms = isset($_GET['num_rooms']) ? intval($_GET['num_rooms']) : 1;

// Lấy room_type_id từ room_id nếu có
if ($room_id) {
    $roomStmt = $mysqli->prepare("SELECT room_type_id FROM room WHERE room_id = ? AND deleted IS NULL");
    $roomStmt->bind_param("i", $room_id);
    $roomStmt->execute();
    $roomResult = $roomStmt->get_result();
    if ($room = $roomResult->fetch_assoc()) {
        $room_type_id = $room['room_type_id'];
    }
    $roomStmt->close();
}

$today = date('Y-m-d');
$currentDay = date('D'); // Mon, Tue, Wed, etc.
$currentHour = date('H:i');

// Map day names
$dayMap = ['Mon' => 'Mon', 'Tue' => 'Tue', 'Wed' => 'Wed', 'Thu' => 'Thu', 
           'Fri' => 'Fri', 'Sat' => 'Sat', 'Sun' => 'Sun'];

$vouchers = [];

// Query lấy voucher phù hợp - Đơn giản hóa để test
$query = "SELECT v.*, 
          COALESCE(COUNT(vu.id), 0) as customer_usage_count
          FROM voucher v
          LEFT JOIN voucher_usage vu ON v.voucher_id = vu.voucher_id AND vu.customer_id = ?
          WHERE v.status = 'active'
          AND v.deleted IS NULL
          AND v.start_date <= ?
          AND v.end_date >= ?
          AND (v.is_public = 1 OR EXISTS (
              SELECT 1 FROM voucher_customer vc 
              WHERE vc.voucher_id = v.voucher_id 
              AND vc.customer_id = ? 
              AND vc.is_used = 0
              AND (vc.expires_at IS NULL OR vc.expires_at >= ?)
          ))
          AND (v.customer_types IS NULL OR v.customer_types = '' OR FIND_IN_SET(?, v.customer_types) > 0)
          AND (v.apply_to = 'all' OR v.apply_to = ?)
          AND (v.used_count < v.total_uses)
          GROUP BY v.voucher_id
          HAVING customer_usage_count < v.per_customer
          ORDER BY v.priority DESC, v.voucher_id DESC";

$stmt = $mysqli->prepare($query);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Prepare failed: ' . $mysqli->error
    ]);
    exit;
}

$stmt->bind_param("isssiss", $customer_id, $today, $today, $customer_id, $today, $customer_type, $apply_to);
if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'error' => 'Execute failed: ' . $stmt->error
    ]);
    $stmt->close();
    exit;
}
$result = $stmt->get_result();

$queryReturnedCount = 0;
while ($voucher = $result->fetch_assoc()) {
    $queryReturnedCount++;
    // Kiểm tra điều kiện bổ sung
    
    // Kiểm tra min_order
    if ($voucher['min_order'] > 0 && $total_amount < $voucher['min_order']) {
        continue;
    }
    
    // Kiểm tra min_nights
    if ($voucher['min_nights'] !== null && $nights < $voucher['min_nights']) {
        continue;
    }
    
    // Kiểm tra min_rooms
    if ($voucher['min_rooms'] !== null && $num_rooms < $voucher['min_rooms']) {
        continue;
    }
    
    // Kiểm tra room_types - chỉ áp dụng nếu voucher áp dụng cho phòng
    if ($voucher['room_types'] !== null && !empty($voucher['room_types']) && 
        ($voucher['apply_to'] === 'room' || $voucher['apply_to'] === 'all') && 
        $room_type_id !== null) {
        $roomTypes = explode(',', $voucher['room_types']);
        $roomTypes = array_map('trim', $roomTypes);
        $roomTypes = array_filter($roomTypes);
        if (!empty($roomTypes) && !in_array((string)$room_type_id, $roomTypes)) {
            continue;
        }
    }
    
    // Kiểm tra service_ids - chỉ áp dụng nếu voucher áp dụng cho dịch vụ
    if ($voucher['service_ids'] !== null && !empty($voucher['service_ids']) && 
        ($voucher['apply_to'] === 'service' || $voucher['apply_to'] === 'all')) {
        // Nếu voucher yêu cầu service cụ thể nhưng user không chọn service nào
        if (empty($service_ids)) {
            continue; // Bỏ qua voucher yêu cầu service nếu không có service nào được chọn
        }
        $voucherServiceIds = explode(',', $voucher['service_ids']);
        $voucherServiceIds = array_map('trim', $voucherServiceIds);
        $voucherServiceIds = array_filter($voucherServiceIds);
        $voucherServiceIds = array_map('intval', $voucherServiceIds);
        $hasMatchingService = false;
        foreach ($service_ids as $sid) {
            if (in_array($sid, $voucherServiceIds)) {
                $hasMatchingService = true;
                break;
            }
        }
        if (!$hasMatchingService) {
            continue;
        }
    }
    
    // Kiểm tra valid_days
    if ($voucher['valid_days'] !== null && !empty($voucher['valid_days'])) {
        $validDays = array_map('trim', explode(',', $voucher['valid_days']));
        $currentDayName = $dayMap[$currentDay] ?? '';
        if (!in_array($currentDayName, $validDays)) {
            continue;
        }
    }
    
    // Kiểm tra valid_hours
    if ($voucher['valid_hours'] !== null && !empty($voucher['valid_hours'])) {
        $hours = explode('-', $voucher['valid_hours']);
        if (count($hours) === 2) {
            $startHour = trim($hours[0]);
            $endHour = trim($hours[1]);
            if ($currentHour < $startHour || $currentHour > $endHour) {
                continue;
            }
        }
    }
    
    // Tính toán discount amount để hiển thị
    $discountAmount = 0;
    if ($voucher['discount_type'] === 'percent') {
        $discountAmount = ($total_amount * $voucher['discount_value']) / 100;
        if ($voucher['max_discount'] !== null && $discountAmount > $voucher['max_discount']) {
            $discountAmount = $voucher['max_discount'];
        }
    } else {
        $discountAmount = $voucher['discount_value'];
    }
    
    $vouchers[] = [
        'voucher_id' => $voucher['voucher_id'],
        'code' => $voucher['code'],
        'name' => $voucher['name'],
        'description' => $voucher['description'],
        'discount_type' => $voucher['discount_type'],
        'discount_value' => floatval($voucher['discount_value']),
        'max_discount' => $voucher['max_discount'] ? floatval($voucher['max_discount']) : null,
        'min_order' => floatval($voucher['min_order']),
        'apply_to' => $voucher['apply_to'],
        'estimated_discount' => $discountAmount,
        'is_featured' => (bool)$voucher['is_featured']
    ];
}

$stmt->close();

// Debug: Log số lượng voucher tìm được
error_log("Voucher API - Query returned {$queryReturnedCount} vouchers, filtered to " . count($vouchers) . " vouchers for customer_type: {$customer_type}, apply_to: {$apply_to}");

$response = [
    'success' => true,
    'vouchers' => $vouchers,
    'debug' => [
        'customer_id' => $customer_id,
        'customer_type' => $customer_type,
        'apply_to' => $apply_to,
        'total_amount' => $total_amount,
        'nights' => $nights,
        'num_rooms' => $num_rooms,
        'room_type_id' => $room_type_id,
        'room_id' => $room_id,
        'service_ids' => $service_ids,
        'query_returned' => $queryReturnedCount,
        'filtered_count' => count($vouchers),
        'today' => $today,
        'current_day' => $currentDay,
        'current_hour' => $currentHour
    ]
];

echo json_encode($response);
?>