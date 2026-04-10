<?php
// ========== KIỂM TRA SESSION VÀ ĐĂNG NHẬP ==========
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);

// Không redirect nữa - cho phép khách vãng lai đặt phòng
// ========== LẤY THÔNG TIN NGƯỜI DÙNG ==========
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : '';
$customerId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// ========== LẤY THÔNG TIN KHÁCH HÀNG TỪ DATABASE ==========
$customer = null;
if ($customerId > 0) {
    $customerStmt = $mysqli->prepare("SELECT full_name, email, phone FROM customer WHERE customer_id = ? AND deleted IS NULL");
    
    if (!$customerStmt) {
        error_log("Database Error: " . $mysqli->error);
        echo "<script>alert('Lỗi hệ thống. Vui lòng thử lại sau.'); window.history.back();</script>";
        exit;
    }
    
    $customerStmt->bind_param("i", $customerId);
    $customerStmt->execute();
    $customerResult = $customerStmt->get_result();
    $customer = $customerResult->fetch_assoc();
    $customerStmt->close();
}

// ========== LẤY THÔNG TIN PHÒNG TỪ URL ==========
$room = null;
$roomId = 0;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $roomId = intval($_GET['id']);

    // Lấy thông tin phòng với JOIN để lấy đầy đủ dữ liệu
    $stmt = $mysqli->prepare("
        SELECT 
            r.room_id, 
            r.status, 
            r.room_number,
            rt.room_type_name AS room_type, 
            rt.base_price AS room_price, 
            rt.description AS `desc`, 
            rt.capacity
        FROM room r
        JOIN room_type rt ON rt.room_type_id = r.room_type_id
        WHERE r.room_id = ? AND r.deleted IS NULL
    ");
    
    if (!$stmt) {
        error_log("Database Error: " . $mysqli->error);
        echo "<script>alert('Lỗi hệ thống. Vui lòng thử lại sau.'); window.history.back();</script>";
        exit;
    }
    
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $roomResult = $stmt->get_result();
    $room = $roomResult->fetch_assoc();
    $stmt->close();
    
    if (!$room) {
        echo "<script>alert('Phòng không tồn tại hoặc đã bị xóa.'); window.location.href = '/My-Web-Hotel/client/index.php';</script>";
        exit;
    }
    
    // Kiểm tra trạng thái phòng
    if ($room['status'] !== 'Available') {
        echo "<script>alert('Phòng hiện không có sẵn.'); window.location.href = '/My-Web-Hotel/client/index.php?page=rooms';</script>";
        exit;
    }
} else {
    echo "<script>alert('Vui lòng chọn phòng để đặt.'); window.location.href = '/My-Web-Hotel/client/index.php?page=rooms';</script>";
    exit;
}

// ========== LẤY DANH SÁCH DỊCH VỤ ==========
$servicesData = [];
$serviceStmt = $mysqli->prepare("
    SELECT service_id, service_name, price, service_type 
    FROM service 
    WHERE service_type = 'Dịch vụ cá nhân' AND deleted IS NULL
    ORDER BY service_name ASC
");

if ($serviceStmt) {
    $serviceStmt->execute();
    $serviceResult = $serviceStmt->get_result();
    
    while ($service = $serviceResult->fetch_assoc()) {
        $servicesData[] = $service;
    }
    
    $serviceStmt->close();
}
?>
<main>
    <div class="container">
        <!-- ========== FORM ĐẶT PHÒNG ========== -->
        <div class="booking-form" id="bookingForm">
            <div class="header">
                <h1>Booking OceanPearl</h1>
                <p>Điền thông tin để hoàn tất đặt phòng</p>
            </div>

            <form autocomplete="off" id="roomBookingForm" style="padding: 40px"
                data-room-id="<?= htmlspecialchars($room['room_id']) ?>"
                data-capacity="<?= htmlspecialchars($room['capacity']) ?>">

                <!-- THÔNG TIN KHÁCH VÃNG LAI (chỉ hiện khi chưa đăng nhập) -->
                <?php if (!$isLoggedIn): ?>
                <div class="form-section" id="walkInGuestSection">
                    <div class="section-title">Thông Tin Khách Vãng Lai</div>
                    <div class="info-box"
                        style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                        <i class="fa-solid fa-info-circle" style="color: #856404; margin-right: 8px;"></i>
                        <span style="color: #856404; font-weight: 500;">
                            Bạn chưa đăng nhập. Vui lòng điền thông tin bên dưới để đặt phòng.
                        </span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="walkInFullName">Họ và tên <span class="required">*</span></label>
                            <input type="text" id="walkInFullName" name="walkInFullName" required
                                placeholder="Nhập họ và tên" pattern="[A-Za-zÀ-ỹ\s]{2,50}"
                                title="Họ và tên phải từ 2-50 ký tự, chỉ chứa chữ cái và khoảng trắng" />
                        </div>
                        <div class="form-group">
                            <label for="walkInPhone">Số điện thoại <span class="required">*</span></label>
                            <input type="tel" id="walkInPhone" name="walkInPhone" required placeholder="0912345678"
                                pattern="[0-9]{10,11}" title="Số điện thoại phải có 10-11 chữ số" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="walkInIdNumber">CMND/CCCD <span class="required">*</span></label>
                            <input type="text" id="walkInIdNumber" name="walkInIdNumber" required
                                placeholder="Nhập số CMND/CCCD (9-12 chữ số)" pattern="[0-9]{9,12}"
                                title="Số CMND/CCCD phải có 9-12 chữ số" />
                        </div>
                        <div class="form-group">
                            <label for="walkInEmail">Email <span class="required">*</span></label>
                            <input type="email" id="walkInEmail" name="walkInEmail" required
                                placeholder="example@email.com" title="Email không hợp lệ" />
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label for="walkInAddress">Địa chỉ</label>
                        <input type="text" id="walkInAddress" name="walkInAddress"
                            placeholder="Nhập địa chỉ (tùy chọn)" />
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========== THÔNG TIN PHÒNG ========== -->
                <div class="form-section">
                    <div class="section-title">Thông Tin Phòng</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="roomName">Tên phòng</label>
                            <input type="text" id="roomName" name="roomName"
                                value="<?= htmlspecialchars($room['room_number']) ?>" readonly
                                style="background-color: #f5f5f5;" />
                        </div>
                        <div class="form-group">
                            <label for="roomType">Loại phòng</label>
                            <input type="text" id="roomType" name="roomType"
                                value="<?= htmlspecialchars($room['room_type']) ?>" readonly
                                style="background-color: #f5f5f5;" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="guestCount">Số người lớn<span class="required">*</span></label>
                            <input type="number" id="guestCount" name="guestCount" min="1"
                                max="<?= htmlspecialchars($room['capacity']) ?>" required placeholder="Nhập số người" />
                        </div>
                        <div class="form-group">
                            <label for="childCount">Số trẻ em <span class="required">*</span></label>
                            <input type="number" id="childCount" name="childCount" min="0"
                                max="<?= htmlspecialchars($room['capacity']) ?>" required placeholder="Nhập số người" />
                        </div>
                    </div>
                </div>

                <!-- ========== THỜI GIAN LƯU TRÚ ========== -->
                <div class="form-section">
                    <div class="section-title">Thời Gian Lưu Trú</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="checkinTime">Check-in <span class="required">*</span></label>
                            <input type="datetime-local" id="checkinTime" name="checkinTime" required />
                        </div>
                        <div class="form-group">
                            <label for="checkoutTime">Check-out <span class="required">*</span></label>
                            <input type="datetime-local" id="checkoutTime" name="checkoutTime" required />
                        </div>
                    </div>
                    <div class="info-box"
                        style="margin-top: 15px; padding: 10px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 4px;">
                        <i class="fa-solid fa-moon" style="color: #4caf50; margin-right: 8px;"></i>
                        <span style="color: #2e7d32; font-weight: 500;">
                            Số đêm: <span id="nightCountDisplay">0</span> đêm
                        </span>
                    </div>
                </div>

                <!-- ========== GIÁ PHÒNG & DỊCH VỤ ========== -->
                <div class="form-section">
                    <div class="section-title">Giá Phòng & Dịch Vụ</div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="roomPrice">Giá phòng/đêm (VNĐ)</label>
                            <input type="text" id="roomPrice" name="roomPrice"
                                value="<?= number_format($room['room_price'], 0, ',', '.') ?>" readonly
                                style="background-color: #f5f5f5;" />
                        </div>
                    </div>

                    <!-- Dịch vụ kèm theo -->
                    <?php if (!empty($servicesData)): ?>
                    <div style="margin-top: 20px">
                        <label style="font-weight: 600; color: #333; margin-bottom: 15px; display: block;">
                            Dịch vụ kèm theo (tùy chọn)
                        </label>
                        <?php foreach ($servicesData as $service): ?>
                        <div class="service-item">
                            <input type="checkbox" id="service_<?= htmlspecialchars($service['service_id']) ?>"
                                name="services" value="<?= htmlspecialchars($service['service_name']) ?>"
                                data-price="<?= htmlspecialchars($service['price']) ?>"
                                data-service-id="<?= htmlspecialchars($service['service_id']) ?>" />
                            <label for="service_<?= htmlspecialchars($service['service_id']) ?>">
                                <span class="service-name"><?= htmlspecialchars($service['service_name']) ?></span>
                                <span class="service-price"><?= number_format($service['price'], 0, ',', '.') ?>
                                    ₫</span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ========== MÃ KHUYẾN MÃI (chỉ hiện khi đã đăng nhập) ========== -->
                <?php if ($isLoggedIn): ?>
                <div class="form-section">
                    <div class="section-title">Mã Khuyến Mãi</div>
                    <div class="form-group full-width">
                        <label for="promoCode">Nhập mã khuyến mãi</label>
                        <div class="promo-input-group">
                            <input type="text" id="promoCode" name="promoCode" placeholder="VD: SUMMER2025, VIP20" />
                            <button type="button" class="btn-apply-promo" onclick="applyPromoCode()">
                                Áp dụng
                            </button>
                        </div>
                        <div class="discount-display" id="discountDisplay">
                            Giảm giá: <span id="discountAmount">0 ₫</span>
                            <button type="button" class="btn-close float-end" id="removeVoucherBtn"
                                aria-label="Đóng"></button>
                        </div>

                    </div>

                    <!-- Danh sách voucher có thể áp dụng -->
                    <div class="voucher-list-container" id="voucherListContainer"
                        style="margin-top: 15px; display: none;">
                        <label
                            style="font-weight: 600; color: #333; margin-bottom: 10px; display: block; font-size: 14px;">
                            Mã có thể áp dụng:
                        </label>
                        <div id="voucherList" class="voucher-list"
                            style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
                            <!-- Voucher sẽ được load từ API -->
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========== TỔNG KẾT THANH TOÁN ========== -->
                <div class="total-summary">
                    <div class="summary-row">
                        <span>Giá phòng:</span>
                        <span id="summaryRoomPrice">0 ₫</span>
                    </div>
                    <div class="summary-row">
                        <span>Dịch vụ kèm:</span>
                        <span id="summaryServicePrice">0 ₫</span>
                    </div>
                    <div class="summary-row">
                        <span>Giảm giá:</span>
                        <span id="summaryDiscount">0 ₫</span>
                    </div>
                    <div class="summary-row">
                        <span>VAT (10%):</span>
                        <span id="summaryVat">0 ₫</span>
                    </div>
                    <div class="summary-row highlight">
                        <span>Tổng cộng:</span>
                        <span id="summaryTotal">0 ₫</span>
                    </div>
                    <div class="summary-row"
                        style="border-top: 1px solid rgba(255, 255, 255, 0.2); margin-top: 10px; padding-top: 10px;">
                        <span>Tiền cọc (30%):</span>
                        <span id="summaryDeposit">0 ₫</span>
                    </div>
                </div>

                <!-- ========== THANH TOÁN ========== -->
                <div class="form-section">
                    <div class="section-title">Phương Thức Thanh Toán</div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="paymentMethod">Chọn phương thức <span class="required">*</span></label>
                            <select id="paymentMethod" name="paymentMethod" required>
                                <option value="">-- Chọn phương thức --</option>
                                <option value="Thẻ tín dụng">Thẻ tín dụng</option>
                                <option value="Chuyển khoản ngân hàng">Chuyển khoản ngân hàng</option>
                                <option value="Ví điện tử">Ví điện tử</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ========== GHI CHÚ ========== -->
                <div class="form-section">
                    <div class="section-title">Ghi Chú</div>
                    <div class="form-group full-width">
                        <label for="notes">Ghi chú đặc biệt</label>
                        <textarea id="notes" name="notes"
                            placeholder="VD: Yêu cầu phòng tầng cao, không hút thuốc..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Tạo Hóa Đơn</button>
            </form>
        </div>

        <!-- ========== HÓA ĐƠN ========== -->
        <form class="invoice-container" id="invoiceContainer" method="POST"
            action="/My-Web-Hotel/client/controller/create-booking.php">

            <!-- Hidden inputs chứa dữ liệu đặt phòng -->
            <input type="hidden" id="hiddenRoomId" name="roomId" />
            <input type="hidden" id="hiddenCheckinTime" name="checkinTime" />
            <input type="hidden" id="hiddenCheckoutTime" name="checkoutTime" />
            <input type="hidden" id="hiddenNotes" name="notes" />
            <input type="hidden" id="hiddenDeposit" name="deposit" />
            <input type="hidden" id="hiddenRoomPrice" name="roomPrice" />
            <input type="hidden" id="hiddenServiceTotal" name="serviceTotal" />
            <input type="hidden" id="hiddenDiscount" name="discount" />
            <input type="hidden" id="hiddenVat" name="vat" />
            <input type="hidden" id="hiddenTotal" name="total" />
            <input type="hidden" id="hiddenVoucherId" name="voucherId" />
            <input type="hidden" id="hiddenPaymentMethod" name="paymentMethod" />
            <input type="hidden" id="hiddenGuestCount" name="guestCount" />
            <input type="hidden" id="hiddenChildCount" name="childCount" />
            <input type="hidden" id="hiddenServices" name="services" />
            <!-- Thông tin khách vãng lai -->
            <input type="hidden" id="hiddenWalkInFullName" name="walkInFullName" />
            <input type="hidden" id="hiddenWalkInPhone" name="walkInPhone" />
            <input type="hidden" id="hiddenWalkInEmail" name="walkInEmail" />
            <input type="hidden" id="hiddenWalkInIdNumber" name="walkInIdNumber" />
            <input type="hidden" id="hiddenWalkInAddress" name="walkInAddress" />

            <div class="header">
                <h1>Booking OceanPearl</h1>
                <p>Cảm ơn quý khách đã tin tưởng sử dụng dịch vụ</p>
            </div>

            <div class="content">
                <!-- Thông tin khách hàng -->
                <div class="section">
                    <div class="invoice-section-title">Thông Tin Khách Hàng</div>
                    <div class="info-row">
                        <span class="info-label">Tên Khách Hàng:</span>
                        <span class="info-value" id="invoiceCustomerName">
                            <?php if ($isLoggedIn && $customer): ?>
                            <?= htmlspecialchars($customer['full_name']) ?>
                            <?php else: ?>
                            <span id="invoiceWalkInName">-</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value" id="invoiceCustomerEmail">
                            <?php if ($isLoggedIn && $customer): ?>
                            <?= htmlspecialchars($customer['email']) ?>
                            <?php else: ?>
                            <span id="invoiceWalkInEmail">-</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số điện thoại:</span>
                        <span class="info-value" id="invoiceCustomerPhone">
                            <?php if ($isLoggedIn && $customer): ?>
                            <?= htmlspecialchars($customer['phone']) ?>
                            <?php else: ?>
                            <span id="invoiceWalkInPhone">-</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!$isLoggedIn): ?>
                    <div class="info-row">
                        <span class="info-label">CMND/CCCD:</span>
                        <span class="info-value" id="invoiceWalkInIdNumber">-</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Thông tin phòng -->
                <div class="section">
                    <div class="invoice-section-title">Thông Tin Phòng</div>
                    <div class="info-row">
                        <div class="room-name" id="invoiceRoomName">-</div>
                        <span class="room-type" id="invoiceRoomType">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số người lớn:</span>
                        <span class="info-value" id="invoiceGuestCount">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số trẻ em:</span>
                        <span class="info-value" id="invoiceChildCount">-</span>
                    </div>
                </div>

                <!-- Thời gian -->
                <div class="section">
                    <div class="invoice-section-title">Thời Gian Lưu Trú</div>
                    <div class="info-row">
                        <span class="info-label">Check-in:</span>
                        <span class="info-value" id="invoiceCheckinTime">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-out:</span>
                        <span class="info-value" id="invoiceCheckoutTime">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số đêm:</span>
                        <span class="info-value" id="invoiceNightCount">-</span>
                    </div>
                </div>

                <!-- Dịch vụ -->
                <div class="section" id="invoiceServicesSection" style="display: none">
                    <div class="invoice-section-title">Dịch Vụ Kèm Theo</div>
                    <div class="service-list" id="invoiceServicesList"></div>
                </div>

                <!-- Khuyến mãi -->
                <div class="section" id="invoicePromotionSection" style="display: none">
                    <div class="invoice-section-title">Khuyến Mãi</div>
                    <span class="promotion-tag" id="invoicePromotion">-</span>
                </div>

                <!-- Ghi chú -->
                <div class="section" id="invoiceNotesSection" style="display: none">
                    <div class="invoice-section-title">Ghi Chú</div>
                    <div class="notes-box" id="invoiceNotes">-</div>
                </div>

                <!-- Chi tiết thanh toán -->
                <div class="section">
                    <div class="invoice-section-title">Chi Tiết Thanh Toán</div>
                    <div class="payment-summary">
                        <div class="payment-row">
                            <span>Giá phòng:</span>
                            <span id="invoiceRoomPrice">-</span>
                        </div>
                        <div class="payment-row" id="invoiceServicePriceRow" style="display: none">
                            <span>Dịch vụ kèm:</span>
                            <span id="invoiceServicePrice">-</span>
                        </div>
                        <div class="payment-row" id="invoiceDiscountRow" style="display: none">
                            <span>Giảm giá:</span>
                            <span style="color: #ff6b6b" id="invoiceDiscount">-</span>
                        </div>
                        <div class="payment-row">
                            <span>VAT (10%):</span>
                            <span id="invoiceVat">-</span>
                        </div>
                        <div class="total-row">
                            <span>Tổng cộng:</span>
                            <span id="invoiceTotalAmount">-</span>
                        </div>
                        <div class="payment-row"
                            style="border-top: 2px solid #e0e0e0; margin-top: 10px; padding-top: 10px;">
                            <span>Tiền cọc (30%):</span>
                            <span style="color: #deb666; font-weight: 700" id="invoiceDeposit">-</span>
                        </div>
                    </div>
                    <div style="margin-top: 20px">
                        <div class="info-label" style="margin-bottom: 10px">Phương thức thanh toán:</div>
                        <span class="payment-method" id="invoicePaymentMethod">-</span>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="editBooking()">
                        Chỉnh sửa
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="saveInvoice()">
                        Lưu hóa đơn
                    </button>
                    <button type="button" class="btn btn-primary" onclick="showPaymentModal()">
                        Thanh toán cọc
                    </button>
                </div>
            </div>

            <div class="footer">
                <p style="margin-top: 5px">Vui lòng giữ lại hóa đơn này để làm thủ tục nhận phòng</p>
            </div>
        </form>
    </div>
    <!-- Modal Xác Nhận Thanh Toán -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Thông Báo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="notificationMessage">
                    <!-- Nội dung thông báo -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-secondary text-white" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="confirmPaymentBtn">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
// Truyền biến PHP sang JavaScript
window.IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

function showPaymentModal() {
    const depositAmount = document.getElementById('invoiceDeposit').textContent;
    // Check if we have a valid deposit amount
    if (!depositAmount || depositAmount === '-') {
        alert('Vui lòng kiểm tra lại thông tin hóa đơn.');
        return;
    }

    const message = `Bạn có chắc chắn muốn thanh toán số tiền cọc <strong>${depositAmount}</strong> không?`;
    document.getElementById('notificationMessage').innerHTML = message;

    const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    modal.show();

    // Setup confirm button
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    // Clone to remove old listeners
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    newConfirmBtn.addEventListener('click', function() {
        modal.hide();
        document.getElementById('invoiceContainer').submit();
    });
}
</script>