<main>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content text-center text-white">
            <h1 class="hero-title">Trải Nghiệm Kỳ Nghỉ Sang Trọng</h1>
            <p class="hero-subtitle">
                OceanPearl Hotel – Nơi tinh tế và thư giãn hòa quyện cùng vẻ đẹp biển trời Phú Quốc.
            </p>
            <button class="hero-btn" id="exploreBtn">Khám Phá Ngay</button>
        </div>
    </section>

    <div class="rooms-header">
        <h1>OceanPearl Rooms & Suites</h1>
        <p>
            OceanPearl Hotel là sự kết hợp giữa kiến ​​trúc cổ điển và thiết kế
            hiện đại, với 280 phòng và suite sang trọng có nội thất phong cách và
            cao cấr. Màu sắc nhẹ nhàng trong mỗi phòng tạo ra bầu không khí thư
            giãn mang đến cho khách doanh nhân và khách du lịch một nơi ẩn náu thư
            thái sau một ngày khám phá Phú Quốc.
        </p>
    </div>

    <div class="rooms-container">
        <div class="filter-div">
            <div class="filter-panel">
                <h3>Tìm kiếm phòng</h3>
                <input type="text" id="searchInput" placeholder="Nhập loại phòng để tìm…" class="search-box" />

                <div class="sort-dropdown">
                    <label for="sort">Sắp xếp:</label>
                    <select id="sort" name="sort">
                        <option value="popular">Phổ biến nhất</option>
                        <option value="price-low">Giá thấp đến cao</option>
                        <option value="price-high">Giá cao đến thấp</option>
                    </select>
                </div>
                <div class="catalog">
                    <span>Loại phòng:</span>
                    <fieldset>
                        <?php 
                            $sql = "SELECT room_type_name FROM room_type WHERE deleted IS NULL";
                            $result = $mysqli->query($sql);
                            while ($row = $result->fetch_assoc()):
                                $type = htmlspecialchars($row['room_type_name']); // tránh lỗi XSS
                            ?>
                        <input type="checkbox" id="<?= $type ?>" name="room_type[]" value="<?= $type ?>">
                        <label for="<?= $type ?>"><?= $type ?></label><br>
                        <?php endwhile; ?>
                    </fieldset>
                </div>
            </div>
        </div>
        <!-- danh sach phong -->
        <div class="rooms-list-div" id="room">
            <div class="rooms-list-header">
                <h2>Danh sách phòng</h2>
            </div>

            <div class="rooms-main">
                <!-- nd danh sách phòng -->
            </div>
        </div>
    </div>

    <section class="features">
        <div class="features-text">
            <h2>LÝ DO CHỌN CHÚNG TÔI</h2>
            <ul>
                <li>Thiết kế hiện đại, view biển tuyệt đẹp</li>
                <li>Tiện nghi cao cấp, đầy đủ sang trọng</li>
                <li>Không gian rộng rãi, thoáng mát</li>
                <li>Dịch vụ phòng 24/7 chu đáo</li>
            </ul>
        </div>
        <div class="features-img"></div>
    </section>
</main>