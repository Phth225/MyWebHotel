<?php

// Truy vấn dữ liệu dịch vụ
$sql = "SELECT * FROM service 
        WHERE status = 'Active' 
        AND service_type = 'Sức khỏe' 
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
    return 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&h=400&fit=crop';
}
?>

<main>
    <section class="hero-section">
        <div class="floating-header">
            <div class="header-links">
                <a href="#about">Về chúng tôi</a>
                <a href="#services">Dịch vụ</a>
                <a href="#journey">Trải nghiệm</a>
            </div>
        </div>
        <div class="hero-content">
            <h1>Trị liệu độc quyền dành cho bạn</h1>
        </div>
    </section>

    <section id="about" class="about-section">
        <div class="container about-container">
            <div class="about-text">
                <h2>Về chúng tôi</h2>
                <p>
                    Chúng tôi mang đến một không gian ẩn mình hoàn hảo để bạn thư
                    giãn, tái tạo năng lượng và kết nối lại với chính mình. Đội ngũ
                    chuyên gia trị liệu được đào tạo chuyên sâu sẽ tạo ra những trải
                    nghiệm cá nhân hóa, đảm bảo bạn rời đi với một cảm giác hoàn toàn
                    tươi mới.
                </p>
            </div>
            <div class="about-image"></div>
        </div>
    </section>

    <section id="services" class="detailed-services-section">
        <div class="container">
            <h2 class="section-title">Liệu Trình Chăm Sóc Chuyên Sâu</h2>

            <?php if ($result && $result->num_rows > 0): ?>
            <div class="service-list">
                <?php 
        $index = 1;
        while($service = $result->fetch_assoc()): 
        ?>
                <article class="service-item">
                    <div class="service-image" id="service-img-<?php echo $index; ?>"
                        style="background-image: url('<?php echo getServiceImage($service['image']); ?>')"></div>
                    <div class="service-details">
                        <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <p class="service-description">
                            <?php 
                  $description = !empty($service['description']) 
                    ? htmlspecialchars($service['description']) 
                    : 'Trải nghiệm dịch vụ chăm sóc chuyên nghiệp, mang lại sự thư giãn và tái tạo năng lượng cho cơ thể và tâm hồn.';
                  echo $description;
                ?>
                        </p>
                        <?php if (!empty($service['unit'])): ?>
                        <div class="service-meta">
                            <span><strong>Thời gian:</strong>
                                <?php echo htmlspecialchars($service['unit']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="service-price"><?php echo formatPrice($service['price']); ?> VNĐ</div>
                        <a href="/My-Web-Hotel/client/index.php?page=service-detail&id=<?php echo $service['service_id']; ?>"
                            class="btn btn-service">Đặt ngay</a>
                    </div>
                </article>
                <?php 
        $index++;
        endwhile; 
        ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; color: #6d6d6d;">
                <p style="font-size: 1.2rem;">Hiện tại chưa có dịch vụ nào.</p>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <?php
    $mysqli->close();
    ?>
    <section id="journey" class="journey-section">
        <div class="container">
            <h2 class="section-title">Hành Trình Trải Nghiệm</h2>
            <p class="journey-subtitle">
                Tại spa của chúng tôi, mỗi liệu trình là một hành trình được thiết
                kế tỉ mỉ để đưa bạn đến trạng thái thư giãn và cân bằng tuyệt đối.
            </p>
            <div class="journey-steps">
                <div class="step">
                    <div class="step-icon">🌿</div>
                    <h3>Chào Đón & Tư Vấn</h3>
                    <p>
                        Bắt đầu với trà thảo mộc và được các chuyên gia tư vấn liệu
                        trình phù hợp nhất với nhu cầu của bạn.
                    </p>
                </div>
                <div class="step">
                    <div class="step-icon">💧</div>
                    <h3>Thanh Lọc Giác Quan</h3>
                    <p>
                        Không gian riêng tư với hương tinh dầu dịu nhẹ và âm nhạc du
                        dương giúp bạn hoàn toàn thả lỏng tâm trí.
                    </p>
                </div>
                <div class="step">
                    <div class="step-icon">✨</div>
                    <h3>Tái Tạo Năng Lượng</h3>
                    <p>
                        Kết thúc liệu trình, bạn sẽ được thưởng thức bữa ăn nhẹ thanh
                        đạm, cảm nhận sự tươi mới lan tỏa khắp cơ thể.
                    </p>
                </div>
            </div>
        </div>
    </section>
</main>