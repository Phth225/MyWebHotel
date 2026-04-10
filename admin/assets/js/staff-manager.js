// Preview avatar khi chọn file
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('avatarPreview');
                    if (preview) {
                        preview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

// Xem chi tiết nhân viên
function viewEmployee(id) {
    fetch(`/My-Web-Hotel/admin/api/staff-api.php?action=view&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const nv = data.data;
                const content = `
                    <div class="text-center mb-4">
                        <img src="${nv.anh_dai_dien ? nv.anh_dai_dien : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(nv.ho_ten) + '&background=d4b896&color=fff&size=120'}" 
                            alt="Avatar" class="avatar-preview">
                        <h5 class="mt-2">${nv.ho_ten}</h5>
                        <p class="text-muted">${nv.ma_nhan_vien}</p>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Chức vụ:</strong> <span class="badge badge-manager">${nv.chuc_vu}</span></div>
                        <div class="col-md-6"><strong>Bộ phận:</strong> ${nv.phong_ban || '-'}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Email:</strong> ${nv.email}</div>
                        <div class="col-md-6"><strong>Số điện thoại:</strong> ${nv.dien_thoai || '-'}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Ngày sinh:</strong> ${nv.ngay_sinh || '-'}</div>
                        <div class="col-md-6"><strong>Giới tính:</strong> ${nv.gioi_tinh}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>CMND/CCCD:</strong> ${nv.cmnd_cccd || '-'}</div>
                        <div class="col-md-6"><strong>Ngày vào làm:</strong> ${nv.ngay_vao_lam}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Địa chỉ:</strong> ${nv.dia_chi || '-'}</div>
                        <div class="col-md-6"><strong>Lương cơ bản:</strong> ${nv.luong_co_ban ? new Intl.NumberFormat('vi-VN').format(nv.luong_co_ban) + ' VNĐ' : '-'}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Trạng thái:</strong> 
                            <span class="badge ${nv.trang_thai === 'Đang làm việc' ? 'bg-success' : nv.trang_thai === 'Nghỉ' ? 'bg-warning' : 'bg-danger'}">${nv.trang_thai}</span>
                        </div>
                    </div>
                    ${nv.ghi_chu ? `<div class="mb-3"><strong>Ghi chú:</strong> ${nv.ghi_chu}</div>` : ''}
                `;
                document.getElementById('viewEmployeeContent').innerHTML = content;
                const modal = new bootstrap.Modal(document.getElementById('viewEmployeeModal'));
                modal.show();
            } else {
                alert('Không thể tải thông tin nhân viên: ' + data.message);
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra khi tải thông tin nhân viên');
        });
}

// Sửa nhân viên
function editEmployee(id) {
    window.location.href = 'index.php?page=staff-manager&action=edit&id=' + id;
}

// Xóa nhân viên
function deleteEmployee(id) {
    if (confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="id_nhan_vien" value="' + id + '">' +
            '<input type="hidden" name="delete_nhan_vien" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Mở modal phân quyền
function openPermissionModal(id) {
    fetch(`/My-Web-Hotel/admin/api/staff-api.php?action=permissions&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const nv = data.staff;
                const permissions = data.permissions;
                const rolePermissions = data.rolePermissions || [];
                const personalPermissions = data.personalPermissions || [];
                
                
                let content = `
                    <div class="mb-3">
                        <h6>Nhân viên: <strong>${nv.ho_ten} (${nv.ma_nhan_vien})</strong></h6>
                        <p class="text-muted">Chức vụ: ${nv.chuc_vu}</p>
                    </div>
                    <form id="permissionForm">
                        <input type="hidden" name="id_nhan_vien" value="${id}">
                        <input type="hidden" name="chuc_vu" value="${nv.chuc_vu}">
                `;
                
                // Nhóm quyền theo category
                const groupedPermissions = {};
                permissions.forEach(perm => {
                    const category = perm.ten_quyen.split(' - ')[0] || 'Khác';
                    if (!groupedPermissions[category]) {
                        groupedPermissions[category] = [];
                    }
                    groupedPermissions[category].push(perm);
                });
                
                // Chuyển sang mảng số để so sánh chính xác
                const rolePermIds = rolePermissions.map(p => parseInt(p));
                const personalPermIds = personalPermissions.map(p => parseInt(p));
                
                // Hợp nhất quyền từ chức vụ và quyền riêng (nếu nhân viên đã có quyền thì tick)
                const allPermIds = [...new Set([...rolePermIds, ...personalPermIds])];
                
                Object.keys(groupedPermissions).forEach(category => {
                    content += `<div class="permission-group mb-4">`;
                    content += `<h6><i class="fas fa-folder"></i> ${category}</h6>`;
                    groupedPermissions[category].forEach(perm => {
                        const permId = parseInt(perm.id_quyen);
                        const isRole = rolePermIds.includes(permId);
                        const isPersonal = personalPermIds.includes(permId);
                        // Tick nếu nhân viên đã có quyền (từ chức vụ hoặc quyền riêng)
                        const isChecked = allPermIds.includes(permId);
                        
                        content += `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                    name="permissions[]" 
                                    value="${perm.id_quyen}" 
                                    id="perm${perm.id_quyen}" 
                                    ${isChecked ? 'checked' : ''}>
                                <label class="form-check-label" for="perm${perm.id_quyen}">
                                    ${perm.ten_quyen}
                                    ${isRole ? ' <span class="badge bg-secondary ms-1">theo chức vụ</span>' : ''}
                                </label>
                            </div>
                        `;
                    });
                    content += `</div>`;
                });
                
                content += `</form>`;
                document.getElementById('permissionContent').innerHTML = content;
                const modal = new bootstrap.Modal(document.getElementById('permissionModal'));
                modal.show();
            } else {
                alert('Không thể tải thông tin quyền: ' + data.message);
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra khi tải thông tin quyền');
        });
}

// Lưu quyền
function savePermissions() {
    const form = document.getElementById('permissionForm');
    if (!form) return;
    
    const formData = new FormData(form);
    formData.append('action', 'save_permissions');
    
    fetch('/My-Web-Hotel/admin/api/staff-api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Lưu phân quyền thành công!');
            // Đóng modal - khi mở lại sẽ tự động load dữ liệu mới nhất
            bootstrap.Modal.getInstance(document.getElementById('permissionModal')).hide();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi lưu phân quyền');
    });
}

// Clear edit mode
function clearEditMode() {
    const url = new URL(window.location);
    url.searchParams.delete('action');
    url.searchParams.delete('id');
    window.history.replaceState({}, '', url);
}
