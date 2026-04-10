<?php
/**
 * Debug script để kiểm tra service
 * Truy cập: /My-Web-Hotel/admin/debug_service.php
 */

require_once 'includes/connect.php';

echo "<h1>Service Debug Tool</h1>";
echo "<h2>Database: hotel_management</h2>";

// Test connection
if ($mysqli->connect_error) {
    die("<p style='color:red'>Connection failed: " . $mysqli->connect_error . "</p>");
}
echo "<p style='color:green'>✓ Database connection successful!</p>";

// Kiểm tra bảng service
echo "<h3>1. Kiểm tra bảng service:</h3>";
$checkService = $mysqli->query("SHOW TABLES LIKE 'service'");
if ($checkService && $checkService->num_rows > 0) {
    echo "<p style='color:green'>✓ Bảng service tồn tại</p>";
    
    // Đếm tổng số records
    $totalResult = $mysqli->query("SELECT COUNT(*) as total FROM service");
    $total = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
    echo "<p>Tổng số records: <strong>$total</strong></p>";
    
    // Đếm records không bị deleted
    $activeResult = $mysqli->query("SELECT COUNT(*) as total FROM service WHERE deleted IS NULL");
    $active = $activeResult ? $activeResult->fetch_assoc()['total'] : 0;
    echo "<p>Records không bị deleted: <strong>$active</strong></p>";
    
    // Hiển thị cấu trúc bảng
    echo "<h4>Cấu trúc bảng service:</h4>";
    $structure = $mysqli->query("DESCRIBE service");
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
    echo "<h4>Sample data từ bảng service (5 records đầu tiên):</h4>";
    $result = $mysqli->query("SELECT * FROM service LIMIT 5");
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
        echo "<p style='color:orange'>⚠ Không có dữ liệu trong bảng service</p>";
    }
} else {
    echo "<p style='color:red'>✗ Bảng service không tồn tại</p>";
}

// Test query giống như trong services-manager.php
echo "<h3>2. Test query giống services-manager.php (không filter):</h3>";
$where = "WHERE s.deleted IS NULL";
$query = "SELECT * FROM service s $where ORDER BY s.service_name ASC LIMIT 10 OFFSET 0";

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
echo "<h3>3. Test query với prepared statement (giống code thực tế):</h3>";
try {
    $where = "WHERE s.deleted IS NULL";
    $dataParams = [];
    $dataTypes = '';
    
    $query = "SELECT * FROM service s $where ORDER BY s.service_name ASC LIMIT ? OFFSET ?";
    
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
    $services = $result->fetch_all(MYSQLI_ASSOC);
    
    echo "<p style='color:green'>✓ Prepared statement thành công, số kết quả: <strong>" . count($services) . "</strong></p>";
    if (count($services) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr>";
        foreach ($services[0] as $key => $value) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        foreach ($services as $service) {
            echo "<tr>";
            foreach ($service as $key => $value) {
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

// Test count query
echo "<h3>4. Test count query:</h3>";
try {
    $where = "WHERE s.deleted IS NULL";
    $countQuery = "SELECT COUNT(*) as total FROM service s $where";
    
    $countStmt = $mysqli->prepare($countQuery);
    if (!$countStmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $total = $totalRow ? $totalRow['total'] : 0;
    
    echo "<p style='color:green'>✓ Count query thành công, tổng số: <strong>$total</strong></p>";
    
    $countStmt->close();
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test service types query
echo "<h3>5. Test service types query:</h3>";
try {
    $typesResult = $mysqli->query("SELECT DISTINCT service_type FROM service WHERE deleted IS NULL ORDER BY service_type");
    if ($typesResult) {
        $serviceTypes = $typesResult->fetch_all(MYSQLI_ASSOC);
        echo "<p style='color:green'>✓ Service types query thành công, số loại: <strong>" . count($serviceTypes) . "</strong></p>";
        if (count($serviceTypes) > 0) {
            echo "<ul>";
            foreach ($serviceTypes as $st) {
                echo "<li>" . htmlspecialchars($st['service_type']) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color:red'>✗ Query failed: " . $mysqli->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
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
ul {
    list-style-type: disc;
    margin-left: 20px;
}
</style>








