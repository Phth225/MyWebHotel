<?php
session_start();
require_once '../includes/connect.php';

header('Content-Type: application/json');

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 5;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Lấy reviews với thông tin is_owner
$query = "SELECT r.*, c.full_name, c.avatar 
          FROM review r 
          JOIN customer c ON r.customer_id = c.customer_id 
          WHERE r.status = 'Approved' AND r.deleted IS NULL
          ORDER BY r.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    // Kiểm tra quyền sở hữu
    $is_owner = ($user_id && $row['customer_id'] == $user_id);
    
    // Lấy ảnh của review
    $images_query = "SELECT image_url FROM review_images 
                     WHERE review_id = ? AND deleted IS NULL
                     ORDER BY display_order ASC";
    $images_stmt = $mysqli->prepare($images_query);
    $images_stmt->bind_param("i", $row['review_id']);
    $images_stmt->execute();
    $images_result = $images_stmt->get_result();
    
    $images = [];
    while ($img = $images_result->fetch_assoc()) {
        if (!empty($img['image_url'])) {
            $images[] = $img['image_url'];
        }
    }
    $images_stmt->close();
    
    $reviews[] = [
        'review_id' => $row['review_id'],
        'customer_id' => $row['customer_id'],
        'full_name' => $row['full_name'],
        'avatar' => $row['avatar'],
        'rating' => $row['rating'],
        'comment' => $row['comment'],
        'created_at' => $row['created_at'],
        'images' => $images,
        'is_owner' => $is_owner
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'reviews' => $reviews,
    'count' => count($reviews)
]);