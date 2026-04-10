<?php
// ========== CẤU HÌNH BẢO MẬT ==========
ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();

// ========== KẾT NỐI DATABASE ==========
require_once __DIR__ . '/../includes/connect.php';

// ========== VALIDATE SESSION - KIỂM TRA ĐĂNG NHẬP ==========
$customer_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$is_walk_in = ($customer_id <= 0); // Khách vãng lai nếu không đăng nhập
$walk_in_guest_id = null;

// ========== LẤY DỮ LIỆU TỪ POST ==========
$room_id = isset($_POST['roomId']) ? intval($_POST['roomId']) : 0;
$check_in_date = isset($_POST['checkinTime']) ? trim($_POST['checkinTime']) : '';
$check_out_date = isset($_POST['checkoutTime']) ? trim($_POST['checkoutTime']) : '';
$special_request = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$guest_count = isset($_POST['guestCount']) ? intval($_POST['guestCount']) : 1;
$child_count = isset($_POST['childCount']) ? intval($_POST['childCount']) : 0;

// Thông tin tài chính
$deposit = isset($_POST['deposit']) ? floatval($_POST['deposit']) : 0;
$room_charge = isset($_POST['roomPrice']) ? floatval($_POST['roomPrice']) : 0;
$service_charge = isset($_POST['serviceTotal']) ? floatval($_POST['serviceTotal']) : 0;
$discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
$total_amount = isset($_POST['total']) ? floatval($_POST['total']) : 0;

// Thông tin voucher - chỉ áp dụng cho khách đã đăng nhập
$voucher_id = null;
if (!$is_walk_in && isset($_POST['voucherId']) && $_POST['voucherId'] !== '') {
    $voucher_id = intval($_POST['voucherId']);
}

// Thông tin thanh toán
$payment_method = isset($_POST['paymentMethod']) ? trim($_POST['paymentMethod']) : '';

// Danh sách dịch vụ (JSON string)
$services = [];
if (isset($_POST['services']) && !empty($_POST['services'])) {
    $tmp = @json_decode($_POST['services'], true);
    if (is_array($tmp)) {
        $services = $tmp;
    }
}

// Thông tin khách vãng lai (nếu không đăng nhập)
$walk_in_full_name = isset($_POST['walkInFullName']) ? trim($_POST['walkInFullName']) : '';
$walk_in_phone = isset($_POST['walkInPhone']) ? trim($_POST['walkInPhone']) : '';
$walk_in_email = isset($_POST['walkInEmail']) ? trim($_POST['walkInEmail']) : '';
$walk_in_id_number = isset($_POST['walkInIdNumber']) ? trim($_POST['walkInIdNumber']) : '';
$walk_in_address = isset($_POST['walkInAddress']) ? trim($_POST['walkInAddress']) : '';

// ========== VALIDATION CƠ BẢN ==========
$errors = [];

// Nếu là khách vãng lai, validate thông tin
if ($is_walk_in) {
    if (empty($walk_in_full_name)) {
        $errors[] = 'Vui lòng nhập họ tên';
    }
    if (empty($walk_in_phone)) {
        $errors[] = 'Vui lòng nhập số điện thoại';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $walk_in_phone)) {
        $errors[] = 'Số điện thoại không hợp lệ (10-11 chữ số)';
    }
    if (empty($walk_in_id_number)) {
        $errors[] = 'Vui lòng nhập số CMND/CCCD';
    } elseif (!preg_match('/^[0-9]{9,12}$/', $walk_in_id_number)) {
        $errors[] = 'Số CMND/CCCD không hợp lệ (phải có 9-12 chữ số)';
    }
    if (empty($walk_in_email)) {
        $errors[] = 'Vui lòng nhập email';
    } elseif (!filter_var($walk_in_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    }
    // Validate tên: chỉ chứa chữ cái, khoảng trắng, và ký tự tiếng Việt
    if (!empty($walk_in_full_name) && !preg_match('/^[A-Za-zÀ-ỹ\s]{2,50}$/u', $walk_in_full_name)) {
        $errors[] = 'Họ và tên không hợp lệ (chỉ được chứa chữ cái và khoảng trắng, 2-50 ký tự)';
    }
}

if ($room_id <= 0) {
    $errors[] = 'ID phòng không hợp lệ';
}

if (empty($check_in_date) || empty($check_out_date)) {
    $errors[] = 'Vui lòng nhập đầy đủ thời gian check-in và check-out';
}

if (empty($payment_method)) {
    $errors[] = 'Vui lòng chọn phương thức thanh toán';
}

if ($guest_count < 1) {
    $errors[] = 'Số người lớn phải ít nhất là 1';
}

if ($child_count < 0) {
    $errors[] = 'Số trẻ em không hợp lệ';
}

if ($deposit < 0 || $total_amount < 0) {
    $errors[] = 'Thông tin tài chính không hợp lệ';
}

// Validate định dạng ngày
$check_in_timestamp = strtotime($check_in_date);
$check_out_timestamp = strtotime($check_out_date);

if ($check_in_timestamp === false || $check_out_timestamp === false) {
    $errors[] = 'Định dạng ngày giờ không hợp lệ';
} elseif ($check_in_timestamp >= $check_out_timestamp) {
    $errors[] = 'Ngày check-out phải sau ngày check-in';
}

// Nếu có lỗi, trả về ngay
if (!empty($errors)) {
    $error_message = implode('\\n', array_map('addslashes', $errors));
    echo "<script>alert('Lỗi:\\n{$error_message}'); window.history.back();</script>";
    exit;
}

// Chuyển đổi sang DATE format
$check_in_date_only = date('Y-m-d', $check_in_timestamp);
$check_out_date_only = date('Y-m-d', $check_out_timestamp);

// ========== BẮT ĐẦU TRANSACTION ==========
$mysqli->begin_transaction();

try {
    // ========== 1. KIỂM TRA PHÒNG ==========
    $checkRoomStmt = $mysqli->prepare("
        SELECT r.status, r.room_number, r.room_type_id, rt.capacity 
        FROM room r
        JOIN room_type rt ON r.room_type_id = rt.room_type_id
        WHERE r.room_id = ? AND r.deleted IS NULL
    ");
    
    if (!$checkRoomStmt) {
        throw new Exception('Lỗi hệ thống. Vui lòng thử lại sau.');
    }
    
    $checkRoomStmt->bind_param("i", $room_id);
    $checkRoomStmt->execute();
    $roomResult = $checkRoomStmt->get_result();
    $room = $roomResult->fetch_assoc();
    $checkRoomStmt->close();

    if (!$room) {
        throw new Exception('Phòng không tồn tại.');
    }

    if ($room['status'] !== 'Available') {
        throw new Exception('Phòng hiện không có sẵn (Trạng thái: ' . $room['status'] . ').');
    }

    // Kiểm tra sức chứa
    $total_guests = $guest_count + $child_count;
    if ($total_guests > intval($room['capacity'])) {
        throw new Exception("Số khách ({$total_guests}) vượt quá sức chứa phòng ({$room['capacity']}).");
    }

    // ========== 2. KIỂM TRA CONFLICT BOOKING ==========
    // $checkConflictStmt = $mysqli->prepare("
    //     SELECT booking_id, check_in_date, check_out_date, status
    //     FROM booking 
    //     WHERE room_id = ? 
    //     AND status IN ('Pending', 'Confirmed', 'CheckedIn')
    //     AND deleted IS NULL
    //     AND (
    //         (check_in_date < ? AND check_out_date > ?)
    //         OR (check_in_date >= ? AND check_in_date < ?)
    //         OR (check_out_date > ? AND check_out_date <= ?)
    //         OR (check_in_date <= ? AND check_out_date >= ?)
    //     )
    //     LIMIT 1
    // ");
    
    // if (!$checkConflictStmt) {
    //     throw new Exception('Lỗi hệ thống. Vui lòng thử lại sau.');
    // }
    
    // $checkConflictStmt->bind_param(
    //     "issssssss", 
    //     $room_id,
    //     $check_out_date_only, $check_in_date_only,
    //     $check_in_date_only, $check_out_date_only,
    //     $check_in_date_only, $check_out_date_only,
    //     $check_in_date_only, $check_out_date_only
    // );
    
    // $checkConflictStmt->execute();
    // $conflictResult = $checkConflictStmt->get_result();
    
    // if ($conflictResult->num_rows > 0) {
    //     $checkConflictStmt->close();
    //     throw new Exception('Phòng đã được đặt trong khoảng thời gian này. Vui lòng chọn ngày khác.');
    // }
    // $checkConflictStmt->close();

    // ========== 3. VALIDATE VOUCHER (NẾU CÓ) - ENHANCED ==========
    $voucher = null;
    $is_public_voucher = false;
    
    // Khách vãng lai không được dùng voucher
    if ($is_walk_in && $voucher_id && $voucher_id > 0) {
        throw new Exception('Khách vãng lai không được sử dụng voucher. Vui lòng đăng nhập để sử dụng voucher.');
    }
    
    if ($voucher_id && $voucher_id > 0 && !$is_walk_in) {
        
        error_log("Checking voucher_id: {$voucher_id} for customer_id: {$customer_id}");
        
        // Bước 1: Kiểm tra xem voucher có phải là public không
        $checkPublicStmt = $mysqli->prepare("
            SELECT is_public FROM voucher WHERE voucher_id = ? AND deleted IS NULL
        ");
        if (!$checkPublicStmt) {
            throw new Exception('Lỗi khi kiểm tra voucher: ' . $mysqli->error);
        }
        $checkPublicStmt->bind_param("i", $voucher_id);
        $checkPublicStmt->execute();
        $publicResult = $checkPublicStmt->get_result();
        $publicData = $publicResult->fetch_assoc();
        $checkPublicStmt->close();
        
        if (!$publicData) {
            throw new Exception('Voucher không tồn tại.');
        }
        
        $is_public_voucher = ($publicData['is_public'] == 1);
        
        // Bước 2: Query khác nhau cho public và private voucher
        if ($is_public_voucher) {
            // Voucher công khai: Chỉ cần check bảng voucher
            $checkVoucherStmt = $mysqli->prepare("
                SELECT 
                    v.voucher_id,
                    v.code, 
                    v.discount_type, 
                    v.discount_value, 
                    v.max_discount, 
                    v.min_order,
                    v.start_date, 
                    v.end_date, 
                    v.status,
                    v.used_count,
                    v.total_uses,
                    v.per_customer,
                    v.apply_to,
                    v.room_types,
                    v.service_ids,
                    v.min_nights,
                    v.min_rooms,
                    v.is_public
                FROM voucher v
                WHERE v.voucher_id = ?
                AND v.is_public = 1
                AND v.status = 'active'
                AND v.deleted IS NULL
                LIMIT 1
            ");
            
            if (!$checkVoucherStmt) {
                throw new Exception('Lỗi khi kiểm tra voucher: ' . $mysqli->error);
            }

            $checkVoucherStmt->bind_param("i", $voucher_id);
            
        } else {
            // Voucher riêng: Phải có trong voucher_customer
            $checkVoucherStmt = $mysqli->prepare("
                SELECT 
                    vc.voucher_id, 
                    vc.is_used, 
                    vc.expires_at,
                    v.code, 
                    v.discount_type, 
                    v.discount_value, 
                    v.max_discount, 
                    v.min_order,
                    v.start_date, 
                    v.end_date, 
                    v.status,
                    v.used_count,
                    v.total_uses,
                    v.per_customer,
                    v.apply_to,
                    v.room_types,
                    v.service_ids,
                    v.min_nights,
                    v.min_rooms,
                    v.is_public
                FROM voucher_customer vc
                JOIN voucher v ON vc.voucher_id = v.voucher_id
                WHERE vc.customer_id = ? 
                AND vc.voucher_id = ?
                AND vc.is_used = 0
                AND (vc.expires_at IS NULL OR vc.expires_at >= CURDATE())
                AND v.deleted IS NULL
                LIMIT 1
            ");
            
            if (!$checkVoucherStmt) {
                throw new Exception('Lỗi khi kiểm tra voucher: ' . $mysqli->error);
            }

            $checkVoucherStmt->bind_param("ii", $customer_id, $voucher_id);
        }
        
        if (!$checkVoucherStmt->execute()) {
            throw new Exception('Lỗi thực thi kiểm tra voucher: ' . $checkVoucherStmt->error);
        }
        
        $voucherResult = $checkVoucherStmt->get_result();
        $voucher = $voucherResult->fetch_assoc();
        
        error_log("Voucher result: " . json_encode($voucher));
        
        $checkVoucherStmt->close();

        if (!$voucher) {
            if ($is_public_voucher) {
                throw new Exception('Voucher công khai không hợp lệ hoặc đã hết hạn.');
            } else {
                throw new Exception('Voucher không hợp lệ, đã được sử dụng, hoặc đã hết hạn.');
            }
        }
        
        // Kiểm tra is_used cho private voucher (public voucher không có field này)
        if (!$is_public_voucher && isset($voucher['is_used']) && $voucher['is_used'] == 1) {
            throw new Exception('Voucher đã được sử dụng.');
        }
        
        // ========== KIỂM TRA STATUS ==========
        if (isset($voucher['status']) && strtolower(trim($voucher['status'])) !== 'active') {
            throw new Exception('Voucher không còn hiệu lực (Trạng thái: ' . $voucher['status'] . ').');
        }
        
        // ========== KIỂM TRA NGÀY HẠN VOUCHER ==========
        $now = date('Y-m-d H:i:s');
        if ((isset($voucher['start_date']) && $now < $voucher['start_date']) || (isset($voucher['end_date']) && $now > $voucher['end_date'])) {
            throw new Exception('Voucher đã hết hạn hoặc chưa có hiệu lực.');
        }
        
        // ========== KIỂM TRA PER_CUSTOMER LIMIT ==========        
        $usageCount = 0;
        
        $tableCheck = $mysqli->query("SHOW TABLES LIKE 'voucher_usage'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $usageCheckStmt = $mysqli->prepare("
                SELECT COUNT(*) as usage_count
                FROM voucher_usage
                WHERE customer_id = ? AND voucher_id = ?
            ");
            if ($usageCheckStmt) {
                $usageCheckStmt->bind_param("ii", $customer_id, $voucher_id);
                $usageCheckStmt->execute();
                $usageResult = $usageCheckStmt->get_result();
                $usageRow = $usageResult->fetch_assoc();
                $usageCount = intval($usageRow['usage_count']);
                $usageCheckStmt->close();
            }
        } else {
            $usageCheckStmt = $mysqli->prepare("
                SELECT COUNT(*) as usage_count
                FROM invoice
                WHERE customer_id = ? 
                AND note LIKE CONCAT('%', ?, '%')
                AND deleted IS NULL
            ");
            if ($usageCheckStmt) {
                $voucher_code = isset($voucher['code']) ? $voucher['code'] : '';
                $usageCheckStmt->bind_param("is", $customer_id, $voucher_code);
                $usageCheckStmt->execute();
                $usageResult = $usageCheckStmt->get_result();
                $usageRow = $usageResult->fetch_assoc();
                $usageCount = intval($usageRow['usage_count']);
                $usageCheckStmt->close();
            }
        }
        
        if (isset($voucher['per_customer']) && $usageCount >= intval($voucher['per_customer'])) {
            throw new Exception('Bạn đã sử dụng voucher này đạt giới hạn cho phép (' . intval($voucher['per_customer']) . ' lần).');
        }
        
        // ========== KIỂM TRA TOTAL_USES LIMIT (WITH LOCK) ==========
        $lockCheckStmt = $mysqli->prepare("
            SELECT used_count, total_uses 
            FROM voucher 
            WHERE voucher_id = ? 
            FOR UPDATE
        ");
        if (!$lockCheckStmt) {
            throw new Exception('Lỗi lock voucher: ' . $mysqli->error);
        }
        
        $lockCheckStmt->bind_param("i", $voucher_id);
        $lockCheckStmt->execute();
        $lockResult = $lockCheckStmt->get_result();
        $lockRow = $lockResult->fetch_assoc();
        $lockCheckStmt->close();
        
        if (isset($lockRow['used_count']) && isset($lockRow['total_uses']) && intval($lockRow['used_count']) >= intval($lockRow['total_uses'])) {
            throw new Exception('Voucher đã hết lượt sử dụng.');
        }
        
        // ========== KIỂM TRA MIN_NIGHTS ==========
        if (!empty($voucher['min_nights'])) {
            $nights = round(($check_out_timestamp - $check_in_timestamp) / 86400);
            if ($nights < intval($voucher['min_nights'])) {
                throw new Exception('Voucher yêu cầu tối thiểu ' . intval($voucher['min_nights']) . ' đêm.');
            }
        }
        
        // ========== KIỂM TRA MIN_ROOMS ==========
        if (!empty($voucher['min_rooms']) && intval($voucher['min_rooms']) > 1) {
            if (1 < intval($voucher['min_rooms'])) {
                throw new Exception('Voucher yêu cầu tối thiểu ' . intval($voucher['min_rooms']) . ' phòng.');
            }
        }
        
        // ========== VALIDATE APPLY_TO ==========
        $subtotal = $room_charge + $service_charge;
        
        if (isset($voucher['apply_to']) && $voucher['apply_to'] === 'room') {
            if (!empty($voucher['room_types'])) {
                $allowedTypes = array_map('trim', explode(',', $voucher['room_types']));
                if (!in_array((string)$room['room_type_id'], $allowedTypes, true)) {
                    throw new Exception('Voucher không áp dụng cho loại phòng này.');
                }
            }
            $subtotal = $room_charge;
            
        } elseif (isset($voucher['apply_to']) && $voucher['apply_to'] === 'service') {
            if (!empty($voucher['service_ids'])) {
                if (empty($services)) {
                    throw new Exception('Voucher yêu cầu chọn dịch vụ.');
                }
                
                $requiredServiceIds = array_map('intval', array_map('trim', explode(',', $voucher['service_ids'])));
                $selectedServiceIds = array_map(function($s) {
                    return isset($s['serviceId']) ? intval($s['serviceId']) : 0;
                }, $services);
                
                $hasMatchingService = false;
                foreach ($selectedServiceIds as $sid) {
                    if (in_array($sid, $requiredServiceIds, true)) {
                        $hasMatchingService = true;
                        break;
                    }
                }
                
                if (!$hasMatchingService) {
                    throw new Exception('Voucher yêu cầu chọn dịch vụ phù hợp.');
                }
            }
            $subtotal = $service_charge;
        }
        // apply_to = 'all' thì giữ nguyên subtotal

        // ========== KIỂM TRA MIN_ORDER ==========
        if ($subtotal < floatval($voucher['min_order'])) {
            throw new Exception('Đơn hàng tối thiểu: ' . number_format($voucher['min_order'], 0, ',', '.') . ' VNĐ (Hiện tại: ' . number_format($subtotal, 0, ',', '.') . ' VNĐ).');
        }
        
        // ========== TÍNH TOÁN LẠI DISCOUNT ĐỂ ĐẢM BẢO CHÍNH XÁC ==========
        $calculated_discount = 0;
        if (isset($voucher['discount_type']) && $voucher['discount_type'] === 'percent') {
            $calculated_discount = round(($subtotal * floatval($voucher['discount_value'])) / 100);
            if (isset($voucher['max_discount']) && $voucher['max_discount'] !== null && $calculated_discount > floatval($voucher['max_discount'])) {
                $calculated_discount = floatval($voucher['max_discount']);
            }
        } else {
            $calculated_discount = floatval($voucher['discount_value']);
        }
        
        // So sánh với discount từ client
        $discount_diff = abs($calculated_discount - $discount);
        if ($discount_diff > 1) { // Cho phép sai số 1 VNĐ do làm tròn
            error_log("Discount mismatch: calculated={$calculated_discount}, received={$discount}");
            $discount = $calculated_discount; // Ưu tiên giá trị tính từ server
        }
    } // end voucher validation

    // ========== 3.5. TẠO WALK_IN_GUEST NẾU LÀ KHÁCH VÃNG LAI ==========
    if ($is_walk_in) {
        // Kiểm tra xem đã có walk_in_guest với id_number này chưa
        $checkWalkInStmt = $mysqli->prepare("SELECT id FROM walk_in_guest WHERE id_number = ?");
        $checkWalkInStmt->bind_param("s", $walk_in_id_number);
        $checkWalkInStmt->execute();
        $existingWalkIn = $checkWalkInStmt->get_result()->fetch_assoc();
        $checkWalkInStmt->close();
        
        if ($existingWalkIn) {
            // Sử dụng walk_in_guest đã có
            $walk_in_guest_id = $existingWalkIn['id'];
        } else {
            // Tạo walk_in_guest mới
            $insertWalkInSql = "INSERT INTO walk_in_guest (
                full_name, phone, email, id_number, address, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())";
            
            $insertWalkInStmt = $mysqli->prepare($insertWalkInSql);
            if (!$insertWalkInStmt) {
                throw new Exception('Lỗi hệ thống khi tạo thông tin khách vãng lai.');
            }
            
            $walk_in_email_null = !empty($walk_in_email) ? $walk_in_email : null;
            $walk_in_address_null = !empty($walk_in_address) ? $walk_in_address : null;
            
            $insertWalkInStmt->bind_param(
                "sssss",
                $walk_in_full_name,
                $walk_in_phone,
                $walk_in_email_null,
                $walk_in_id_number,
                $walk_in_address_null
            );
            
            if (!$insertWalkInStmt->execute()) {
                throw new Exception('Lỗi khi tạo thông tin khách vãng lai. Vui lòng thử lại.');
            }
            
            $walk_in_guest_id = $mysqli->insert_id;
            $insertWalkInStmt->close();
        }
    }

    // ========== 4. INSERT BOOKING ==========
    $quantity = intval($total_guests);
    $booking_method = 'Online';
    
    $insertBookingSql = "INSERT INTO booking (
        booking_date, 
        check_in_date, 
        check_out_date, 
        quantity,
        special_request, 
        booking_method, 
        deposit,
        status, 
        customer_id, 
        room_id,
        walk_in_guest_id,
        created_at
    ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, NOW())";

    $insertBookingStmt = $mysqli->prepare($insertBookingSql);
    
    if (!$insertBookingStmt) {
        throw new Exception('Lỗi hệ thống. Vui lòng thử lại sau.');
    }

    // Nếu là khách vãng lai, customer_id = NULL và có walk_in_guest_id
    // Nếu là khách có tài khoản, customer_id có giá trị và walk_in_guest_id = NULL
    $booking_customer_id = $is_walk_in ? null : $customer_id;

    $insertBookingStmt->bind_param(
        "ssissdiii",
        $check_in_date_only,
        $check_out_date_only,
        $quantity,
        $special_request,
        $booking_method,
        $deposit,
        $booking_customer_id,
        $room_id,
        $walk_in_guest_id
    );

    if (!$insertBookingStmt->execute()) {
        throw new Exception('Lỗi khi tạo booking. Vui lòng thử lại.');
    }

    $booking_id = $mysqli->insert_id;
    $insertBookingStmt->close();

    // ========== 5. TẠO INVOICE ==========
    $subtotal_before_vat = $room_charge + $service_charge - $discount;
    $vat = round($subtotal_before_vat * 0.10, 2);
    $other_fees = 0;
    $final_total = $subtotal_before_vat + $vat + $other_fees;
    
    // Map payment method
    $payment_method_enum = 'Bank Transfer'; // Mặc định
    if (stripos($payment_method, 'Thẻ tín dụng') !== false ) {
        $payment_method_enum = 'Credit Card';
    } elseif (stripos($payment_method, 'Ví điện tử') !== false || stripos($payment_method, 'zalo') !== false || stripos($payment_method, 'ví') !== false) {
        $payment_method_enum = 'E-Wallet';
    }
    // Tạo note cho invoice
    $invoice_note = '';
    if ($voucher_id && $voucher) {
        $invoice_note = "Áp dụng voucher " . (isset($voucher['code']) ? $voucher['code'] : '') . ". Giảm giá: " . number_format($discount, 0, ',', '.') . " VNĐ";
    }
    if (!empty($special_request)) {
        $invoice_note .= ($invoice_note ? ' | ' : '') . "Ghi chú: " . substr($special_request, 0, 100);
    }
    
    // Nếu là khách vãng lai, customer_id = NULL
    $invoice_customer_id = $is_walk_in ? null : $customer_id;
    
    $insertInvoiceSql = "INSERT INTO invoice (
        booking_id,
        room_charge,
        service_charge,
        vat,
        discount,
        other_fees,
        total_amount,
        payment_method,
        status,
        payment_time,
        note,
        customer_id,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Unpaid', NOW(), ?, ?, NOW())";
    
    $insertInvoiceStmt = $mysqli->prepare($insertInvoiceSql);
    
    if (!$insertInvoiceStmt) {
        throw new Exception('Lỗi hệ thống. Vui lòng thử lại sau.');
    }
    
    $insertInvoiceStmt->bind_param(
        "iddddddssi",
        $booking_id,
        $room_charge,
        $service_charge,
        $vat,
        $discount,
        $other_fees,
        $final_total,
        $payment_method_enum,
        $invoice_note,
        $invoice_customer_id
    );
    
    if (!$insertInvoiceStmt->execute()) {
        throw new Exception('Lỗi khi tạo hóa đơn. Vui lòng thử lại.');
    }
    
    $invoice_id = $mysqli->insert_id;
    $insertInvoiceStmt->close();

    // ========== 6. LƯU THÔNG TIN DỊCH VỤ (NẾU CÓ) ==========
    if (!empty($services)) {
        $insertServiceSql = "INSERT INTO booking_service (booking_id, service_id, unit_price) VALUES (?, ?, ?)";
        $insertServiceStmt = $mysqli->prepare($insertServiceSql);
        
        if ($insertServiceStmt) {
            foreach ($services as $service) {
                $service_id = isset($service['serviceId']) ? intval($service['serviceId']) : 0;
                $service_price = isset($service['price']) ? intval($service['price'])/100 : 0;
                
                if ($service_id > 0) {
                    $insertServiceStmt->bind_param("iid", $booking_id, $service_id, $service_price);
                    if (!$insertServiceStmt->execute()) {
                        throw new Exception('Lỗi khi lưu dịch vụ: ' . $insertServiceStmt->error);
                    }
                }
            }
            $insertServiceStmt->close();
        }
    }

    // ========== 7. CẬP NHẬT VOUCHER (NẾU CÓ) - ENHANCED ==========
    if ($voucher_id && $voucher) {
        // 7.1. Nếu là private voucher: Đánh dấu voucher_customer là đã sử dụng
        if (!$is_public_voucher) {
            $updateVoucherStmt = $mysqli->prepare("
                UPDATE voucher_customer
                SET is_used = 1, used_at = NOW(), invoice_id = ?
                WHERE customer_id = ? 
                AND voucher_id = ? 
                AND is_used = 0
            ");
            if (!$updateVoucherStmt) {
                throw new Exception('Không thể cập nhật voucher_customer: ' . $mysqli->error);
            }

            $updateVoucherStmt->bind_param("iii", $invoice_id, $customer_id, $voucher_id);
            if (!$updateVoucherStmt->execute()) {
                $updateVoucherStmt->close();
                throw new Exception('Lỗi khi cập nhật voucher_customer: ' . $updateVoucherStmt->error);
            }
            
            if ($updateVoucherStmt->affected_rows !== 1) {
                $updateVoucherStmt->close();
                throw new Exception('Voucher đã được sử dụng bởi một giao dịch khác (race condition).');
            }
            $updateVoucherStmt->close();
        }
        
        // 7.2. Tăng used_count trong bảng voucher (cho cả public và private)
        $incrementStmt = $mysqli->prepare("
            UPDATE voucher
            SET used_count = used_count + 1
            WHERE voucher_id = ?
        ");
        if (!$incrementStmt) {
            throw new Exception('Không thể cập nhật voucher.used_count: ' . $mysqli->error);
        }
        
        $incrementStmt->bind_param("i", $voucher_id);
        if (!$incrementStmt->execute()) {
            $incrementStmt->close();
            throw new Exception('Lỗi khi tăng used_count: ' . $incrementStmt->error);
        }
        $incrementStmt->close();
        
        // 7.3. Ghi log vào voucher_usage (nếu bảng tồn tại)
        $tableCheck = $mysqli->query("SHOW TABLES LIKE 'voucher_usage'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $logUsageStmt = $mysqli->prepare("
                INSERT INTO voucher_usage (
                    voucher_id, 
                    customer_id, 
                    invoice_id, 
                    discount_amount,
                    used_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            if ($logUsageStmt) {
                // Với khách vãng lai, customer_id = NULL (nhưng code này không chạy vì walk-in không được dùng voucher)
                $log_customer_id = $is_walk_in ? null : $customer_id;
                $logUsageStmt->bind_param("iiid", $voucher_id, $log_customer_id, $invoice_id, $discount);
                $logUsageStmt->execute();
                $logUsageStmt->close();
            }
        }
        
        error_log("Voucher " . (isset($voucher['code']) ? $voucher['code'] : '') . " (public: {$is_public_voucher}) successfully applied for booking {$booking_id}");
    }

    // ========== 8. UPDATE TRẠNG THÁI PHÒNG ==========
    $updateRoomStmt = $mysqli->prepare("UPDATE room SET status = 'Booked' WHERE room_id = ?");
    
    if (!$updateRoomStmt) {
        throw new Exception('Lỗi hệ thống. Vui lòng thử lại sau.');
    }
    
    $updateRoomStmt->bind_param("i", $room_id);
    
    if (!$updateRoomStmt->execute()) {
        throw new Exception('Lỗi khi cập nhật trạng thái phòng.');
    }
    
    $updateRoomStmt->close();

    // ========== 9. COMMIT TRANSACTION ==========
    $mysqli->commit();

    // Redirect về home với param để hiện modal
    $redirectUrl = '/My-Web-Hotel/client/index.php?page=home&booking_success=1';

    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // fallback nếu đã có output
        echo "<script>window.location.href = '" . addslashes($redirectUrl) . "';</script>";
        exit;
    }

} catch (Exception $e) {
    // ========== ROLLBACK NẾU CÓ LỖI ==========
    $mysqli->rollback();
    
    // Log error với context đầy đủ
    error_log('=== BOOKING ERROR ===');
    error_log('Customer ID: ' . $customer_id);
    error_log('Room ID: ' . $room_id);
    error_log('Voucher ID: ' . ($voucher_id ?? 'null'));
    error_log('Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('===================');
    
    $error_message = addslashes($e->getMessage());
    echo "<script>
        alert('❌ Lỗi: {$error_message}');
        window.history.back();
    </script>";
    exit;
}

// ========== ĐÓNG KẾT NỐI ==========
$mysqli->close();
?>