<?php
// Truy vấn dữ liệu dịch vụ
$sql = "SELECT * FROM service 
        WHERE status = 'Active' 
        AND service_type = 'Ăn uống' 
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
        <h1>Ẩm Thực Đẳng Cấp</h1>
        <p>
            Khám phá hương vị tuyệt hảo từ các nhà hàng cao cấp tại OceanPearl
            Hotel
        </p>
    </div>

    <div class="container">
        <h2 class="section-title">Nhà Hàng & Quán Bar</h2>
        <p class="section-subtitle">
            Trải nghiệm ẩm thực đa dạng từ á đến âu với đội ngũ đầu bếp tài năng
        </p>

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
                        <a href="/My-Web-Hotel/client/index.php?page=service-detail&id=<?php echo $row['service_id'];  ?>"
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

    <div class="features-section">
        <div class="container">
            <h2 class="section-title">Đặc Biệt Dành Cho Bạn</h2>
            <p class="section-subtitle">
                Các dịch vụ và tiện ích đi kèm cho trải nghiệm ẩm thực hoàn hảo nhất
            </p>

            <div class="features-grid">
                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1577219491135-ce391730fb2c?w=200&q=80" alt="Đầu Bếp"
                        class="feature-icon" />
                    <h4>Đầu Bếp Michelin</h4>
                    <p>Đội ngũ đầu bếp đẳng cấp quốc tế</p>
                </div>

                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=200&q=80" alt="Rượu Vang"
                        class="feature-icon" />
                    <h4>Hầm Rượu Vang</h4>
                    <p>Bộ sưu tập rượu quý hiếm</p>
                </div>

                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=200&q=80" alt="Tiệc"
                        class="feature-icon" />
                    <h4>Tiệc Riêng Tư</h4>
                    <p>Tổ chức sự kiện cao cấp</p>
                </div>

                <div class="feature-card">
                    <img src="https://images.unsplash.com/photo-1556910110-a5a63dfd393c?w=200&q=80" alt="Room Service"
                        class="feature-icon" />
                    <h4>Room Service</h4>
                    <p>Giao món 24/7 tận phòng</p>
                </div>
            </div>
        </div>
    </div>
    <div class="menu-preview">
        <div class="container">
            <h2 class="section-title">Menu Đặc Trưng</h2>
            <p class="section-subtitle">
                Khám phá một số món ăn nổi bật trong thực đơn đa dạng của chúng tôi
            </p>

            <div class="menu-categories">
                <div class="menu-category">
                    <div class="menu-icon">🥩</div>
                    <h3>Món Chính</h3>
                    <ul class="menu-items">
                        <li>Bò Wagyu Úc Nướng</li>
                        <li>Tôm Hùm Alaska Hấp</li>
                        <li>Cá Hồi Na Uy Áp Chảo</li>
                        <li>Sườn Cừu New Zealand</li>
                        <li>Gà Tây Nhồi Nấm Truffle</li>
                    </ul>
                </div>

                <div class="menu-category">
                    <div class="menu-icon">🍣</div>
                    <h3>Món Á</h3>
                    <ul class="menu-items">
                        <li>Sushi & Sashimi Cao Cấp</li>
                        <li>Phở Bò Đặc Biệt</li>
                        <li>Dim Sum Hồng Kông</li>
                        <li>Pad Thai Thái Lan</li>
                        <li>Bibimbap Hàn Quốc</li>
                    </ul>
                </div>

                <div class="menu-category">
                    <div class="menu-icon">🍰</div>
                    <h3>Tráng Miệng</h3>
                    <ul class="menu-items">
                        <li>Tiramisu Ý Truyền Thống</li>
                        <li>Crème Brûlée Pháp</li>
                        <li>Chocolate Lava Cake</li>
                        <li>Bánh Macaron Pháp</li>
                        <li>Panna Cotta Dâu Tây</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="cta-section">
        <h2>Đặt Lịch Ngay Hôm Nay</h2>
        <p>
            Trải nghiệm ẩm thực đẳng cấp 5 sao với đội ngũ phục vụ chuyên nghiệp
        </p>
        <a href="/My-Web-Hotel/client/index.php?page=dichVu#service" class="btn btn-outline">Đặt Ngay</a>
    </div>
</main>