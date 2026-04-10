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
$service_id = isset($_POST['serviceId']) ? intval($_POST['serviceId']) : 0;
$service_date = isset($_POST['serviceDate']) ? trim($_POST['serviceDate']) : '';
$service_time = isset($_POST['serviceTime']) ? trim($_POST['serviceTime']) : '';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
// Thông tin tài chính
$service_price = isset($_POST['servicePrice']) ? floatval($_POST['servicePrice']) : 0;
$vat = isset($_POST['vat']) ? floatval($_POST['vat']) : 0;
$discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
$total_amount = isset($_POST['total']) ? floatval($_POST['total']) : 0;

// Thông tin voucher
$voucher_id = isset($_POST['voucherId']) && $_POST['voucherId'] !== '' ? intval($_POST['voucherId']) : null;

// Thông tin thanh toán
$payment_method = isset($_POST['paymentMethod']) ? trim($_POST['paymentMethod']) : '';

// Thông tin khách vãng lai (nếu không đăng nhập)
$walk_in_full_name = isset($_POST['walkInFullName']) ? trim($_POST['walkInFullName']) : '';
$walk_in_phone = isset($_POST['walkInPhone']) ? trim($_POST['walkInPhone']) : '';
$walk_in_email = isset($_POST['walkInEmail']) ? trim($_POST['walkInEmail']) : '';
$walk_in_id_number = isset($_POST['walkInIdNumber']) ? trim($_POST['walkInIdNumber']) : '';
$walk_in_address = isset($_POST['walkInAddress']) ? trim($_POST['walkInAddress']) : '';

// ========== VALIDATION CƠ BẢN ==========
$errors = [];
$usage_date = ''; // ← THÊM DÒNG NÀY để khởi tạo biến

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
    }
    if (!empty($walk_in_email) && !filter_var($walk_in_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    }
}

if ($service_id <= 0) {
    $errors[] = 'ID dịch vụ không hợp lệ';
}

if (empty($service_date) || empty($service_time)) {
    $errors[] = 'Vui lòng nhập đầy đủ ngày và giờ sử dụng dịch vụ';
}

if (empty($payment_method)) {
    $errors[] = 'Vui lòng chọn phương thức thanh toán';
}

if ($quantity < 1) {
    $errors[] = 'Số người phải ít nhất là 1';
}

if ($total_amount < 0) {
    $errors[] = 'Thông tin tài chính không hợp lệ';
}

// Validate date format (expected format: YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $service_date)) {
    $errors[] = 'Định dạng ngày phải là YYYY-MM-DD';
} else {
    // Validate date components
    $date_parts = explode('-', $service_date);
    if (count($date_parts) === 3) {
        $year = (int)$date_parts[0];
        $month = (int)$date_parts[1];
        $day = (int)$date_parts[2];
        
        if (!checkdate($month, $day, $year)) {
            $errors[] = 'Ngày không hợp lệ';
        } else {
            $usage_date = date('Y-m-d', strtotime($service_date));
            
            // Check if date is in the future
            $today = new DateTime();
            $selected_date = new DateTime($usage_date);
            $interval = $today->diff($selected_date);
            
            if ($interval->invert && !$interval->days === 0) {
                $errors[] = 'Ngày sử dụng phải là ngày hiện tại hoặc trong tương lai';
            }
        }
    }
}

if (empty($usage_date) && empty($errors)) {
    $usage_date = date('Y-m-d', strtotime($service_date));
}
// Validate date and time together
$usage_datetime = $service_date . ' ' . $service_time;
$usage_timestamp = strtotime($usage_datetime);

if ($usage_timestamp === false) {
    $errors[] = 'Định dạng giờ không hợp lệ';
} elseif ($usage_timestamp < time()) {
    $errors[] = 'Thời gian sử dụng phải là thời gian hiện tại hoặc trong tương lai';
}

// Nếu có lỗi, trả về ngay
if (!empty($errors)) {
    $error_message = implode('\\n', array_map('addslashes', $errors));
    echo "<script>alert('Lỗi:\\n{$error_message}'); window.history.back();</script>";
    exit;
}

// ========== BẮT ĐẦU TRANSACTION ==========
$mysqli->begin_transaction();

try {
    // ========== 1. KIỂM TRA DỊCH VỤ ==========
    $checkServiceStmt = $mysqli->prepare("
        SELECT service_id, service_name, price, status, unit
        FROM service 
        WHERE service_id = ? AND deleted IS NULL
    ");
    
    if (!$checkServiceStmt) {
        throw new Exception('Lỗi hệ thống. Vui lòng thử lại sau.');
    }
    
    $checkServiceStmt->bind_param("i", $service_id);
    $checkServiceStmt->execute();
    $serviceResult = $checkServiceStmt->get_result();
    $service = $serviceResult->fetch_assoc();
    $checkServiceStmt->close();

    if (!$service) {
        throw new Exception('Dịch vụ không tồn tại.');
    }

    if ($service['status'] !== 'Active') {
        throw new Exception('Dịch vụ hiện không khả dụng (Trạng thái: ' . $service['status'] . ').');
    }

    // ========== 2. VALIDATE VOUCHER (NẾU CÓ) - ENHANCED ==========
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
            // Voucher công khai
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
                    v.service_ids,
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
            // Voucher riêng
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
                    v.service_ids,
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
        
        // Kiểm tra is_used cho private voucher
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
                FROM invoice_service
                WHERE customer_id = ? 
                AND note LIKE CONCAT('%', ?, '%')
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
        
        // ========== VALIDATE APPLY_TO ==========
        $subtotal = $service_price;
        
        if (isset($voucher['apply_to']) && $voucher['apply_to'] === 'service') {
            if (!empty($voucher['service_ids'])) {
                $allowedServiceIds = array_map('intval', array_map('trim', explode(',', $voucher['service_ids'])));
                if (!in_array($service_id, $allowedServiceIds, true)) {
                    throw new Exception('Voucher không áp dụng cho dịch vụ này.');
                }
            }
        } elseif (isset($voucher['apply_to']) && $voucher['apply_to'] === 'room') {
            throw new Exception('Voucher chỉ áp dụng cho đặt phòng.');
        }
        // apply_to = 'all' thì OK

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
        if ($discount_diff > 1) {
            error_log("Discount mismatch: calculated={$calculated_discount}, received={$discount}");
            $discount = $calculated_discount;
        }
    } // end voucher validation

    // ========== 3. TÍNH TOÁN LẠI GIÁ TRỊ ĐƠN HÀNG ==========
    $unit_price = floatval($service['price']);
    $calculated_service_price = $unit_price * $quantity;
    
    // Kiểm tra service_price từ client
    if (abs($calculated_service_price - $service_price) > 1) {
        error_log("Service price mismatch: calculated={$calculated_service_price}, received={$service_price}");
        $service_price = $calculated_service_price;
    }
    
    $taxable_amount = max(0, $service_price - $discount);
    $calculated_vat = round($taxable_amount * 0.1);
    $calculated_total = $taxable_amount + $calculated_vat;
    
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
    
    // ========== 4. INSERT BOOKING_SERVICE ==========
    $unit = $service['unit'] ?? '';

    $insertServiceSql = "INSERT INTO booking_service (
        booking_id,
        service_id,
        customer_id,
        walk_in_guest_id,
        quantity,
        usage_date,
        usage_time,
        unit_price,
        created_at,
        status,
        deleted
    ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending', NULL)";

    $insertServiceStmt = $mysqli->prepare($insertServiceSql);

    if (!$insertServiceStmt) {
        throw new Exception('Lỗi hệ thống. Vui lòng thử lại sau.');
    }

    // Nếu là khách vãng lai, customer_id = NULL và có walk_in_guest_id
    // Nếu là khách có tài khoản, customer_id có giá trị và walk_in_guest_id = NULL
    $booking_service_customer_id = $is_walk_in ? null : $customer_id;
    $booking_service_walk_in_guest_id = $is_walk_in ? $walk_in_guest_id : null;

    $insertServiceStmt->bind_param(
        "iiisssd",
        $service_id,
        $booking_service_customer_id,
        $booking_service_walk_in_guest_id,
        $quantity,
        $usage_date,
        $service_time,
        $unit_price
    );

    if (!$insertServiceStmt->execute()) {
        throw new Exception('Lỗi khi tạo booking service. Vui lòng thử lại.');
    }

    $booking_service_id = $mysqli->insert_id;
    $insertServiceStmt->close();
    // TẠO INVOICE
    // Nếu là khách vãng lai, customer_id = NULL
    $invoice_customer_id = $is_walk_in ? null : $customer_id;
    
    $insertInvoiceSql = "INSERT INTO invoice (
                        customer_id,
                        total_amount,
                        vat,
                        discount,
                        payment_method,
                        note,
                        status,
                        created_at
                        ) VALUES (?, ?, ?, ?, ?, ?,'Paid', NOW())";

    $insertInvoiceStmt = $mysqli->prepare($insertInvoiceSql);
    
    if (!$insertInvoiceStmt) {
        throw new Exception('Lỗi hệ thống. Vui lòng thử lại sau.');
    }

    // Map payment method
    $payment_method_enum = 'Bank Transfer'; // Mặc định
    if (stripos($payment_method, 'Thẻ tín dụng') !== false ) {
        $payment_method_enum = 'Credit Card';
    } elseif (stripos($payment_method, 'Ví điện tử') !== false || stripos($payment_method, 'zalo') !== false || stripos($payment_method, 'ví') !== false) {
        $payment_method_enum = 'E-Wallet';
    }

    $insertInvoiceStmt->bind_param(
        "idddss",
        $invoice_customer_id,
        $total_amount,
        $calculated_vat,
        $discount,
        $payment_method_enum,
        $notes
    );

    if (!$insertInvoiceStmt->execute()) {
        throw new Exception('Lỗi khi tạo hóa đơn. Vui lòng thử lại.');
    }

    $invoice_id = $insertInvoiceStmt->insert_id;
    $insertInvoiceStmt->close();   
 // ========== 5. TẠO INVOICE_SERVICE ==========
    
    // Tạo note cho invoice
    
    // Tạo note cho invoice
    $invoice_note = '';
    if ($voucher_id && $voucher) {
        $invoice_note = "Áp dụng voucher " . (isset($voucher['code']) ? $voucher['code'] : '') . ". Giảm giá: " . number_format($discount, 0, ',', '.') . " VNĐ";
    }
    if (!empty($notes)) {
        $invoice_note .= ($invoice_note ? ' | ' : '') . "Ghi chú: " . substr($notes, 0, 100);
    }
    
    $insertInvoiceServiceSql = "INSERT INTO invoice_service (
        booking_service_id,
        invoice_id,
        created_at
    ) VALUES (?, ?, NOW())";
    
    $insertInvoiceServiceStmt = $mysqli->prepare($insertInvoiceServiceSql);
    
    if (!$insertInvoiceServiceStmt) {
        throw new Exception('Lỗi hệ thống. Vui lòng thử lại sau.');
    }
    
    $insertInvoiceServiceStmt->bind_param(
        "ii",
        $booking_service_id,
        $invoice_id
    );
    
    if (!$insertInvoiceServiceStmt->execute()) {
        throw new Exception('Lỗi khi tạo hóa đơn. Vui lòng thử lại.');
    }
    
    $insertInvoiceServiceStmt->close();

    // ========== 6. CẬP NHẬT VOUCHER (NẾU CÓ) - ENHANCED ==========
    if ($voucher_id && $voucher) {
        // 6.1. Nếu là private voucher: Đánh dấu voucher_customer là đã sử dụng
        // Khách vãng lai không có private voucher nên bỏ qua
        if (!$is_public_voucher && !$is_walk_in) {
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
        
        // 6.2. Tăng used_count trong bảng voucher
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
        
        // 6.3. Ghi log vào voucher_usage
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
        
        error_log("Voucher " . (isset($voucher['code']) ? $voucher['code'] : '') . " (public: {$is_public_voucher}) successfully applied for service booking {$booking_service_id}");
    }

    // ========== 7. COMMIT TRANSACTION ==========
    $mysqli->commit();

    // Redirect về home với param để hiện modal
    $redirectUrl = '/My-Web-Hotel/client/index.php?page=home&booking_success=1';

    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        echo "<script>window.location.href = '" . addslashes($redirectUrl) . "';</script>";
        exit;
    }

} catch (Exception $e) {
    // ========== ROLLBACK NẾU CÓ LỖI ==========
    $mysqli->rollback();
    
    // Log error với context đầy đủ
    error_log('=== SERVICE BOOKING ERROR ===');
    error_log('Customer ID: ' . $customer_id);
    error_log('Service ID: ' . $service_id);
    error_log('Voucher ID: ' . ($voucher_id ?? 'null'));
    error_log('Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('============================');
    
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