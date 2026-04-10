// Simple utilities for Services Manager
function editService(id, modalId) {
  const modalTitle = document.getElementById("serviceModalLabel");
  if (modalTitle) modalTitle.textContent = "Chỉnh Sửa Dịch Vụ";
  // In thực tế sẽ load dữ liệu theo id; demo set mã vào input
  document.getElementById("serviceId").value = id;
  const modal = new bootstrap.Modal(document.getElementById(modalId));
  modal.show();
}

function deleteService(id) {
  if (confirm("Xóa dịch vụ " + id + " ?")) {
  }
}

function saveService() {
  const id = document.getElementById("serviceId").value.trim();
  const name = document.getElementById("serviceName").value.trim();
  const type = document.getElementById("serviceType").value;
  const unit = document.getElementById("serviceUnit").value.trim();
  const price = document.getElementById("servicePrice").value;
  if (!id || !name || !type || !unit || price === "") {
    alert("Vui lòng nhập đầy đủ thông tin bắt buộc.");
    return;
  }
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("serviceModal")
  );
  modal && modal.hide();
}

// Filters
function filterTableByInputs(searchInputId, statusSelectId, tableId) {
  const searchTerm = (
    document.getElementById(searchInputId)?.value || ""
  ).toLowerCase();
  const statusValue = document.getElementById(statusSelectId)?.value || "";
  const table = document.getElementById(tableId);
  if (!table) return;
  const rows = table.getElementsByTagName("tr");
  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const rowText = row.textContent.toLowerCase();
    let ok = rowText.includes(searchTerm);
    if (statusValue) {
      const statusText =
        row.cells[row.cells.length - 2].textContent.toLowerCase();
      if (statusValue === "active") ok = ok && statusText.includes("đang");
      if (statusValue === "inactive") ok = ok && statusText.includes("tạm");
    }
    row.style.display = ok ? "" : "none";
  }
}

document
  .getElementById("searchRoomService")
  ?.addEventListener("input", () =>
    filterTableByInputs(
      "searchRoomService",
      "statusRoomService",
      "tableRoomService"
    )
  );
document
  .getElementById("statusRoomService")
  ?.addEventListener("change", () =>
    filterTableByInputs(
      "searchRoomService",
      "statusRoomService",
      "tableRoomService"
    )
  );

document
  .getElementById("searchStandaloneService")
  ?.addEventListener("input", () =>
    filterTableByInputs(
      "searchStandaloneService",
      "statusStandaloneService",
      "tableStandaloneService"
    )
  );
document
  .getElementById("statusStandaloneService")
  ?.addEventListener("change", () =>
    filterTableByInputs(
      "searchStandaloneService",
      "statusStandaloneService",
      "tableStandaloneService"
    )
  );
// Hàm mở modal Sửa từ modal Xem chi tiết
function editServiceFromView(id) {
  // Đóng modal view hiện tại
  const viewModal = bootstrap.Modal.getInstance(
    document.getElementById("viewServiceModal" + id)
  );
  if (viewModal) {
    viewModal.hide();
  }

  // Chuyển đến trang edit
  setTimeout(function () {
    editService(id);
  }, 300);
}
