<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/connect.php';

$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

// Lấy thông tin user nếu đã đăng nhập
$user_info = null;
if ($user_id) {
    $stmt = $mysqli->prepare("SELECT full_name, avatar FROM customer WHERE customer_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}

// Lấy 5 reviews đầu tiên
$reviews_query = "SELECT r.*, c.full_name, c.avatar 
                  FROM review r 
                  JOIN customer c ON r.customer_id = c.customer_id 
                  WHERE r.status = 'Approved' AND r.deleted IS NULL
                  ORDER BY r.created_at DESC
                  LIMIT 5";
$reviews_result = $mysqli->query($reviews_query);

// Tính rating trung bình và tổng số reviews
$avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
              FROM review WHERE status = 'Approved' AND deleted IS NULL";

$avg_result = $mysqli->query($avg_query);

if ($avg_result) {
    $avg_data = $avg_result->fetch_assoc();
    $avg_rating = round($avg_data['avg_rating'], 1);
    $total_reviews = $avg_data['total_reviews'];
} else {
    die("Query error: " . $mysqli->error);
}

?>
<main class="container">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Summary -->
            <div class="summary-card">
                <h2>Đánh giá tổng quan</h2>
                <div class="rating-large"><?= $avg_rating ?>/5.0</div>
                <div class="stars">
                    <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= floor($avg_rating)) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - $avg_rating < 1) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                </div>
                <p class="mt-3 mb-0">Dựa trên <strong><?= $total_reviews ?></strong> đánh giá</p>
            </div>

            <!-- Write Review Form -->
            <div class="write-review-card">
                <h3><i class="fas fa-pen-fancy me-2"></i>Viết đánh giá của bạn</h3>

                <?php if (!$isLoggedIn): ?>
                <div class="alert-login">
                    <i class="fas fa-info-circle" style="color: #ffc107; font-size: 1.5rem;"></i>
                    <div>
                        <strong>Vui lòng đăng nhập để viết đánh giá</strong>
                        <p class="mb-0">Bạn cần đăng nhập để chia sẻ trải nghiệm của mình.</p>
                    </div>
                    <a href="login.php" class="btn btn-warning ms-auto">Đăng nhập</a>
                </div>
                <?php else: ?>
                <form id="reviewForm" method="POST" action="/My-Web-Hotel/client/controller/submit-review.php"
                    enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Đánh giá của bạn</label>
                        <div class="star-rating" id="starRating">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Chia sẻ trải nghiệm của bạn</label>
                        <textarea class="form-control" name="comment" rows="5"
                            placeholder="Hãy chia sẻ những điều bạn thích về khách sạn..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-images me-2"></i>Thêm ảnh (tùy chọn)
                        </label>
                        <input type="file" class="form-control" id="imageInput" name="imageInput[]" accept="image/*"
                            multiple onchange="previewImages(event)">
                        <small class="text-muted">Bạn có thể chọn nhiều ảnh</small>
                        <div class="image-upload-preview" id="imagePreview"></div>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-paper-plane me-2"></i>Gửi đánh giá
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Reviews List -->
            <h3 class="mb-4"><i class="fas fa-comments me-2"></i>Đánh giá từ khách hàng</h3>

            <?php if ($reviews_result->num_rows > 0): ?>
            <div id="reviewsList">
                <?php while ($review = $reviews_result->fetch_assoc()): 
                    $is_owner = ($user_id && $review['customer_id'] == $user_id);
                ?>
                <div class="review-card" data-review-id="<?= $review['review_id'] ?>">
                    <div class="review-header">
                        <img src="<?= htmlspecialchars(!empty($review['avatar']) ? $review['avatar'] : '/My-Web-Hotel/client/assets/images/275f99923b080b18e7b474ed6155a17f.jpg') ?>"
                            alt="Avatar" class="review-avatar">
                        <div class="review-info flex-grow-1">
                            <h5><?= htmlspecialchars($review['full_name']) ?></h5>
                            <div class="review-date">
                                <i class="far fa-clock me-1"></i>
                                <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                            <i class="fas fa-star"></i>
                            <?php endfor; ?>
                            <?php for ($i = $review['rating']; $i < 5; $i++): ?>
                            <i class="far fa-star"></i>
                            <?php endfor; ?>
                        </div>

                        <!-- Menu hành động -->
                        <div class="review-actions">
                            <button class="review-actions-btn"
                                onclick="toggleReviewActions(this, <?= $review['review_id'] ?>, <?= $is_owner ? 'true' : 'false' ?>)">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="review-actions-menu">
                                <?php if ($is_owner): ?>
                                <button class="review-actions-menu-item delete"
                                    onclick="confirmDeleteReview(<?= $review['review_id'] ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                    <span>Xóa bình luận</span>
                                </button>
                                <?php endif; ?>
                                <button class="review-actions-menu-item"
                                    onclick="copyReviewContent(<?= $review['review_id'] ?>)">
                                    <i class="fas fa-copy"></i>
                                    <span>Sao chép nội dung</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="review-text">
                        <?= nl2br(htmlspecialchars($review['comment'])) ?>
                    </div>

                    <?php
                    $review_id = $review['review_id'];
                    $images_query = "SELECT image_url FROM review_images 
                                     WHERE review_id = ? AND deleted IS NULL
                                     ORDER BY display_order ASC";
                    $images_stmt = $mysqli->prepare($images_query);
                    
                    if ($images_stmt) {
                        $images_stmt->bind_param("i", $review_id);
                        if ($images_stmt->execute()) {
                            $images_result = $images_stmt->get_result();
                            
                            if ($images_result && $images_result->num_rows > 0):
                            ?>
                    <div class="review-images">
                        <?php while ($img_row = $images_result->fetch_assoc()): 
                                    if (!empty($img_row['image_url'])):
                                ?>
                        <img src="<?= htmlspecialchars($img_row['image_url']) ?>" alt="Review Image"
                            onclick="openImageModal(this.src)">
                        <?php 
                                    endif;
                                endwhile; ?>
                    </div>
                    <?php 
                            endif;
                        }
                        $images_stmt->close();
                    }
                    ?>
                </div>
                <?php endwhile; ?>
            </div>

            <?php if ($total_reviews > 5): ?>
            <div class="load-more text-center">
                <button class="btn btn-load-more" id="loadMoreBtn" onclick="loadMoreReviews()">
                    <i class="fas fa-chevron-down me-2"></i>Xem thêm đánh giá
                </button>
                <input type="hidden" id="currentOffset" value="5">
                <input type="hidden" id="totalReviews" value="<?= $total_reviews ?>">
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="mt-3 text-muted">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="sidebar">
                <div class="gallery-card">
                    <h4><i class="fas fa-camera me-2"></i>Ảnh từ khách hàng</h4>
                    <div class="gallery-grid">
                        <?php
                            $gallery_query = "SELECT ri.image_url 
                                            FROM review_images ri
                                            INNER JOIN review r ON ri.review_id = r.review_id
                                            WHERE r.status = 'Approved' 
                                            AND (r.deleted IS NULL OR r.deleted = '0000-00-00 00:00:00')
                                            AND (ri.deleted IS NULL OR ri.deleted = '0000-00-00 00:00:00')
                                            ORDER BY ri.created_at DESC
                                            LIMIT 8";

                            $gallery_result = $mysqli->query($gallery_query);

                            if ($gallery_result && $gallery_result->num_rows > 0) {
                                while ($img_row = $gallery_result->fetch_assoc()) {
                                    if (!empty($img_row['image_url'])) {
                            ?>
                        <img src="<?= htmlspecialchars($img_row['image_url']) ?>" alt="Gallery"
                            onclick="openImageModal(this.src)">
                        <?php
                                    }
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body p-0">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3 bg-white"
                    data-bs-dismiss="modal" style="z-index: 10;"></button>
                <img src="" id="modalImage" class="w-100">
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal -->
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
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Xác nhận xóa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3 mb-0">Bạn có chắc muốn xóa bình luận này không?<br>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn bg-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger btn-confirm-delete" onclick="handleConfirmDelete()">
                    <i class="fas fa-trash-alt me-2"></i>Xóa bình luận
                </button>
            </div>
        </div>
    </div>
</div>