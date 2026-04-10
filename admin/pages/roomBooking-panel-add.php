<?php
// Form thêm mới booking phòng - File riêng để tránh lẫn lộn với edit

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

<!-- Modal Thêm Booking -->
<div class="modal fade" id="addRoomBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title">Thêm Booking Phòng Mới</h5>
            </div>

            <form method="POST" id="addBookingForm">
                <div class="modal-body px-4 pt-3 pb-4">

                    <!-- Section: Customer -->
                    <div class="bg-light rounded-3 p-3 mb-4">
                        <label class="text-uppercase text-secondary fw-bold small mb-3">
                            <i class="fas fa-user me-2"></i>Thông tin khách hàng
                        </label>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-muted">Khách hàng <span
                                        class="text-danger">*</span></label>
                                <select class="form-select customer-search shadow-sm" name="customer_id" required
                                    id="customerSelectRoom">
                                    <option value="">-- Chọn khách hàng --</option>
                                    <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['customer_id']; ?>"
                                        data-phone="<?php echo h($customer['phone']); ?>"
                                        data-email="<?php echo h($customer['email']); ?>">
                                        <?php echo h($customer['full_name']); ?> - <?php echo h($customer['phone']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-muted">Số điện thoại <span
                                        class="text-danger">*</span></label>
                                <input type="tel" class="form-control shadow-sm" name="phone"
                                    placeholder="Tự động điền..." required>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Stay Info -->
                    <div class="mb-4">
                        <label class="text-uppercase text-secondary fw-bold small mb-3">
                            <i class="fas fa-bed me-2"></i>Thông tin lưu trú
                        </label>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted">Chọn phòng <span
                                        class="text-danger">*</span></label>
                                <select class="form-select shadow-sm" name="room_id[]" id="room_id_select" multiple
                                    required style="border-color: #dee2e6; height: 150px;">
                                    <?php foreach ($availableRooms as $room): ?>
                                    <option value="<?php echo $room['room_id']; ?>" class="py-2">
                                        P.<?php echo h($room['room_number']); ?> -
                                        <?php echo h($room['room_type_name']); ?>
                                        (<?php echo number_format($room['base_price']); ?>đ)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text small"><i class="fas fa-info-circle me-1"></i>Giữ phím Ctrl
                                    (Windows) hoặc Cmd (Mac) để chọn nhiều phòng</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Ngày Check-in <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control shadow-sm" name="check_in_date" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Ngày Check-out <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control shadow-sm" name="check_out_date" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Số lượng khách <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control shadow-sm" name="quantity" min="1" value="1"
                                    required>
                            </div>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-4">

                    <!-- Section: Payment & Status -->
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Trạng thái <span
                                    class="text-danger">*</span></label>
                            <select class="form-select shadow-sm" name="status" required>
                                <option value="Pending" class="text-warning">Chờ xác nhận</option>
                                <option value="Confirmed" class="text-primary">Đã xác nhận</option>
                                <option value="Completed" class="text-success">Đã hoàn thành</option>
                                <option value="Cancelled" class="text-danger">Đã hủy</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Phương thức đặt</label>
                            <select class="form-select shadow-sm" name="booking_method">
                                <option value="Website">Website</option>
                                <option value="Phone" selected>Điện thoại</option>
                                <option value="Email">Email</option>
                                <option value="Walk-in">Trực tiếp</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Tiền cọc (VNĐ)</label>
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white text-muted border-end-0">₫</span>
                                <input type="number" class="form-control border-start-0 ps-0" name="deposit" min="0"
                                    step="1000" placeholder="0">
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold small text-muted">Ghi chú / Yêu cầu đặc biệt</label>
                            <textarea class="form-control shadow-sm" name="special_request" rows="2"
                                placeholder="Nhập ghi chú (nếu có)..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn text-light bg-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" name="add_booking_room" class="btn btn-primary px-4 shadow-sm">
                        Thêm
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
    const customerSelect = document.querySelector('#addBookingForm select[name="customer_id"]');
    const phoneInput = document.querySelector('#addBookingForm input[name="phone"]');

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
    const checkInDate = document.querySelector('#addBookingForm input[name="check_in_date"]');
    const checkOutDate = document.querySelector('#addBookingForm input[name="check_out_date"]');

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
    function initCustomerSelect2Room() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            return false;
        }

        const $customerSelect = jQuery('#customerSelectRoom');
        if (!$customerSelect.length) {
            return false;
        }

        // Destroy nếu đã khởi tạo trước đó
        if ($customerSelect.hasClass('select2-hidden-accessible')) {
            $customerSelect.select2('destroy');
        }

        // Lấy modal để set dropdownParent
        const $modal = jQuery('#addRoomBookingModal');
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
            jQuery('#addBookingForm input[name="phone"]').val(phone);
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

    // Khởi tạo Select2 khi modal mở
    const modal = document.getElementById('addRoomBookingModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            setTimeout(initCustomerSelect2Room, 200);
        });
    }

    // Khởi tạo lần đầu nếu không trong modal
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            setTimeout(initCustomerSelect2Room, 300);
        });
    }

    // Reset form khi modal đóng
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('addBookingForm');
            if (form) {
                form.reset();
                // Reset Select2
                if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    const $customerSelect = jQuery('#customerSelectRoom');
                    if ($customerSelect.length) {
                        $customerSelect.val(null).trigger('change');
                    }
                }
            }
        });
    }
});
</script>