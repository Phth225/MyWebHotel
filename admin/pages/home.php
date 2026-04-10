<?php
// Phân biệt nhân viên và quản lý
// Nếu là nhân viên (không phải quản lý), hiển thị trang nhiệm vụ
$chuc_vu = $_SESSION['chuc_vu'] ?? '';
$id_nhan_vien = $_SESSION['id_nhan_vien'] ?? null;

// Kiểm tra xem có phải quản lý không
$isManager = false;
if (!empty($chuc_vu)) {
    $chuc_vu_lower = mb_strtolower($chuc_vu, 'UTF-8');
    $isManager = (
        stripos($chuc_vu, 'Quản lý') !== false ||
        stripos($chuc_vu, 'Manager') !== false ||
        stripos($chuc_vu, 'Admin') !== false ||
        stripos($chuc_vu, 'Giám đốc') !== false ||
        stripos($chuc_vu, 'Director') !== false
    );
}

// Nếu không phải quản lý và có id_nhan_vien, hiển thị trang nhiệm vụ
if (!$isManager && $id_nhan_vien) {
    // Nhân viên: hiển thị trang nhiệm vụ
    include 'my-tasks.php';
    return;
}

// Quản lý: hiển thị dashboard
// Thống kê tổng quan
$stats = [];

// Tổng số phòng
$result = $mysqli->query("SELECT COUNT(*) as total FROM room WHERE deleted IS NULL");
$stats['total_rooms'] = $result->fetch_assoc()['total'];

// Phòng đã thuê (Occupied hoặc Booked)
$result = $mysqli->query("SELECT COUNT(*) as total FROM room WHERE status IN ('Occupied', 'Booked') AND deleted IS NULL");
$stats['occupied_rooms'] = $result->fetch_assoc()['total'];

// Phòng trống
$result = $mysqli->query("SELECT COUNT(*) as total FROM room WHERE status = 'Available' AND deleted IS NULL");
$stats['available_rooms'] = $result->fetch_assoc()['total'];

// Doanh thu tháng hiện tại
$result = $mysqli->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoice 
    WHERE status = 'Paid' 
    AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
    AND deleted IS NULL");
$stats['monthly_revenue'] = $result->fetch_assoc()['total'];

// Doanh thu tháng trước để so sánh
$result = $mysqli->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoice 
    WHERE status = 'Paid' 
    AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
    AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
    AND deleted IS NULL");
$prev_month_revenue = $result->fetch_assoc()['total'];
$revenue_growth = $prev_month_revenue > 0 ?
    (($stats['monthly_revenue'] - $prev_month_revenue) / $prev_month_revenue) * 100 : 0;

// Tỷ lệ phòng
$roomStats = [];
$result = $mysqli->query("SELECT status, COUNT(*) as count FROM room WHERE deleted IS NULL GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $roomStats[$row['status']] = $row['count'];
}

// Doanh thu 7 ngày qua
$revenueData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $mysqli->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoice 
        WHERE status = 'Paid' 
        AND DATE(created_at) = ?
        AND deleted IS NULL");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $revenueData[] = [
        'date' => date('d/m', strtotime("-$i days")),
        'revenue' => floatval($row['total'] ?? 0)
    ];
    $stmt->close();
}

// Thống kê booking: khách vãng lai vs khách có tài khoản
$result = $mysqli->query("SELECT COUNT(*) as total FROM booking 
    WHERE walk_in_guest_id IS NOT NULL AND deleted IS NULL");
$stats['walk_in_bookings'] = $result->fetch_assoc()['total'];

$result = $mysqli->query("SELECT COUNT(*) as total FROM booking 
    WHERE walk_in_guest_id IS NULL AND customer_id > 0 AND deleted IS NULL");
$stats['registered_bookings'] = $result->fetch_assoc()['total'];

// Booking gần đây (bao gồm cả khách vãng lai)
$recentBookings = [];
$result = $mysqli->query("SELECT b.booking_id, b.check_in_date, b.check_out_date, 
    COALESCE(c.full_name, w.full_name) as full_name,
    r.room_number, i.total_amount, i.status,
    CASE WHEN b.walk_in_guest_id IS NOT NULL THEN 'Khách vãng lai' ELSE 'Khách có tài khoản' END as guest_type
    FROM booking b
    LEFT JOIN customer c ON b.customer_id = c.customer_id AND b.walk_in_guest_id IS NULL
    LEFT JOIN walk_in_guest w ON b.walk_in_guest_id = w.id
    INNER JOIN room r ON b.room_id = r.room_id
    LEFT JOIN invoice i ON b.booking_id = i.booking_id
    WHERE b.deleted IS NULL
    ORDER BY b.created_at DESC
    LIMIT 5");
$recentBookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_rooms']; ?></h3>
                    <p>Tổng Phòng</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['occupied_rooms']; ?></h3>
                    <p>Phòng Đã Thuê</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['available_rooms']; ?></h3>
                    <p>Phòng Trống</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['monthly_revenue'] / 1000000, 1); ?>M</h3>
                    <p>Doanh Thu Tháng</p>
                    <?php if ($revenue_growth != 0): ?>
                        <small class="<?php echo $revenue_growth > 0 ? 'text-success' : 'text-danger'; ?>">
                            <i class="fas fa-arrow-<?php echo $revenue_growth > 0 ? 'up' : 'down'; ?>"></i>
                            <?php echo number_format(abs($revenue_growth), 1); ?>% so với tháng trước
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê Booking theo loại khách -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['registered_bookings']; ?></h3>
                    <p>Booking Khách Có Tài Khoản</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['walk_in_bookings']; ?></h3>
                    <p>Booking Khách Vãng Lai</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="card-header">
                    <h5>Tỷ Lệ Phòng</h5>
                </div>
                <div style="max-width: 400px; margin: 0 auto">
                    <canvas id="roomChart" style="height: 300px"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="card-header">
                    <h5>Doanh Thu 7 Ngày Qua</h5>
                </div>
                <div style="position: relative; height: 300px; padding: 20px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="chart-card">
                <div class="card-header">
                    <h5>Phòng đặt gần đây</h5>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã Đặt</th>
                                <th>Khách hàng</th>
                                <th>Loại</th>
                                <th>Phòng</th>
                                <th>Ngày</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Không có dữ liệu</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo $booking['booking_id']; ?></td>
                                        <td><?php echo h($booking['full_name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $booking['guest_type'] == 'Khách vãng lai' ? 'bg-warning' : 'bg-info'; ?>">
                                                <?php echo h($booking['guest_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo h($booking['room_number']); ?></td>
                                        <td>
                                            <?php echo formatDate($booking['check_in_date']); ?><br>
                                            <small><?php echo formatDate($booking['check_out_date']); ?></small>
                                        </td>
                                        <td><?php echo formatCurrency($booking['total_amount'] ?? 0); ?></td>
                                        <td>
                                            <span class="badge <?php
                                                                echo $booking['status'] == 'Paid' ? 'bg-success' : ($booking['status'] == 'Unpaid' ? 'bg-warning' : 'bg-secondary');
                                                                ?>">
                                                <?php echo h($booking['status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="quick-actions-card">
                <div class="card-header">
                    <h5>Thao Tác Nhanh</h5>
                </div>
                <div class="quick-actions">
                    <a href="index.php?page=room-manager" class="quick-action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Thêm Phòng</span>
                    </a>
                    <a href="index.php?page=services-manager" class="quick-action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Thêm Dịch Vụ</span>
                    </a>
                    <a href="index.php?page=customers-manager" class="quick-action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Thêm Khách Hàng</span>
                    </a>
                    <a href="index.php?page=reports-manager" class="quick-action-btn">
                        <i class="fas fa-chart-bar"></i>
                        <span>Xem Báo Cáo</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Chuẩn bị dữ liệu cho Chart.js
$chartLabels = array_map(function ($d) {
    return "'" . htmlspecialchars($d['date'], ENT_QUOTES) . "'";
}, $revenueData);
$chartData = array_map(function ($d) {
    return $d['revenue'];
}, $revenueData);
?>

<!-- Script tạo biểu đồ - ĐẶT Ở CUỐI FILE -->
<script>
    // Lưu dữ liệu vào biến global để dùng sau
    window.dashboardData = {
        roomStats: {
            available: <?php echo $roomStats['Available'] ?? 0; ?>,
            booked: <?php echo $roomStats['Booked'] ?? 0; ?>,
            occupied: <?php echo $roomStats['Occupied'] ?? 0; ?>,
            maintenance: <?php echo $roomStats['Maintenance'] ?? 0; ?>,
            cleaning: <?php echo $roomStats['Cleaning'] ?? 0; ?>
        },
        revenueLabels: [<?php echo implode(',', $chartLabels); ?>],
        revenueData: [<?php echo implode(',', $chartData); ?>]
    };

    // Hàm khởi tạo biểu đồ
    function initDashboardCharts() {

        // Lưu instance của các chart
        if (window.roomChartInstance) {
            window.roomChartInstance.destroy();
        }
        if (window.revenueChartInstance) {
            window.revenueChartInstance.destroy();
        }

        // Room Chart
        const roomCtxEl = document.getElementById('roomChart');
        if (roomCtxEl) {
            const roomCtx = roomCtxEl.getContext('2d');
            window.roomChartInstance = new Chart(roomCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Có sẵn', 'Đã đặt', 'Đang thuê', 'Bảo trì', 'Đang dọn'],
                    datasets: [{
                        data: [
                            window.dashboardData.roomStats.available,
                            window.dashboardData.roomStats.booked,
                            window.dashboardData.roomStats.occupied,
                            window.dashboardData.roomStats.maintenance,
                            window.dashboardData.roomStats.cleaning
                        ],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d', '#17a2b8']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Revenue Chart
        const revenueCtxEl = document.getElementById('revenueChart');
        if (revenueCtxEl) {
            const revenueCtx = revenueCtxEl.getContext('2d');
            window.revenueChartInstance = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: window.dashboardData.revenueLabels,
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: window.dashboardData.revenueData,
                        borderColor: '#deb666',
                        backgroundColor: 'rgba(222, 182, 102, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return (value / 1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return (value / 1000).toFixed(0) + 'K';
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Kiểm tra Chart.js đã load chưa
    if (typeof Chart !== 'undefined') {
        // Chart.js đã có, khởi tạo ngay
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardCharts);
        } else {
            initDashboardCharts();
        }
    } else {
        // Chart.js chưa có, đợi window load
        window.addEventListener('load', function() {
            // Đợi thêm 100ms để chắc chắn Chart.js đã load
            setTimeout(initDashboardCharts, 100);
        });
    }
</script>