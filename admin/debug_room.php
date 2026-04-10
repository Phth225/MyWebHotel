<?php
/**
 * Debug script để kiểm tra room và room_type
 * Truy cập: /My-Web-Hotel/admin/debug_room.php
 */

require_once 'includes/connect.php';

echo "<h1>Room Debug Tool</h1>";
echo "<h2>Database: hotel_management</h2>";

// Test connection
if ($mysqli->connect_error) {
    die("<p style='color:red'>Connection failed: " . $mysqli->connect_error . "</p>");
}
echo "<p style='color:green'>✓ Database connection successful!</p>";

// Kiểm tra bảng room
echo "<h3>1. Kiểm tra bảng room:</h3>";
$checkRoom = $mysqli->query("SHOW TABLES LIKE 'room'");
if ($checkRoom && $checkRoom->num_rows > 0) {
    echo "<p style='color:green'>✓ Bảng room tồn tại</p>";
    
    // Đếm tổng số records
    $totalResult = $mysqli->query("SELECT COUNT(*) as total FROM room");
    $total = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
    echo "<p>Tổng số records: <strong>$total</strong></p>";
    
    // Đếm records không bị deleted
    $activeResult = $mysqli->query("SELECT COUNT(*) as total FROM room WHERE deleted IS NULL");
    $active = $activeResult ? $activeResult->fetch_assoc()['total'] : 0;
    echo "<p>Records không bị deleted: <strong>$active</strong></p>";
    
    // Hiển thị cấu trúc bảng
    echo "<h4>Cấu trúc bảng room:</h4>";
    $structure = $mysqli->query("DESCRIBE room");
    if ($structure) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Hiển thị sample data
    echo "<h4>Sample data từ bảng room (5 records đầu tiên):</h4>";
    $result = $mysqli->query("SELECT * FROM room LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr>";
        while ($row = $result->fetch_assoc()) {
            foreach ($row as $key => $value) {
                echo "<th>$key</th>";
            }
            break;
        }
        echo "</tr>";
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠ Không có dữ liệu trong bảng room</p>";
    }
} else {
    echo "<p style='color:red'>✗ Bảng room không tồn tại</p>";
}

// Kiểm tra bảng room_type
echo "<h3>2. Kiểm tra bảng room_type:</h3>";
$checkRoomType = $mysqli->query("SHOW TABLES LIKE 'room_type'");
if ($checkRoomType && $checkRoomType->num_rows > 0) {
    echo "<p style='color:green'>✓ Bảng room_type tồn tại</p>";
    
    // Đếm tổng số records
    $totalResult = $mysqli->query("SELECT COUNT(*) as total FROM room_type");
    $total = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
    echo "<p>Tổng số records: <strong>$total</strong></p>";
    
    // Đếm records không bị deleted
    $activeResult = $mysqli->query("SELECT COUNT(*) as total FROM room_type WHERE deleted IS NULL");
    $active = $activeResult ? $activeResult->fetch_assoc()['total'] : 0;
    echo "<p>Records không bị deleted: <strong>$active</strong></p>";
    
    // Hiển thị sample data
    echo "<h4>Sample data từ bảng room_type:</h4>";
    $result = $mysqli->query("SELECT * FROM room_type LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr>";
        while ($row = $result->fetch_assoc()) {
            foreach ($row as $key => $value) {
                echo "<th>$key</th>";
            }
            break;
        }
        echo "</tr>";
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠ Không có dữ liệu trong bảng room_type</p>";
    }
} else {
    echo "<p style='color:red'>✗ Bảng room_type không tồn tại</p>";
}

// Test query giống như trong room-manager.php
echo "<h3>3. Test query giống room-manager.php:</h3>";
$where = "WHERE r.deleted IS NULL";
$query = "SELECT r.*, rt.room_type_name, rt.base_price, rt.capacity, rt.area, rt.amenities 
    FROM room r 
    LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id 
    $where 
    ORDER BY r.room_number ASC 
    LIMIT 10 OFFSET 0";

echo "<p><strong>Query:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($query) . "</pre>";

$testResult = $mysqli->query($query);
if ($testResult) {
    echo "<p style='color:green'>✓ Query thành công, số kết quả: <strong>" . $testResult->num_rows . "</strong></p>";
    if ($testResult->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr>";
        while ($row = $testResult->fetch_assoc()) {
            foreach ($row as $key => $value) {
                echo "<th>$key</th>";
            }
            break;
        }
        echo "</tr>";
        $testResult->data_seek(0);
        while ($row = $testResult->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠ Query thành công nhưng không có dữ liệu trả về</p>";
    }
} else {
    echo "<p style='color:red'>✗ Query failed: " . $mysqli->error . "</p>";
}

// Test query với prepared statement
echo "<h3>4. Test query với prepared statement (giống code thực tế):</h3>";
try {
    $where = "WHERE r.deleted IS NULL";
    $dataParams = [];
    $dataTypes = '';
    
    $query = "SELECT r.*, rt.room_type_name, rt.base_price, rt.capacity, rt.area, rt.amenities 
        FROM room r 
        LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id 
        $where 
        ORDER BY r.room_number ASC 
        LIMIT ? OFFSET ?";
    
    $perPage = 10;
    $offset = 0;
    
    $dataParams[] = $perPage;
    $dataParams[] = $offset;
    $dataTypes .= 'ii';
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param($dataTypes, ...$dataParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $rooms = $result->fetch_all(MYSQLI_ASSOC);
    
    echo "<p style='color:green'>✓ Prepared statement thành công, số kết quả: <strong>" . count($rooms) . "</strong></p>";
    if (count($rooms) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr>";
        foreach ($rooms[0] as $key => $value) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        foreach ($rooms as $room) {
            echo "<tr>";
            foreach ($room as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠ Prepared statement thành công nhưng không có dữ liệu trả về</p>";
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}

// Kiểm tra foreign key relationship
echo "<h3>5. Kiểm tra relationship giữa room và room_type:</h3>";
$checkRel = $mysqli->query("
    SELECT r.room_id, r.room_number, r.room_type_id, rt.room_type_name 
    FROM room r 
    LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id 
    WHERE r.deleted IS NULL 
    LIMIT 5
");
if ($checkRel && $checkRel->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>room_id</th><th>room_number</th><th>room_type_id</th><th>room_type_name</th></tr>";
    while ($row = $checkRel->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['room_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
        echo "<td>" . $row['room_type_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['room_type_name'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>⚠ Không có dữ liệu để kiểm tra relationship</p>";
}

$mysqli->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
    background: #f9f9f9;
}
h1, h2, h3, h4 {
    color: #333;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: white;
}
th {
    background: #deb666;
    color: #000;
    padding: 10px;
    text-align: left;
}
td {
    padding: 8px;
    border: 1px solid #ddd;
}
pre {
    overflow-x: auto;
}
</style>









