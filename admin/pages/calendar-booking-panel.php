<?php
// Kiểm tra quyền xem lịch
$canViewCalendar = function_exists('checkPermission') ? checkPermission('service.calendar.view') : true;

if (!$canViewCalendar) {
    echo '<div class="alert alert-danger m-4">Bạn không có quyền xem lịch đặt phòng và dịch vụ.</div>';
    return;
}
?>

<div class="calendar-wrapper">
    <div class="calendar-filters-section mb-4">
        <div class="filter-buttons-group">
            <button type="button" class="filter-btn" id="filterAll" data-filter="all">
                <i class="fas fa-list me-2"></i>Tất Cả
            </button>
            <button type="button" class="filter-btn" id="filterRoom" data-filter="room">
                <i class="fas fa-bed me-2"></i>Đặt Phòng
            </button>
            <button type="button" class="filter-btn" id="filterService" data-filter="service">
                <i class="fas fa-concierge-bell me-2"></i>Sự Kiện
            </button>
        </div>
    </div>

    <div class="calendar-container">
        <div id="calendar"></div>
    </div>
</div>

<!-- Modal Chi Tiết -->
<div class="modal fade" id="bookingDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Chi Tiết Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Nội dung sẽ được điền bằng JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/locales/vi.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let calendar;
    let currentFilter = 'all'; // 'all', 'room', 'service'
    
    // Khởi tạo FullCalendar
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'vi',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Hôm nay',
            month: 'Tháng',
            week: 'Tuần',
            day: 'Ngày'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(`api/calendar-unified.php?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                .then(response => response.json())
                .then(data => {
                    // Lọc events dựa trên filter
                    let filteredData = data;
                    if (currentFilter === 'room') {
                        filteredData = data.filter(event => event.extendedProps.type === 'room');
                    } else if (currentFilter === 'service') {
                        filteredData = data.filter(event => event.extendedProps.type === 'service');
                    }
                    successCallback(filteredData);
                })
                .catch(error => {
                    failureCallback(error);
                });
        },
        eventClassNames: function(arg) {
            if (arg.event.extendedProps.type === 'room') {
                return ['event-room'];
            } else if (arg.event.extendedProps.type === 'service') {
                return ['event-service'];
            }
            return [];
        },
        eventClick: function(info) {
            showBookingDetails(info.event);
        },
        eventContent: function(arg) {
            let icon = '';
            if (arg.event.extendedProps.type === 'room') {
                icon = '<i class="fa-solid fa-door-open me-1"></i>';
            } else if (arg.event.extendedProps.type === 'service') {
                icon = '<i class="fa-solid fa-concierge-bell me-1"></i>';
            }
            
            return {
                html: `<div class="fc-event-main-frame">
                        <div class="fc-event-title-container">
                            <div class="fc-event-title fc-sticky">${icon}${arg.event.title}</div>
                        </div>
                      </div>`
            };
        },
        eventDisplay: 'block',
        height: 'auto',
        contentHeight: 600,
        dayMaxEvents: 3,
        moreLinkClick: 'popover'
    });
    
    calendar.render();
    
    // Xử lý filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            currentFilter = filter;
            updateFilterButtons(filter);
            calendar.refetchEvents();
        });
    });
    
    // Set default active filter
    updateFilterButtons('all');
    
    function updateFilterButtons(activeFilter) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeBtn = document.querySelector(`.filter-btn[data-filter="${activeFilter}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
    
    function showBookingDetails(event) {
        const props = event.extendedProps;
        const modal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        let html = '';
        
        if (props.type === 'room') {
            modalTitle.textContent = 'Chi Tiết Đặt Phòng';
            html = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-hashtag"></i></div>
                        <div class="detail-content">
                            <label>Mã Booking</label>
                            <span>#${props.booking_id}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-user"></i></div>
                        <div class="detail-content">
                            <label>Khách Hàng</label>
                            <span>${props.customer_name}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-bed"></i></div>
                        <div class="detail-content">
                            <label>Phòng</label>
                            <span>${props.room_number} (${props.room_type})</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-phone"></i></div>
                        <div class="detail-content">
                            <label>Số Điện Thoại</label>
                            <span>${props.customer_phone || 'N/A'}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="detail-content">
                            <label>Check-in</label>
                            <span>${formatDate(props.check_in)}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-calendar-times"></i></div>
                        <div class="detail-content">
                            <label>Check-out</label>
                            <span>${formatDate(props.check_out)}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-moon"></i></div>
                        <div class="detail-content">
                            <label>Thời Gian</label>
                            <span>${props.nights} đêm</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-info-circle"></i></div>
                        <div class="detail-content">
                            <label>Trạng Thái</label>
                            <span class="badge bg-${getStatusColor(props.status)}">${getStatusLabel(props.status)}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block text-uppercase" style="font-size: 10px; font-weight: 700;">Tổng Tiền</small>
                        <strong class="text-primary fs-4">${formatCurrency(props.base_price)}</strong>
                    </div>
                </div>
            `;
        } else if (props.type === 'service') {
            modalTitle.textContent = 'Chi Tiết Đặt Dịch Vụ';
            html = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-hashtag"></i></div>
                        <div class="detail-content">
                            <label>Mã Booking</label>
                            <span>#${props.booking_service_id}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-user"></i></div>
                        <div class="detail-content">
                            <label>Khách Hàng</label>
                            <span>${props.customer_name}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-concierge-bell"></i></div>
                        <div class="detail-content">
                            <label>Dịch Vụ</label>
                            <span>${props.service_name}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-phone"></i></div>
                        <div class="detail-content">
                            <label>Số Điện Thoại</label>
                            <span>${props.customer_phone || 'N/A'}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-calendar-day"></i></div>
                        <div class="detail-content">
                            <label>Ngày Sử Dụng</label>
                            <span>${formatDate(props.usage_date)}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-clock"></i></div>
                        <div class="detail-content">
                            <label>Giờ Sử Dụng</label>
                            <span>${props.usage_time}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-layer-group"></i></div>
                        <div class="detail-content">
                            <label>Số Lượng</label>
                            <span>${props.quantity}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-info-circle"></i></div>
                        <div class="detail-content">
                            <label>Trạng Thái</label>
                            <span class="badge bg-${getStatusColor(props.status)}">${getStatusLabel(props.status)}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block text-uppercase" style="font-size: 10px; font-weight: 700;">Tổng Tiền</small>
                        <strong class="text-primary fs-4">${formatCurrency(props.unit_price * props.quantity)}</strong>
                    </div>
                </div>
            `;
        }
        
        modalBody.innerHTML = html;
        modal.show();
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        return date.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }
    
    function formatCurrency(amount) {
        if (!amount) return '0 ₫';
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }
    
    function getStatusLabel(status) {
        status = (status || '').toLowerCase();
        const labels = {
            'confirmed': 'Đã xác nhận',
            'checked-in': 'Đã nhận phòng',
            'occupied': 'Đang ở',
            'pending': 'Chờ xử lý',
            'cancelled': 'Đã hủy',
            'completed': 'Đã hoàn thành'
        };
        return labels[status] || status;
    }


    function getStatusColor(status) {
        status = (status || '').toLowerCase();
        const colors = {
            'confirmed': 'primary', // Original style for room was primary, service success. Let's stick to consistent Bootstrap classes.
            'checked-in': 'info',
            'occupied': 'primary',
            'pending': 'warning',
            'cancelled': 'danger',
            'completed': 'success'
        };
        return colors[status] || 'secondary';
    }
});
</script>

