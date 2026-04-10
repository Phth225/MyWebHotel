<?php
// Lấy ID dịch vụ từ URL
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($service_id <= 0) {
    header("Location: /My-Web-Hotel/client/index.php?page=dichVu");
    exit();
}

// Kiểm tra đăng nhập
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

// Lấy thông tin chi tiết dịch vụ
$sql = "SELECT * FROM service WHERE service_id = ? AND deleted IS NULL";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: /My-Web-Hotel/client/index.php?page=404");
    exit();
}

$service = $result->fetch_assoc();

// Lấy 3 dịch vụ gợi ý ngẫu nhiên (loại trừ dịch vụ hiện tại)
$suggestStmt = $mysqli->prepare("
    SELECT * FROM service 
    WHERE LOWER(TRIM(status)) = 'active' AND service_id != ? AND service_type <> 'Dịch vụ cá nhân' AND deleted IS NULL
    ORDER BY RAND()
    LIMIT 3
");
$suggestStmt->bind_param("i", $service_id);
$suggestStmt->execute();
$suggestResult = $suggestStmt->get_result();
$suggestedServices = [];
while ($suggestService = $suggestResult->fetch_assoc()) {
    $suggestedServices[] = $suggestService;
}
?>

<script>
window.IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
window.CURRENT_USER_NAME = "<?php echo htmlspecialchars($userName); ?>";
window.SERVICE_ID = <?php echo $service_id; ?>;
window.SERVICE_NAME = "<?php echo htmlspecialchars($service['service_name']); ?>";
window.SERVICE_PRICE = <?php echo $service['price']; ?>;
</script>

<main>
    <div class="header-title">
        <h1><?php echo htmlspecialchars($service['service_name']); ?></h1>
    </div>

    <div class="detail-container">
        <div class="main-content">
            <div class="service-info">
                <!-- Image Section -->
                <div class="image-section">
                    <div class="main-image-container">
                        <?php if (!empty($service['image'])): ?>
                        <img src="<?php echo htmlspecialchars($service['image']); ?>"
                            alt="<?php echo htmlspecialchars($service['service_name']); ?>">
                        <?php else: ?>
                        <i class="bi bi-image" style="font-size: 5rem; color: white;"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Meta Info -->
                <div class="service-meta">
                    <div class="meta-item">
                        <i class="fa-solid fa-tag"></i>
                        <span><?php echo htmlspecialchars($service['service_type']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fa-solid fa-ruler-vertical"></i>
                        <span><?php echo htmlspecialchars($service['unit']); ?></span>
                    </div>
                </div>

                <!-- Description -->
                <div class="service-description">
                    <h2>Mô Tả Dịch Vụ</h2>
                    <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                </div>

                <!-- Features -->
                <div class="features-section">
                    <h2>Đặc Điểm Dịch Vụ</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <i class="bi bi-clock"></i>
                            <span>Phục vụ 24/7</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-star-fill"></i>
                            <span>Chất lượng cao cấp</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-people"></i>
                            <span>Đội ngũ chuyên nghiệp</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-shield-check"></i>
                            <span>An toàn & Vệ sinh</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-gem"></i>
                            <span>Tiện nghi hiện đại</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-headset"></i>
                            <span>Hỗ trợ tận tình</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Card -->
            <div class="booking-card">
                <div class="price-section">
                    <div class="price">
                        <?php echo number_format($service['price'], 0, ',', '.'); ?>₫
                        <span>/<?php echo htmlspecialchars($service['unit']); ?></span>
                    </div>
                </div>

                <?php if (strtolower(trim($service['status'])) == 'active'): ?>
                <form class="booking-form" id="bookingForm">
                    <div class="form-group">
                        <label>Ngày sử dụng</label>
                        <input type="date" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Thời gian</label>
                        <input type="time" id="booking_time" name="booking_time" required>
                    </div>
                    <div class="form-group">
                        <label>Số lượng</label>
                        <input type="number" id="number_of_people" name="number_of_people" min="1" value="1">
                    </div>
                    <button type="button" class="check-service-btn" id="check-service-btn">
                        Đặt Dịch Vụ Ngay
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-warning text-center">
                    <i class="bi bi-exclamation-triangle"></i> Dịch vụ hiện đang ngừng hoạt động
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Popup thông báo -->
        <div class="modal fade" id="loginPopup" tabindex="-1" aria-labelledby="loginPopupLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="loginPopupLabel">Ưu đãi đặc biệt</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body text-center">
                        <i class="fa-solid fa-gift fa-3x text-primary mb-3"></i>
                        <p>Hãy đăng nhập để có nhiều ưu đãi</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="/My-Web-Hotel/client/pages/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                            class="btn btn-primary">
                            <i class="fa fa-sign-in-alt me-2"></i>Đăng nhập
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            id="continueServiceBookingBtn">
                            <i class="fa fa-arrow-right me-2"></i>Tiếp tục đặt
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Thông báo chung -->
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
                    </div>
                </div>
            </div>
        </div>
        <!-- Suggestions Section -->
        <?php if (!empty($suggestedServices)): ?>
        <div class="suggestions-section">
            <h2 class="section-title">Các Dịch Vụ Khác Bạn Có Thể Thích</h2>
            <div class="suggestions">
                <?php foreach ($suggestedServices as $suggested): ?>
                <div class="suggestion-card">
                    <div class="suggestion-image"
                        style="background-image: url('<?php echo !empty($suggested['image']) ? htmlspecialchars($suggested['image']) : 'default-service.jpg'; ?>');">
                    </div>
                    <div class="suggestion-content">
                        <h3 class="suggestion-title"><?php echo htmlspecialchars($suggested['service_name']); ?></h3>
                        <div class="suggestion-meta">
                            <span><i class="fa-solid fa-tag"></i>
                                <?php echo htmlspecialchars($suggested['service_type']); ?></span>
                        </div>
                        <div class="suggestion-price">
                            <div class="price"><?php echo number_format($suggested['price'], 0, ',', '.'); ?>₫</div>
                            <a href="/My-Web-Hotel/client/index.php?page=service-detail&id=<?php echo $suggested['service_id']; ?>"
                                class="view-btn">Xem Chi Tiết</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const checkBtn = document.getElementById("check-service-btn");
    const loginModalEl = document.getElementById("loginPopup");
    const continueServiceBookingBtn = document.getElementById("continueServiceBookingBtn");

    let loginModal = null;
    if (loginModalEl) {
        loginModal = new bootstrap.Modal(loginModalEl);
    }

    // Notification modal
    const notificationModalEl = document.getElementById("notificationModal");
    let notificationModal = null;
    if (notificationModalEl) {
        notificationModal = new bootstrap.Modal(notificationModalEl);
    }

    function showNotification(msg) {
        const msgEl = document.getElementById("notificationMessage");
        if (msgEl) msgEl.textContent = msg;
        if (notificationModal) notificationModal.show();
        else alert(msg);
    }

    // Function to proceed with service booking
    function proceedToServiceBooking() {
        const bookingDate = document.getElementById("booking_date").value;
        const bookingTime = document.getElementById("booking_time").value;
        const numberOfPeople = document.getElementById("number_of_people").value;

        if (!bookingDate || !bookingTime) {
            showNotification("Vui lòng chọn ngày và thời gian!");
            return false;
        }

        // VALIDATION: Check for past date/time
        const selectedDateTime = new Date(`${bookingDate}T${bookingTime}`);
        const now = new Date();

        if (selectedDateTime < now) {
            showNotification("Vui lòng chọn thời gian trong tương lai!");
            return false;
        }

        // Redirect to service-booking.php via index.php
        // Use sessionStorage to pass data like Room Booking
        const bookingData = {
            date: bookingDate,
            time: bookingTime,
            guests: numberOfPeople
        };
        sessionStorage.setItem('serviceBookingData', JSON.stringify(bookingData));

        const url = `/My-Web-Hotel/client/index.php?page=service-booking&id=${window.SERVICE_ID}`;
        window.location.href = url;
        return true;
    }

    if (checkBtn) {
        checkBtn.addEventListener("click", (e) => {
            e.preventDefault();

            // ========== KIỂM TRA ĐĂNG NHẬP ==========
            if (!window.IS_LOGGED_IN) {
                // Hiển thị modal khuyến khích đăng nhập
                if (loginModal) loginModal.show();
                return;
            }

            // Nếu đã đăng nhập, tiếp tục đặt dịch vụ
            proceedToServiceBooking();
        });
    }

    // Handle "Continue Booking" button in login modal
    if (continueServiceBookingBtn) {
        continueServiceBookingBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (loginModal) loginModal.hide();
            // Proceed with service booking without login
            proceedToServiceBooking();
        });
    }
});
</script>