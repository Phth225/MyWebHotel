<?php

/**
 * Customer Rank Helper
 * Hàm tự động cập nhật hạng khách hàng dựa trên tổng số tiền đã tiêu
 */

/**
 * Tính tổng số tiền đã tiêu của khách hàng từ các hóa đơn đã thanh toán
 * 
 * @param mysqli $mysqli Database connection
 * @param int $customer_id ID của khách hàng
 * @return float Tổng số tiền đã tiêu (VNĐ)
 */
function calculateCustomerTotalSpent($mysqli, $customer_id)
{
    if (!$customer_id || $customer_id <= 0) {
        return 0;
    }

    $stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total_spent
        FROM invoice
        WHERE customer_id = ?
        AND status = 'Paid'
        AND deleted IS NULL
    ");

    if (!$stmt) {
        error_log("Error preparing query: " . $mysqli->error);
        return 0;
    }

    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return floatval($row['total_spent'] ?? 0);
}

/**
 * Xác định hạng khách hàng dựa trên tổng số tiền đã tiêu
 * 
 * @param float $totalSpent Tổng số tiền đã tiêu (VNĐ)
 * @return string Hạng khách hàng: 'Regular', 'Corporate', hoặc 'VIP'
 */
function determineCustomerRank($totalSpent)
{
    if ($totalSpent >= 100000000) { // >= 100 triệu
        return 'VIP';
    } elseif ($totalSpent >= 40000000) { // >= 40 triệu
        return 'Corporate';
    } else {
        return 'Regular';
    }
}

/**
 * Tự động cập nhật hạng khách hàng dựa trên tổng số tiền đã tiêu
 * 
 * @param mysqli $mysqli Database connection
 * @param int $customer_id ID của khách hàng
 * @return array Kết quả cập nhật: ['success' => bool, 'old_rank' => string, 'new_rank' => string, 'total_spent' => float]
 */
function updateCustomerRank($mysqli, $customer_id)
{
    if (!$customer_id || $customer_id <= 0) {
        return [
            'success' => false,
            'message' => 'Customer ID không hợp lệ'
        ];
    }

    // Kiểm tra khách hàng có tồn tại không
    $checkStmt = $mysqli->prepare("SELECT customer_type FROM customer WHERE customer_id = ? AND deleted IS NULL");
    if (!$checkStmt) {
        return [
            'success' => false,
            'message' => 'Lỗi kiểm tra khách hàng: ' . $mysqli->error
        ];
    }

    $checkStmt->bind_param("i", $customer_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $customer = $result->fetch_assoc();
    $checkStmt->close();

    if (!$customer) {
        return [
            'success' => false,
            'message' => 'Khách hàng không tồn tại'
        ];
    }

    $old_rank = $customer['customer_type'] ?? 'Regular';

    // Tính tổng số tiền đã tiêu
    $totalSpent = calculateCustomerTotalSpent($mysqli, $customer_id);

    // Xác định hạng mới
    $new_rank = determineCustomerRank($totalSpent);

    // Chỉ cập nhật nếu hạng thay đổi
    if ($old_rank !== $new_rank) {
        $updateStmt = $mysqli->prepare("UPDATE customer SET customer_type = ? WHERE customer_id = ? AND deleted IS NULL");
        if (!$updateStmt) {
            return [
                'success' => false,
                'message' => 'Lỗi chuẩn bị cập nhật: ' . $mysqli->error
            ];
        }

        $updateStmt->bind_param("si", $new_rank, $customer_id);

        if ($updateStmt->execute()) {
            $updateStmt->close();

            error_log("Customer rank updated: Customer ID {$customer_id} - {$old_rank} -> {$new_rank} (Total spent: " . number_format($totalSpent, 0, ',', '.') . " VNĐ)");

            return [
                'success' => true,
                'old_rank' => $old_rank,
                'new_rank' => $new_rank,
                'total_spent' => $totalSpent,
                'message' => "Hạng khách hàng đã được cập nhật từ {$old_rank} lên {$new_rank}"
            ];
        } else {
            $error = $updateStmt->error;
            $updateStmt->close();
            return [
                'success' => false,
                'message' => 'Lỗi cập nhật hạng: ' . $error
            ];
        }
    } else {
        // Hạng không thay đổi
        return [
            'success' => true,
            'old_rank' => $old_rank,
            'new_rank' => $new_rank,
            'total_spent' => $totalSpent,
            'message' => "Hạng khách hàng không thay đổi: {$old_rank} (Total spent: " . number_format($totalSpent, 0, ',', '.') . " VNĐ)"
        ];
    }
}

/**
 * Cập nhật hạng cho tất cả khách hàng (dùng cho batch update hoặc cron job)
 * 
 * @param mysqli $mysqli Database connection
 * @return array Kết quả: ['total' => int, 'updated' => int, 'errors' => array]
 */
function updateAllCustomerRanks($mysqli)
{
    $result = [
        'total' => 0,
        'updated' => 0,
        'errors' => []
    ];

    // Lấy tất cả khách hàng chưa bị xóa
    $customersQuery = $mysqli->query("SELECT customer_id FROM customer WHERE deleted IS NULL");

    if (!$customersQuery) {
        $result['errors'][] = 'Lỗi lấy danh sách khách hàng: ' . $mysqli->error;
        return $result;
    }

    while ($row = $customersQuery->fetch_assoc()) {
        $result['total']++;
        $updateResult = updateCustomerRank($mysqli, $row['customer_id']);

        if ($updateResult['success']) {
            if ($updateResult['old_rank'] !== $updateResult['new_rank']) {
                $result['updated']++;
            }
        } else {
            $result['errors'][] = "Customer ID {$row['customer_id']}: " . $updateResult['message'];
        }
    }

    return $result;
}



