<?php
// Kiểm tra quyền xem lịch
$canViewCalendar = function_exists('checkPermission') ? checkPermission('service.calendar.view') : true;

if (!$canViewCalendar) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem lịch đặt phòng.</div></div>';
    return;
}
?>

<div class="main-content">
    <?php
    // Include panel file
    if (file_exists(__DIR__ . '/calendar-booking-panel.php')) {
        include __DIR__ . '/calendar-booking-panel.php';
    } else {
        echo '<div class="alert alert-danger m-4">File lịch đặt phòng không tồn tại.</div>';
    }
    ?>
</div>

