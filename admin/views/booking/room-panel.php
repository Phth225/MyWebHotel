<?php
// View for Room Booking Panel
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message'], $_SESSION['messageType']);
?>

<div class="main-content">
    <div class="container-fluid">
        <h1 class="mb-4">Quản Lý Booking Phòng</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh Sách Booking Phòng</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookingModal">
                    <i class="bi bi-plus-circle"></i> Thêm Booking
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Khách Hàng</th>
                                <th>Phòng</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Trạng Thái</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Không có dữ liệu</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
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
                                        <td>
                                            <a href="index.php?page=booking-manager&panel=roomBooking-panel&action=edit&id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i> Sửa
                                            </a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <button type="submit" name="delete_booking_room" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i> Xóa
                                                </button>
                                            </form>
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

<!-- Add/Edit Booking Modal -->
<div class="modal fade" id="addBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $editBooking ? 'Sửa Booking' : 'Thêm Booking Phòng'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if ($editBooking): ?>
                        <input type="hidden" name="booking_id" value="<?php echo $editBooking['booking_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Khách Hàng *</label>
                        <select name="customer_id" class="form-select" id="customerSelect" required>
                            <option value="">Chọn khách hàng...</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['customer_id']; ?>" 
                                    <?php echo ($editBooking['customer_id'] ?? '') == $customer['customer_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['full_name'] . ' - ' . $customer['phone']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phòng *</label>
                        <?php if ($editBooking): ?>
                            <select name="room_id" class="form-select" disabled>
                                <?php foreach ($availableRooms as $room): ?>
                                    <option value="<?php echo $room['room_id']; ?>" 
                                        <?php echo ($editBooking['room_id'] ?? '') == $room['room_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['room_type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="room_id" value="<?php echo $editBooking['room_id']; ?>">
                        <?php else: ?>
                            <select name="room_id[]" class="form-select" multiple size="5" required>
                                <?php foreach ($availableRooms as $room): ?>
                                    <option value="<?php echo $room['room_id']; ?>">
                                        <?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['room_type_name'] . ' (' . formatCurrency($room['base_price']) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Giữ Ctrl/Cmd để chọn nhiều phòng</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Check-in *</label>
                                <input type="date" name="check_in_date" class="form-control" required value="<?php echo $editBooking['check_in_date'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Check-out *</label>
                                <input type="date" name="check_out_date" class="form-control" required value="<?php echo $editBooking['check_out_date'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Số Lượng</label>
                        <input type="number" name="quantity" class="form-control" value="<?php echo $editBooking['quantity'] ?? 1; ?>" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phương Thức Booking</label>
                        <select name="booking_method" class="form-select">
                            <option value="Website" <?php echo ($editBooking['booking_method'] ?? 'Website') == 'Website' ? 'selected' : ''; ?>>Website</option>
                            <option value="Phone" <?php echo ($editBooking['booking_method'] ?? '') == 'Phone' ? 'selected' : ''; ?>>Điện Thoại</option>
                            <option value="Walk-in" <?php echo ($editBooking['booking_method'] ?? '') == 'Walk-in' ? 'selected' : ''; ?>>Trực Tiếp</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tiền Cọc</label>
                        <input type="number" name="deposit" class="form-control" step="0.01" value="<?php echo $editBooking['deposit'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Trạng Thái</label>
                        <select name="status" class="form-select">
                            <option value="Pending" <?php echo ($editBooking['status'] ?? 'Pending') == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Confirmed" <?php echo ($editBooking['status'] ?? '') == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Cancelled" <?php echo ($editBooking['status'] ?? '') == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Yêu Cầu Đặc Biệt</label>
                        <textarea name="special_request" class="form-control" rows="3"><?php echo htmlspecialchars($editBooking['special_request'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="<?php echo $editBooking ? 'update_room_booking' : 'add_booking_room'; ?>" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editBooking): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('addBookingModal'));
            modal.show();
        });
    </script>
<?php endif; ?>

<script>
// Initialize Select2 for customer dropdown
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery('#customerSelect').select2({
            dropdownParent: jQuery('#addBookingModal'),
            width: '100%',
            minimumInputLength: 0
        });
    }
});
</script>









