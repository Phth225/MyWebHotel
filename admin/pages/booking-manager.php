<?php
$canViewBooking = function_exists('checkPermission') ? checkPermission('booking.view') : true;

if (!$canViewBooking) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem trang booking.</div></div>';
    return;
}
?>
<div class="main-content">
    <div class="content-header">
        <h1>Quản Lý Booking</h1>
        <?php
            $current_panel = isset($panel) ? $panel : (isset($_GET['panel']) ? $_GET['panel'] : 'roomBooking-panel');
        ?>
        <?php
            // Kiểm tra quyền cho calendar panel
            $canViewCalendar = function_exists('canAccessSection') ? canAccessSection('calendar-booking-panel') : 
                              (function_exists('checkPermission') ? checkPermission('service.calendar.view') : true);
        ?>
        <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel=='roomBooking-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="index.php?page=booking-manager&panel=roomBooking-panel">
                    <span>Booking Phòng</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel=='serviceBooking-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="index.php?page=booking-manager&panel=serviceBooking-panel">
                    <span>Booking Dịch Vụ</span>
                </a>
            </li>
            <?php if ($canViewCalendar): ?>
            <li class="nav-item" role="presentation">
                <a class="<?php echo ($current_panel=='calendar-booking-panel') ? 'nav-link active' : 'nav-link'; ?>"
                    href="index.php?page=booking-manager&panel=calendar-booking-panel">
                    <span>Lịch Đặt Phòng & Sự Kiện</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="tab-content">
        <?php
            $panel = isset($panel) ? $panel : (isset($_GET['panel']) ? trim($_GET['panel']) : 'roomBooking-panel');
            $panelAllowed = [
                'roomBooking-panel' => 'roomBooking-panel.php',
                'serviceBooking-panel' => 'serviceBooking-panel.php',
                'calendar-booking-panel' => 'calendar-booking-panel.php',
            ];
            if (isset($panelAllowed[$panel])) {
                // Kiểm tra quyền cho calendar panel
                if ($panel === 'calendar-booking-panel') {
                    $canViewCalendar = function_exists('canAccessSection') ? canAccessSection('calendar-booking-panel') : 
                                      (function_exists('checkPermission') ? checkPermission('service.calendar.view') : true);
                    if (!$canViewCalendar) {
                        echo '<div class="alert alert-danger m-4">Bạn không có quyền xem lịch đặt phòng và dịch vụ.</div>';
                        return;
                    }
                }
                
                // $mysqli should be available from parent scope (index.php or Controller)
                // If not, try to get it from global scope
                if (!isset($mysqli)) {
                    global $mysqli;
                    if (!isset($mysqli)) {
                        require_once __DIR__ . '/../includes/connect.php';
                    }
                }
                // Use absolute path to avoid issues
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

    <!-- Modal Xác nhận xóa dùng chung cho Room và Service Booking -->
    <div class="modal fade" id="confirmDeleteBookingModal" tabindex="-1" aria-labelledby="confirmDeleteBookingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteBookingModalLabel">
                        Xác nhận xóa
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <p class="mt-3 mb-0" id="deleteModalMessage">Bạn có chắc muốn xóa dịch vụ này?<br>Hành động này không thể hoàn tác.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">Hủy</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fas fa-trash-alt me-2"></i>Xác nhận xóa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
let bookingToDelete = { id: null, type: null };

function showDeleteModal(id, type) {
    bookingToDelete.id = id;
    bookingToDelete.type = type;
    
    const messageEl = document.getElementById('deleteModalMessage');
    
    if (type === 'room') {
        messageEl.innerHTML = 'Bạn có chắc muốn xóa booking phòng này?<br>Hành động này không thể hoàn tác.';
    } else {
        messageEl.innerHTML = 'Bạn có chắc muốn xóa dịch vụ này?<br>Hành động này không thể hoàn tác.';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteBookingModal'));
    modal.show();
}

document.getElementById('btnConfirmDelete')?.addEventListener('click', function() {
    if (!bookingToDelete.id) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    
    if (bookingToDelete.type === 'room') {
        form.innerHTML = '<input type="hidden" name="booking_id" value="' + bookingToDelete.id + '">' +
                        '<input type="hidden" name="delete_booking_room" value="1">';
    } else {
        form.innerHTML = '<input type="hidden" name="booking_service_id" value="' + bookingToDelete.id + '">' +
                        '<input type="hidden" name="delete_booking_service" value="1">';
    }
    
    document.body.appendChild(form);
    form.submit();
});
</script>