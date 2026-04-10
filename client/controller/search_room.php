<?php
require_once '../includes/connect.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$roomTypes = isset($_GET['room_types']) ? $_GET['room_types'] : [];

// Câu SQL cơ bản
$sql = "SELECT r.room_id, r.status, r.room_number,
               rt.room_type_name AS room_type, 
               rt.base_price AS room_price, 
               rt.description AS `desc`, 
               rt.capacity,
               (SELECT image_url 
             FROM roomtype_images 
             WHERE roomtype_images.room_type_id = rt.room_type_id 
             ORDER BY RAND()
             LIMIT 1) AS room_image 
        FROM room r
        JOIN room_type rt ON rt.room_type_id = r.room_type_id
        WHERE r.status = 'available' and r.deleted IS NULL";

$params = [];
$types = "";

// Nếu có từ khóa => tìm theo LoaiPhong hoặc MaPhong
if ($keyword !== '') {
    $sql .= " AND (rt.room_type_name LIKE ? OR r.room_number LIKE ?)";
    $like = "%$keyword%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

// Lọc theo loại phòng được chọn
if (!empty($roomTypes) && is_array($roomTypes)) {
    $placeholders = implode(',', array_fill(0, count($roomTypes), '?'));
    $sql .= " AND rt.room_type_name IN ($placeholders)";
    
    foreach ($roomTypes as $type) {
        $params[] = $type;
        $types .= "s";
    }
}

// Sắp xếp
switch ($sort) {
    case 'price-low':
        $sql .= " ORDER BY rt.base_price ASC";
        break;
    case 'price-high':
        $sql .= " ORDER BY rt.base_price DESC";
        break;
    case 'popular':
    default:
        $sql .= " ORDER BY r.room_id ASC";
        break;
}

// Chuẩn bị câu SQL và kiểm tra lỗi
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Lỗi SQL: " . $mysqli->error);
}

// Gắn giá trị nếu có
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<div class="card" style="max-width: 90%">';
        echo '  <div class="row g-0">';
        echo '    <div class="picture col-md-4">';
        echo '      <img src="' . htmlspecialchars($row["room_image"]) . '" class="img-fluid rounded-start" alt="' . htmlspecialchars($row["room_type"]) . '" />';
        echo '    </div>';
        echo '    <div class="information col-md-5">';
        echo '      <div class="card-body">';
        echo '        <h3 class="card-title"> Phòng ' . htmlspecialchars($row["room_number"]) . ' </h3>';
        echo '        <p class="card-text"><span><strong>Loại</strong>: ' . htmlspecialchars($row["room_type"]) . ' </span> </p>';
        echo '        <p class="card-text"><span><strong>Diện tích</strong>: 38m²</span> </p>';
        echo '        <p class="card-text"> <span><strong>Tối đa</strong>: '.  htmlspecialchars($row["capacity"]) .' khách</span> </p>';
        echo '      </div>';
        echo '    </div>';
        echo '    <div class="money col-md-3">';
        echo '      <h2>' . number_format($row["room_price"]) . 'đ</h2>';
        echo '      <p>mỗi đêm</p>';
        echo "      <a href='/My-Web-Hotel/client/index.php?page=room-detail&id=" . htmlspecialchars($row['room_id']) . "' class='view-all' >Chi tiết</a>";
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
} else {
    echo '<p style="text-align: center; padding: 20px; color: #666;">Không có phòng nào phù hợp với tìm kiếm.</p>';
}

$stmt->close();
?>