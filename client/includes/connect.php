<?php
//Cấu hình database
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'hotel_management';

$error = '';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) {
    error_log('DB connect error: ' . $mysqli->connect_error);
    $error = 'Có lỗi máy chủ. Vui lòng thử lại sau.';
}
// Thiết lập charset
$mysqli->set_charset('utf8mb4');
?>