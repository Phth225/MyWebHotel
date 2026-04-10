<?php
// Lấy slug từ URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("Location: /My-Web-Hotel/client/index.php?page=blog");
    exit();
}

// Lấy thông tin bài viết
$sql_blog = "SELECT * FROM blog WHERE slug = ? LIMIT 1";
$stmt = $mysqli->prepare($sql_blog);
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /My-Web-Hotel/client/index.php?page=blog");
    exit();
}

$blog = $result->fetch_assoc();

// Tăng view_count
$update_view = "UPDATE blog SET view_count = view_count + 1 WHERE blog_id = ?";
$stmt_update = $mysqli->prepare($update_view);
$stmt_update->bind_param("i", $blog['blog_id']);
$stmt_update->execute();

// Lấy bài viết liên quan (cùng category)
$sql_related = "SELECT * FROM blog 
                WHERE category = ? AND blog_id != ? 
                ORDER BY RAND() 
                LIMIT 3";
$stmt_related = $mysqli->prepare($sql_related);
$stmt_related->bind_param("si", $blog['category'], $blog['blog_id']);
$stmt_related->execute();
$result_related = $stmt_related->get_result();
?>



<main>
    <!-- Back Button -->
    <button class="back-button" onclick="history.back()">
        <i class="fas fa-arrow-left me-2"></i>Quay lại
    </button>

    <!-- Hero Section -->
    <div class="hero-section">
        <img src="<?= htmlspecialchars($blog['thumbnail']) ?>" alt="<?= htmlspecialchars($blog['title']) ?>"
            class="hero-image">
        <div class="hero-overlay">
            <div class="hero-content">
                <span class="hero-category">
                    <i class="fas fa-tag me-1"></i><?= htmlspecialchars($blog['category']) ?>
                </span>
                <h1 class="hero-title"><?= htmlspecialchars($blog['title']) ?></h1>
                <div class="hero-meta">
                    <span>
                        <i class="far fa-calendar-alt"></i>
                        <?= date('d/m/Y', strtotime($blog['created_at'])) ?>
                    </span>
                    <span>
                        <i class="far fa-eye"></i>
                        <?= number_format($blog['view_count']) ?> lượt xem
                    </span>
                    <span>
                        <i class="far fa-clock"></i>
                        <?= ceil(str_word_count(strip_tags($blog['content'])) / 200) ?> phút đọc
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <!-- Blog Content -->
        <div class="blog-content">
            <div class="lead mb-4" style="font-size: 1.3rem; color: var(--text-muted);">
                <?= htmlspecialchars($blog['description']) ?>
            </div>

            <hr style="border-color: var(--primary-color); opacity: 0.3; margin: 40px 0;">

            <div>
                <?= $blog['content'] ?>
            </div>
        </div>

        <!-- Author Box -->
        <div class="author-box">
            <img src="https://ui-avatars.com/api/?name=OceanPearl+Hotel&size=100&background=ffffff&color=deb666"
                alt="OceanPearl Hotel" class="author-avatar">
            <div class="author-info">
                <h4>OceanPearl Hotel</h4>
                <p>
                    Khách sạn sang trọng tại Phú Quốc – Nơi nghỉ dưỡng hoàn hảo bên bờ biển đảo ngọc.
                    Chúng tôi mang đến cho bạn những trải nghiệm du lịch đáng nhớ nhất.
                </p>
            </div>
        </div>

        <!-- Share Section -->
        <div class="share-section">
            <h4><i class="fas fa-share-alt me-2"></i>Chia sẻ bài viết này</h4>
            <div class="share-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>"
                    target="_blank" class="share-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($blog['title']) ?>"
                    target="_blank" class="share-btn twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>"
                    target="_blank" class="share-btn linkedin">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="https://pinterest.com/pin/create/button/?url=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>&media=<?= urlencode($blog['thumbnail']) ?>&description=<?= urlencode($blog['title']) ?>"
                    target="_blank" class="share-btn pinterest">
                    <i class="fab fa-pinterest-p"></i>
                </a>
                <a href="https://wa.me/?text=<?= urlencode($blog['title'] . ' ' . 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>"
                    target="_blank" class="share-btn whatsapp">
                    <i class="fab fa-whatsapp"></i>
                </a>
            </div>
        </div>

        <!-- Related Posts -->
        <?php if ($result_related->num_rows > 0): ?>
        <div class="related-section">
            <div class="section-title">
                <h2>Bài Viết Liên Quan</h2>
                <div class="divider"></div>
            </div>

            <div class="row g-4">
                <?php while($related = $result_related->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card related-card">
                        <div class="related-card-img">
                            <img src="<?= htmlspecialchars($related['thumbnail']) ?>"
                                alt="<?= htmlspecialchars($related['title']) ?>">
                            <span class="related-badge"><?= htmlspecialchars($related['category']) ?></span>
                        </div>
                        <div class="related-card-body">
                            <h5 class="related-card-title">
                                <a href="/My-Web-Hotel/client/index.php?page=blog-detail&slug=<?= htmlspecialchars($related['slug']) ?>"
                                    class="text-decoration-none">
                                    <?= htmlspecialchars($related['title']) ?>
                                </a>
                            </h5>
                            <div class="related-meta">
                                <span><i class="far fa-calendar-alt"></i>
                                    <?= date('d/m/Y', strtotime($related['created_at'])) ?>
                                </span>
                                <span><i class="far fa-eye"></i>
                                    <?= number_format($related['view_count']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>


    <script>
    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Reading progress bar (optional - có thể thêm)
    window.addEventListener('scroll', function() {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;

        // Có thể thêm progress bar ở đây nếu muốn
    });
    </script>

</main>
<?php $mysqli->close(); ?>