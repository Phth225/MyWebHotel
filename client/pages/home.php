<?php
$sql = " SELECT 
            r.room_id,
            r.room_number,
            rt.area,
            rt.room_type_name AS room_type,
            rt.base_price AS room_price,
            rt.capacity,
            (SELECT image_url 
             FROM roomtype_images 
             WHERE roomtype_images.room_type_id = r.room_type_id 
             ORDER BY RAND()
             LIMIT 1) AS room_image
        FROM room r
        JOIN room_type rt ON rt.room_type_id = r.room_type_id
        WHERE r.status = 'available'
        ORDER BY RAND()
        LIMIT 10
    ";

$stmt = $mysqli->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();


$bestRooms = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bestRooms[] = $row;
    }
}
$reviews_query = "SELECT r.*, c.full_name, c.avatar, c.address
                FROM review r
                JOIN customer c ON r.customer_id = c.customer_id
                WHERE r.status = 'Approved' AND r.deleted IS NULL
                ORDER BY r.rating DESC, r.created_at DESC
                LIMIT 10;";
$reviews_result = $mysqli->query($reviews_query);
$reviews = [];
?>

<main>
    <!-- Hero -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Khám phá không gian nghỉ dưỡng lý tưởng</h1>
            <p class="hero-subtitle">
                Đặt phòng khách sạn nhanh chóng, minh bạch và an toàn. Trải nghiệm
                dịch vụ cao cấp với mức giá tốt nhất.
            </p>
            <div class="cta-buttons">
                <a href="/My-Web-Hotel/client/index.php?page=room" class="btn btn-primary">Đặt phòng ngay</a>
                <a href="/My-Web-Hotel/client/index.php?page=about" class="btn btn-secondary">Tìm hiểu thêm</a>
            </div>
        </div>
        <div class="honeycomb-container">
            <div class="honeycomb">
                <!-- Center large hexagon -->
                <div class="hexagon hex-center">
                    <div class="hex-inner" style="
                background-image: url('https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800&q=80');
              "></div>
                </div>

                <!-- Top -->
                <div class="hexagon hex-1">
                    <div class="hex-inner" style="
                background-image: url('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80');
              "></div>
                </div>

                <!-- Middle row -->
                <div class="hexagon hex-2">
                    <div class="hex-inner" style="
                background-image: url('https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=800&q=80');
              "></div>
                </div>

                <div class="hexagon hex-3">
                    <div class="hex-inner" style="
                background-image: url('https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&q=80');
              "></div>
                </div>

                <!-- Bottom middle row -->
                <div class="hexagon hex-4">
                    <div class="hex-inner" style="
                background-image: url('https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=800&q=80');
              "></div>
                </div>

                <div class="hexagon hex-5">
                    <div class="hex-inner" style="
                background-image: url('https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=800&q=80');
              "></div>
                </div>

                <!-- Bottom -->
                <div class="hexagon hex-6">
                    <div class="hex-inner" style="
                background-image: url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&q=80');
              "></div>
                </div>
            </div>
        </div>
    </section>
    <!-- Rooms -->
    <section class="room-slider">
        <h2>Phòng tốt nhất</h2>
        <p>Phòng ngủ đa dạng và sang trọng cho nhiều sự lựa chọn</p>

        <div class="slider-container" data-aos="zoom-in" data-aos-duration="1000">
            <button class="btn left" onclick="moveSlide(-1)">&#10094;</button>
            <div class="slider" id="slider">
                <?php foreach ($bestRooms as $room): ?>
                <div class="slide" data-id="<?php echo $room['room_id']; ?>">
                    <div class="image-container">
                        <img src="<?php echo htmlspecialchars($room['room_image'])  ?>"
                            alt="Room <?php echo htmlspecialchars($room['room_number']); ?>" />
                    </div>

                    <div class="room-info">
                        <h3>Phòng <?php echo htmlspecialchars($room['room_number']); ?> -
                            <?php echo htmlspecialchars($room['room_type']); ?></h3>
                        <div class="room-meta">
                            <span><i class="fa-solid fa-ruler"></i> <?php echo $room['area']; ?> m²</span>
                            <span><i class="fa-solid fa-person fa-lg"></i> <?php echo $room['capacity']; ?>
                                khách</span>
                        </div>
                    </div>
                    <div class="room-footer">
                        <div class="price"><?php echo number_format(htmlspecialchars($room['room_price'])); ?>₫
                            <span style="color: #999; font-size: 1.4rem;">/đêm</span>
                        </div> <button class="btn btn-primary"
                            onclick="window.location.href='/My-Web-Hotel/client/index.php?page=room-detail&room_id=<?php echo $room['room_id']; ?>'"><i
                                class=" fa-solid fa-cart-shopping"></i> Đặt phòng</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="btn right" onclick="moveSlide(1)">&#10095;</button>
        </div>

        <a href="/My-Web-Hotel/client/index.php?page=room#room" class="view-all room">Xem Tất Cả</a>
    </section>
    <!-- Service -->
    <section class="services">
        <div class="services-header">
            <div class="text">
                <h2>Dịch vụ nổi bật</h2>
                <p>Nhiều dịch vụ để đảm bảo sự thư giãn và thoải mái của bạn</p>
            </div>
            <a href="/My-Web-Hotel/client/index.php?page=dichVu#service" class="view-all service">Xem Tất Cả</a>
        </div>

        <div class="services-grid">
            <div class="service-card" onclick="window.location.href='/My-Web-Hotel/client/index.php?page=spa'"
                data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100">
                <img src="/my-web-hotel/client/assets/images/Spa.jpg" alt="Spa thư giãn" />
                <h3>Spa & sức khỏe</h3>
                <p>
                    Trải nghiệm liệu pháp chăm sóc cơ thể chuyên nghiệp trong không
                    gian yên tĩnh.
                </p>
            </div>
            <div class="service-card" onclick="window.location.href='/My-Web-Hotel/client/index.php?page=nhaHang'"
                data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
                <img src="/my-web-hotel/client/assets/images/NhaAn.jpg" alt="Nhà hàng cao cấp" />
                <h3>Nhà hàng cao cấp</h3>
                <p>Thực đơn đa dạng từ Á đến Âu, phục vụ bởi đầu bếp 5 sao.</p>
            </div>
            <div class="service-card" onclick="window.location.href='/My-Web-Hotel/client/index.php?page=giaiTri'"
                data-aos="fade-up" data-aos-duration="1000" data-aos-delay="300">
                <img src="/my-web-hotel/client/assets/images/HoBoi.jpg" alt="Hồ bơi ngoài trời" />
                <h3>Trải nghiệm giải trí hấp dẫn</h3>
                <p>Hồ bơi rộng rãi với tầm nhìn thoáng đãng và quầy bar bên hồ.</p>
            </div>
            <div class="service-card" onclick="window.location.href='/My-Web-Hotel/client/index.php?page=suKien'"
                data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
                <img src="/my-web-hotel/client/assets/images/PHop.jpg" alt="Phòng họp hiện đại" />
                <h3>Tổ chức sự kiện hiện đại</h3>
                <p>
                    Trang bị đầy đủ thiết bị hội nghị, phù hợp cho sự kiện và hội
                    thảo.
                </p>
            </div>
        </div>
    </section>
    <!-- experience -->
    <section class="experience">
        <h2>Trải nghiệm cao cấp</h2>
        <p>
            Ghi lại những trải nghiệm kỳ nghỉ của bạn tại Khách sạn Ocean Pearl
        </p>

        <div class="experience-container" data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="100">
            <button class="btn left" onclick="moveExperience(-1)">
                &#10094;
            </button>

            <div class="experience-grid" id="experience-slider"></div>

            <button class="btn right" onclick="moveExperience(1)">
                &#10095;
            </button>
        </div>
        <a href="/my-web-hotel/client/index.php?page=gallery" class="view-all">Xem Tất Cả</a>
    </section>
    <!-- Places -->
    <section class="places">
        <div class="places-header">
            <div class="text">
                <h2>Những địa điểm nổi tiếng</h2>
                <p>
                    Các địa điểm vui chơi và giải trí tuyệt vời đang chờ bạn khám phá
                </p>
            </div>
            <a href="/my-web-hotel/client/index.php?page=places" class="view-all">Xem Tất Cả</a>
        </div>

        <div class="places-grid">
            <div class="place-card" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="300">
                <img src="/my-web-hotel/client/assets/images/sun-world-hon-thom-nature-park-vietnam-water-slides.jpeg"
                    alt="Hạ Long Bay" />
                <div class="overlay">
                    <h3>Sun World</h3>
                </div>
            </div>

            <div class="place-card" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="150">
                <img src="/my-web-hotel/client/assets/images/GW-T5.2021-01-1.jpg" alt="Hội An Ancient Town" />
                <div class="overlay">
                    <h3>Grand World</h3>
                </div>
            </div>

            <div class="place-card" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="300">
                <img src="/my-web-hotel/client/assets/images/sun-grand-city-hillside-residence-6.jpg"
                    alt="Phú Quốc Island" />
                <div class="overlay">
                    <h3>Sunset Town</h3>
                </div>
            </div>
        </div>
    </section>
    <!-- Reviews -->
    <section class="testimonial">
        <h2>Đánh giá khách sạn</h2>
        <p>Khách hàng nói gì về kỳ nghỉ tại Ocean Pearl</p>
        <div class="testimonial-container" data-aos="flip-down" data-aos-duration="1000">
            <button class="btn left" onclick="moveTestimonial(-1)">
                &#10094;
            </button>

            <div class="testimonial-grid" id="testimonial-slider">
                <?php while ($review = $reviews_result->fetch_assoc()): ?>
                <div class="testimonial-card">

                    <div class="row g-0">
                        <div class="col col-md-3">
                            <img src=" <?php echo htmlspecialchars(!empty($review['avatar']) ? $review['avatar'] : '/My-Web-Hotel/client/assets/images/275f99923b080b18e7b474ed6155a17f.jpg'); ?>"
                                alt="avatar" class="avatar" />
                        </div>
                        <div class="col col-md-6">
                            <h3><?php echo htmlspecialchars($review['full_name']); ?></h3>
                            <div class="stars">
                                <?php
                                for ($i = 0; $i < 5; $i++) {
                                    if ($i < $review['rating']) {
                                        echo '★';
                                    } else {
                                        echo '☆';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <p class="review">
                        <?php echo htmlspecialchars($review['comment']); ?>
                    </p>
                </div>
                <?php endwhile; ?>
            </div>

            <button class="btn right" onclick="moveTestimonial(1)">
                &#10095;
            </button>
        </div>

        <a href="/My-Web-Hotel/client/index.php?page=danhGia" class="view-all">Xem Tất Cả</a>
    </section>

    <!-- Booking success modal (Bootstrap) -->
    <div class="modal fade" id="bookingSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Đặt phòng thành công</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body text-center">
                    <p id="bookingSuccessText">Cảm ơn quý khách đã tin tưởng, nhân viên chúng tôi sẽ liên hệ sớm nhất có
                        thể!</p>
                </div>
                <div class="modal-footer">
                    <button id="bookingSuccessClose" type="button" class="btn bg-secondary"
                        data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const success = params.get('booking_success');
        const code = params.get('booking_code');

        if (success) {
            // Show Bootstrap 5 modal if available
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(document.getElementById('bookingSuccessModal'));
                modal.show();
            } else if (window.jQuery && typeof jQuery('#bookingSuccessModal').modal === 'function') {
                // Bootstrap 3/4 via jQuery
                jQuery('#bookingSuccessModal').modal('show');
            } else {
                // Fallback simple alert
                alert(textEl.textContent);
            }

            // Remove params so modal won't show again on reload
            params.delete('booking_success');
            params.delete('booking_code');
            const newSearch = params.toString();
            const newUrl = window.location.pathname + (newSearch ? '?' + newSearch : '');
            window.history.replaceState({}, document.title, newUrl);
        }
    });
    </script>
</main>