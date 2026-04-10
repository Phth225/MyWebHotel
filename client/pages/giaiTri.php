<?php
// Truy vấn dữ liệu dịch vụ
$sql = "SELECT * FROM service 
        WHERE status = 'Active' 
        AND service_type = 'Giải trí' 
        AND deleted IS NULL 
        ORDER BY service_id DESC";
$result = $mysqli->query($sql);

// Hàm format giá tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.');
}

?>

<main>
    <div class="hero">
        <h1>Dịch Vụ Giải Trí Đẳng Cấp</h1>
        <p>
            Trải nghiệm những khoảnh khắc thư giãn tuyệt vời tại OceanPearl Hotel
        </p>
    </div>

    <div class="container">
        <h2 class="section-title">Tiện Nghi Giải Trí Của Chúng Tôi</h2>

        <div class="services-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $serviceName = htmlspecialchars($row['service_name']);
                    $description = htmlspecialchars($row['description'] ?? 'Trải nghiệm dịch vụ đẳng cấp 5 sao');
                    $price = formatPrice($row['price']);
                    $unit = htmlspecialchars($row['unit'] ?? 'VNĐ');
                    $image = htmlspecialchars($row['image']);
                    $serviceId = $row['service_id'];
            ?>
            <div class="service-card">
                <div class="service-image-wrapper">
                    <img src="<?php echo $image; ?>" alt="<?php echo $serviceName; ?>" class="service-image"
                        loading="lazy">
                </div>
                <div class="service-content">
                    <h3><?php echo $serviceName; ?></h3>
                    <p class="service-description"><?php echo $description; ?></p>
                    <div class="service-footer">
                        <div class="service-price">
                            <span class="price-label">Giá từ</span>
                            <div class="price-amount">
                                <span class="price-value"><?php echo $price; ?></span>
                            </div>
                        </div>
                        <a href="/My-Web-Hotel/client/index.php?page=service-detail&id=<?php echo $row['service_id']; ?>"
                            class="btn-book">Đặt ngay</a>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
            ?>
            <div class="no-services">
                <p>Hiện tại không có dịch vụ giải trí nào. Vui lòng quay lại sau.</p>
            </div>
            <?php
            }
            ?>
        </div>
    </div>

    <div class="hours-section">
        <div class="container">
            <h2 class="section-title">Giờ Hoạt Động</h2>
            <div class="hours-grid">
                <div class="hours-item">
                    <h4>Hồ Bơi</h4>
                    <p>06:00 - 22:00</p>
                </div>
                <div class="hours-item">
                    <h4>Khu Căm Trại</h4>
                    <p>24/7</p>
                </div>
                <div class="hours-item">
                    <h4>Phòng Gym</h4>
                    <p>24/7</p>
                </div>
                <div class="hours-item">
                    <h4>Khu Vui Chơi</h4>
                    <p>10:00 - 22:00</p>
                </div>
            </div>
        </div>
    </div>

    <div class="cta-section">
        <h2>Sẵn Sàng Trải Nghiệm?</h2>
        <p>
            Đặt phòng ngay hôm nay và tận hưởng những dịch vụ giải trí đẳng cấp 5 sao
        </p>
        <a href="/Room/index.html" class="btn">Đặt phòng Ngay</a>
    </div>
</main>