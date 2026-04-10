<?php
// Form chỉnh sửa booking phòng - File riêng để tránh lẫn lộn với add

// Lấy thông tin booking ra để edit
$editBookingRoom = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare(
        "SELECT b.*, c.phone, c.full_name, c.email, 
                r.room_number, rt.room_type_name
         FROM booking b
         LEFT JOIN customer c ON b.customer_id = c.customer_id
         LEFT JOIN room r ON b.room_id = r.room_id
         LEFT JOIN room_type rt ON r.room_type_id = rt.room_type_id
         WHERE b.booking_id=? AND b.deleted IS NULL"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editBookingRoom = $result->fetch_assoc();
    $stmt->close();
}

if (!$editBookingRoom) {
    echo '<div class="alert alert-danger">Không tìm thấy booking để chỉnh sửa</div>';
    return;
}

// LẤY DANH SÁCH CUSTOMER
$customersResult = $mysqli->query(
    "SELECT customer_id, full_name, phone, email 
     FROM customer 
     WHERE deleted IS NULL 
     ORDER BY full_name ASC"
);
$customers = $customersResult->fetch_all(MYSQLI_ASSOC);

// LẤY DANH SÁCH PHÒNG AVAILABLE
$roomsResult = $mysqli->query(
    "SELECT r.room_id, r.room_number, rt.room_type_name, rt.base_price
     FROM room r
     JOIN room_type rt ON r.room_type_id = rt.room_type_id
     WHERE r.deleted IS NULL AND r.status = 'Available'
     ORDER BY r.room_number ASC"
);
$availableRooms = $roomsResult->fetch_all(MYSQLI_ASSOC);
?>

<!-- Modal Sửa Booking -->
<div class="modal fade" id="editRoomBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa Booking Phòng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editBookingForm">
                <input type="hidden" name="booking_id" value="<?php echo $editBookingRoom['booking_id']; ?>">

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tên Khách Hàng *</label>
                            <select class="form-select customer-search" name="customer_id" required
                                id="customerSelectRoomEdit">
                                <option value="">-- Chọn khách hàng --</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['customer_id']; ?>"
                                    <?php echo ($editBookingRoom['customer_id'] == $customer['customer_id']) ? 'selected' : ''; ?>
                                    data-phone="<?php echo h($customer['phone']); ?>"
                                    data-email="<?php echo h($customer['email']); ?>">
                                    <?php echo h($customer['full_name']); ?> - <?php echo h($customer['phone']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Số Điện Thoại *</label>
                            <input type="tel" class="form-control" name="phone"
                                value="<?php echo h($editBookingRoom['phone']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Chọn Phòng *</label>
                            <select class="form-select" name="room_id" id="room_id_edit" required readonly disabled
                                style="background-color: #e9ecef; cursor: not-allowed;">
                                <option value="">-- Chọn phòng --</option>
                                <?php foreach ($availableRooms as $room): ?>
                                <option value="<?php echo $room['room_id']; ?>"
                                    <?php echo ($editBookingRoom['room_id'] == $room['room_id']) ? 'selected' : ''; ?>>
                                    <?php echo h($room['room_number']); ?> - <?php echo h($room['room_type_name']); ?>
                                    (<?php echo number_format($room['base_price']); ?> VNĐ)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="room_id" value="<?php echo $editBookingRoom['room_id']; ?>">
                            <small class="text-muted">Không thể thay đổi phòng khi chỉnh sửa booking</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Số Khách *</label>
                            <input type="number" class="form-control" name="quantity" min="1"
                                value="<?php echo h($editBookingRoom['quantity']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày Check-in *</label>
                            <input type="date" class="form-control" name="check_in_date"
                                value="<?php echo h($editBookingRoom['check_in_date']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày Check-out *</label>
                            <input type="date" class="form-control" name="check_out_date"
                                value="<?php echo h($editBookingRoom['check_out_date']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trạng Thái *</label>
                            <select class="form-select" name="status" required>
                                <option value="Pending"
                                    <?php echo ($editBookingRoom['status'] == 'Pending') ? 'selected' : ''; ?>>Chờ xác
                                    nhận</option>
                                <option value="Confirmed"
                                    <?php echo ($editBookingRoom['status'] == 'Confirmed') ? 'selected' : ''; ?>>Đã xác
                                    nhận</option>
                                <option value="Completed"
                                    <?php echo ($editBookingRoom['status'] == 'Completed') ? 'selected' : ''; ?>>Đã hoàn
                                    thành</option>
                                <option value="Cancelled"
                                    <?php echo ($editBookingRoom['status'] == 'Cancelled') ? 'selected' : ''; ?>>Đã hủy
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phương Thức Đặt Phòng</label>
                            <select class="form-select" name="booking_method">
                                <option value="Website"
                                    <?php echo (($editBookingRoom['booking_method'] ?? 'Website') == 'Website') ? 'selected' : ''; ?>>
                                    Website</option>
                                <option value="Phone"
                                    <?php echo (($editBookingRoom['booking_method'] ?? '') == 'Phone') ? 'selected' : ''; ?>>
                                    Điện thoại</option>
                                <option value="Email"
                                    <?php echo (($editBookingRoom['booking_method'] ?? '') == 'Email') ? 'selected' : ''; ?>>
                                    Email</option>
                                <option value="Walk-in"
                                    <?php echo (($editBookingRoom['booking_method'] ?? '') == 'Walk-in') ? 'selected' : ''; ?>>
                                    Trực tiếp</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tiền Cọc (VNĐ)</label>
                            <input type="number" class="form-control" name="deposit" min="0" step="0.01"
                                value="<?php echo $editBookingRoom['deposit'] ?? ''; ?>"
                                placeholder="Nhập số tiền cọc (nếu có)">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi Chú</label>
                        <textarea class="form-control" name="special_request"
                            rows="3"><?php echo h($editBookingRoom['special_request'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_room_booking" class="btn-primary-custom">
                        <i class="fas fa-save"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.select2-search__field {
    width: 100% !important;
    border: none !important;
    outline: none !important;
    padding: 5px !important;
    margin: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
}

.select2-search__field:focus {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da !important;
    border-radius: 0.375rem !important;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
    border-color: #86b7fe !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill phone number when selecting customer
    const customerSelect = document.querySelector('#editBookingForm select[name="customer_id"]');
    const phoneInput = document.querySelector('#editBookingForm input[name="phone"]');

    if (customerSelect && phoneInput) {
        customerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const phone = selectedOption.getAttribute('data-phone') || '';
            if (phone) {
                phoneInput.value = phone;
            }
        });
    }

    // Set minimum date for check-in to today
    const checkInDate = document.querySelector('#editBookingForm input[name="check_in_date"]');
    const checkOutDate = document.querySelector('#editBookingForm input[name="check_out_date"]');

    if (checkInDate) {
        const today = new Date().toISOString().split('T')[0];
        checkInDate.setAttribute('min', today);

        checkInDate.addEventListener('change', function() {
            if (checkOutDate) {
                checkOutDate.setAttribute('min', this.value);
                if (checkOutDate.value && checkOutDate.value <= this.value) {
                    checkOutDate.value = '';
                }
            }
        });
    }

    // Khởi tạo Select2 cho customer
    function initCustomerSelect2RoomEdit() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            return false;
        }

        const $customerSelect = jQuery('#customerSelectRoomEdit');
        if (!$customerSelect.length) {
            return false;
        }

        // Destroy nếu đã khởi tạo trước đó
        if ($customerSelect.hasClass('select2-hidden-accessible')) {
            $customerSelect.select2('destroy');
        }

        // Lấy modal để set dropdownParent
        const $modal = jQuery('#editRoomBookingModal');
        const dropdownParent = $modal.length ? $modal : jQuery('body');

        $customerSelect.select2({
            theme: 'bootstrap-5',
            placeholder: '-- Chọn khách hàng --',
            allowClear: true,
            minimumInputLength: 0,
            width: '100%',
            dropdownParent: dropdownParent,
            language: {
                noResults: function() {
                    return "Không tìm thấy khách hàng";
                },
                searching: function() {
                    return "Đang tìm kiếm...";
                }
            }
        });

        // Tự động điền số điện thoại khi chọn khách hàng
        $customerSelect.off('change.select2-customer').on('change.select2-customer', function() {
            const selectedOption = jQuery(this).find('option:selected');
            const phone = selectedOption.data('phone') || '';
            jQuery('#editBookingForm input[name="phone"]').val(phone);
        });

        // Đảm bảo input tìm kiếm có thể gõ được
        $customerSelect.on('select2:open', function() {
            setTimeout(function() {
                const $searchField = jQuery('.select2-search__field');
                $searchField.attr('placeholder', 'Gõ để tìm kiếm...');
                $searchField.prop('readonly', false);
                $searchField.prop('disabled', false);
                $searchField.focus();
            }, 100);
        });

        return true;
    }

    // Mở modal tự động khi page load
    const modalEl = document.getElementById('editRoomBookingModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Khởi tạo Select2 sau khi modal mở
        modalEl.addEventListener('shown.bs.modal', function() {
            setTimeout(initCustomerSelect2RoomEdit, 200);
        });
    }

    // Khởi tạo lần đầu nếu không trong modal
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            setTimeout(initCustomerSelect2RoomEdit, 300);
        });
    }
});
</script>