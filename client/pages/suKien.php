<?php
// Truy vấn dữ liệu dịch vụ
$sql = "SELECT * FROM service 
        WHERE status = 'Active' 
        AND service_type = 'Sự kiện' 
        AND deleted IS NULL 
        ORDER BY service_id DESC";
$result = $mysqli->query($sql);

// Hàm format giá tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.');
}

// Hàm lấy ảnh
function getServiceImage($imagePath) {
    if (!empty($imagePath) && trim($imagePath) !== '') {
        return htmlspecialchars($imagePath);
    }
    return 'https://images.unsplash.com/photo-1519167758481-83f29da8585c?w=800&q=80';
}
?>

<main>
    <!-- Hero Section -->
    <section class="hero">
        <h1>Tổ Chức Sự Kiện</h1>
        <p>
            Tiệc cưới - hội nghị - gala dinner bên bờ biển
        </p>
    </section>

    <!-- Dịch vụ nổi bật -->
    <div class="services">
        <h2 class="section-title">Dịch Vụ Của Chúng Tôi</h2>
        <div class="services-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $serviceName = htmlspecialchars($row['service_name']);
                    $description = htmlspecialchars($row['description'] ?? 'Tổ chức sự kiện chuyên nghiệp, đẳng cấp 5 sao');
                    $price = formatPrice($row['price']);
                    $unit = htmlspecialchars($row['unit'] ?? 'VNĐ');
                    $image = getServiceImage($row['image']);
                    $serviceId = $row['service_id'];
            ?>
            <div class="service-card">
                <div class="card-image-wrapper">
                    <img src="<?php echo $image; ?>" alt="<?php echo $serviceName; ?>" class="card-image">
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo $serviceName; ?></h3>
                    <div class="card-footer">
                        <div class="price-section">
                            <span class="price-value"><?php echo $price; ?>₫</span>
                            <span class="price-unit"><?php echo $unit; ?></span>
                        </div>
                        <a href="/My-Web-Hotel/client/index.php?page=service-detail&id=<?php echo $row['service_id']; ?>"
                            class="btn-view">
                            Đặt ngay
                        </a>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
            ?>
            <div class="empty-state">
                <div class="empty-icon">📅</div>
                <h3>Chưa có dịch vụ</h3>
                <p>Hiện tại chưa có dịch vụ tổ chức sự kiện nào. Vui lòng quay lại sau.</p>
            </div>
            <?php
            }
            ?>
        </div>
    </div>

    <!-- Giới thiệu tiện ích -->
    <section class="features">
        <div class="features-content">
            <h2>TIỆN ÍCH & KHÔNG GIAN MỚI</h2>
            <p>
                Khách sạn liên tục nâng cấp không gian, từ bãi biển riêng tư, hồ bơi
                vô cực, đến hệ thống phòng nghỉ sang trộng. Chúng tôi mang đến trải
                nghiệm hoàn hảo cho các sự kiện đáng nhớ của bạn.
            </p>
        </div>
        <div class="features-image"></div>
    </section>

    <!-- Album hình ảnh -->
    <section class="album">
        <h2 class="section-title">KHÔNG GIAN TỔ CHỨC</h2>
        <div class="album-grid">
            <div class="album-item album1"></div>
            <div class="album-item album2"></div>
            <div class="album-item album3"></div>
            <div class="album-item album4"></div>
        </div>
    </section>
</main>