<?php
session_start();
require_once '../includes/connect.php';
require_once __DIR__ . '/../../admin/includes/cloudinary_helper.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/danhGia.php?error=not_logged_in");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    // Validate
    if (empty($rating) || empty($comment) || $rating < 1 || $rating > 5) {
        header("Location: ../pages/danhGia.php?error=invalid_data");
        exit();
    }

    // Insert review vào database trước
    $stmt = $mysqli->prepare("INSERT INTO review (customer_id, rating, comment, status, created_at) 
                              VALUES (?, ?, ?, 'Approved', NOW())");
    $stmt->bind_param("iis", $user_id, $rating, $comment);

    if (!$stmt->execute()) {
        header("Location: ../pages/danhGia.php?error=submit_failed");
        exit();
    }

    $review_id = $mysqli->insert_id;
    $stmt->close();

    // Xử lý upload ảnh lên Cloudinary và lưu vào review_images
    if (isset($_FILES['imageInput']) && is_array($_FILES['imageInput']['name']) && !empty($_FILES['imageInput']['name'][0])) {
        $total_files = count($_FILES['imageInput']['name']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $uploaded_count = 0;

        for ($i = 0; $i < $total_files; $i++) {
            // Kiểm tra xem file có được upload không
            if (!isset($_FILES['imageInput']['error'][$i]) || $_FILES['imageInput']['error'][$i] !== UPLOAD_ERR_OK) {
                error_log("File upload error for index $i: " . ($_FILES['imageInput']['error'][$i] ?? 'NOT_SET'));
                continue;
            }

            $file_type = $_FILES['imageInput']['type'][$i] ?? '';
            $file_size = $_FILES['imageInput']['size'][$i] ?? 0;
            $file_tmp = $_FILES['imageInput']['tmp_name'][$i] ?? '';

            // Validate file type
            if (empty($file_type) || !in_array($file_type, $allowed_types)) {
                error_log("Invalid file type for index $i: $file_type");
                continue; // Bỏ qua file không hợp lệ
            }

            // Validate file size
            if ($file_size <= 0 || $file_size > $max_size) {
                error_log("Invalid file size for index $i: $file_size");
                continue; // Bỏ qua file quá lớn hoặc rỗng
            }

            // Validate tmp file exists
            if (empty($file_tmp) || !file_exists($file_tmp)) {
                error_log("Tmp file not found for index $i: $file_tmp");
                continue;
            }

            // Upload lên Cloudinary
            $cloudinaryUrl = CloudinaryHelper::upload($file_tmp, 'review');

            if ($cloudinaryUrl !== false && !empty($cloudinaryUrl)) {
                // Lưu vào bảng review_images
                $imageStmt = $mysqli->prepare("INSERT INTO review_images (review_id, image_url, display_order, created_at) 
                                               VALUES (?, ?, ?, NOW())");
                if ($imageStmt) {
                    $imageStmt->bind_param("isi", $review_id, $cloudinaryUrl, $uploaded_count);
                    if ($imageStmt->execute()) {
                        $uploaded_count++;
                    } else {
                        error_log("Error inserting image to database: " . $imageStmt->error);
                    }
                    $imageStmt->close();
                } else {
                    error_log("Error preparing image insert statement: " . $mysqli->error);
                }
            } else {
                error_log("Cloudinary upload failed for index $i");
            }
        }
        
        // Log số lượng ảnh đã upload thành công
        if ($uploaded_count > 0) {
            error_log("Successfully uploaded $uploaded_count images for review_id: $review_id");
        }
    } else {
        // Log để debug
        if (!isset($_FILES['imageInput'])) {
            error_log("No imageInput in FILES array");
        } elseif (!is_array($_FILES['imageInput']['name'])) {
            error_log("imageInput is not an array. Type: " . gettype($_FILES['imageInput']['name']));
        } elseif (empty($_FILES['imageInput']['name'][0])) {
            error_log("imageInput name[0] is empty");
        }
    }

    header("Location: ../index.php?page=danhGia");
    exit();
}
