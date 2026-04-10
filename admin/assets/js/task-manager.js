function openGlobalTaskModal() {
  const today = new Date().toISOString().split("T")[0];
  const form = document.getElementById("globalTaskForm");
  if (!form) return;

  const ngayBatDau = form.querySelector('input[name="ngay_bat_dau"]');
  const hanHoanThanh = form.querySelector('input[name="han_hoan_thanh"]');
  if (ngayBatDau) {
    ngayBatDau.value = today;
    ngayBatDau.setAttribute("min", today);
  }
  if (hanHoanThanh) {
    hanHoanThanh.value = today;
    hanHoanThanh.setAttribute("min", today);
    ngayBatDau?.addEventListener("change", function () {
      hanHoanThanh.setAttribute("min", this.value);
    });
  }

  const modal = new bootstrap.Modal(document.getElementById("globalTaskModal"));
  modal.show();
}

function submitGlobalTask() {
  const form = document.getElementById("globalTaskForm");
  if (!form) return;

  const formData = new FormData(form);
  formData.append("action", "assign_task");

  fetch("/My-Web-Hotel/admin/api/staff-api.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Giao nhiệm vụ thành công!");
        bootstrap.Modal.getInstance(
          document.getElementById("globalTaskModal")
        ).hide();
        window.location.reload();
      } else {
        alert("Lỗi: " + data.message);
      }
    })
    .catch((error) => {
      alert("Có lỗi xảy ra khi giao nhiệm vụ");
    });
}

function viewTaskDetail(id) {
  fetch(`/My-Web-Hotel/admin/api/staff-api.php?action=view_task&id=${id}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const task = data.task;
        let content = `
                    <div class="mb-3">
                        <h5><strong>${task.ten_nhiem_vu}</strong></h5>
                        <p class="text-muted">Người giao: ${
                          task.nguoi_gan_ten || "N/A"
                        }</p>
                    </div>
                    <div class="mb-3">
                        <strong>Mô tả chi tiết:</strong>
                        <p>${task.mo_ta_chi_tiet || "Không có mô tả"}</p>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Nhân viên được giao:</strong> ${
                              task.ten_nhan_vien
                            } (${task.ma_nhan_vien})
                        </div>
                        <div class="col-md-6">
                            <strong>Mức độ ưu tiên:</strong> <span class="badge">${
                              task.muc_do_uu_tien
                            }</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ngày bắt đầu:</strong> ${task.ngay_bat_dau}
                        </div>
                        <div class="col-md-6">
                            <strong>Hạn hoàn thành:</strong> ${
                              task.han_hoan_thanh
                            }
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Tiến độ:</strong> ${
                              task.tien_do_hoan_thanh
                            }%
                        </div>
                        <div class="col-md-6">
                            <strong>Trạng thái:</strong> <span class="badge">${
                              task.trang_thai
                            }</span>
                        </div>
                    </div>
                    ${
                      task.ghi_chu
                        ? `<div class="mb-3"><strong>Ghi chú:</strong><p>${task.ghi_chu}</p></div>`
                        : ""
                    }
                `;
        document.getElementById("viewTaskContent").innerHTML = content;
        const modal = new bootstrap.Modal(
          document.getElementById("viewTaskModal")
        );
        modal.show();
      } else {
        alert("Không thể tải thông tin nhiệm vụ: " + data.message);
      }
    })
    .catch((error) => {
      alert("Có lỗi xảy ra khi tải thông tin nhiệm vụ");
    });
}

function editTask(id) {
  fetch(`/My-Web-Hotel/admin/api/staff-api.php?action=view_task&id=${id}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      if (data.success && data.task) {
        const task = data.task;
        const form = document.getElementById("editTaskForm");
        if (!form) {
          alert("Không tìm thấy form chỉnh sửa (editTaskForm)");
          return;
        }

        // Populate form với dữ liệu từ server
        const idNhiemVu = form.querySelector('input[name="id_nhiem_vu"]');
        const idNhanVien = form.querySelector(
          'select[name="id_nhan_vien_duoc_gan"]'
        );
        const tenNhiemVu = form.querySelector('input[name="ten_nhiem_vu"]');
        const moTaChiTiet = form.querySelector(
          'textarea[name="mo_ta_chi_tiet"]'
        );
        const mucDoUuTien = form.querySelector('select[name="muc_do_uu_tien"]');
        const ngayBatDau = form.querySelector('input[name="ngay_bat_dau"]');
        const hanHoanThanh = form.querySelector('input[name="han_hoan_thanh"]');
        const ghiChu = form.querySelector('textarea[name="ghi_chu"]');
        const trangThai = form.querySelector('select[name="trang_thai"]');
        const tienDoHoanThanh = form.querySelector(
          'input[name="tien_do_hoan_thanh"]'
        );


        // Set values - đảm bảo tất cả fields được populate
        // API trả về nv.* nên tất cả fields từ nhiem_vu sẽ có trong task object
        if (idNhiemVu && task.id_nhiem_vu) {
          idNhiemVu.value = String(task.id_nhiem_vu);
        }
        if (idNhanVien && task.id_nhan_vien_duoc_gan) {
          idNhanVien.value = String(task.id_nhan_vien_duoc_gan);
        }
        if (tenNhiemVu && task.ten_nhiem_vu) {
          tenNhiemVu.value = String(task.ten_nhiem_vu);
        }
        if (moTaChiTiet) {
          moTaChiTiet.value = task.mo_ta_chi_tiet
            ? String(task.mo_ta_chi_tiet)
            : "";
        }
        if (mucDoUuTien) {
          mucDoUuTien.value = task.muc_do_uu_tien
            ? String(task.muc_do_uu_tien)
            : "Trung bình";
        }
        if (ngayBatDau && task.ngay_bat_dau) {
          // Format date (YYYY-MM-DD) - lấy phần date nếu có datetime
          let dateValue = String(task.ngay_bat_dau);
          if (dateValue.includes(" ")) {
            dateValue = dateValue.split(" ")[0];
          }
          // Convert từ format khác nếu cần (DD/MM/YYYY -> YYYY-MM-DD)
          if (dateValue.includes("/")) {
            const parts = dateValue.split("/");
            if (parts.length === 3) {
              dateValue = parts[2] + "-" + parts[1] + "-" + parts[0];
            }
          }
          ngayBatDau.value = dateValue;
        }
        if (hanHoanThanh && task.han_hoan_thanh) {
          // Format date (YYYY-MM-DD) - lấy phần date nếu có datetime
          let dateValue = String(task.han_hoan_thanh);
          if (dateValue.includes(" ")) {
            dateValue = dateValue.split(" ")[0];
          }
          // Convert từ format khác nếu cần (DD/MM/YYYY -> YYYY-MM-DD)
          if (dateValue.includes("/")) {
            const parts = dateValue.split("/");
            if (parts.length === 3) {
              dateValue = parts[2] + "-" + parts[1] + "-" + parts[0];
            }
          }
          hanHoanThanh.value = dateValue;
        }
        if (ghiChu) {
          ghiChu.value = task.ghi_chu ? String(task.ghi_chu) : "";
        }
        if (trangThai) {
          trangThai.value = task.trang_thai
            ? String(task.trang_thai)
            : "Chưa bắt đầu";
        }
        if (tienDoHoanThanh) {
          tienDoHoanThanh.value =
            task.tien_do_hoan_thanh !== undefined &&
            task.tien_do_hoan_thanh !== null
              ? String(task.tien_do_hoan_thanh)
              : "0";
        }

        // Trigger change event để đảm bảo Select2 và các event listeners được cập nhật
        if (idNhanVien) {
          const event = new Event("change", {
            bubbles: true,
          });
          idNhanVien.dispatchEvent(event);
        }

        // Delay để đảm bảo values đã được set trước khi mở modal
        // Mở modal trước, sau đó populate data
        setTimeout(function () {
          const modalEl = document.getElementById("editTaskModal");
          if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            // Sau khi modal đã mở, đảm bảo values được set lại (đôi khi modal.show() reset form)
            setTimeout(function () {
              // Set lại values sau khi modal đã render
              if (idNhiemVu && task.id_nhiem_vu) {
                idNhiemVu.value = String(task.id_nhiem_vu);
              }
              if (idNhanVien && task.id_nhan_vien_duoc_gan) {
                idNhanVien.value = String(task.id_nhan_vien_duoc_gan);
                // Trigger change event lại
                const event = new Event("change", {
                  bubbles: true,
                });
                idNhanVien.dispatchEvent(event);
              }
              if (tenNhiemVu && task.ten_nhiem_vu) {
                tenNhiemVu.value = String(task.ten_nhiem_vu);
              }
              if (moTaChiTiet) {
                moTaChiTiet.value = task.mo_ta_chi_tiet
                  ? String(task.mo_ta_chi_tiet)
                  : "";
              }
              if (mucDoUuTien) {
                mucDoUuTien.value = task.muc_do_uu_tien
                  ? String(task.muc_do_uu_tien)
                  : "Trung bình";
              }
              if (ngayBatDau && task.ngay_bat_dau) {
                let dateValue = String(task.ngay_bat_dau);
                if (dateValue.includes(" ")) {
                  dateValue = dateValue.split(" ")[0];
                }
                if (dateValue.includes("/")) {
                  const parts = dateValue.split("/");
                  if (parts.length === 3) {
                    dateValue = parts[2] + "-" + parts[1] + "-" + parts[0];
                  }
                }
                ngayBatDau.value = dateValue;
              }
              if (hanHoanThanh && task.han_hoan_thanh) {
                let dateValue = String(task.han_hoan_thanh);
                if (dateValue.includes(" ")) {
                  dateValue = dateValue.split(" ")[0];
                }
                if (dateValue.includes("/")) {
                  const parts = dateValue.split("/");
                  if (parts.length === 3) {
                    dateValue = parts[2] + "-" + parts[1] + "-" + parts[0];
                  }
                }
                hanHoanThanh.value = dateValue;
              }
              if (ghiChu) {
                ghiChu.value = task.ghi_chu ? String(task.ghi_chu) : "";
              }
              if (trangThai) {
                trangThai.value = task.trang_thai
                  ? String(task.trang_thai)
                  : "Chưa bắt đầu";
              }
              if (tienDoHoanThanh) {
                tienDoHoanThanh.value =
                  task.tien_do_hoan_thanh !== undefined &&
                  task.tien_do_hoan_thanh !== null
                    ? String(task.tien_do_hoan_thanh)
                    : "0";
              }
            }, 300);
          } else {
          }
        }, 100);
      } else {
        alert(
          "Không thể tải thông tin nhiệm vụ: " +
            (data.message || "Lỗi không xác định")
        );
      }
    })
    .catch((error) => {
      alert("Có lỗi xảy ra khi tải thông tin nhiệm vụ: " + error.message);
    });
}

// Hàm reset form tổng quát
function resetFormFields(form) {
  if (!form) return;

  form.reset();

  // Xóa input hidden (trừ các field cần thiết)
  form.querySelectorAll('input[type="hidden"]').forEach((input) => {
    if (input.name === "id_nhiem_vu") {
      input.value = "";
    }
  });

  // Reset text/number/tel/email/date inputs
  form
    .querySelectorAll(
      'input[type="text"], input[type="number"], input[type="tel"], input[type="email"], input[type="date"]'
    )
    .forEach((input) => {
      input.value = "";
    });

  // Reset select
  form.querySelectorAll("select").forEach((select) => {
    select.selectedIndex = 0;
  });

  // Reset textarea
  form.querySelectorAll("textarea").forEach((textarea) => {
    textarea.value = "";
  });
}

// Hàm reset modal về trạng thái ban đầu
function resetModalToAddMode(modalElement, form) {
  if (!modalElement || !form) return;

  const modalTitle = modalElement.querySelector(".modal-title");
  if (modalTitle) {
    modalTitle.innerHTML = '<i class="fas fa-edit"></i> Sửa Nhiệm Vụ';
  }
}

// Reset edit task form when modal is closed
document.addEventListener("DOMContentLoaded", function () {
  const editModal = document.getElementById("editTaskModal");
  if (editModal) {
    editModal.addEventListener("hidden.bs.modal", function () {
      const form = document.getElementById("editTaskForm");
      if (form) {
        resetFormFields(form);
        resetModalToAddMode(editModal, form);
      }
    });
  }

  // Reset global task form when modal is closed
  const globalModal = document.getElementById("globalTaskModal");
  if (globalModal) {
    globalModal.addEventListener("hidden.bs.modal", function () {
      const form = document.getElementById("globalTaskForm");
      if (form) {
        form.reset();
      }
    });
  }
});

function submitEditTask() {
  const form = document.getElementById("editTaskForm");
  if (!form) return;

  const formData = new FormData(form);
  formData.append("action", "update_task");

  fetch("/My-Web-Hotel/admin/api/staff-api.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Cập nhật nhiệm vụ thành công!");
        bootstrap.Modal.getInstance(
          document.getElementById("editTaskModal")
        ).hide();
        window.location.reload();
      } else {
        alert("Lỗi: " + data.message);
      }
    })
    .catch((error) => {
      alert("Có lỗi xảy ra khi cập nhật nhiệm vụ");
    });
}

// Biến lưu ID nhiệm vụ cần xóa
let taskIdToDelete = null;

function deleteTask(id) {
    taskIdToDelete = id;
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteTaskModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const btnConfirmDelete = document.getElementById('btnConfirmDeleteTask');
    if (btnConfirmDelete) {
        // Remove existing listeners to avoid duplicates
        btnConfirmDelete.replaceWith(btnConfirmDelete.cloneNode(true));
        
        // Re-select after replacement and add listener
        document.getElementById('btnConfirmDeleteTask').addEventListener('click', function() {
            if (taskIdToDelete) {
                const formData = new FormData();
                formData.append("action", "delete_task");
                formData.append("id_nhiem_vu", taskIdToDelete);

                fetch("/My-Web-Hotel/admin/api/staff-api.php", {
                    method: "POST",
                    body: formData,
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // Close modal manually if needed, though reload will handle it
                        const modalEl = document.getElementById('confirmDeleteTaskModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if(modal) modal.hide();
                        
                        alert("Xóa nhiệm vụ thành công!");
                        window.location.reload();
                    } else {
                        alert("Lỗi: " + data.message);
                    }
                })
                .catch((error) => {
                    alert("Có lỗi xảy ra khi xóa nhiệm vụ");
                });
            }
        });
    }
});
