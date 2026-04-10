<?php
session_start();
require_once '../includes/connect.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = intval($_POST['review_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($review_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID bình luận không hợp lệ']);
        exit();
    }

    // Kiểm tra quyền sở hữu bình luận
    $check_stmt = $mysqli->prepare("SELECT customer_id FROM review WHERE review_id = ? AND deleted IS NULL");
    $check_stmt->bind_param("i", $review_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Bình luận không tồn tại']);
        exit();
    }
    
    $review = $result->fetch_assoc();
    $check_stmt->close();

    // Kiểm tra quyền xóa
    if ($review['customer_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa bình luận này']);
        exit();
    }

    // Soft delete bình luận
    $delete_stmt = $mysqli->prepare("UPDATE review SET deleted = NOW() WHERE review_id = ?");
    $delete_stmt->bind_param("i", $review_id);
    
    if ($delete_stmt->execute()) {
        // Soft delete các ảnh liên quan
        $delete_images_stmt = $mysqli->prepare("UPDATE review_images SET deleted = NOW() WHERE review_id = ?");
        $delete_images_stmt->bind_param("i", $review_id);
        $delete_images_stmt->execute();
        $delete_images_stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Đã xóa bình luận thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa bình luận']);
    }
    
    $delete_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
}