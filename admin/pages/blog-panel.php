<?php
  // Build base URL for pagination
$baseUrl = "index.php?page=blogs-manager&panel=blog-panel";
if ($search) $baseUrl .= "&search=" . urlencode($search);
if ($status_filter) $baseUrl .= "&status=" . urlencode($status_filter);
if ($category_filter) $baseUrl .= "&category=" . urlencode($category_filter);
?>
<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-newspaper"></i>
        </div>
        <div class="stat-label">Tổng Bài Viết</div>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-label">Đã Xuất Bản</div>
        <div class="stat-value"><?php echo $stats['published']; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-edit"></i>
        </div>
        <div class="stat-label">Bản Nháp</div>
        <div class="stat-value"><?php echo $stats['draft']; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-eye"></i>
        </div>
        <div class="stat-label">Lượt Xem</div>
        <div class="stat-value"><?php echo number_format($stats['total_views']); ?></div>
    </div>
</div>

<!-- Content Card -->
<div class="content-card">
    <div class="card-header-custom">
        <h3 class="card-title">Danh Sách Bài Viết</h3>
        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addBlogModal">
            <i class="fas fa-plus"></i> Thêm Bài Viết Mới
        </button>
    </div>

    <!-- Filter -->
    <div class="filter-section">
        <form>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Tìm kiếm bài viết..." />
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Published" <?php echo $status_filter == 'Published' ? 'selected' : ''; ?>>Đã xuất
                            bản
                        </option>
                        <option value="Draft" <?php echo $status_filter == 'Draft' ? 'selected' : ''; ?>>Bản nháp
                        </option>
                        <option value="Archived" <?php echo $status_filter == 'Archived' ? 'selected' : ''; ?>>Đã lưu
                            trữ
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo h($cat['category']); ?>"
                            <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo h($cat['category']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Blog Items -->
    <?php if (empty($blogs)): ?>
    <div class="text-center py-5">
        <p class="text-muted">Không có bài viết nào</p>
    </div>
    <?php else: ?>
    <?php foreach ($blogs as $blog): ?>
    <div class="content-item">
        <div class="image">
            <?php if (!empty($blog['thumbnail'])): ?>
                <img src="<?php echo h($blog['thumbnail']); ?>" alt="Blog" class="content-thumbnail" />
            <?php else: ?>
                <img src="../assets/images/placeholder.jpg" alt="Blog" class="content-thumbnail" />
            <?php endif; ?>
        </div>
        <div class="content-details">
            <div class="content-title">
                <?php echo h($blog['title']); ?>
            </div>
            <div class="content-meta">
                <span><i class="fas fa-user"></i> Admin</span>
                <span><i class="fas fa-calendar"></i> <?php echo formatDate($blog['created_at']); ?></span>
                <span><i class="fas fa-eye"></i> <?php echo number_format($blog['view_count']); ?> lượt xem</span>
                <span class="category-badge"><?php echo h($blog['category'] ?: 'Chưa phân loại'); ?></span>
                <span class="badge-status <?php
                                                                echo $blog['status'] == 'Published' ? 'badge-published' : ($blog['status'] == 'Draft' ? 'badge-draft' : 'badge-archived');
                                                                ?>">
                    <?php
                                        echo $blog['status'] == 'Published' ? 'Đã xuất bản' : ($blog['status'] == 'Draft' ? 'Bản nháp' : 'Đã lưu trữ');
                                        ?>
                </span>
            </div>
            <div class="content-excerpt">
                <?php echo h(mb_substr($blog['description'], 0, 150)); ?>...
            </div>
            <div class="content-actions">
                <button class="btn-sm-custom view" data-bs-toggle="modal"
                    data-bs-target="#viewBlogModal<?php echo $blog['blog_id'];  ?>">
                    <i class="fas fa-eye"></i> Xem
                </button>
                <button class="btn-sm-custom edit" onclick="editBlog(<?php echo $blog['blog_id']; ?>)">
                    <i class="fas fa-edit"></i> Sửa
                </button>
                <button class="btn-sm-custom delete" onclick="deleteBlog(<?php echo $blog['blog_id']; ?>)">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: View Blog Details -->
    <div class="modal fade" id="viewBlogModal<?php echo $blog['blog_id'];  ?>" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-newspaper"></i> Chi Tiết Bài Viết
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 80vh; overflow-y: auto">
                    <!-- Blog Preview Content -->
                    <div id="blogPreviewContent">
                        <!-- Content will be loaded dynamically -->
                        <div class="blog-preview-header">
                            <span class="blog-preview-category"
                                id="previewCategory"><?php echo h($blog['category'] ?: 'Chưa phân loại'); ?></span>
                            <h1 class="blog-preview-title" id="previewTitle">
                                <?php echo h($blog['title']); ?>
                            </h1>
                            <div class="blog-preview-meta">
                                <span><i class="fas fa-user"></i>
                                    <span id="previewAuthor">Admin</span></span>
                                <span><i class="fas fa-calendar"></i>
                                    <span id="previewDate"><?php echo formatDate($blog['created_at']); ?></span></span>
                                <span><i class="fas fa-eye"></i>
                                    <span id="previewViews"><?php echo number_format($blog['view_count']); ?>
                                    </span> lượt xem</span>
                            </div>
                        </div>

                        <?php if (!empty($blog['thumbnail'])): ?>
                            <img src="<?php echo h($blog['thumbnail']); ?>" alt="Blog" class="blog-preview-image" id="previewImage" />
                        <?php else: ?>
                            <img src="../assets/images/placeholder.jpg" alt="Blog" class="blog-preview-image" id="previewImage" />
                        <?php endif; ?>

                        <div class="blog-preview-body" id="previewBody">
                            <p>
                                <strong><?php echo $blog['description']; ?></strong>
                            </p>

                            <p>
                                <?php echo $blog['content']; ?>
                            </p>
                            <div class="blog-preview-tags">
                                <strong>Trạng thái:</strong>
                                <span class="blog-preview-tag">
                                    <span class="badge-status <?php echo $blog['status'] == 'Published' ? 'badge-published' : ($blog['status'] == 'Draft' ? 'badge-draft' : 'badge-archived');
                                                                                    ?>">
                                        <?php echo $blog['status'] == 'Published' ? 'Đã xuất bản' : ($blog['status'] == 'Draft' ? 'Bản nháp' : 'Đã lưu trữ');
                                                            ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Đóng
                        </button>
                        <button type="button" class="btn-primary-custom" onclick="editFromPreview()">
                            <i class="fas fa-edit"></i> Chỉnh Sửa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php endforeach; ?>
    <?php endif; ?>
    <!-- Pagination -->
    <?php echo getPagination($total, $perPage, $pageNum, $baseUrl); ?>
</div>
<!-- Modal: Add/Edit Blog -->
<div class="modal fade" id="addBlogModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> <?php echo $editBlog ? 'Sửa' : 'Thêm'; ?> Bài Viết
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php if ($editBlog): ?>
                    <input type="hidden" name="blog_id" value="<?php echo $editBlog['blog_id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Tiêu Đề Bài Viết *</label>
                                <input type="text" class="form-control" name="title"
                                    value="<?php echo h($editBlog['title'] ?? ''); ?>"
                                    placeholder="Nhập tiêu đề bài viết..." required />
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mô Tả Ngắn</label>
                                <textarea class="form-control" name="description" rows="3"
                                    placeholder="Mô tả ngắn về bài viết (hiển thị trong danh sách)"><?php echo h($editBlog['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nội Dung *</label>
                                <textarea class="form-control" id="blogEditor" name="content" rows="10" required
                                    placeholder="Nhập nội dung bài viết..."><?php echo h($editBlog['content'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Trạng Thái *</label>
                                <select class="form-select" name="status" required>
                                    <option value="Draft"
                                        <?php echo ($editBlog['status'] ?? 'Draft') == 'Draft' ? 'selected' : ''; ?>>
                                        Bản
                                        nháp</option>
                                    <option value="Published"
                                        <?php echo ($editBlog['status'] ?? '') == 'Published' ? 'selected' : ''; ?>>
                                        Xuất
                                        bản ngay</option>
                                    <option value="Archived"
                                        <?php echo ($editBlog['status'] ?? '') == 'Archived' ? 'selected' : ''; ?>>
                                        Đã
                                        lưu trữ</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Danh Mục *</label>
                                <input type="text" class="form-control" name="category"
                                    value="<?php echo h($editBlog['category'] ?? ''); ?>"
                                    placeholder="VD: Tin tức, Khuyến mãi..." />
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tác Giả</label>
                                <input type="text" class="form-control" value="Admin" readonly />
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ngày Xuất Bản</label>
                                <input type="date" class="form-control"
                                    value="<?php echo $editBlog ? date('Y-m-d', strtotime($editBlog['created_at'])) : date('Y-m-d'); ?>" />
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ảnh Đại Diện</label>
                                <div class="image-upload-area" onclick="document.getElementById('blogImage').click()"
                                    style="border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 5px; cursor: pointer;">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Click để chọn ảnh</p>
                                    <small class="text-muted">hoặc kéo thả ảnh vào đây</small>
                                </div>
                                <input type="file" id="blogImage" name="thumbnail" accept="image/*"
                                    style="display: none" onchange="previewImage(this, 'blogPreview')" />
                                <input type="hidden" name="remove_thumbnail" id="removeBlogThumbnail" value="0">
                                <div id="blogImageWrapper" class="image-preview-item position-relative me-2 mb-2 mt-3">
                                    <?php
                                        $hasThumb = $editBlog && !empty($editBlog['thumbnail']);
                                        $thumbUrl = $hasThumb ? $editBlog['thumbnail'] : '';
                                    ?>
                                    <img id="blogPreview"
                                        src="<?php echo h($thumbUrl); ?>"
                                        style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; <?php echo $hasThumb ? 'display: block;' : 'display: none;'; ?>" />
                                    <button type="button"
                                            id="blogImageRemoveBtn"
                                            class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                            style="<?php echo $hasThumb ? '' : 'display: none;'; ?>"
                                            onclick="clearBlogImage(this)">
                                        ×
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">Định dạng: JPG, PNG, GIF, WEBP. Kích thước tối đa: 5MB</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Hủy
                    </button>
                    <button type="submit" class="btn-primary-custom"
                        name="<?php echo $editBlog ? 'update_blog' : 'add_blog'; ?>">
                        <i class="fas fa-save"></i> <?php echo $editBlog ? 'Cập nhật' : 'Thêm'; ?> Bài Viết
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xác nhận xóa bài viết -->
<div class="modal fade" id="confirmDeleteBlogModal" tabindex="-1" aria-labelledby="confirmDeleteBlogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteBlogModalLabel">Xác nhận xóa</h5>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3 mb-0">Bạn có chắc muốn xóa bài viết này?<br>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">Hủy</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteBlog" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function editBlog(id) {
        window.location.href = 'index.php?page=blogs-manager&action=edit&id=' + id;
    }

    let blogIdToDelete = null;

    function deleteBlog(id) {
        blogIdToDelete = id;
        const modal = new bootstrap.Modal(document.getElementById('confirmDeleteBlogModal'));
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnConfirmDelete = document.getElementById('btnConfirmDeleteBlog');
        if (btnConfirmDelete) {
            btnConfirmDelete.addEventListener('click', function() {
                if (blogIdToDelete) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="blog_id" value="' + blogIdToDelete + '">' +
                        '<input type="hidden" name="delete_blog" value="1">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    });

    <?php if ($editBlog): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('addBlogModal'));
            modal.show();
        });
    <?php endif; ?>
</script>