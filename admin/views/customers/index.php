<?php
// View for Customers Manager
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message'], $_SESSION['messageType']);
?>

<div class="main-content">
    <div class="container-fluid">
        <h1 class="mb-4">Quản Lý Khách Hàng</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh Sách Khách Hàng</h5>
                <?php if ($canCreate): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                        <i class="bi bi-plus-circle"></i> Thêm Khách Hàng
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <form method="GET" action="index.php" class="mb-3">
                    <input type="hidden" name="page" value="customers-manager">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo tên, email, SĐT..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-secondary w-100">Tìm Kiếm</button>
                        </div>
                        <?php if ($search): ?>
                            <div class="col-md-2">
                                <a href="index.php?page=customers-manager" class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Customers Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Họ Tên</th>
                                <th>Email</th>
                                <th>Số Điện Thoại</th>
                                <th>Ngày Sinh</th>
                                <th>Giới Tính</th>
                                <th>Tổng Booking</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">Không có dữ liệu</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo $customer['customer_id']; ?></td>
                                        <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                        <td><?php echo $customer['date_of_birth'] ? formatDate($customer['date_of_birth']) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($customer['gender'] ?? '-'); ?></td>
                                        <td><?php echo intval($customer['total_bookings'] ?? 0); ?></td>
                                        <td>
                                            <?php if ($canEdit): ?>
                                                <a href="index.php?page=customers-manager&action=edit&id=<?php echo $customer['customer_id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Sửa
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($canDelete): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa?');">
                                                    <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                                                    <button type="submit" name="delete_customer" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i> Xóa
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="index.php?page=customers-manager&page_num=<?php echo $currentPage - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Trước</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="index.php?page=customers-manager&page_num=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="index.php?page=customers-manager&page_num=<?php echo $currentPage + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Sau</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $editCustomer ? 'Sửa Khách Hàng' : 'Thêm Khách Hàng'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if ($editCustomer): ?>
                        <input type="hidden" name="customer_id" value="<?php echo $editCustomer['customer_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Họ Tên *</label>
                        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($editCustomer['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($editCustomer['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Số Điện Thoại *</label>
                        <input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($editCustomer['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ngày Sinh</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?php echo $editCustomer['date_of_birth'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Giới Tính</label>
                        <select name="gender" class="form-select">
                            <option value="Nam" <?php echo ($editCustomer['gender'] ?? '') == 'Nam' ? 'selected' : ''; ?>>Nam</option>
                            <option value="Nữ" <?php echo ($editCustomer['gender'] ?? '') == 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                            <option value="Other" <?php echo ($editCustomer['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Khác</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="<?php echo $editCustomer ? 'update_customer' : 'add_customer'; ?>" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editCustomer): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
            modal.show();
        });
    </script>
<?php endif; ?>









