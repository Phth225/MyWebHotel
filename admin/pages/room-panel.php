<?php
// Phân quyền
$canViewRoom = function_exists('checkPermission') ? checkPermission('room.view') : true;
$canCreateRoom = function_exists('checkPermission') ? checkPermission('room.create') : true;
$canEditRoom = function_exists('checkPermission') ? checkPermission('room.edit') : true;
$canDeleteRoom = function_exists('checkPermission') ? checkPermission('room.delete') : true;

if (!$canViewRoom) {
    http_response_code(403);
    echo '<div class="alert alert-danger m-4">Bạn không có quyền xem phòng.</div>';
    return;
}

// Build base URL for pagination
$baseUrl = "index.php?page=room-manager&panel=room-panel";
$search = isset($search) ? $search : (isset($_GET['search']) ? trim($_GET['search']) : '');
$status_filter = isset($status_filter) ? $status_filter : (isset($_GET['status']) ? trim($_GET['status']) : '');
$type_filter = isset($type_filter) ? $type_filter : (isset($_GET['type']) ? intval($_GET['type']) : 0);
if ($search) $baseUrl .= "&search=" . urlencode($search);
if ($status_filter) $baseUrl .= "&status=" . urlencode($status_filter);
if ($type_filter) $baseUrl .= "&type=" . $type_filter;
?>


<div class="content-card">
    <div class="card-header-custom">
        <div class="d-flex align-items-center gap-3">
            <h3 class="card-title">Danh Sách Phòng</h3>
            <div class="view-toggle-buttons">
                <button type="button" class="btn btn-view-toggle active" id="btnTableView" title="Xem dạng bảng">
                    <i class="fas fa-list"></i>
                </button>
                <button type="button" class="btn btn-view-toggle" id="btnGridView" title="Xem dạng lưới">
                    <i class="fas fa-th"></i>
                </button>
            </div>
        </div>
        <?php if ($canCreateRoom): ?>
        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addRoomModal">
            <i class="fas fa-plus"></i> Thêm Phòng
        </button>
        <?php endif; ?>
    </div>
    <?php
    // Nhóm phòng theo tầng cho Grid View - sử dụng $allRooms để hiển thị hết các phòng
    $roomsByFloor = [];
    $displayRooms = isset($allRooms) ? $allRooms : $rooms;
    foreach ($displayRooms as $room) {
        $floor = $room['floor'] ?? 0;
        $roomsByFloor[$floor][] = $room;
    }
    ksort($roomsByFloor);
    ?>
    <div class="filter-section">
        <form method="GET" action="">
            <input type="hidden" name="page" value="room-manager">
            <div class="row">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" name="search" placeholder="Tìm kiếm phòng..."
                            value="<?php echo h($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Available" <?php echo $status_filter == 'Available' ? 'selected' : ''; ?>>Có
                            sẵn</option>
                        <option value="Booked" <?php echo $status_filter == 'Booked' ? 'selected' : ''; ?>>
                            Đã
                            đặt
                        </option>
                        <option value="Occupied" <?php echo $status_filter == 'Occupied' ? 'selected' : ''; ?>>
                            Đang
                            thuê</option>
                        <option value="Maintenance" <?php echo $status_filter == 'Maintenance' ? 'selected' : ''; ?>>Bảo
                            trì
                        </option>
                        <option value="Cleaning" <?php echo $status_filter == 'Cleaning' ? 'selected' : ''; ?>>
                            Đang
                            dọn</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type" id="typeFilter">
                        <option value="0">Tất cả loại phòng</option>
                        <?php foreach ($roomTypes as $rt): ?>
                        <option value="<?php echo $rt['room_type_id']; ?>"
                            <?php echo $type_filter == $rt['room_type_id'] ? 'selected' : ''; ?>>
                            <?php echo h($rt['room_type_name']); ?>
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

    <!-- Status Legend - Tách biệt ra ngoài khung filter -->
    <div class="status-legend d-none">
        <h6><strong>Trạng thái phòng:</strong></h6>
        <span class="legend-item"><span class="legend-dot status-available"></span> Có sẵn</span>
        <span class="legend-item"><span class="legend-dot status-booked"></span> Đã đặt</span>
        <span class="legend-item"><span class="legend-dot status-occupied"></span> Đang thuê</span>
        <span class="legend-item"><span class="legend-dot status-maintenance"></span> Bảo trì</span>
        <span class="legend-item"><span class="legend-dot status-cleaning"></span> Đang dọn</span>
    </div>
    <!-- Bảng danh sách phòng -->
    <div class="table-container">
        <table class="table table-hover" id="roomsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Số Phòng</th>
                    <th>Loại Phòng</th>
                    <th>Giá/Đêm</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                <tr>
                    <td colspan="9" class="text-center">Không có dữ liệu</td>
                </tr>
                <?php else: ?>
                <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?php echo $room['room_id']; ?></td>
                    <td><?php echo h($room['room_number']); ?></td>
                    <td>
                        <strong> <?php echo $room['room_type_name']; ?></strong>
                    </td>
                    <td><?php echo formatCurrency($room['base_price'] ?? 0); ?></td>
                    <td>
                        <?php
                                $statusClass = 'bg-secondary';
                                $statusText = $room['status'];
                                switch ($room['status']) {
                                    case 'Available': $statusClass = 'bg-success'; $statusText = 'Có sẵn'; break;
                                    case 'Booked': $statusClass = 'bg-warning'; $statusText = 'Đã đặt'; break;
                                    case 'Occupied': $statusClass = 'bg-danger'; $statusText = 'Đang thuê'; break;
                                    case 'Maintenance': $statusClass = 'bg-dark'; $statusText = 'Bảo trì'; break;
                                    case 'Cleaning': $statusClass = 'bg-info'; $statusText = 'Đang dọn'; break;
                                }
                                ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                            data-bs-target="#viewRoomModal<?php echo $room['room_id']; ?>" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($canEditRoom): ?>
                        <button class="btn btn-sm btn-outline-warning"
                            onclick="editRoom(<?php echo $room['room_id']; ?>)" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($canDeleteRoom): ?>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                            data-bs-target="#confirmDeleteModal" data-room-id="<?php echo $room['room_id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Grid danh sách phòng -->
    <div class="grid-container d-none" id="roomsGrid">
        <?php if (empty($rooms)): ?>
        <div class="text-center p-5 w-100">Không có dữ liệu</div>
        <?php else: ?>
        <?php foreach ($roomsByFloor as $floor => $floorRooms): ?>
        <div class="floor-section mb-4">
            <div class="floor-title">
                <h5>Tầng <?php echo $floor; ?></h5>
            </div>
            <div class="floor-grid">
                <?php foreach ($floorRooms as $room): 
                    $statusClass = '';
                    switch ($room['status']) {
                        case 'Available': $statusClass = 'status-available'; break;
                        case 'Booked': $statusClass = 'status-booked'; break;
                        case 'Occupied': $statusClass = 'status-occupied'; break;
                        case 'Maintenance': $statusClass = 'status-maintenance'; break;
                        case 'Cleaning': $statusClass = 'status-cleaning'; break;
                    }
                ?>
                <div class="room-card <?php echo $statusClass; ?>">
                    <div class="room-number"><?php echo h($room['room_number']); ?></div>
                    <div class="room-actions">
                        <button class="btn btn-sm btn-outline-info" title="Xem chi tiết" data-bs-toggle="modal"
                            data-bs-target="#viewRoomModal<?php echo $room['room_id']; ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($canEditRoom): ?>
                        <button class="btn btn-sm btn-outline-warning" title="Sửa"
                            onclick="editRoom(<?php echo $room['room_id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($canDeleteRoom): ?>
                        <button class="btn btn-sm btn-outline-danger delete-shortcut" title="Xóa"
                            data-room-id="<?php echo $room['room_id']; ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <!-- Pagination -->
    <div id="paginationContainer">
        <?php echo getPagination($total, $perPage, $pageNum, $baseUrl); ?>
    </div>

    <!-- View Modals cho TẤT CẢ các phòng (phủ cho cả Table và Grid View) -->
    <?php 
    $modalRooms = isset($allRooms) ? $allRooms : $rooms;
    foreach ($modalRooms as $room): 
        // Tính toán lại status class/text cho modal
        $statusClass = 'bg-secondary';
        $statusText = $room['status'];
        switch ($room['status']) {
            case 'Available': $statusClass = 'bg-success'; $statusText = 'Có sẵn'; break;
            case 'Booked': $statusClass = 'bg-warning'; $statusText = 'Đã đặt'; break;
            case 'Occupied': $statusClass = 'bg-danger'; $statusText = 'Đang thuê'; break;
            case 'Maintenance': $statusClass = 'bg-dark'; $statusText = 'Bảo trì'; break;
            case 'Cleaning': $statusClass = 'bg-info'; $statusText = 'Đang dọn'; break;
        }
    ?>
    <div class="modal fade" id="viewRoomModal<?php echo $room['room_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Phòng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <?php
                            $roomTypeImages = [];
                            if (!empty($room['room_type_id'])) {
                                $rtId = (int)$room['room_type_id'];
                                $imagesStmt = $mysqli->prepare("SELECT image_url FROM roomtype_images WHERE room_type_id = ? ORDER BY is_primary DESC, display_order ASC, id ASC");
                                $imagesStmt->bind_param("i", $rtId);
                                $imagesStmt->execute();
                                $imagesResult = $imagesStmt->get_result();
                                while ($imgRow = $imagesResult->fetch_assoc()) {
                                    $roomTypeImages[] = $imgRow['image_url'];
                                }
                                $imagesStmt->close();
                            }
                            if (!empty($roomTypeImages)):
                            ?>
                            <div class="image-section">
                                <div class="main-image-slider">
                                    <div class="slider-track" id="sliderTrack">
                                        <?php foreach ($roomTypeImages as $img): ?>
                                        <div class="slide">
                                            <img src="<?php echo $img; ?>" alt="Room Image">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="slider-btn slider-btn-prev"
                                        onclick="moveSlide(-1, 'viewRoomModal<?php echo $room['room_id']; ?>')">‹</button>
                                    <button class="slider-btn slider-btn-next"
                                        onclick="moveSlide(1, 'viewRoomModal<?php echo $room['room_id']; ?>')">›</button>
                                </div>

                                <div class="thumbnail-gallery" id="thumbnailGallery">
                                    <?php foreach ($roomTypeImages as $index => $img): ?>
                                    <div class="gallery-item <?php echo $index === 0 ? 'active' : ''; ?>"
                                        onclick="goToSlide(<?php echo $index; ?>, 'viewRoomModal<?php echo $room['room_id']; ?>')">
                                        <img src="<?php echo $img; ?>" alt="Thumb <?php echo $index + 1; ?>">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light rounded"
                                style="width: 100%; height: 200px;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-image fa-3x mb-2"></i>
                                    <p class="mb-0">Chưa có ảnh</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5">
                            <h4>Phòng <?php echo h($room['room_number']); ?></h4>
                            <p><strong>Loại phòng:</strong>
                                <span class="badge bg-info"><?php echo h($room['room_type_name'] ?? '-'); ?></span>
                            </p>
                            <p><strong>Tầng:</strong> <?php echo $room['floor']; ?></p>
                            <p><strong>Giá/đêm:</strong>
                                <span class="fw-bold"
                                    style="color: #b69854;"><?php echo formatCurrency($room['base_price'] ?? 0); ?></span>
                            </p>
                            <p><strong>Diện tích:</strong> <?php echo $room['area'] ? $room['area'] . 'm²' : '-'; ?></p>
                            <p><strong>Sức chứa:</strong> <?php echo $room['capacity'] ?? '-'; ?> người</p>
                            <p><strong>Trạng thái:</strong>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </p>
                            <p><strong>Mô tả:</strong>
                            <div class="p-3 desc">
                                <?php echo nl2br(($room['description'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary"
                        onclick="editRoomFromView(<?php echo $room['room_id']; ?>)">
                        Chỉnh Sửa
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<!-- Modal Xác nhận xóa -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">
                    Xác nhận xóa
                </h5>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3 mb-0">Bạn có chắc muốn xóa phòng này không?<br>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">
                    <i class="fas fa-trash-alt me-2"></i>Xóa phòng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm/Sửa Phòng -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $editRoom ? 'Sửa' : 'Thêm'; ?> Phòng</h5>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php if ($editRoom): ?>
                    <input type="hidden" name="room_id" value="<?php echo $editRoom['room_id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Số phòng *</label>
                            <input type="text" class="form-control" name="room_number"
                                value="<?php echo h($editRoom['room_number'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tầng *</label>
                            <input type="number" class="form-control" name="floor"
                                value="<?php echo $editRoom['floor'] ?? ''; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Loại phòng *</label>
                            <select class="form-select" name="room_type_id" required>
                                <option value="">Chọn loại phòng</option>
                                <?php foreach ($roomTypes as $rt): ?>
                                <option value="<?php echo $rt['room_type_id']; ?>"
                                    <?php echo ($editRoom['room_type_id'] ?? '') == $rt['room_type_id'] ? 'selected' : ''; ?>>
                                    <?php echo h($rt['room_type_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trạng thái *</label>
                            <select class="form-select" name="status" required>
                                <option value="Available"
                                    <?php echo ($editRoom['status'] ?? 'Available') == 'Available' ? 'selected' : ''; ?>>
                                    Có sẵn</option>
                                <option value="Booked"
                                    <?php echo ($editRoom['status'] ?? '') == 'Booked' ? 'selected' : ''; ?>>Đã
                                    đặt
                                </option>
                                <option value="Occupied"
                                    <?php echo ($editRoom['status'] ?? '') == 'Occupied' ? 'selected' : ''; ?>>
                                    Đang
                                    thuê
                                </option>
                                <option value="Maintenance"
                                    <?php echo ($editRoom['status'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>
                                    Bảo
                                    trì</option>
                                <option value="Cleaning"
                                    <?php echo ($editRoom['status'] ?? '') == 'Cleaning' ? 'selected' : ''; ?>>
                                    Đang
                                    dọn
                                </option>
                            </select>
                        </div>
                    </div>
                    <!-- Ảnh phòng được lấy theo loại phòng (room_type) nên không upload tại đây nữa -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"
                        name="<?php echo $editRoom ? 'update_room' : 'add_room'; ?>">
                        <?php echo $editRoom ? 'Cập nhật' : 'Thêm'; ?> Phòng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview image function (single)
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = "block";
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ==================== HELPER FUNCTIONS ====================

// Hàm reset form tổng quát
function resetFormFields(form) {
    if (!form) return;

    form.reset();

    // Xóa input hidden (trừ page và panel)
    form.querySelectorAll('input[type="hidden"]').forEach(input => {
        if (input.name !== 'page' && input.name !== 'panel') {
            input.remove();
        }
    });

    // Reset text/number/tel/email inputs
    form.querySelectorAll('input[type="text"], input[type="number"], input[type="tel"], input[type="email"],select')
        .forEach(input => {
            input.value = '';
        });

    // Reset textarea
    form.querySelectorAll('textarea').forEach(textarea => {
        textarea.value = '';
    });

    // Reset date về hôm nay
    const today = new Date().toISOString().split('T')[0];
    form.querySelectorAll('input[type="date"]').forEach(input => {
        input.value = today;
    });

    // Clear readonly fields
    form.querySelectorAll('input[readonly]').forEach(input => {
        input.value = '';
    });
}

// Hàm xóa query string edit
function clearEditQueryString() {
    const url = new URL(window.location);
    url.searchParams.delete('action');
    url.searchParams.delete('id');
    window.history.replaceState({}, '', url.toString());
}

// Hàm force cleanup backdrop
function forceCleanupBackdrop() {
    const openModals = document.querySelectorAll('.modal.show');
    if (openModals.length === 0) {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
}

// Hàm reset modal về trạng thái "Thêm mới"
function resetModalToAddMode(modalElement, form) {
    if (!modalElement || !form) return;

    const modalId = modalElement.id;
    const modalTitle = modalElement.querySelector('.modal-title');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Config cho từng modal
    const modalConfig = {
        'addRoomModal': {
            title: 'Thêm Phòng',
            buttonName: 'add_room',
            buttonHTML: '<i class="fas fa-save"></i> Thêm Phòng'
        },
        'addRoomTypeModal': {
            title: 'Thêm Loại Phòng',
            buttonName: 'add_room_type',
            buttonHTML: '<i class="fas fa-save"></i> Thêm Loại Phòng'
        },
        'addBookingServiceModal': {
            title: 'Thêm Booking Dịch Vụ',
            buttonName: 'add_booking_service',
            buttonHTML: 'Thêm Booking dịch vụ'
        }
    };

    const config = modalConfig[modalId];
    if (config) {
        if (modalTitle) modalTitle.textContent = config.title;
        if (submitBtn) {
            submitBtn.name = config.buttonName;
            submitBtn.innerHTML = config.buttonHTML;
        }
    }
}

// ==================== ROOM FUNCTIONS ====================



function editRoom(id) {
    const url = new URL(window.location.href);
    url.searchParams.set('action', 'edit');
    url.searchParams.set('id', id);
    window.location.href = url.toString();
}

function deleteRoom(id) {
    if (confirm('Bạn có chắc chắn muốn xóa phòng này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="room_id" value="' + id + '">' +
            '<input type="hidden" name="delete_room" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}

// ==================== MODAL AUTO-RESET ====================

// Khởi tạo slider khi modal được hiển thị
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('shown.bs.modal', function() {
        const modalId = this.id;
        if (modalId.startsWith('viewRoomModal')) {
            // Reset slider khi mở modal mới
            currentSlide = 0;
            initSlider(modalId);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Tự động mở modal edit nếu có action=edit
    <?php if ($editRoom): ?>
    const editModal = new bootstrap.Modal(document.getElementById('addRoomModal'));
    editModal.show();
    <?php endif; ?>

    // Danh sách modal cần auto-reset
    const resettableModals = ['addRoomModal', 'addRoomTypeModal', 'addBookingServiceModal'];

    // Xử lý TỔNG QUÁT cho TẤT CẢ modal
    document.querySelectorAll('.modal').forEach(modalElement => {

        // Event: Khi modal đã đóng hoàn toàn
        modalElement.addEventListener('hidden.bs.modal', function() {
            const form = modalElement.querySelector('form');
            const modalId = modalElement.id;

            // Chỉ xử lý modal trong danh sách
            if (resettableModals.includes(modalId)) {
                const isEditMode = window.location.search.includes('action=edit');

                if (isEditMode) {
                    // Xóa query string edit
                    clearEditQueryString();
                }

                // Reset form về trạng thái "Thêm mới"
                if (form) {
                    resetFormFields(form);
                    resetModalToAddMode(modalElement, form);
                }
            }

            // Cleanup backdrop
            setTimeout(forceCleanupBackdrop, 100);
        });

        // Event: Khi modal sắp mở
        modalElement.addEventListener('show.bs.modal', function() {
            const form = modalElement.querySelector('form');
            const isEditMode = window.location.search.includes('action=edit');

            // Nếu KHÔNG phải edit mode, reset form
            if (!isEditMode && form && resettableModals.includes(modalElement.id)) {
                resetFormFields(form);
            }
        });
    });

    // Xử lý nút "Thêm mới" - xóa query string edit và reset form
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            const isEditMode = window.location.search.includes('action=edit');
            if (isEditMode) {
                clearEditQueryString();
            }
            // Reset form when opening add modal
            const modalId = button.getAttribute('data-bs-target');
            if (modalId && (modalId.includes('addRoomModal') || modalId.includes(
                    'addRoomTypeModal'))) {
                setTimeout(function() {
                    const form = document.querySelector(modalId + ' form');
                    if (form && !window.location.search.includes('action=edit')) {
                        resetFormFields(form);
                        resetModalToAddMode(document.querySelector(modalId), form);
                    }
                }, 200);
            }
        });
    });

    // Xử lý ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            setTimeout(forceCleanupBackdrop, 150);
        }
    });

    let currentRoomId = null; // Biến lưu room_id

    // Xử lý khi click nút xóa (các nút có class btn-outline-danger)
    document.querySelectorAll('[data-bs-target="#confirmDeleteModal"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const roomId = this.getAttribute('data-room-id');

            if (roomId) {
                currentRoomId = roomId;
            }
        });
    });

    // Xử lý khi click nút "Xóa phòng" trong modal
    const confirmDeleteButton = document.getElementById('confirmDeleteButton');

    if (confirmDeleteButton) {
        confirmDeleteButton.addEventListener('click', function() {

            if (currentRoomId) {
                // Tạo form ẩn để gửi POST request
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input type="hidden" name="room_id" value="${currentRoomId}">
                    <input type="hidden" name="delete_room" value="1">
                `;

                document.body.appendChild(form);
                form.submit();
            } else {
                alert('Lỗi: Không tìm thấy ID phòng');
            }
        });
    }

    // ==================== VIEW TOGGLE LOGIC ====================
    const btnTableView = document.getElementById('btnTableView');
    const btnGridView = document.getElementById('btnGridView');
    const tableContainer = document.querySelector('.table-container');
    const gridContainer = document.getElementById('roomsGrid');
    const paginationContainer = document.getElementById('paginationContainer');
    const statusLegend = document.querySelector('.status-legend');

    function setView(view) {
        if (view === 'grid') {
            tableContainer.classList.add('d-none');
            gridContainer.classList.remove('d-none');
            if (paginationContainer) paginationContainer.classList.add('d-none');
            if (statusLegend) statusLegend.classList.remove('d-none');
            btnGridView.classList.add('active');
            btnTableView.classList.remove('active');
            localStorage.setItem('room_view_preference', 'grid');
        } else {
            gridContainer.classList.add('d-none');
            tableContainer.classList.remove('d-none');
            if (paginationContainer) paginationContainer.classList.remove('d-none');
            if (statusLegend) statusLegend.classList.add('d-none');
            btnTableView.classList.add('active');
            btnGridView.classList.remove('active');
            localStorage.setItem('room_view_preference', 'table');
        }
    }

    btnTableView.addEventListener('click', () => setView('table'));
    btnGridView.addEventListener('click', () => setView('grid'));

    // Khởi tạo view từ preference
    const savedView = localStorage.getItem('room_view_preference');
    if (savedView === 'grid') {
        setView('grid');
    }

    // Xử lý nút xóa trong Grid View
    document.querySelectorAll('.delete-shortcut').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const roomId = this.getAttribute('data-room-id');
            if (roomId) {
                currentRoomId = roomId;
                const deleteModal = new bootstrap.Modal(document.getElementById(
                    'confirmDeleteModal'));
                deleteModal.show();
            }
        });
    });

    // Hàm hỗ trợ sửa phòng khi đang xem chi tiết
    window.editRoomFromView = function(id) {
        const modalEl = document.getElementById('viewRoomModal' + id);
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        editRoom(id);
    };
});
</script>