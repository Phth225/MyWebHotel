<?php
// View for Home/Dashboard
?>

<div class="main-content">
    <div class="container-fluid">
        <h1 class="mb-4">Tổng Quan</h1>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Tổng Booking</h5>
                        <h2><?php echo number_format($totalBookings ?? 0); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Hóa Đơn Đã Thanh Toán</h5>
                        <h2><?php echo number_format($totalInvoices ?? 0); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Phòng Trống</h5>
                        <h2>-</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Doanh Thu</h5>
                        <h2>-</h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Booking Gần Đây</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Khách Hàng</th>
                                <th>Phòng</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Không có dữ liệu</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo $booking['booking_id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['full_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($booking['room_number'] ?? '-'); ?></td>
                                        <td><?php echo $booking['check_in_date'] ? formatDate($booking['check_in_date']) : '-'; ?></td>
                                        <td><?php echo $booking['check_out_date'] ? formatDate($booking['check_out_date']) : '-'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] == 'Confirmed' ? 'success' : 
                                                    ($booking['status'] == 'Pending' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo htmlspecialchars($booking['status']); ?>
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
    </div>
</div>









