<?php
require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'get_invoice') {
    $invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($invoice_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid invoice ID']);
        exit;
    }
    
    // Lấy thông tin hóa đơn cơ bản
    $stmt = $mysqli->prepare("
        SELECT i.invoice_id, i.booking_id, i.customer_id, 
               i.room_charge, i.service_charge, i.vat, i.other_fees,
               i.total_amount, i.deposit_amount, i.remaining_amount,
               i.payment_method, i.status, i.payment_time, i.note, i.created_at,
               c.full_name, c.phone, c.email,
               b.check_in_date, b.check_out_date,
               r.room_number, rt.room_type_name
        FROM invoice i
        LEFT JOIN customer c ON i.customer_id = c.customer_id
        LEFT JOIN booking b ON i.booking_id = b.booking_id
        LEFT JOIN room r ON b.room_id = r.room_id
        LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
        WHERE i.invoice_id = ? AND i.deleted IS NULL
    ");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoice = $result->fetch_assoc();
    $stmt->close();
    
    if ($invoice) {
        // Đảm bảo các trường quan trọng được xử lý đúng
        // Format created_at nếu có
        if (isset($invoice['created_at']) && $invoice['created_at']) {
            // Giữ nguyên format từ database
        }
        
        // Debug log (có thể xóa sau)
        error_log('Invoice API - created_at: ' . ($invoice['created_at'] ?? 'NULL'));
        error_log('Invoice API - phone: ' . ($invoice['phone'] ?? 'NULL'));
        error_log('Invoice API - payment_time: ' . ($invoice['payment_time'] ?? 'NULL'));
        error_log('Invoice API - note: ' . ($invoice['note'] ?? 'NULL'));
        // Lấy danh sách dịch vụ chi tiết
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
        $services = [];
        while ($service = $services_result->fetch_assoc()) {
            $services[] = $service;
        }
        $services_stmt->close();
        
        $invoice['services'] = $services;
        echo json_encode(['success' => true, 'invoice' => $invoice]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>

