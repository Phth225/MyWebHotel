<?php
// Phân quyền
$canViewReview = function_exists('checkPermission') ? (checkPermission('review.view')) : true;
$canEditReview = function_exists('checkPermission') ? (checkPermission('review.edit')) : true;
$canDeleteReview = function_exists('checkPermission') ? (checkPermission('review.delete')) : true;

if (!$canViewReview) {
    http_response_code(403);
    echo '<div class="alert alert-danger m-4">Bạn không có quyền xem đánh giá.</div>';
    return;
}

// Xử lý CRUD
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_review_status']) && $canEditReview) {
        $review_id = intval($_POST['review_id']);
        $status = trim($_POST['status']);

        $stmt = $mysqli->prepare("UPDATE review SET status = ? WHERE review_id = ? AND deleted IS NULL");
        $stmt->bind_param("si", $status, $review_id);

        if ($stmt->execute()) {
            $message = 'Cập nhật trạng thái đánh giá thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }

    if (isset($_POST['delete_review']) && $canDeleteReview) {
        $review_id = intval($_POST['review_id']);
        $stmt = $mysqli->prepare("UPDATE review SET deleted = NOW() WHERE review_id = ?");
        $stmt->bind_param("i", $review_id);

        if ($stmt->execute()) {
            $message = 'Xóa đánh giá thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Phân trang và filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'created_at';
$sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';
$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum);
$perPage = 10;
$offset = ($pageNum - 1) * $perPage;

// Xây dựng query
$where = "WHERE r.deleted IS NULL";
$params = [];
$types = '';

// Tìm kiếm: tên khách hàng, email, nội dung đánh giá
if ($search) {
    $where .= " AND (c.full_name LIKE ? OR c.email LIKE ? OR r.comment LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= 'sss';
}

// Filter theo trạng thái
if ($status_filter) {
    $where .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Filter theo rating
if ($rating_filter > 0) {
    $where .= " AND r.rating = ?";
    $params[] = $rating_filter;
    $types .= 'i';
}

// Filter theo ngày từ
if ($date_from) {
    $where .= " AND DATE(r.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

// Filter theo ngày đến
if ($date_to) {
    $where .= " AND DATE(r.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Sắp xếp
$allowed_sort = ['created_at', 'rating', 'full_name', 'room_number'];
$sort_by = in_array($sort_by, $allowed_sort) ? $sort_by : 'created_at';
$sort_order = strtoupper($sort_order) == 'ASC' ? 'ASC' : 'DESC';
$orderBy = "ORDER BY ";
if ($sort_by == 'full_name') {
    $orderBy .= "c.full_name $sort_order";
} elseif ($sort_by == 'room_number') {
    $orderBy .= "d.room_number $sort_order";
} else {
    $orderBy .= "r.$sort_by $sort_order";
}

// Đếm tổng số đánh giá (theo customer_id)
$countQuery = "SELECT COUNT(*) as total 
               FROM review r
               INNER JOIN customer c ON c.customer_id = r.customer_id
               $where";
$countStmt = $mysqli->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalReview = $totalResult->fetch_assoc()['total'];
$countStmt->close();

// Lấy thông tin tổng số các đánh giá và trung bình rating (theo filter hiện tại)
$statsWhere = "WHERE r.deleted IS NULL";
$statsParams = [];
$statsTypes = '';

if ($status_filter) {
    $statsWhere .= " AND r.status = ?";
    $statsParams[] = $status_filter;
    $statsTypes .= 's';
}

if ($rating_filter > 0) {
    $statsWhere .= " AND r.rating = ?";
    $statsParams[] = $rating_filter;
    $statsTypes .= 'i';
}

if ($date_from) {
    $statsWhere .= " AND DATE(r.created_at) >= ?";
    $statsParams[] = $date_from;
    $statsTypes .= 's';
}

if ($date_to) {
    $statsWhere .= " AND DATE(r.created_at) <= ?";
    $statsParams[] = $date_to;
    $statsTypes .= 's';
}

$statsQuery = "SELECT 
    COUNT(*) as total,
    ROUND(AVG(r.rating), 1) as avg_rating,
    SUM(CASE WHEN r.status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN r.status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN r.status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM review r
    INNER JOIN customer c ON c.customer_id = r.customer_id
    $statsWhere";

$statsStmt = $mysqli->prepare($statsQuery);
if (!empty($statsParams)) {
    $statsStmt->bind_param($statsTypes, ...$statsParams);
}
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$reviewCount = $statsResult->fetch_assoc();
$statsStmt->close();

// Lấy dữ liệu review (theo customer_id)
$reviews = [];
if ($totalReview > 0) {
    $query = "SELECT r.*, c.full_name, c.email
              FROM review r
              INNER JOIN customer c ON c.customer_id = r.customer_id
              $where
              $orderBy
              LIMIT ? OFFSET ?";

    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $mysqli->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Build base URL for pagination
$baseUrl = "index.php?page=blogs-manager&panel=review-panel";
if ($search) $baseUrl .= "&search=" . urlencode($search);
if ($status_filter) $baseUrl .= "&status=" . urlencode($status_filter);
if ($rating_filter) $baseUrl .= "&rating=" . $rating_filter;
if ($date_from) $baseUrl .= "&date_from=" . urlencode($date_from);
if ($date_to) $baseUrl .= "&date_to=" . urlencode($date_to);
if ($sort_by != 'created_at') $baseUrl .= "&sort_by=" . urlencode($sort_by);
if ($sort_order != 'DESC') $baseUrl .= "&sort_order=" . urlencode($sort_order);
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo h($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-comments"></i>
        </div>
        <div class="stat-label">Tổng Đánh Giá</div>
        <div class="stat-value"><?php echo $reviewCount['total'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-label">Đánh Giá TB</div>
        <div class="stat-value"><?php echo number_format($reviewCount['avg_rating'] ?? 0, 1); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-label">Đã Duyệt</div>
        <div class="stat-value"><?php echo $reviewCount['approved_count'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-label">Chờ Duyệt</div>
        <div class="stat-value"><?php echo $reviewCount['pending_count'] ?? 0; ?></div>
    </div>
</div>

<!-- Content Card -->
<div class="content-card">
    <div class="card-header-custom">
        <h3 class="card-title">Danh Sách Đánh Giá</h3>
    </div>

    <!-- Filter -->
    <div class="filter-section">
        <form method="GET" action="">
            <input type="hidden" name="page" value="blogs-manager">
            <input type="hidden" name="panel" value="review-panel">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Tìm kiếm (tên, email, số phòng, nội dung)..." value="<?php echo h($search); ?>" />
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                        <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Đã duyệt</option>
                        <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Từ chối</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="rating">
                        <option value="0">Tất cả sao</option>
                        <option value="5" <?php echo $rating_filter == 5 ? 'selected' : ''; ?>>5 sao</option>
                        <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>4 sao</option>
                        <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>3 sao</option>
                        <option value="2" <?php echo $rating_filter == 2 ? 'selected' : ''; ?>>2 sao</option>
                        <option value="1" <?php echo $rating_filter == 1 ? 'selected' : ''; ?>>1 sao</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">Từ ngày</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo h($date_from); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Đến ngày</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo h($date_to); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Sắp xếp theo</label>
                    <select class="form-select" name="sort_by">
                        <option value="created_at" <?php echo $sort_by == 'created_at' ? 'selected' : ''; ?>>Ngày tạo</option>
                        <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>Đánh giá</option>
                        <option value="full_name" <?php echo $sort_by == 'full_name' ? 'selected' : ''; ?>>Tên khách hàng</option>
                        <option value="room_number" disabled>Số phòng (không dùng)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Thứ tự</label>
                    <select class="form-select" name="sort_order">
                        <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Giảm dần</option>
                        <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Tăng dần</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">&nbsp;</label>
                    <a href="index.php?page=blogs-manager&panel=review-panel" class="btn btn-secondary w-100">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Review Items -->
    <?php if (empty($reviews)): ?>
        <div class="text-center py-5">
            <p class="text-muted">Không có đánh giá nào</p>
        </div>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-info">
                        <div class="reviewer-avatar"><?php echo strtoupper(substr($review['full_name'], 0, 2)); ?></div>
                        <div>
                            <div class="reviewer-name"><?php echo h($review['full_name']); ?></div>
                            <div class="review-date">
                                <?php echo formatDate($review['created_at']); ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="rating-stars">
                            <?php
                            $rating = intval($review['rating']);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="mt-2">
                            <?php
                            $statusClass = 'bg-secondary';
                            $statusText = $review['status'];
                            switch ($review['status']) {
                                case 'Approved':
                                    $statusClass = 'bg-success';
                                    $statusText = 'Đã duyệt';
                                    break;
                                case 'Pending':
                                    $statusClass = 'bg-warning';
                                    $statusText = 'Chờ duyệt';
                                    break;
                                case 'Rejected':
                                    $statusClass = 'bg-danger';
                                    $statusText = 'Từ chối';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                        </div>
                    </div>
                </div>
                <div class="review-content">
                    <?php echo nl2br(h($review['comment'])); ?>
                </div>
                <?php
                // Ảnh đính kèm review: lấy từ bảng review_images (nhiều ảnh / 1 review)
                $images = [];
                $revId = (int)$review['review_id'];
                $imgStmt = $mysqli->prepare("SELECT image_url FROM review_images WHERE review_id = ? AND deleted IS NULL ORDER BY display_order ASC, id ASC");
                if ($imgStmt) {
                    $imgStmt->bind_param("i", $revId);
                    if ($imgStmt->execute()) {
                        $imgRes = $imgStmt->get_result();
                        while ($rowImg = $imgRes->fetch_assoc()) {
                            if (!empty($rowImg['image_url'])) {
                                $images[] = $rowImg['image_url'];
                            }
                        }
                    }
                    $imgStmt->close();
                }
                if (!empty($images)):
                ?>
                    <div class="review-images mt-3">
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($images as $imgUrl): ?>
                                <a href="<?php echo h($imgUrl); ?>" target="_blank" class="review-image-thumbnail">
                                    <img src="<?php echo h($imgUrl); ?>"
                                         alt="Review Image"
                                         class="img-thumbnail"
                                         style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; border: 2px solid #ddd; cursor: pointer; transition: transform 0.2s;"
                                         onmouseover="this.style.transform='scale(1.1)'"
                                         onmouseout="this.style.transform='scale(1)'"
                                         onclick="event.preventDefault(); openImageModal('<?php echo h($imgUrl); ?>'); return false;">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="review-actions">
                    <?php if ($canEditReview): ?>
                        <button class="btn-sm-custom view" data-bs-toggle="modal" data-bs-target="#viewReviewModal<?php echo $review['review_id']; ?>">
                            <i class="fas fa-eye"></i> Xem chi tiết
                        </button>
                        <?php if ($review['status'] == 'Pending'): ?>
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Bạn có chắc muốn duyệt đánh giá này?');">
                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit" name="update_review_status" class="btn-sm-custom approve">
                                    <i class="fas fa-check"></i> Duyệt
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($canDeleteReview): ?>
                        <button type="button" class="btn-sm-custom delete" onclick="deleteReview(<?php echo $review['review_id']; ?>)">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- View Review Modal -->
            <div class="modal fade" id="viewReviewModal<?php echo $review['review_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Chi Tiết Đánh Giá</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Khách hàng:</strong> <?php echo h($review['full_name']); ?></p>
                            <p><strong>Đánh giá:</strong>
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star text-warning"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                (<?php echo $rating; ?>/5)
                            </p>
                            <p><strong>Nội dung:</strong></p>
                            <p><?php echo nl2br(h($review['comment'])); ?></p>
                            <?php
                            // Lấy ảnh cho modal
                            $modalImages = [];
                            $modalImgStmt = $mysqli->prepare("SELECT image_url FROM review_images WHERE review_id = ? AND deleted IS NULL ORDER BY display_order ASC, id ASC");
                            if ($modalImgStmt) {
                                $modalImgStmt->bind_param("i", $review['review_id']);
                                if ($modalImgStmt->execute()) {
                                    $modalImgRes = $modalImgStmt->get_result();
                                    while ($modalRow = $modalImgRes->fetch_assoc()) {
                                        if (!empty($modalRow['image_url'])) {
                                            $modalImages[] = $modalRow['image_url'];
                                        }
                                    }
                                }
                                $modalImgStmt->close();
                            }
                            if (!empty($modalImages)):
                            ?>
                            <p><strong>Ảnh đính kèm:</strong></p>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php foreach ($modalImages as $modalImgUrl): ?>
                                    <a href="<?php echo h($modalImgUrl); ?>" target="_blank">
                                        <img src="<?php echo h($modalImgUrl); ?>"
                                             alt="Review Image"
                                             class="img-thumbnail"
                                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; cursor: pointer; transition: transform 0.2s;"
                                             onmouseover="this.style.transform='scale(1.1)'"
                                             onmouseout="this.style.transform='scale(1)'"
                                             onclick="event.preventDefault(); openImageModal('<?php echo h($modalImgUrl); ?>'); return false;">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <p><strong>Trạng thái:</strong> <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></p>
                            <p><strong>Ngày tạo:</strong> <?php echo formatDateTime($review['created_at']); ?></p>
                        </div>
                        <div class="modal-footer">
                            <?php if ($canEditReview && $review['status'] == 'Pending'): ?>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit" name="update_review_status" class="btn btn-success">
                                    <i class="fas fa-check"></i> Duyệt
                                </button>
                            </form>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                <input type="hidden" name="status" value="Rejected">
                                <button type="submit" name="update_review_status" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Từ chối
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($canDeleteReview): ?>
                            <button type="button" class="btn btn-danger" onclick="deleteReview(<?php echo $review['review_id']; ?>); bootstrap.Modal.getInstance(document.getElementById('viewReviewModal<?php echo $review['review_id']; ?>')).hide();">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination -->
    <?php echo getPagination($totalReview, $perPage, $pageNum, $baseUrl); ?>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xem ảnh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="modalImage" src="" alt="Review Image" class="img-fluid" style="max-height: 80vh; width: auto;">
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận xóa đánh giá -->
<div class="modal fade" id="confirmDeleteReviewModal" tabindex="-1" aria-labelledby="confirmDeleteReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteReviewModalLabel">Xác nhận xóa</h5>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3 mb-0">Bạn có chắc muốn xóa đánh giá này?<br>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">Hủy</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteReview" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openImageModal(imageUrl) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    document.getElementById('modalImage').src = imageUrl;
    modal.show();
}

let reviewIdToDelete = null;

function deleteReview(id) {
    reviewIdToDelete = id;
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteReviewModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const btnConfirmDelete = document.getElementById('btnConfirmDeleteReview');
    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', function() {
            if (reviewIdToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="review_id" value="' + reviewIdToDelete + '">' +
                    '<input type="hidden" name="delete_review" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});
</script>