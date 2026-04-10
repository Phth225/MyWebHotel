<?php
// ========== KIỂM TRA SESSION VÀ ĐĂNG NHẬP ==========
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);

// Không redirect nữa - cho phép khách vãng lai đặt dịch vụ
// ========== LẤY THÔNG TIN NGƯỜI DÙNG ==========
$currentUser = null;
if ($isLoggedIn) {
$userId = $_SESSION['user_id'];

$userStmt = $mysqli->prepare("SELECT full_name, email, phone FROM customer WHERE customer_id = ? AND deleted IS NULL");

if (!$userStmt) {
    error_log("Database Error: " . $mysqli->error);
    echo "<script>alert('Lỗi hệ thống. Vui lòng thử lại sau.'); window.history.back();</script>";
    exit;
}

$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$currentUser = $userResult->fetch_assoc();
$userStmt->close();
}

// ========== LẤY THÔNG TIN DỊCH VỤ ==========
$serviceData = null;
if (isset($_GET['id'])) {
    $sId = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM service WHERE service_id = ? AND deleted IS NULL");
    
    if (!$stmt) {
        error_log("Database Error: " . $mysqli->error);
        echo "<script>alert('Lỗi hệ thống. Vui lòng thử lại sau.'); window.history.back();</script>";
        exit;
    }
    
    $stmt->bind_param("i", $sId);
    $stmt->execute();
    $res = $stmt->get_result();
    $serviceData = $res->fetch_assoc();
    $stmt->close();
    
    if (!$serviceData) {
        echo "<script>alert('Dịch vụ không tồn tại hoặc đã bị xóa.'); window.location.href = '/My-Web-Hotel/client/index.php';</script>";
        exit;
    }
    
    // Kiểm tra trạng thái dịch vụ
    if ($serviceData['status'] !== 'Active') {
        echo "<script>alert('Dịch vụ hiện không khả dụng.'); window.location.href = '/My-Web-Hotel/client/index.php?page=dichVu';</script>";
        exit;
    }
} else {
    echo "<script>alert('Vui lòng chọn dịch vụ để đặt.'); window.location.href = '/My-Web-Hotel/client/index.php?page=dichVu';</script>";
    exit;
}
?>
<div class="container main-container">
    <script>
    // Set global variable để JavaScript biết user đã đăng nhập chưa
    window.IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

    const SERVER_DATA = {
        user: <?php echo json_encode($currentUser); ?>,
        service: <?php echo json_encode($serviceData); ?>,
        isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>
    };
    </script>
    <!-- FORM ĐẶT DỊCH VỤ -->
    <div id="bookingForm">
        <div class="card">
            <div class="card-header-custom">
                <h1><i class="bi bi-star-fill"></i> Đặt Dịch Vụ OceanPearl</h1>
                <p>Hoàn tất thông tin để xác nhận đặt dịch vụ</p>
            </div>

            <div class="card-body">
                <form autocomplete="off" id="serviceBookingForm"
                    data-service-id="<?php echo htmlspecialchars($serviceData['service_id']); ?>">

                    <!-- Thông tin dịch vụ đã chọn -->
                    <div class="service-highlight">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="service-name" id="serviceName">
                                    <?php echo $serviceData ? htmlspecialchars($serviceData['service_name']) : '-'; ?>
                                </div>
                                <div class="service-description" id="serviceDescription">
                                    <?php echo $serviceData ? htmlspecialchars($serviceData['description']) : '-'; ?>
                                </div>
                            </div>
                            <div class="service-price" id="servicePrice">
                                <?php echo $serviceData ? number_format($serviceData['price'], 0, ',', '.') . ' ₫' : '-'; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin khách hàng -->
                    <div class="mb-4">
                        <div class="section-title">
                            <i class="bi bi-person-fill"></i>
                            Thông Tin Khách Hàng
                        </div>

                        <?php if ($isLoggedIn && $currentUser): ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="customerName" class="form-label">
                                    Họ và tên <span class="required">*</span>
                                </label>
                                <input type="text" class="form-control" id="customerName" placeholder="Nguyễn Văn A"
                                    value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" readonly
                                    style="background-color: #f5f5f5;" />
                            </div>
                            <div class="col-md-4">
                                <label for="customerPhone" class="form-label">
                                    Số điện thoại <span class="required">*</span>
                                </label>
                                <input type="tel" class="form-control" id="customerPhone" placeholder="0912345678"
                                    value="<?php echo htmlspecialchars($currentUser['phone']); ?>" readonly
                                    style="background-color: #f5f5f5;" />
                            </div>
                            <div class="col-md-4">
                                <label for="customerEmail" class="form-label">
                                    Email <span class="required">*</span>
                                </label>
                                <input type="email" class="form-control" id="customerEmail"
                                    placeholder="example@email.com"
                                    value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly
                                    style="background-color: #f5f5f5;" />
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Form khách vãng lai -->
                        <div class="alert alert-warning" style="margin-bottom: 20px;">
                            <i class="bi bi-info-circle-fill"></i>
                            <strong>Lưu ý:</strong> Bạn chưa đăng nhập. Vui lòng điền thông tin bên dưới để đặt dịch vụ.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="walkInFullName" class="form-label">
                                    Họ và tên <span class="required">*</span>
                                </label>
                                <input type="text" class="form-control" id="walkInFullName" name="walkInFullName"
                                    placeholder="Nguyễn Văn A" required />
                            </div>
                            <div class="col-md-4">
                                <label for="walkInPhone" class="form-label">
                                    Số điện thoại <span class="required">*</span>
                                </label>
                                <input type="tel" class="form-control" id="walkInPhone" name="walkInPhone"
                                    placeholder="0912345678" pattern="[0-9]{10,11}" required />
                            </div>
                            <div class="col-md-4">
                                <label for="walkInEmail" class="form-label">
                                    Email
                                </label>
                                <input type="email" class="form-control" id="walkInEmail" name="walkInEmail"
                                    placeholder="example@email.com" />
                            </div>
                            <div class="col-md-4">
                                <label for="walkInIdNumber" class="form-label">
                                    CMND/CCCD <span class="required">*</span>
                                </label>
                                <input type="text" class="form-control" id="walkInIdNumber" name="walkInIdNumber"
                                    placeholder="Nhập số CMND/CCCD" required />
                            </div>
                            <div class="col-md-8">
                                <label for="walkInAddress" class="form-label">
                                    Địa chỉ
                                </label>
                                <input type="text" class="form-control" id="walkInAddress" name="walkInAddress"
                                    placeholder="Nhập địa chỉ (tùy chọn)" />
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Thời gian sử dụng dịch vụ -->
                    <div class="mb-4">
                        <div class="section-title">
                            <i class="bi bi-calendar-check"></i>
                            Chi Tiết Sử Dụng Dịch Vụ
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="serviceDate" class="form-label">
                                    Ngày sử dụng <span class="required">*</span>
                                </label>
                                <input type="date" class="form-control" id="serviceDate" required />
                            </div>
                            <div class="col-md-4">
                                <label for="serviceTime" class="form-label">
                                    Giờ sử dụng <span class="required">*</span>
                                </label>
                                <input type="time" class="form-control" id="serviceTime" required />
                            </div>
                            <div class="col-md-4">
                                <label for="numberOfPeople" class="form-label">
                                    Số lượng <span class="required">*</span>
                                </label>
                                <input type="number" class="form-control" id="numberOfPeople" required min="1" max="20"
                                    value="1" />
                            </div>
                        </div>
                    </div>

                    <!-- Mã khuyến mãi - Chỉ hiển thị khi đã đăng nhập -->
                    <?php if ($isLoggedIn): ?>
                    <div class="mb-4">
                        <div class="section-title">
                            <i class="bi bi-ticket-perforated"></i>
                            Mã Khuyến Mãi
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="promoCode" class="form-label">Nhập mã khuyến mãi</label>
                                <div class="promo-input-group">
                                    <input type="text" class="form-control" id="promoCode"
                                        placeholder="VD: SUMMER2025, VIP20" />
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
                        </div>

                        <!-- Danh sách voucher có thể áp dụng -->
                        <div class="mt-3" id="voucherListContainer" style="display: none;">
                            <label class="form-label" style="font-weight: 600; color: #333;">
                                Mã có thể áp dụng:
                            </label>
                            <div id="voucherList" style="max-height: 300px; overflow-y: auto;">
                                <!-- Voucher sẽ được load từ API -->
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Tổng kết -->
                    <div class="summary-card">
                        <div class="summary-row">
                            <span><i class="bi bi-tag"></i> Giá dịch vụ:</span>
                            <span id="totalPrice">0 ₫</span>
                        </div>
                        <div class="summary-row" id="discountRow">
                            <span><i class="bi bi-tag-fill"></i> Giảm giá:</span>
                            <span id="summaryDiscount">0 ₫</span>
                        </div>
                        <div class="summary-row">
                            <span><i class="bi bi-receipt"></i> VAT (10%):</span>
                            <span id="vatAmount">0 ₫</span>
                        </div>
                        <div class="summary-row total">
                            <span><i class="bi bi-cash-coin"></i> Tổng thanh toán:</span>
                            <span id="finalTotal">0 ₫</span>
                        </div>
                    </div>

                    <!-- Phương thức thanh toán -->
                    <div class="mb-4">
                        <div class="section-title">
                            <i class="bi bi-credit-card"></i>
                            Phương Thức Thanh Toán
                        </div>
                        <select class="form-select" id="paymentMethod" required>
                            <option value="">-- Chọn phương thức --</option>
                            <option value="Thẻ tín dụng">Thẻ tín dụng</option>
                            <option value="Chuyển khoản ngân hàng">
                                Chuyển khoản ngân hàng
                            </option>
                            <option value="Ví điện tử">Ví điện tử</option>
                        </select>
                    </div>

                    <!-- Ghi chú -->
                    <div class="mb-4">
                        <div class="section-title">
                            <i class="bi bi-chat-left-text"></i>
                            Ghi Chú
                        </div>
                        <textarea class="form-control" id="notes" rows="3"
                            placeholder="Vui lòng cho chúng tôi biết nếu bạn có yêu cầu đặc biệt..."></textarea>
                    </div>

                    <div class="alert alert-info-custom">
                        <i class="bi bi-info-circle-fill"></i>
                        <strong>Lưu ý:</strong> Vui lòng đến trước giờ hẹn 10 phút. Quý
                        khách có thể hủy hoặc đổi lịch miễn phí trước 24 giờ.
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-check-circle"></i> Tạo Hóa Đơn
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- HÓA ĐƠN -->
    <form id="invoiceContainer" style="display: none" method="POST"
        action="/My-Web-Hotel/client/controller/create-service-booking.php">

        <!-- Hidden inputs chứa dữ liệu đặt dịch vụ -->
        <input type="hidden" id="hiddenServiceId" name="serviceId" />
        <input type="hidden" id="hiddenServiceDate" name="serviceDate" />
        <input type="hidden" id="hiddenServiceTime" name="serviceTime" />
        <input type="hidden" id="hiddenQuantity" name="quantity" />
        <input type="hidden" id="hiddenNotes" name="notes" />
        <input type="hidden" id="hiddenServicePrice" name="servicePrice" />
        <input type="hidden" id="hiddenVAT" name="vat" />
        <input type="hidden" id="hiddenDiscount" name="discount" />
        <input type="hidden" id="hiddenTotal" name="total" />
        <input type="hidden" id="hiddenVoucherId" name="voucherId" />
        <input type="hidden" id="hiddenPaymentMethod" name="paymentMethod" />
        <!-- Thông tin khách vãng lai -->
        <input type="hidden" id="hiddenWalkInFullName" name="walkInFullName" />
        <input type="hidden" id="hiddenWalkInPhone" name="walkInPhone" />
        <input type="hidden" id="hiddenWalkInEmail" name="walkInEmail" />
        <input type="hidden" id="hiddenWalkInIdNumber" name="walkInIdNumber" />
        <input type="hidden" id="hiddenWalkInAddress" name="walkInAddress" />

        <div class="card">
            <div class="card-header-custom">
                <h1><i class="bi bi-check-circle-fill"></i> OceanPearl Hotel</h1>
                <p>Cảm ơn quý khách đã tin tưởng sử dụng dịch vụ</p>
            </div>

            <div class="card-body">

                <!-- Thông tin dịch vụ -->
                <div class="invoice-section">
                    <div class="invoice-section-title">
                        <i class="bi bi-star-fill"></i>
                        Dịch Vụ Đã Đặt
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Tên dịch vụ:</span>
                        <span class="invoice-value" id="invoiceServiceName">-</span>
                    </div>
                    <div id="invoiceServiceDetails"></div>
                </div>

                <!-- Thông tin khách hàng -->
                <div class="invoice-section">
                    <div class="invoice-section-title">
                        <i class="bi bi-person-fill"></i>
                        Thông Tin Khách Hàng
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Họ tên:</span>
                        <span class="invoice-value" id="invoiceName">
                            <?php if ($isLoggedIn && $currentUser): ?>
                            <?php echo htmlspecialchars($currentUser['full_name']); ?>
                            <?php else: ?>
                            <span id="invoiceWalkInName">-</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Số điện thoại:</span>
                        <span class="invoice-value" id="invoicePhone">
                            <?php if ($isLoggedIn && $currentUser): ?>
                            <?php echo htmlspecialchars($currentUser['phone']); ?>
                            <?php else: ?>
                            <span id="invoiceWalkInPhone">-</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Email:</span>
                        <span class="invoice-value" id="invoiceEmail">
                            <?php if ($isLoggedIn && $currentUser): ?>
                            <?php echo htmlspecialchars($currentUser['email']); ?>
                            <?php else: ?>
                            <span id="invoiceWalkInEmail">-</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!$isLoggedIn): ?>
                    <div class="invoice-row">
                        <span class="invoice-label">CMND/CCCD:</span>
                        <span class="invoice-value" id="invoiceWalkInIdNumber">-</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Thời gian -->
                <div class="invoice-section">
                    <div class="invoice-section-title">
                        <i class="bi bi-calendar-check"></i>
                        Thời Gian Sử Dụng
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Ngày:</span>
                        <span class="invoice-value" id="invoiceDate">-</span>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Giờ:</span>
                        <span class="invoice-value" id="invoiceTime">-</span>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Số lượng:</span>
                        <span class="invoice-value" id="invoiceQuantity">-</span>
                    </div>
                </div>

                <!-- Khuyến mãi -->
                <div class="invoice-section" id="invoicePromotionSection" style="display: none">
                    <div class="invoice-section-title">
                        <i class="bi bi-ticket-perforated"></i>
                        Khuyến Mãi
                    </div>
                    <span class="promotion-tag" id="invoicePromotion">-</span>
                </div>

                <!-- Ghi chú -->
                <div class="invoice-section" id="invoiceNotesSection" style="display: none">
                    <div class="invoice-section-title">
                        <i class="bi bi-chat-left-text"></i>
                        Ghi Chú
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-value" id="invoiceNotes" style="color: #666">-</span>
                    </div>
                </div>

                <!-- Thanh toán -->
                <div class="invoice-section">
                    <div class="invoice-section-title">
                        <i class="bi bi-receipt"></i>
                        Chi Tiết Thanh Toán
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Giá dịch vụ:</span>
                        <span class="invoice-value" id="invoiceSubtotal">-</span>
                    </div>
                    <div class="invoice-row" id="invoiceDiscountRow" style="display: none;">
                        <span class="invoice-label">Giảm giá:</span>
                        <span class="invoice-value" style="color: #ff6b6b;" id="invoiceDiscount">-</span>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">VAT (10%):</span>
                        <span class="invoice-value" id="invoiceVAT">-</span>
                    </div>
                    <div class="invoice-row" style="
                  border-top: 2px solid var(--gold);
                  margin-top: 10px;
                  padding-top: 10px;
                ">
                        <span class="invoice-label" style="color: var(--gold); font-weight: 700">Tổng thanh
                            toán:</span>
                        <span class="invoice-value" style="color: var(--gold); font-size: 1.25rem">
                            <span id="invoiceTotal">-</span>
                        </span>
                    </div>
                    <div class="invoice-row" style="margin-top: 10px">
                        <span class="invoice-label">Phương thức:</span>
                        <span class="invoice-value" id="invoicePayment">-</span>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-gold w-100" onclick="editBooking()">
                            <i class="bi bi-pencil"></i> Chỉnh sửa
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-gold w-100" onclick="saveInvoice()">
                            <i class="bi bi-download"></i> Lưu hóa đơn
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-gold w-100" onclick="showPaymentModal()">
                            <i class="bi bi-check-circle"></i> Xác nhận
                        </button>
                    </div>
                </div>

                <div class="alert alert-info-custom">
                    <i class="bi bi-shield-check"></i>
                    <strong>Chính sách hủy:</strong> Miễn phí hủy trước 24 giờ. Hủy
                    trong vòng 24 giờ sẽ bị tính phí 50%.
                </div>
            </div>
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
                <button type="button" class="btn btn-gold" id="confirmPaymentBtn">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<script>
function showPaymentModal() {
    const totalAmount = document.getElementById('invoiceTotal').textContent;
    // Check if we have a valid total amount
    if (!totalAmount || totalAmount === '-') {
        alert('Vui lòng kiểm tra lại thông tin hóa đơn.');
        return;
    }

    const message = `Bạn có chắc chắn muốn thanh toán số tiền <strong>${totalAmount}</strong> không?`;
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