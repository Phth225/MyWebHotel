<?php
session_start();
require_once 'includes/connect.php'; 
$page = isset($_GET['page']) ? trim($_GET['page']) : 'home';
include 'controller/auth.php';
include 'includes/header.php';
// include 'includes/loading.php';
$allowed = [
    'home' => 'pages/home.php',
    'room' => 'pages/room.php',
    'dichVu' => 'pages/dichVu.php',
    'service-detail' => 'pages/service-detail.php',
    'booking' => 'pages/booking.php',
    'service-booking' => 'pages/service-booking.php',
    'blog' => 'pages/blog.php',
    'blog-detail' => 'pages/blog-detail.php',
    'danhGia' => 'pages/danhGia.php',
    'spa' => 'pages/spa.php',
    'giaiTri' => 'pages/giaiTri.php',
    'nhaHang' => 'pages/nhaHang.php',
    'suKien' => 'pages/suKien.php',
    'forgotPass' => 'pages/forgotPass.php',
    'signUp' => 'pages/signUp.php',
    'about' => 'pages/about.php',
    'places' => 'pages/places.php',
    'gallery' => 'pages/gallery.php',
    'room-detail' => 'pages/room-detail.php',
];
if (isset($allowed[$page])) {
    include $allowed[$page];
} else {
    include 'pages/404.php';
}  
include 'includes/footer.php';

?>