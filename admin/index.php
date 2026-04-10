<?php
session_start();
require 'includes/connect.php';
require 'includes/auth.php'; // Kiểm tra đăng nhập

// Helper function for safe redirects
if (!function_exists('safe_redirect')) {
    function safe_redirect($url) {
        if (headers_sent()) {
            echo "<script>window.location.href = '" . htmlspecialchars($url, ENT_QUOTES) . "';</script>";
            exit;
        } else {
            header("Location: {$url}");
            exit;
        }
    }
}

// Try to use MVC Router
$useMVC = true; // Set to false to use old pages structure

if ($useMVC && file_exists(__DIR__ . '/core/Router.php')) {
    require_once __DIR__ . '/core/Router.php';
    
    // Kiểm tra xem có phải quản lý không
    $chuc_vu = $_SESSION['chuc_vu'] ?? '';
    $isManager = false;
    if (!empty($chuc_vu)) {
        $isManager = (
            stripos($chuc_vu, 'Quản lý') !== false ||
            stripos($chuc_vu, 'Manager') !== false ||
            stripos($chuc_vu, 'Admin') !== false ||
            stripos($chuc_vu, 'Giám đốc') !== false ||
            stripos($chuc_vu, 'Director') !== false
        );
    }
    
    // Nếu không có page và là nhân viên (không phải quản lý), redirect đến my-tasks
    if (!isset($_GET['page']) || trim($_GET['page']) === '') {
        if (!$isManager) {
            header("Location: index.php?page=my-tasks");
            exit;
        }
    }
    
    $router = new Router($mysqli);
    
    // Define $page for footer.php compatibility
    $page = isset($_GET['page']) ? trim($_GET['page']) : 'home';
    
    include 'includes/header.php';
    include 'includes/sidebar.php';
    $router->dispatch();
    include 'includes/footer.php';
} else {
    // Fallback to old structure
    include 'includes/header.php';
    include 'includes/sidebar.php';
    
    $allowed = [
        'home' => 'pages/home.php',
        'room-manager' => 'pages/room-manager.php',
        'services-manager' => 'pages/services-manager.php',
        'invoices-manager' => 'pages/invoices-manager.php',
        'booking-manager' => 'pages/booking-manager.php',
        'calendar-booking' => 'pages/calendar-booking.php',
        'customers-manager' => 'pages/customers-manager.php',
        'staff-manager' => 'pages/staff-manager.php',
        'task-manager' => 'pages/task-manager.php',
        'permission-manager' => 'pages/permission-manager.php',
        'reports-manager' => 'pages/reports-manager.php',
        'blogs-manager' => 'pages/blogs-manager.php',
        'profile' => 'pages/profile.php',
        'logout'=>'pages/logout.php',
        'my-tasks' => 'pages/my-tasks.php',
    ];
    
    $pageName = isset($_GET['page']) ? trim($_GET['page']) : 'home';
    $page = $pageName; // For footer.php compatibility
    
    if (isset($allowed[$pageName])) {
        if (function_exists('canAccessSection') && !canAccessSection($pageName)) {
            include 'pages/403.php';
        } else {
            include $allowed[$pageName];
        }
    } else {
        include 'pages/404.php';
    }
    
    include 'includes/footer.php';
}
?>
