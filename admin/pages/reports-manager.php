<?php
$canViewReport = function_exists('checkPermission') ? checkPermission('report.view') : true;
$canExportReport = function_exists('checkPermission') ? checkPermission('report.export') : true;

if (!$canViewReport) {
    http_response_code(403);
    echo '<div class="main-content"><div class="alert alert-danger m-4">Bạn không có quyền xem trang báo cáo.</div></div>';
    return;
}

// Lấy tham số filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$time_range = isset($_GET['range']) ? $_GET['range'] : 'month';

// Tính toán date range dựa trên time_range
$today = date('Y-m-d');
switch ($time_range) {
    case 'today':
        $start_date = $today;
        $end_date = $today;
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
}

// Lấy dữ liệu tổng quan
// Tính cả invoice có booking_id và invoice service only (booking_id NULL)
$summary_query = "
    SELECT 
        COALESCE(SUM(i.total_amount), 0) as total_revenue,
        COALESCE(SUM(i.room_charge), 0) as room_revenue,
        COALESCE(SUM(i.service_charge), 0) as service_revenue,
        COUNT(DISTINCT i.invoice_id) as total_invoices,
        COUNT(DISTINCT CASE WHEN i.booking_id IS NOT NULL THEN i.booking_id END) as total_room_bookings,
        COUNT(DISTINCT CASE WHEN i.booking_id IS NULL THEN i.invoice_id END) as total_service_only_invoices,
        COUNT(DISTINCT isv.booking_service_id) as total_service_bookings
    FROM invoice i
    LEFT JOIN booking b ON i.booking_id = b.booking_id
    LEFT JOIN invoice_service isv ON i.invoice_id = isv.invoice_id
    WHERE i.deleted IS NULL 
    AND DATE(i.created_at) BETWEEN '$start_date' AND '$end_date'
    AND i.status IN ('Paid', 'Unpaid', 'Refunded')
";
$summary_result = $mysqli->query($summary_query);
$summary = $summary_result ? $summary_result->fetch_assoc() : [];
$summary['total_bookings'] = ($summary['total_room_bookings'] ?? 0) + ($summary['total_service_bookings'] ?? 0);

// Tính tỷ lệ lấp đầy
$occupancy_query = "
    SELECT 
        SUM(DATEDIFF(
            LEAST(b.check_out_date, '$end_date'),
            GREATEST(b.check_in_date, '$start_date')
        ) + 1) as total_nights_booked
    FROM booking b
    WHERE b.deleted IS NULL 
    AND b.status IN ('Confirmed', 'Completed')
    AND b.check_in_date <= '$end_date' 
    AND b.check_out_date >= '$start_date'
";
$occupancy_result = $mysqli->query($occupancy_query);
$nights = $occupancy_result ? $occupancy_result->fetch_assoc() : ['total_nights_booked' => 0];

$total_rooms = $mysqli->query("SELECT COUNT(*) as total FROM room WHERE deleted IS NULL")->fetch_assoc()['total'] ?? 0;
$days_in_period = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;
$total_available_nights = $total_rooms * $days_in_period;
$occupancy_rate = $total_available_nights > 0 ? ($nights['total_nights_booked'] / $total_available_nights) * 100 : 0;

// Đánh giá trung bình
$rating_query = "
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
    FROM review 
    WHERE deleted IS NULL 
    AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'
    AND status = 'Approved'
";
$rating_result = $mysqli->query($rating_query);
$rating = $rating_result ? $rating_result->fetch_assoc() : ['avg_rating' => 0, 'total_reviews' => 0];

// Top 5 phòng (chỉ tính booking phòng, không tính service only)
$top_rooms_query = "
    SELECT 
        r.room_number,
        rt.room_type_name,
        COUNT(DISTINCT b.booking_id) as booking_count,
        COALESCE(SUM(i.room_charge), 0) as revenue
    FROM room r
    INNER JOIN room_type rt ON r.room_type_id = rt.room_type_id
    LEFT JOIN booking b ON r.room_id = b.room_id AND b.deleted IS NULL
    LEFT JOIN invoice i ON b.booking_id = i.booking_id AND i.deleted IS NULL
    WHERE r.deleted IS NULL
    AND (i.created_at IS NULL OR DATE(i.created_at) BETWEEN '$start_date' AND '$end_date')
    AND (i.status IS NULL OR i.status IN ('Paid', 'Unpaid'))
    GROUP BY r.room_id, r.room_number, rt.room_type_name
    HAVING revenue > 0
    ORDER BY revenue DESC
    LIMIT 5
";
$top_rooms_result = $mysqli->query($top_rooms_query);
$top_rooms = $top_rooms_result ? $top_rooms_result->fetch_all(MYSQLI_ASSOC) : [];

// Top 5 khách hàng (tính cả invoice service only)
$top_customers_query = "
    SELECT 
        c.full_name,
        c.customer_type,
        COUNT(DISTINCT CASE WHEN i.booking_id IS NOT NULL THEN b.booking_id END) as room_booking_count,
        COUNT(DISTINCT isv.booking_service_id) as service_booking_count,
        COALESCE(SUM(i.total_amount), 0) as total_spent
    FROM customer c
    LEFT JOIN invoice i ON c.customer_id = i.customer_id AND i.deleted IS NULL
    LEFT JOIN booking b ON i.booking_id = b.booking_id AND b.deleted IS NULL
    LEFT JOIN invoice_service isv ON i.invoice_id = isv.invoice_id
    WHERE c.deleted IS NULL
    AND (i.created_at IS NULL OR DATE(i.created_at) BETWEEN '$start_date' AND '$end_date')
    AND (i.status IS NULL OR i.status IN ('Paid', 'Unpaid'))
    GROUP BY c.customer_id, c.full_name, c.customer_type
    HAVING total_spent > 0
    ORDER BY total_spent DESC
    LIMIT 5
";
$top_customers_result = $mysqli->query($top_customers_query);
$top_customers = $top_customers_result ? $top_customers_result->fetch_all(MYSQLI_ASSOC) : [];

// Tính tổng booking count cho mỗi khách hàng
foreach ($top_customers as &$customer) {
    $customer['booking_count'] = intval($customer['room_booking_count'] ?? 0) + intval($customer['service_booking_count'] ?? 0);
}
unset($customer);

// Kiểm tra xem các hàm helper đã được định nghĩa chưa
if (!function_exists('formatMoney')) {
    function formatMoney($amount) {
        return number_format($amount, 0, ',', '.') . ' VNĐ';
    }
}

if (!function_exists('getTypeBadge')) {
    function getTypeBadge($type) {
        $badges = [
            'VIP' => 'bg-warning',
            'Corporate' => 'bg-info',
            'Regular' => 'bg-secondary',
            'Suite Room' => 'bg-info',
            'Deluxe Room' => 'bg-success',
            'Standard Room' => 'bg-secondary',
            'Family Room' => 'bg-primary',
            'Budget Room' => 'bg-secondary'
        ];
        return $badges[$type] ?? 'bg-secondary';
    }
}
?>
  <div class="main-content">
      <!-- Page Header -->
      <div class="filter-group">
          <div class="row g-3">
              <div class="col-md-5 ">
                  <button class="filter-btn <?php echo $time_range === 'today' ? 'active' : ''; ?>" onclick="setTimeRange('today', this)">
                      Hôm nay
                  </button>
                  <button class="filter-btn <?php echo $time_range === 'week' ? 'active' : ''; ?>" onclick="setTimeRange('week', this)">
                      Tuần này
                  </button>
                  <button class="filter-btn <?php echo $time_range === 'month' ? 'active' : ''; ?>" onclick="setTimeRange('month', this)">
                      Tháng này
                  </button>
                  <button class="filter-btn <?php echo $time_range === 'year' ? 'active' : ''; ?>" onclick="setTimeRange('year', this)">
                      Năm nay
                  </button>
              </div>
              <div class="col-md-4">
                  <div class="date-range-picker">
                      <input type="date" id="startDate" value="<?php echo $start_date; ?>" />
                      <span>đến</span>
                      <input type="date" id="endDate" value="<?php echo $end_date; ?>" />
                  </div>
              </div>
              <div class="col-md-2">
                  <?php if ($canExportReport): ?>
                  <button class="btn-export  w-100" onclick="exportReport()">
                      <i class="fas fa-download"></i> Xuất báo cáo
                  </button>
                  <?php else: ?>
                  <button class="btn-export  w-100" disabled title="Bạn không có quyền xuất báo cáo">
                      <i class="fas fa-download"></i> Xuất báo cáo
                  </button>
                  <?php endif; ?>
              </div>
          </div>
      </div>

      <!-- Summary Box -->
      <div class="summary-box">
          <div class="summary-title" id="summaryTitle">Tổng Quan <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></div>
          <div class="summary-stats">
              <div class="summary-item">
                  <div class="summary-item-value" id="summaryRevenue"><?php echo number_format($summary['total_revenue'] / 1000000, 1); ?>M</div>
                  <div class="summary-item-label">Tổng Doanh Thu</div>
              </div>
              <div class="summary-item">
                  <div class="summary-item-value" id="summaryBookings"><?php echo $summary['total_bookings']; ?></div>
                  <div class="summary-item-label">Tổng Đặt Phòng</div>
              </div>
              <div class="summary-item">
                  <div class="summary-item-value" id="summaryOccupancy"><?php echo number_format($occupancy_rate, 1); ?>%</div>
                  <div class="summary-item-label">Tỷ Lệ Lấp Đầy</div>
              </div>
              <div class="summary-item">
                  <div class="summary-item-value" id="summaryRating"><?php echo number_format($rating['avg_rating'], 1); ?></div>
                  <div class="summary-item-label">Đánh Giá TB</div>
              </div>
          </div>
      </div>

      <!-- Charts Row 1 -->
      <div class="row">
          <div class="col-lg-8">
              <div class="chart-card">
                  <div class="chart-header">
                      <h3 class="chart-title">
                          <i class="fas fa-chart-line"></i>
                          Xu Hướng Doanh Thu & Đặt Phòng
                      </h3>
                      <div class="chart-actions">
                          <button class="chart-btn active" onclick="updateRevenueChart('month', this)">
                              Tháng
                          </button>
                          <button class="chart-btn" onclick="updateRevenueChart('quarter', this)">
                              Quý
                          </button>
                          <button class="chart-btn" onclick="updateRevenueChart('year', this)">
                              Năm
                          </button>
                      </div>
                  </div>
                  <div class="chart-container">
                      <canvas id="revenueChart"></canvas>
                  </div>
              </div>
          </div>

          <div class="col-lg-4">
              <div class="chart-card">
                  <div class="chart-header">
                      <h3 class="chart-title">
                          <i class="fas fa-chart-pie"></i>
                          Phân Bổ Doanh Thu
                      </h3>
                  </div>
                  <div class="chart-container small">
                      <canvas id="revenuePieChart"></canvas>
                  </div>
              </div>
          </div>
      </div>

      <!-- Charts Row 2 -->
      <div class="row">
          <div class="col-lg-6">
              <div class="chart-card">
                  <div class="chart-header">
                      <h3 class="chart-title">
                          <i class="fas fa-chart-bar"></i>
                          Doanh thu theo loại dịch vụ
                      </h3>
                  </div>
                  <div class="chart-container small">
                      <canvas id="roomTypeChart"></canvas>
                  </div>
              </div>
          </div>

          <div class="col-lg-6">
              <div class="chart-card">
                  <div class="chart-header">
                      <h3 class="chart-title">
                          <i class="fas fa-percentage"></i>
                          Tỷ Lệ Lấp Đầy Theo Tầng
                      </h3>
                  </div>
                  <div class="chart-container small">
                      <canvas id="occupancyChart"></canvas>
                  </div>
              </div>
          </div>
      </div>

      <!-- Data Tables -->
      <div class="row">
          <div class="col-lg-6">
              <div class="chart-card">
                  <div class="chart-header">
                      <h3 class="chart-title">
                          <i class="fas fa-trophy"></i>
                          Top 5 Phòng Có Doanh Thu Cao Nhất
                      </h3>
                  </div>
                  <div class="data-table">
                      <table>
                          <thead>
                              <tr>
                                  <th>Phòng</th>
                                  <th>Loại</th>
                                  <th>Số Lần Đặt</th>
                                  <th>Doanh Thu</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php if (empty($top_rooms)): ?>
                                  <tr>
                                      <td colspan="4" class="text-center">Không có dữ liệu</td>
                                  </tr>
                              <?php else: ?>
                                  <?php foreach ($top_rooms as $room): ?>
                                      <tr>
                                          <td><strong>Phòng <?php echo h($room['room_number']); ?></strong></td>
                                          <td><span class="badge <?php echo getTypeBadge($room['room_type_name']); ?>"><?php echo h($room['room_type_name']); ?></span></td>
                                          <td><?php echo $room['booking_count']; ?></td>
                                          <td><strong><?php echo formatMoney($room['revenue']); ?></strong></td>
                                      </tr>
                                  <?php endforeach; ?>
                              <?php endif; ?>
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>

          <div class="col-lg-6">
              <div class="chart-card">
                  <div class="chart-header">
                      <h3 class="chart-title">
                          <i class="fas fa-user-star"></i>
                          Top 5 Khách Hàng VIP
                      </h3>
                  </div>
                  <div class="data-table">
                      <table>
                          <thead>
                              <tr>
                                  <th>Khách Hàng</th>
                                  <th>Số Lần Đặt</th>
                                  <th>Tổng Chi Tiêu</th>
                                  <th>Hạng</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php if (empty($top_customers)): ?>
                                  <tr>
                                      <td colspan="4" class="text-center">Không có dữ liệu</td>
                                  </tr>
                              <?php else: ?>
                                  <?php foreach ($top_customers as $customer): ?>
                                      <tr>
                                          <td><strong><?php echo h($customer['full_name']); ?></strong></td>
                                          <td><?php echo $customer['booking_count']; ?></td>
                                          <td><?php echo formatMoney($customer['total_spent']); ?></td>
                                          <td><span class="badge <?php echo getTypeBadge($customer['customer_type']); ?>"><?php echo h($customer['customer_type']); ?></span></td>
                                      </tr>
                                  <?php endforeach; ?>
                              <?php endif; ?>
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
      </div>

<script>
// Truyền dữ liệu từ PHP sang JavaScript
const reportData = {
    startDate: '<?php echo $start_date; ?>',
    endDate: '<?php echo $end_date; ?>',
    summary: <?php echo json_encode($summary); ?>,
    revenueDistribution: {
        room: <?php echo $summary['room_revenue'] ?? 0; ?>,
        service: <?php echo $summary['service_revenue'] ?? 0; ?>
    }
};
</script>
