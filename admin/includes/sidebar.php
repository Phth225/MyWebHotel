<?php
$canViewSection = function ($sectionKey) {
    return function_exists('canAccessSection') ? canAccessSection($sectionKey) : true;
};
$canAccessCustomers = $canViewSection('customers-manager');
?>
<div class="sidebar" id="sidebar">
    <div>
        <div class="header" style="cursor: pointer;" onclick="window.location.href='index.php?page=profile'">
            <?php
            $avatarUrl = '';
            $fallbackAvatar = '';
            if (!empty($_SESSION['anh_dai_dien'])) {
                // URL từ session đã là Cloudinary URL
                    $avatarUrl = $_SESSION['anh_dai_dien'];
                // Fallback to UI avatar if image fails
                $hoTen = isset($_SESSION['ho_ten']) ? $_SESSION['ho_ten'] : 'Admin';
                $fallbackAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($hoTen) . '&background=d4b896&color=fff&size=150';
            } else {
                // Use UI avatar generator if no avatar
                $hoTen = isset($_SESSION['ho_ten']) ? $_SESSION['ho_ten'] : 'Admin';
                $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($hoTen) . '&background=d4b896&color=fff&size=150';
                $fallbackAvatar = $avatarUrl;
            }
            ?>
            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="avatar"
                onerror="if(this.src !== this.getAttribute('data-fallback')) { this.src = this.getAttribute('data-fallback'); } else { this.style.display='none'; }"
                data-fallback="<?php echo htmlspecialchars($fallbackAvatar); ?>" />
            <div class="info">
                <strong><?php echo isset($_SESSION['ho_ten']) ? htmlspecialchars($_SESSION['ho_ten']) : 'Admin'; ?></strong><br />
                <small><?php echo isset($_SESSION['chuc_vu']) ? htmlspecialchars($_SESSION['chuc_vu']) : ''; ?></small>
            </div>
        </div>
        <?php 
            $current_page = isset($page) ? $page : (isset($_GET['page']) ? $_GET['page'] : 'home');
            // Kiểm tra xem có phải quản lý không
            $chuc_vu = $_SESSION['chuc_vu'] ?? '';
            $isManager = false;
            if (!empty($chuc_vu)) {
                $isManager = (
                    stripos($chuc_vu, 'Quản lý') !== false ||
                    stripos($chuc_vu, 'Manager') !== false ||
                    stripos($chuc_vu, 'Admin') !== false ||
                    stripos($chuc_vu, 'Giám đốc') !== false ||
                    stripos($chuc_vu, 'Director') !== false
                );
            }
        ?>
        <div class="menu">
            <?php if ($isManager && $canViewSection('home')): ?>
            <div class="menu-item <?php echo $current_page === 'home' ? 'active' : ''; ?>" data-page="home">
                <i class="fas fa-home"></i><span>Tổng Quan</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('room-manager')): ?>
            <div class="menu-item <?php echo $current_page === 'room-manager' ? 'active' : ''; ?>"
                data-page="room-manager">
                <i class="fas fa-bed"></i><span>Phòng</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('services-manager')): ?>
            <div class="menu-item <?php echo $current_page === 'services-manager' ? 'active' : ''; ?>"
                data-page="services-manager">
                <i class="fas fa-concierge-bell"></i><span>Dịch Vụ</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('invoices-manager')): ?>
            <div class="menu-item <?php echo $current_page === 'invoices-manager' ? 'active' : ''; ?>"
                data-page="invoices-manager">
                <i class="fa-solid fa-coins"></i><span>Hóa Đơn</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('booking-manager')): ?>
            <div class="menu-item <?php echo $current_page === 'booking-manager' ? 'active' : ''; ?>"
                data-page="booking-manager">
                <i class="fa-solid fa-calendar-days"></i><span>Booking</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('customers-manager')): ?>
            <div class="menu-item <?php echo $current_page === 'customers-manager' ? 'active' : ''; ?>"
                data-page="customers-manager">
                <i class="fas fa-users"></i><span>Khách Hàng</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('staff-manager')): ?>
            <div class="menu-item <?php echo $current_page === 'staff-manager' ? 'active' : ''; ?>"
                data-page="staff-manager">
                <i class="fa-solid fa-user-tie"></i><span>Nhân Viên</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('reports-manager')): ?>
            <div class="menu-item <?php echo $current_page === 'reports-manager' ? 'active' : ''; ?>"
                data-page="reports-manager">
                <i class="fas fa-chart-line"></i><span>Thống Kê</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('blogs-manager')): ?>
            <div class="menu-item <?php echo $current_page === 'blogs-manager' ? 'active' : ''; ?>"
                data-page="blogs-manager">
                <i class="fa-solid fa-comments"></i><span>Blog & Review</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('voucher-manager')): ?>
            <div class="menu-item <?php echo $current_page === 'voucher-manager' ? 'active' : ''; ?>"
                data-page="voucher-manager">
                <i class="fa-solid fa-ticket-alt"></i><span>Voucher</span>
            </div>
            <?php endif; ?>
            <?php if ($canViewSection('my-tasks')): ?>
            <div class="menu-item <?php echo $current_page === 'my-tasks' ? 'active' : ''; ?>" data-page="my-tasks">
                <i class="fas fa-list-check"></i><span>Nhiệm Vụ Của Tôi</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="bottom">
        <div class="toggle-logout" data-page="logout" onclick="handleLogout(event)">
            <i class="fas fa-sign-out-alt"></i><span>Đăng Xuất</span>
        </div>
        <div class="toggle-theme">
            <i class="fas fa-moon"></i><span>Chế Độ Tối</span>
            <label class="switch">
                <input type="checkbox" id="theme-toggle" />
            </label>
        </div>
        <div class="toggle-sidebar">
            <i class="fas fa-angle-double-left" id="collapse-icon"></i><span>Thu Gọn</span>
        </div>
    </div>
</div>