<?php
// Phân quyền module Nội Dung (Blog & Review)
$canViewContent   = function_exists('checkPermission') ? checkPermission('blog.view')   : true;
$canCreateContent = function_exists('checkPermission') ? checkPermission('blog.create') : true;
$canEditContent   = function_exists('checkPermission') ? checkPermission('blog.edit')   : true;
$canDeleteContent = function_exists('checkPermission') ? checkPermission('blog.delete') : true;

if (!$canViewContent) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem trang nội dung.</div></div>';
    return;
}

// Xử lý CRUD
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$messageType = '';

// Tạo slug từ title
if (!function_exists('createSlug')) {
    function createSlug($title)
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}

// Xử lý upload ảnh thumbnail cho blog lên Cloudinary
if (!function_exists('uploadBlogThumbnail')) {
    function uploadBlogThumbnail($file, $oldThumbnail = '') {
        if (!isset($file['name']) || empty($file['name'])) {
            return $oldThumbnail;
        }
        
        require_once __DIR__ . '/../includes/cloudinary_helper.php';
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Upload lên Cloudinary
        $cloudinaryUrl = CloudinaryHelper::upload($file['tmp_name'], 'blog');
        
        if ($cloudinaryUrl !== false) {
            // Xóa ảnh cũ trên Cloudinary nếu có
            if (!empty($oldThumbnail)) {
                CloudinaryHelper::deleteByUrl($oldThumbnail);
            }
            return $cloudinaryUrl;
        }
        
        return $oldThumbnail;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_blog'])) {
        if (!$canCreateContent) {
            $message = 'Bạn không có quyền thêm bài viết.';
            $messageType = 'danger';
        } else {
            $title = trim($_POST['title']);
            $slug = createSlug($title);
            $description = trim($_POST['description'] ?? '');
            $content = trim($_POST['content']);
            $category = trim($_POST['category'] ?? '');
            $status = $_POST['status'] ?? 'Draft';

            // Kiểm tra slug unique
            $checkStmt = $mysqli->prepare("SELECT blog_id FROM blog WHERE slug = ? AND deleted IS NULL");
            $checkStmt->bind_param("s", $slug);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $slug .= '-' . time();
            }
            $checkStmt->close();

            // Xử lý upload ảnh thumbnail
            $thumbnail = '';
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
                $uploadResult = uploadBlogThumbnail($_FILES['thumbnail']);
                if ($uploadResult !== false) {
                    $thumbnail = $uploadResult;
                } else {
                    $message = 'Lỗi: Không thể upload ảnh. Vui lòng kiểm tra định dạng và kích thước file (tối đa 5MB).';
                    $messageType = 'danger';
                }
            }

            if ($messageType != 'danger') {
                $stmt = $mysqli->prepare("INSERT INTO blog (title, slug, description, content, category, status, thumbnail) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $title, $slug, $description, $content, $category, $status, $thumbnail);

                if ($stmt->execute()) {
                    $message = 'Thêm bài viết thành công!';
                    $messageType = 'success';
                    if (function_exists('safe_redirect')) {
                        safe_redirect("index.php?page=blogs-manager&panel=blog-panel");
                    } else {
                        echo "<script>window.location.href = 'index.php?page=blogs-manager&panel=blog-panel';</script>";
                        exit;
                    }
                } else {
                    $message = 'Lỗi: ' . $stmt->error;
                    $messageType = 'danger';
                }
                $stmt->close();
            }
        }
    }

    if (isset($_POST['update_blog'])) {
        if (!$canEditContent) {
            $message = 'Bạn không có quyền chỉnh sửa bài viết.';
            $messageType = 'danger';
        } else {
            $blog_id = intval($_POST['blog_id']);
            $title = trim($_POST['title']);
            $slug = createSlug($title);
            $description = trim($_POST['description'] ?? '');
            $content = trim($_POST['content']);
            $category = trim($_POST['category'] ?? '');
            $status = $_POST['status'] ?? 'Draft';

            // Kiểm tra slug unique (trừ chính nó)
            $checkStmt = $mysqli->prepare("SELECT blog_id FROM blog WHERE slug = ? AND blog_id != ? AND deleted IS NULL");
            $checkStmt->bind_param("si", $slug, $blog_id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $slug .= '-' . time();
            }
            $checkStmt->close();

            // Lấy ảnh cũ
            $oldThumbnailStmt = $mysqli->prepare("SELECT thumbnail FROM blog WHERE blog_id = ?");
            $oldThumbnailStmt->bind_param("i", $blog_id);
            $oldThumbnailStmt->execute();
            $oldThumbnailResult = $oldThumbnailStmt->get_result();
            $oldThumbnail = $oldThumbnailResult->fetch_assoc()['thumbnail'] ?? '';
            $oldThumbnailStmt->close();

            $thumbnail = $oldThumbnail;

            // Nếu bấm nút X để xóa ảnh đại diện
            if (!empty($_POST['remove_thumbnail']) && $_POST['remove_thumbnail'] === '1') {
                if (!empty($oldThumbnail)) {
                    require_once __DIR__ . '/../includes/cloudinary_helper.php';
                    CloudinaryHelper::deleteByUrl($oldThumbnail);
                }
                $thumbnail = '';
            }

            // Xử lý upload ảnh thumbnail mới nếu có
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
                $uploadResult = uploadBlogThumbnail($_FILES['thumbnail'], $oldThumbnail);
                if ($uploadResult !== false) {
                    $thumbnail = $uploadResult;
                } else {
                    $message = 'Lỗi: Không thể upload ảnh. Vui lòng kiểm tra định dạng và kích thước file (tối đa 5MB).';
                    $messageType = 'danger';
                }
            }

            if ($messageType != 'danger') {
                $stmt = $mysqli->prepare("UPDATE blog SET title=?, slug=?, description=?, content=?, category=?, status=?, thumbnail=? WHERE blog_id=? AND deleted IS NULL");
                $stmt->bind_param("sssssssi", $title, $slug, $description, $content, $category, $status, $thumbnail, $blog_id);

                if ($stmt->execute()) {
                    $message = 'Cập nhật bài viết thành công!';
                    $messageType = 'success';
                    if (function_exists('safe_redirect')) {
                        safe_redirect("index.php?page=blogs-manager&panel=blog-panel");
                    } else {
                        echo "<script>window.location.href = 'index.php?page=blogs-manager&panel=blog-panel';</script>";
                        exit;
                    }
                } else {
                    $message = 'Lỗi: ' . $stmt->error;
                    $messageType = 'danger';
                }
                $stmt->close();
            }
        }
    }

    if (isset($_POST['delete_blog'])) {
        $blog_id = intval($_POST['blog_id']);
        $stmt = $mysqli->prepare("UPDATE blog SET deleted = NOW() WHERE blog_id = ?");
        $stmt->bind_param("i", $blog_id);

        if ($stmt->execute()) {
            $message = 'Xóa bài viết thành công!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Lấy thông tin blog để edit
$editBlog = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM blog WHERE blog_id = ? AND deleted IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editBlog = $result->fetch_assoc();
    $stmt->close();
}


// Phân trang và tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$pageNum = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : 1;
$pageNum = max(1, $pageNum); // Đảm bảo pageNum >= 1
$perPage = 2;
$offset = ($pageNum - 1) * $perPage;

// Xây dựng WHERE clause
$where = "WHERE b.deleted IS NULL";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (b.title LIKE ? OR b.description LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
    $types .= 'ss';
}

if ($status_filter) {
    $where .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($category_filter) {
    $where .= " AND b.category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

// Đếm tổng số
$countQuery = "SELECT COUNT(*) as total FROM blog b $where";
$countStmt = $mysqli->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$countStmt->close();

// Lấy dữ liệu - FIX: Hardcode LIMIT và OFFSET
$query = "SELECT * FROM blog b 
    $where 
    ORDER BY b.created_at DESC 
    LIMIT $perPage OFFSET $offset";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $blogs = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Lỗi query: " . $stmt->error);
}
$stmt->close();

// Đếm tổng số đánh giá


// Lấy danh sách categories
$categoriesResult = $mysqli->query("SELECT DISTINCT category FROM blog WHERE deleted IS NULL AND category IS NOT NULL AND category != '' ORDER BY category");
$categories = $categoriesResult->fetch_all(MYSQLI_ASSOC);


// Thống kê bài viết 
$statsResult = $mysqli->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) as published,
    SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft,
    SUM(view_count) as total_views
    FROM blog WHERE deleted IS NULL");
$stats = $statsResult->fetch_assoc();

// Lưu ý: phần Review (thống kê + danh sách) đã được xử lý riêng trong review-panel.php
// nên không cần truy vấn review ở đây để tránh lỗi schema (booking_id, room, room_type,...)

?>
<div class="main-content">
    <div class="content-header">
        <h1>Quản Lý Nội Dung</h1>
        <?php
        $current_panel = isset($panel) ? $panel : (isset($_GET['panel']) ? $_GET['panel'] : 'blog-panel');
        ?>
        <ul class="nav nav-pills mb-3" id="contentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel == 'blog-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="/My-Web-Hotel/admin/index.php?page=blogs-manager&panel=blog-panel">
                    <span>Bài Viết & Tin Tức</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel == 'review-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="/My-Web-Hotel/admin/index.php?page=blogs-manager&panel=review-panel">
                    <span>Đánh Giá & Review</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <?php
        $panel = isset($panel) ? $panel : (isset($_GET['panel']) ? trim($_GET['panel']) : 'blog-panel');
        $panelAllowed = [
            'blog-panel' => 'blog-panel.php',
            'review-panel' => 'review-panel.php',
        ];
        if (isset($panelAllowed[$panel])) {
            // Ensure $mysqli is available
            if (!isset($mysqli)) {
                global $mysqli;
                if (!isset($mysqli)) {
                    require_once __DIR__ . '/../includes/connect.php';
                }
            }
            $panelFile = __DIR__ . DIRECTORY_SEPARATOR . $panelAllowed[$panel];
            if (file_exists($panelFile)) {
                include $panelFile;
            } else {
                echo '<div class="alert alert-danger">File not found: ' . htmlspecialchars($panelFile) . '</div>';
            }
        } else {
            $notFoundFile = __DIR__ . DIRECTORY_SEPARATOR . '404.php';
            if (file_exists($notFoundFile)) {
                include $notFoundFile;
            } else {
                echo '<div class="alert alert-danger">404 - Page not found</div>';
            }
        }
        ?>
    </div>
</div>

