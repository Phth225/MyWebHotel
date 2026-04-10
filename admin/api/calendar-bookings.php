<?php
session_start();
require_once __DIR__ . '/../includes/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['id_nhan_vien'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

header('Content-Type: application/json');

// Lấy tham số start và end từ request
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-t');

try {
    // Lấy tất cả booking trong khoảng thời gian
    $stmt = $mysqli->prepare("
        SELECT 
            b.booking_id,
            b.check_in_date,
            b.check_out_date,
            b.status,
            COALESCE(c.full_name, w.full_name) as customer_name,
            COALESCE(c.phone, w.phone) as customer_phone,
            r.room_number,
            rt.room_type_name,
            rt.base_price,
            CASE WHEN b.walk_in_guest_id IS NOT NULL THEN 'Walk-in' ELSE 'Registered' END as guest_type
        FROM booking b
        LEFT JOIN customer c ON b.customer_id = c.customer_id
        LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
        LEFT JOIN room r ON b.room_id = r.room_id
        LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
        WHERE b.deleted IS NULL
        AND (
            (b.check_in_date <= ? AND b.check_out_date >= ?) OR
            (b.check_in_date >= ? AND b.check_in_date <= ?) OR
            (b.check_out_date >= ? AND b.check_out_date <= ?)
        )
        ORDER BY b.check_in_date ASC
    ");
    
    // Bind parameters: end, start, start, end, start, end
    $stmt->bind_param("ssssss", $end, $start, $start, $end, $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Xác định màu sắc dựa trên trạng thái
        $color = '#deb666'; // Màu mặc định (vàng)
        $textColor = '#000';
        
        switch ($row['status']) {
            case 'Confirmed':
                $color = '#4caf50'; // Xanh lá
                $textColor = '#fff';
                break;
            case 'Checked-in':
            case 'Occupied':
                $color = '#2196f3'; // Xanh dương
                $textColor = '#fff';
                break;
            case 'Pending':
                $color = '#ff9800'; // Cam
                $textColor = '#fff';
                break;
            case 'Cancelled':
                $color = '#f44336'; // Đỏ
                $textColor = '#fff';
                break;
            case 'Completed':
                $color = '#9e9e9e'; // Xám
                $textColor = '#fff';
                break;
            default:
                $color = '#deb666'; // Vàng (màu chủ đạo)
                $textColor = '#000';
        }
        
        // Tính số đêm
        $checkIn = new DateTime($row['check_in_date']);
        $checkOut = new DateTime($row['check_out_date']);
        $nights = $checkIn->diff($checkOut)->days;
        
        $events[] = [
            'id' => $row['booking_id'],
            'title' => $row['room_number'] . ' - ' . $row['customer_name'],
            'start' => $row['check_in_date'],
            'end' => date('Y-m-d', strtotime($row['check_out_date'] . ' +1 day')), // FullCalendar cần end là ngày sau
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => $textColor,
            'extendedProps' => [
                'booking_id' => $row['booking_id'],
                'customer_name' => $row['customer_name'],
                'customer_phone' => $row['customer_phone'],
                'room_number' => $row['room_number'],
                'room_type' => $row['room_type_name'],
                'status' => $row['status'],
                'guest_type' => $row['guest_type'],
                'base_price' => $row['base_price'],
                'nights' => $nights,
                'check_in' => $row['check_in_date'],
                'check_out' => $row['check_out_date']
            ]
        ];
    }
    
    $stmt->close();
    
    echo json_encode($events);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

