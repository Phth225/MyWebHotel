    <main>
        <!-- Banner chính -->
        <section class="hero">
            <div class="hero-text">
                <h1>DỊCH VỤ ĐẲNG CẤP</h1>
                <p>
                    Tận hưởng không gian sang trọng, tiện nghi và đẳng cấp bậc nhất.
                </p>
            </div>
        </section>

        <!-- Dịch vụ nổi bật -->
        <section class="services" id="services">
            <h2>DỊCH VỤ CỦA CHÚNG TÔI</h2>
            <div class="service-grid">
                <a href="/My-Web-Hotel/client/index.php?page=spa" class="service-card spa">
                    <h3>Sức khoẻ & Spa</h3>
                </a>
                <a href="/My-Web-Hotel/client/index.php?page=giaiTri" class="service-card entertain">
                    <h3>Giải trí</h3>
                </a>
                <a href="/My-Web-Hotel/client/index.php?page=restaurant" class="service-card restaurant">
                    <h3>Ăn uống</h3>
                </a>
                <a href="/My-Web-Hotel/client/index.php?page=suKien" class="service-card event">
                    <h3>Hội nghị & Sự kiện</h3>
                </a>
            </div>
        </section>

        <!-- Giới thiệu -->
        <section class="about">
            <h2>TRẢI NGHIỆM SANG TRỌNG</h2>
            <p>
                Chúng tôi mang đến không gian nghỉ dưỡng và dịch vụ cao cấp, giúp
                khách hàng tận hưởng kỳ nghỉ hoàn hảo.
            </p>
        </section>

        <!-- Danh sách dịch vụ -->
        <div class="services-list-container">
            <div class="filter-div">
                <div class="filter-panel">
                    <h3>Tìm kiếm dịch vụ</h3>
                    <input type="text" id="searchInput" placeholder="Nhập tên dịch vụ để tìm…" class="search-box" />

                    <div class="sort-dropdown">
                        <label for="sort">Sắp xếp:</label>
                        <select id="sort" name="sort">
                            <option value="popular">Phổ biến nhất</option>
                            <option value="price-low">Giá thấp đến cao</option>
                            <option value="price-high">Giá cao đến thấp</option>
                        </select>
                    </div>
                    <div class="catalog">
                        <span>Loại dịch vụ:</span>
                        <fieldset>
                            <?php 
                            $sql = "SELECT DISTINCT service_type FROM service WHERE service_type <> 'Dịch vụ cá nhân'";
                            $result = $mysqli->query($sql);
                            while ($row = $result->fetch_assoc()):
                                $type = htmlspecialchars($row['service_type']); // tránh lỗi XSS
                            ?>
                            <input type="checkbox" id="<?= $type ?>" name="service_type[]" value="<?= $type ?>">
                            <label for="<?= $type ?>"><?= $type ?></label><br>
                            <?php endwhile; ?>
                        </fieldset>
                    </div>
                </div>
            </div>
            <div class="services-list-div" id="service">
                <div class="services-list-header">
                    <h2>Danh sách dịch vụ</h2>
                </div>

                <div class="services-main">

                </div>
            </div>
        </div>

        <!-- Ưu điểm -->
        <section class="features">
            <div class="features-text">
                <h2>LÝ DO CHỌN CHÚNG TÔI</h2>
                <ul>
                    <li>Không gian đẳng cấp, view biển sang trọng</li>
                    <li>Dịch vụ 5 sao chuyên nghiệp</li>
                    <li>Ẩm thực tinh hoa</li>
                    <li>Hội nghị & sự kiện quy mô lớn</li>
                </ul>
            </div>
            <div class="features-img"></div>
        </section>
    </main>