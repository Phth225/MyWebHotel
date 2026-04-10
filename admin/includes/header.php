<!DOCTYPE html>
<html lang="vi">

<head>
    <script>
    // Kiểm tra theme đã lưu trước khi CSS load
    if (
        localStorage.theme === 'dark' ||
        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
    ) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- favicon -->
    <link rel="icon" href="/My-Web-Hotel/client/assets/images/favicon.png" type="image/x-icon" />
    <title>OceanPearl Hotel Manager</title>
    <!-- reset css -->
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/reset.css?v=<?php echo time(); ?>">
    <!-- embed bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- embed icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <!-- Select2 for searchable select -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <!-- embed styles -->
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/common.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/sidebar.css?v=<?php echo time(); ?>" />
    <?php
  $current_page = isset($page) ? $page : (isset($_GET['page']) ? $_GET['page'] : 'home');
    if ($current_page == 'home') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/home.css?v=<?php echo time(); ?>">';
    if ($current_page == 'room-manager') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/room-manager.css?v=<?php echo time(); ?>">';
    if ($current_page == 'services-manager') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/services-manager.css?v=<?php echo time(); ?>">';
    if ($current_page == 'invoices-manager') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/invoices-manager.css?v=<?php echo time(); ?>">';
    if ($current_page == 'booking-manager') {
        echo '<link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/booking-manager.css?v=' . time() . '">';
        // Load calendar CSS for all booking panels to share premium status styles
        echo '<link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/calendar-booking.css?v=' . time() . '">';
    }
    if ($current_page == 'calendar-booking') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/calendar-booking.css?v=<?php echo time(); ?>">';
    if ($current_page == 'customers-manager') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/customers-manager.css?v=<?php echo time(); ?>">';
    if ($current_page == 'staff-manager') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/staff-manager.css?v=<?php echo time(); ?>">';
    if ($current_page == 'reports-manager') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/reports-manager.css?v=<?php echo time(); ?>">';
    if ($current_page == 'blogs-manager') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/blogs-manager.css?v=<?php echo time(); ?>">';
    if ($current_page == 'profile') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/profile.css?v=<?php echo time(); ?>">';
    if ($current_page == 'voucher-manager') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/voucher-manager.css?v=<?php echo time(); ?>">';
    if ($current_page == 'my-tasks') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/admin/assets/css/my-tasks.css?v=<?php echo time(); ?>">';
    ?>

</head>

<body>