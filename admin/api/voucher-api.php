<?php
session_start();
require '../includes/connect.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Xem chi tiết voucher
if ($action == 'view' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT v.*, 
        nv.ho_ten as created_by_name,
        (SELECT COUNT(*) FROM voucher_usage vu WHERE vu.voucher_id = v.voucher_id) as usage_count,
        (SELECT COUNT(*) FROM voucher_customer vc WHERE vc.voucher_id = v.voucher_id) as assigned_count
        FROM voucher v
        LEFT JOIN nhan_vien nv ON v.created_by = nv.id_nhan_vien
        WHERE v.voucher_id = ? AND v.deleted IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $voucher = $result->fetch_assoc();
    $stmt->close();

    if ($voucher) {
        // Parse các trường dạng string thành array
        $voucher['customer_types_array'] = !empty($voucher['customer_types']) ? explode(',', $voucher['customer_types']) : [];
        $voucher['room_types_array'] = !empty($voucher['room_types']) ? explode(',', $voucher['room_types']) : [];
        $voucher['service_ids_array'] = !empty($voucher['service_ids']) ? explode(',', $voucher['service_ids']) : [];
        $voucher['valid_days_array'] = !empty($voucher['valid_days']) ? explode(',', $voucher['valid_days']) : [];
        $voucher['payment_methods_array'] = !empty($voucher['payment_methods']) ? explode(',', $voucher['payment_methods']) : [];

        // Lấy tên room types
        if (!empty($voucher['room_types_array'])) {
            $roomTypeIds = implode(',', array_map('intval', $voucher['room_types_array']));
            $roomTypesResult = $mysqli->query("SELECT room_type_id, room_type_name FROM room_type WHERE room_type_id IN ($roomTypeIds)");
            $voucher['room_types_names'] = $roomTypesResult->fetch_all(MYSQLI_ASSOC);
        }

        // Lấy tên services
        if (!empty($voucher['service_ids_array'])) {
            $serviceIds = implode(',', array_map('intval', $voucher['service_ids_array']));
            $servicesResult = $mysqli->query("SELECT service_id, service_name FROM service WHERE service_id IN ($serviceIds)");
            $voucher['services_names'] = $servicesResult->fetch_all(MYSQLI_ASSOC);
        }

        // Format HTML
        ob_start();
?>
        <div class="px-2 pb-3">
            <!-- Header Info -->
            <div class="text-center mb-4">
                 <span class="badge bg-primary px-3 py-2 rounded-pill mb-2 fs-6 shadow-sm"><?php echo h($voucher['code']); ?></span>
                 <h4 class="fw-bold text-dark mb-1"><?php echo h($voucher['name']); ?></h4>
                 <?php if($voucher['description']): ?>
                    <div class="text-muted fst-italic small"><?php echo h($voucher['description']); ?></div>
                 <?php endif; ?>
                 <div class="mt-2 text-muted small">
                     <i class="fas fa-clock me-1"></i>Ngày tạo: <?php echo formatDate($voucher['created_at']); ?>
                     <span class="mx-2">|</span>
                     <i class="fas fa-user me-1"></i>Bởi: <?php echo h($voucher['created_by_name'] ?: 'System'); ?>
                 </div>
            </div>

            <!-- Main Discount Card -->
            <div class="bg-light rounded-3 p-4 text-center border mb-4 position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 p-3 opacity-10">
                    <i class="fas fa-gift fa-5x text-primary"></i>
                </div>
                <div class="position-relative z-1">
                    <div class="text-uppercase text-secondary fw-bold small mb-2">Giá Trị Ưu Đãi</div>
                    <div class="display-4 fw-bold text-success mb-0" style="font-family: 'Outfit', sans-serif;">
                         <?php if ($voucher['discount_type'] == 'percent'): ?>
                            <?php echo number_format($voucher['discount_value'], 0); ?>%
                        <?php else: ?>
                            <?php echo number_format($voucher['discount_value'], 0, ',', '.'); ?><span class="fs-4">đ</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($voucher['discount_type'] == 'percent' && $voucher['max_discount']): ?>
                        <div class="text-muted small mt-1">Giảm tối đa <?php echo formatCurrency($voucher['max_discount']); ?></div>
                    <?php endif; ?>
                    
                    <div class="mt-3 d-flex justify-content-center gap-2">
                        <span class="badge bg-white text-dark border shadow-sm px-3 py-2">
                             <i class="fas fa-shopping-cart me-1 text-secondary"></i>
                             Đơn tối thiểu: <?php echo $voucher['min_order'] > 0 ? formatCurrency($voucher['min_order']) : '0đ'; ?>
                        </span>
                        <span class="badge <?php echo $voucher['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?> px-3 py-2 shadow-sm">
                             <?php echo $voucher['status'] == 'active' ? 'Đang hoạt động' : 'Tạm dừng'; ?>
                        </span>
                    </div>
                </div>
            </div>

             <div class="row g-4">
                 <!-- Left Column: Conditions -->
                 <div class="col-md-6">
                     <div class="card h-100 border-0 shadow-sm">
                         <div class="card-header bg-white border-0 pt-3 pb-0">
                             <h6 class="text-uppercase text-secondary fw-bold small mb-0">
                                <i class="fas fa-clipboard-check me-2 text-primary"></i>Điều Kiện Áp Dụng
                             </h6>
                         </div>
                         <div class="card-body">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted ps-0" style="width: 130px;">Phạm vi:</td>
                                    <td class="fw-bold text-dark">
                                         <?php
                                        $applyToText = ['all' => 'Tất cả', 'room' => 'Phòng nghỉ', 'service' => 'Dịch vụ'];
                                        echo $applyToText[$voucher['apply_to']] ?? $voucher['apply_to'];
                                        ?>
                                    </td>
                                </tr>
                                <?php if (!empty($voucher['customer_types_array'])): ?>
                                <tr>
                                    <td class="text-muted ps-0">Khách hàng:</td>
                                    <td>
                                        <?php foreach ($voucher['customer_types_array'] as $type): ?>
                                            <span class="badge bg-light text-dark border me-1"><?php echo $type; ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($voucher['min_nights'] || $voucher['min_rooms']): ?>
                                <tr>
                                    <td class="text-muted ps-0">Yêu cầu đặt:</td>
                                    <td>
                                        <?php if($voucher['min_nights']) echo '<div>Min ' . $voucher['min_nights'] . ' đêm</div>'; ?>
                                        <?php if($voucher['min_rooms']) echo '<div>Min ' . $voucher['min_rooms'] . ' phòng</div>'; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($voucher['room_types_names'])): ?>
                                <tr>
                                    <td class="text-muted ps-0">Loại phòng:</td>
                                    <td>
                                        <?php foreach ($voucher['room_types_names'] as $rt): ?>
                                            <div class="small mb-1"><i class="fas fa-check text-success me-1"></i><?php echo h($rt['room_type_name']); ?></div>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($voucher['services_names'])): ?>
                                <tr>
                                    <td class="text-muted ps-0">Dịch vụ:</td>
                                    <td>
                                        <?php foreach ($voucher['services_names'] as $sv): ?>
                                             <div class="small mb-1"><i class="fas fa-check text-success me-1"></i><?php echo h($sv['service_name']); ?></div>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                         </div>
                     </div>
                 </div>

                 <!-- Right Column: Usage & Validity -->
                 <div class="col-md-6">
                     <div class="card h-100 border-0 shadow-sm">
                         <div class="card-header bg-white border-0 pt-3 pb-0">
                             <h6 class="text-uppercase text-secondary fw-bold small mb-0">
                                <i class="fas fa-history me-2 text-primary"></i>Hiệu Lực & Sử Dụng
                             </h6>
                         </div>
                         <div class="card-body">
                             <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted ps-0" style="width: 130px;">Ngày bắt đầu:</td>
                                    <td class="fw-bold"><?php echo date('d/m/Y', strtotime($voucher['start_date'])); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0">Ngày kết thúc:</td>
                                    <td class="fw-bold"><?php echo date('d/m/Y', strtotime($voucher['end_date'])); ?></td>
                                </tr>
                                <?php if (!empty($voucher['valid_days_array'])): ?>
                                <tr>
                                    <td class="text-muted ps-0">Ngày dùng:</td>
                                    <td><?php echo implode(', ', $voucher['valid_days_array']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($voucher['valid_hours']): ?>
                                <tr>
                                    <td class="text-muted ps-0">Khung giờ:</td>
                                    <td><span class="badge bg-warning text-dark"><?php echo h($voucher['valid_hours']); ?></span></td>
                                </tr>
                                <?php endif; ?>
                                <tr><td colspan="2"><hr class="my-2 text-muted opacity-25"></td></tr>
                                <tr>
                                    <td class="text-muted ps-0">Lượt dùng:</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <?php 
                                                    $percent = ($voucher['usage_count'] / max(1, $voucher['total_uses'])) * 100;
                                                ?>
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                                            </div>
                                            <span class="small fw-bold"><?php echo $voucher['usage_count']; ?>/<?php echo $voucher['total_uses']; ?></span>
                                        </div>
                                        <div class="small text-muted mt-1">(Max <?php echo $voucher['per_customer']; ?>/khách)</div>
                                    </td>
                                </tr>
                             </table>
                         </div>
                     </div>
                 </div>
             </div>
             
             <!-- Settings Badges -->
             <div class="mt-4 pt-3 border-top text-center">
                 <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <?php if($voucher['is_public']): ?>
                        <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info"><i class="fas fa-globe me-1"></i>Công khai Website</span>
                    <?php endif; ?>
                    <?php if($voucher['auto_apply']): ?>
                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success"><i class="fas fa-magic me-1"></i>Tự động áp dụng</span>
                    <?php endif; ?>
                    <?php if($voucher['is_stackable']): ?>
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary"><i class="fas fa-layer-group me-1"></i>Dùng chung Voucher khác</span>
                    <?php endif; ?>
                    <?php if($voucher['is_featured']): ?>
                        <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning"><i class="fas fa-star me-1"></i>Nổi bật</span>
                    <?php endif; ?>
                 </div>
             </div>
        </div>
    <?php
        $html = ob_get_clean();

        echo json_encode(['success' => true, 'html' => $html]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy voucher']);
    }
    exit;
}

// Xem lịch sử sử dụng voucher
if ($action == 'usage' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Lấy thông tin voucher
    $stmt = $mysqli->prepare("SELECT code, name FROM voucher WHERE voucher_id = ? AND deleted IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $voucher = $result->fetch_assoc();
    $stmt->close();

    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy voucher']);
        exit;
    }

    // Lấy lịch sử sử dụng
    $stmt = $mysqli->prepare("SELECT vu.*, 
        c.full_name as customer_name, c.phone as customer_phone,
        i.invoice_id, i.total_amount, i.discount
        FROM voucher_usage vu
        LEFT JOIN customer c ON vu.customer_id = c.customer_id
        LEFT JOIN invoice i ON vu.invoice_id = i.invoice_id
        WHERE vu.voucher_id = ?
        ORDER BY vu.used_at DESC
        LIMIT 100");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usages = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Format HTML
    ob_start();
    ?>
    <div class="mb-3">
        <h6>Voucher: <strong><?php echo h($voucher['code']); ?> - <?php echo h($voucher['name']); ?></strong></h6>
        <p class="text-muted">Tổng số lần sử dụng: <strong><?php echo count($usages); ?></strong></p>
    </div>

    <?php if (empty($usages)): ?>
        <div class="alert alert-info">Chưa có lịch sử sử dụng.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Khách hàng</th>
                        <th>Hóa đơn</th>
                        <th>Giá trị giảm</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usages as $usage): ?>
                        <tr>
                            <td><?php echo formatDate($usage['used_at'], true); ?></td>
                            <td>
                                <?php if ($usage['customer_name']): ?>
                                    <?php echo h($usage['customer_name']); ?><br>
                                    <small class="text-muted"><?php echo h($usage['customer_phone']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($usage['invoice_id']): ?>
                                    <a href="index.php?page=invoices-manager&action=view&id=<?php echo $usage['invoice_id']; ?>" target="_blank">
                                        #<?php echo $usage['invoice_id']; ?>
                                    </a>
                                    <br><small class="text-muted"><?php echo formatCurrency($usage['total_amount']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-success">-<?php echo formatCurrency($usage['discount_amount']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php
    $html = ob_get_clean();

    echo json_encode(['success' => true, 'html' => $html]);
    exit;
}

// Lấy danh sách khách hàng đã được gán voucher
if ($action == 'assigned_customers' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $mysqli->prepare("SELECT vc.*, 
        c.full_name, c.phone, c.email,
        nv.ho_ten as assigned_by_name
        FROM voucher_customer vc
        LEFT JOIN customer c ON vc.customer_id = c.customer_id
        LEFT JOIN nhan_vien nv ON vc.assigned_by = nv.id_nhan_vien
        WHERE vc.voucher_id = ?
        ORDER BY vc.assigned_at DESC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assigned = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $assigned]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
