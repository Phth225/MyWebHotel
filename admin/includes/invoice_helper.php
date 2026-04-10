<?php
/**
 * Hàm tự động tạo hóa đơn khi booking được xác nhận
 * Dựa trên cấu trúc database (Giải pháp 1):
 * - invoice.booking_id có thể NULL
 * - Có bảng invoice_service để liên kết invoice với booking_service
 * - booking_service.status là varchar (có thể là 'confirmed', 'pending', 'cancelled')
 * - booking.status là enum ('Pending','Confirmed','Cancelled','Completed')
 * - room_type.base_price (không phải price_per_night)
 * 
 * Logic xử lý:
 * 1. Booking phòng: invoice.booking_id có giá trị, room_charge > 0, service_charge = 0
 * 2. Booking dịch vụ: invoice.booking_id = NULL, room_charge = 0, service_charge > 0, liên kết qua invoice_service
 * 3. Booking cả hai: invoice.booking_id có giá trị, room_charge > 0, service_charge > 0, liên kết qua invoice_service
 */

/**
 * Tạo hóa đơn tự động cho booking dịch vụ khi status = confirmed
 */
function createInvoiceForServiceBooking($mysqli, $booking_service_id) {
    // Kiểm tra xem booking_service đã có trong invoice_service chưa
    $check_service_stmt = $mysqli->prepare("
        SELECT invoice_id 
        FROM invoice_service 
        WHERE booking_service_id = ?
    ");
    $check_service_stmt->bind_param("i", $booking_service_id);
    $check_service_stmt->execute();
    $check_service_result = $check_service_stmt->get_result();
    if ($check_service_result->num_rows > 0) {
        $check_service_stmt->close();
        return false; // Đã có hóa đơn cho booking_service này
    }
    $check_service_stmt->close();
    
    // Lấy thông tin booking dịch vụ
    $stmt = $mysqli->prepare("
        SELECT bs.*, s.service_name, c.full_name, c.customer_id
        FROM booking_service bs
        INNER JOIN service s ON bs.service_id = s.service_id
        INNER JOIN customer c ON bs.customer_id = c.customer_id
        WHERE bs.booking_service_id = ? AND bs.deleted IS NULL
    ");
    $stmt->bind_param("i", $booking_service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking_service = $result->fetch_assoc();
    $stmt->close();
    
    // Kiểm tra booking_service có tồn tại và status = 'confirmed'
    if (!$booking_service) {
        return false;
    }
    
    // Chỉ tạo hóa đơn khi status = 'confirmed'
    if ($booking_service['status'] !== 'confirmed') {
        return false;
    }
    
    $customer_id = $booking_service['customer_id'];
    $service_charge = floatval($booking_service['amount'] ?? 0) * floatval($booking_service['unit_price'] ?? 0);
    $booking_id = $booking_service['booking_id'];
    
    $room_charge = 0;
    $invoice_id = null;
    
    if ($booking_id) {
        // Có booking_id - booking dịch vụ liên kết với booking phòng
        // Lấy thông tin booking phòng
        $room_stmt = $mysqli->prepare("
            SELECT b.booking_id, 
                   DATEDIFF(b.check_out_date, b.check_in_date) as nights,
                   rt.base_price
            FROM booking b
            INNER JOIN room r ON b.room_id = r.room_id
            INNER JOIN room_type rt ON r.room_type_id = rt.room_type_id
            WHERE b.booking_id = ? AND b.customer_id = ? AND b.deleted IS NULL AND b.status = 'Confirmed'
        ");
        $room_stmt->bind_param("ii", $booking_id, $customer_id);
        $room_stmt->execute();
        $room_result = $room_stmt->get_result();
        if ($room_row = $room_result->fetch_assoc()) {
            $nights = max(1, $room_row['nights'] ?? 1);
            $room_charge = floatval($room_row['base_price'] ?? 0) * $nights;
        }
        $room_stmt->close();
        
        // Kiểm tra xem đã có hóa đơn cho booking này chưa
        $check_stmt = $mysqli->prepare("
            SELECT invoice_id, service_charge, room_charge, vat, other_fees
            FROM invoice 
            WHERE booking_id = ? AND deleted IS NULL
        ");
        $check_stmt->bind_param("i", $booking_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            // Đã có hóa đơn - cập nhật service_charge và liên kết booking_service
            $existing_invoice = $check_result->fetch_assoc();
            $invoice_id = $existing_invoice['invoice_id'];
            
            // Cộng thêm service_charge mới
            $new_service_charge = floatval($existing_invoice['service_charge'] ?? 0) + $service_charge;
            $total_amount = floatval($existing_invoice['room_charge'] ?? 0) + $new_service_charge + floatval($existing_invoice['vat'] ?? 0) + floatval($existing_invoice['other_fees'] ?? 0);
            
            // Cập nhật remaining_amount khi service_charge thay đổi
            $existing_deposit = floatval($existing_invoice['deposit_amount'] ?? 0);
            $new_remaining = $total_amount - $existing_deposit;
            
            $update_stmt = $mysqli->prepare("
                UPDATE invoice 
                SET service_charge = ?, total_amount = ?, remaining_amount = ?
                WHERE invoice_id = ?
            ");
            $update_stmt->bind_param("dddi", $new_service_charge, $total_amount, $new_remaining, $invoice_id);
            $update_stmt->execute();
            $update_stmt->close();
            $check_stmt->close();
            
            // Liên kết booking_service với invoice qua bảng invoice_service
            $check_table = $mysqli->query("SHOW TABLES LIKE 'invoice_service'");
            if ($check_table && $check_table->num_rows > 0) {
                $link_stmt = $mysqli->prepare("
                    INSERT INTO invoice_service (invoice_id, booking_service_id, created_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE created_at = created_at
                ");
                $link_stmt->bind_param("ii", $invoice_id, $booking_service_id);
                $link_stmt->execute();
                $link_stmt->close();
            }
        } else {
            // Chưa có hóa đơn - tạo mới
            $vat = 0;
            $other_fees = 0;
            $total_amount = $room_charge + $service_charge + $vat + $other_fees;
            
            $invoice_stmt = $mysqli->prepare("
                INSERT INTO invoice (booking_id, customer_id, room_charge, service_charge, vat, other_fees, total_amount, payment_method, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Cash', 'Unpaid', NOW())
            ");
            $invoice_stmt->bind_param("iiddddd", $booking_id, $customer_id, $room_charge, $service_charge, $vat, $other_fees, $total_amount);
            
            if ($invoice_stmt->execute()) {
                $invoice_id = $invoice_stmt->insert_id;
            }
            $invoice_stmt->close();
        }
    } else {
        // Không có booking_id - chỉ booking dịch vụ (service only)
        // Tạo hóa đơn với booking_id = NULL
        // Kiểm tra xem đã có hóa đơn cho customer này (service-only) chưa
        // Nếu chưa có thì tạo mới, nếu có rồi thì cập nhật
        
        $vat = 0;
        $other_fees = 0;
        $total_amount = $service_charge + $vat + $other_fees;
        
        // Kiểm tra xem đã có hóa đơn service-only cho customer này chưa (trong cùng ngày hoặc gần đây)
        // Hoặc tạo mới hóa đơn cho mỗi booking_service
        // Ở đây chúng ta tạo mới hóa đơn cho mỗi booking_service để dễ quản lý
        
        $deposit_amount = 0;
        $remaining_amount = $total_amount;
        
        $invoice_stmt = $mysqli->prepare("
            INSERT INTO invoice (booking_id, customer_id, room_charge, service_charge, vat, other_fees, total_amount, deposit_amount, remaining_amount, payment_method, status, created_at)
            VALUES (NULL, ?, 0, ?, ?, ?, ?, ?, ?, 'Cash', 'Unpaid', NOW())
        ");
        $invoice_stmt->bind_param("idddddd", $customer_id, $service_charge, $vat, $other_fees, $total_amount, $deposit_amount, $remaining_amount);
        
        if ($invoice_stmt->execute()) {
            $invoice_id = $invoice_stmt->insert_id;
        } else {
            error_log("Error creating invoice for service booking: " . $invoice_stmt->error);
            $invoice_stmt->close();
            return false;
        }
        $invoice_stmt->close();
    }
    
    // Liên kết booking_service với invoice qua bảng invoice_service
    if ($invoice_id) {
        // Kiểm tra xem bảng invoice_service có tồn tại không
        $check_table = $mysqli->query("SHOW TABLES LIKE 'invoice_service'");
        if ($check_table && $check_table->num_rows > 0) {
            $link_stmt = $mysqli->prepare("
                INSERT INTO invoice_service (invoice_id, booking_service_id, created_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE created_at = created_at
            ");
            $link_stmt->bind_param("ii", $invoice_id, $booking_service_id);
            $link_stmt->execute();
            $link_stmt->close();
        }
        
        return $invoice_id;
    }
    
    return false;
}

/**
 * Tạo hóa đơn tự động cho booking phòng khi status = Confirmed
 */
function createInvoiceForRoomBooking($mysqli, $booking_id) {
    // Kiểm tra xem đã có hóa đơn chưa
    $check_stmt = $mysqli->prepare("
        SELECT invoice_id FROM invoice 
        WHERE booking_id = ? AND deleted IS NULL
    ");
    $check_stmt->bind_param("i", $booking_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($result->num_rows > 0) {
        $check_stmt->close();
        return false; // Đã có hóa đơn
    }
    $check_stmt->close();
    
    // Lấy thông tin booking phòng
    $stmt = $mysqli->prepare("
        SELECT b.*, c.customer_id, c.full_name,
               DATEDIFF(b.check_out_date, b.check_in_date) as nights,
               rt.base_price
        FROM booking b
        INNER JOIN customer c ON b.customer_id = c.customer_id
        INNER JOIN room r ON b.room_id = r.room_id
        INNER JOIN room_type rt ON r.room_type_id = rt.room_type_id
        WHERE b.booking_id = ? AND b.deleted IS NULL
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    if (!$booking || $booking['status'] !== 'Confirmed') {
        return false;
    }
    
    $customer_id = $booking['customer_id'];
    $nights = max(1, $booking['nights'] ?? 1);
    $room_charge = floatval($booking['base_price'] ?? 0) * $nights;
    
    // Tính tổng phí dịch vụ liên quan (nếu có) - tính theo amount * unit_price
    $service_charge = 0;
    $service_stmt = $mysqli->prepare("
        SELECT bs.booking_service_id, SUM(bs.amount * bs.unit_price) as total_service
        FROM booking_service bs
        WHERE bs.booking_id = ? AND bs.status = 'confirmed' AND bs.deleted IS NULL
        GROUP BY bs.booking_id
    ");
    $service_stmt->bind_param("i", $booking_id);
    $service_stmt->execute();
    $service_result = $service_stmt->get_result();
    if ($service_row = $service_result->fetch_assoc()) {
        $service_charge = floatval($service_row['total_service'] ?? 0);
    }
    $service_stmt->close();
    
    // Tính tổng
    $vat = 0;
    $other_fees = 0;
    $total_amount = $room_charge + $service_charge + $vat + $other_fees;
    
    // Tạo hóa đơn
    $deposit_amount = 0;
    $remaining_amount = $total_amount;
    
    $invoice_stmt = $mysqli->prepare("
        INSERT INTO invoice (booking_id, customer_id, room_charge, service_charge, vat, other_fees, total_amount, deposit_amount, remaining_amount, payment_method, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Cash', 'Unpaid', NOW())
    ");
    $invoice_stmt->bind_param("iiddddddd", $booking_id, $customer_id, $room_charge, $service_charge, $vat, $other_fees, $total_amount, $deposit_amount, $remaining_amount);
    
    if ($invoice_stmt->execute()) {
        $invoice_id = $invoice_stmt->insert_id;
        $invoice_stmt->close();
        
        // Liên kết tất cả booking_service của booking này với invoice
        // Kiểm tra xem bảng invoice_service có tồn tại không
        $check_table = $mysqli->query("SHOW TABLES LIKE 'invoice_service'");
        if ($check_table && $check_table->num_rows > 0) {
            // Lấy tất cả booking_service_id của booking này
            $bs_stmt = $mysqli->prepare("
                SELECT booking_service_id
                FROM booking_service
                WHERE booking_id = ? AND status = 'confirmed' AND deleted IS NULL
            ");
            $bs_stmt->bind_param("i", $booking_id);
            $bs_stmt->execute();
            $bs_result = $bs_stmt->get_result();
            
            $link_stmt = $mysqli->prepare("
                INSERT INTO invoice_service (invoice_id, booking_service_id, created_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE created_at = created_at
            ");
            
            while ($bs_row = $bs_result->fetch_assoc()) {
                $link_stmt->bind_param("ii", $invoice_id, $bs_row['booking_service_id']);
                $link_stmt->execute();
            }
            
            $link_stmt->close();
            $bs_stmt->close();
        }
        
        return $invoice_id;
    }
    
    $invoice_stmt->close();
    return false;
}
?>

