// Hiển thị username
document.addEventListener("DOMContentLoaded", function () {
  if (window.CURRENT_USER_NAME) {
    document.getElementById("username-display").textContent =
      window.CURRENT_USER_NAME;
    document.getElementById("logout-btn").style.display = "block";
  }
});
// Tự động ẩn alert sau 3 giây
document.addEventListener("DOMContentLoaded", function () {
  const alertEl = document.querySelector(".alert");
  if (alertEl) {
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(alertEl);
      bsAlert.close();
    }, 3000);
  }
});
// Mở tab tương ứng nếu có ?tab=... trong URL
const params = new URLSearchParams(window.location.search);
const activeTab = params.get("tab");
if (activeTab) {
  const triggerEl = document.querySelector(`[href="#${activeTab}"]`);
  if (triggerEl) {
    const tab = new bootstrap.Tab(triggerEl);
    tab.show();
  }
}
document.addEventListener("DOMContentLoaded", function () {
  // Lấy tất cả các icon mắt
  document.querySelectorAll(".toggle-password").forEach((icon) => {
    icon.addEventListener("click", function () {
      const input = this.previousElementSibling; // input ngay trước icon
      const isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";
      // Đổi icon tương ứng
      this.classList.toggle("fa-eye");
      this.classList.toggle("fa-eye-slash");
    });
  });
});

// Xử lý nút đăng xuất
const logoutBtn = document.getElementById("logout-btn");
const logoutModal = new bootstrap.Modal(document.getElementById("logoutModal"));

logoutBtn?.addEventListener("click", function () {
  logoutModal.show();
});

// Xác nhận đăng xuất
document
  .getElementById("confirmLogout")
  ?.addEventListener("click", function () {
    window.location.href = "/My-Web-Hotel/client/controller/logout.php";
  });

// Hàm quay về trang chủ
function goHome() {
  window.location.href = "/My-Web-Hotel/client/index.php?page=home";
}

// Xử lý thay đổi ảnh đại diện
const avatarInput = document.getElementById("avatar-input");
const avatarPreview = document.getElementById("avatar-preview");
const sidebarAvatar = document.getElementById("sidebar-avatar");
const fileNameDisplay = document.getElementById("file-name");
const removeAvatarBtn = document.getElementById("remove-avatar");

avatarInput.addEventListener("change", function (e) {
  const file = e.target.files[0];
  if (file) {
    // Validate file type
    const allowedTypes = ["image/jpeg", "image/png", "image/jpg", "image/gif"];
    if (!allowedTypes.includes(file.type)) {
      alert("Chỉ chấp nhận file ảnh (JPG, PNG, GIF)!");
      avatarInput.value = "";
      return;
    }

    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert("Kích thước file quá lớn (tối đa 5MB)!");
      avatarInput.value = "";
      return;
    }

    // Preview image
    const reader = new FileReader();
    reader.onload = function (event) {
      avatarPreview.src = event.target.result;
      sidebarAvatar.src = event.target.result;
    };
    reader.readAsDataURL(file);

    fileNameDisplay.textContent = file.name;
    removeAvatarBtn.style.display = "inline-block";
  }
});

removeAvatarBtn.addEventListener("click", function () {
  avatarInput.value = "";
  avatarPreview.src = window.DEFAULT_AVATAR; // dùng biến từ PHP
  sidebarAvatar.src = window.DEFAULT_AVATAR; // dùng biến từ PHP
  fileNameDisplay.textContent = "Chưa chọn file";
  removeAvatarBtn.style.display = "none";
});
// Logic để active nav-item "Lịch sử đặt" khi hiển thị tab con
document.addEventListener("DOMContentLoaded", function () {
  const historyDropdownToggle = document.querySelector(
    '[href="#historyDropdown"]'
  );
  const historyRoomTab = document.querySelector('[href="#historyRoom"]');
  const historyServiceTab = document.querySelector('[href="#historyService"]');
  const historyDropdown = document.getElementById("historyDropdown");

  // Hàm để active parent nav-link
  function activateHistoryParent() {
    // Xóa active khỏi tất cả nav-link chính
    document.querySelectorAll(".nav-pills > .nav-link").forEach((link) => {
      link.classList.remove("active");
    });

    // Active nav-link "Lịch sử đặt"
    historyDropdownToggle.classList.add("active");

    // Mở dropdown
    const bsCollapse = new bootstrap.Collapse(historyDropdown, {
      toggle: false,
    });
    bsCollapse.show();
  }

  // Lắng nghe sự kiện khi tab được hiển thị
  historyRoomTab?.addEventListener("shown.bs.tab", function () {
    activateHistoryParent();
    // Active dropdown item hiện tại
    document.querySelectorAll(".dropdown-item-custom").forEach((item) => {
      item.classList.remove("active");
    });
    historyRoomTab.classList.add("active");
  });

  historyServiceTab?.addEventListener("shown.bs.tab", function () {
    activateHistoryParent();
    // Active dropdown item hiện tại
    document.querySelectorAll(".dropdown-item-custom").forEach((item) => {
      item.classList.remove("active");
    });
    historyServiceTab.classList.add("active");
  });

  // Xử lý khi click vào nav-link khác (không phải lịch sử)
  document
    .querySelectorAll(".nav-pills > .nav-link:not(.dropdown-toggle)")
    .forEach((link) => {
      link.addEventListener("shown.bs.tab", function () {
        // Xóa active khỏi dropdown toggle
        historyDropdownToggle.classList.remove("active");

        // Đóng dropdown
        const bsCollapse = bootstrap.Collapse.getInstance(historyDropdown);
        if (bsCollapse) {
          bsCollapse.hide();
        }

        // Xóa active khỏi tất cả dropdown items
        document.querySelectorAll(".dropdown-item-custom").forEach((item) => {
          item.classList.remove("active");
        });
      });
    });

  // Xử lý URL params để active đúng tab khi load trang
  const params = new URLSearchParams(window.location.search);
  const activeTab = params.get("tab");

  if (activeTab === "historyRoom" || activeTab === "historyService") {
    // Đợi một chút để Bootstrap khởi tạo xong
    setTimeout(() => {
      activateHistoryParent();

      // Active dropdown item tương ứng
      const targetTab = document.querySelector(`[href="#${activeTab}"]`);
      if (targetTab) {
        targetTab.classList.add("active");
      }
    }, 100);
  }

  // Xử lý click vào dropdown items
  document.querySelectorAll(".dropdown-item-custom").forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault();

      // Active parent
      activateHistoryParent();

      // Xóa active khỏi các dropdown items khác
      document.querySelectorAll(".dropdown-item-custom").forEach((i) => {
        i.classList.remove("active");
      });

      // Active item hiện tại
      this.classList.add("active");

      // Kích hoạt tab tương ứng
      const targetId = this.getAttribute("href");
      const targetTab = document.querySelector(targetId);
      if (targetTab) {
        const tab = new bootstrap.Tab(this);
        tab.show();
      }
    });
  });
});

// Hàm format service type
function formatServiceType(type) {
  const types = {
    Food: "Ăn uống",
    Spa: "Spa & Massage",
    Entertainment: "Giải trí",
    Transport: "Vận chuyển",
    Laundry: "Giặt ủi",
    Other: "Khác",
  };
  return types[type] || type;
}
// ===== HÀM HỖ TRỢ =====

function setElementValue(element, value) {
  if (element) {
    element.textContent = value || "N/A";
  }
}

function formatDate(dateString) {
  if (!dateString) return "N/A";
  try {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
  } catch (e) {
    return dateString;
  }
}

function formatDateTime(dateString) {
  if (!dateString) return "N/A";
  try {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");
    return `${day}/${month}/${year} ${hours}:${minutes}`;
  } catch (e) {
    return dateString;
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return "0 ₫";
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(amount);
}

function formatPaymentMethod(method) {
  const methods = {
    "credit card": "Thẻ tín dụng",
    "bank transfer": "Chuyển khoản",
    "e-wallet": "Ví điện tử",
  };
  return methods[method?.toLowerCase()] || method || "Tiền mặt";
}

function formatBookingMethod(method) {
  const methods = {
    online: "Đặt trực tuyến",
    offline: "Đặt tại quầy",
  };
  return methods[method?.toLowerCase()] || method || "Đặt trực tuyến";
}

function getBookingStatusBadge(status) {
  const badges = {
    pending:
      '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Chờ xác nhận</span>',
    confirmed:
      '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Đã xác nhận</span>',
    cancelled:
      '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Đã hủy</span>',
    completed:
      '<span class="badge bg-primary"><i class="fas fa-check-double me-1"></i>Hoàn thành</span>',
  };
  return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
}

function getPaymentStatusBadge(status) {
  const badges = {
    unpaid:
      '<span class="badge bg-warning text-dark"><i class="fas fa-check-circle me-1"></i>Đã cọc</span>',
    paid: '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Đã thanh toán</span>',
    refunded:
      '<span class="badge bg-info"><i class="fas fa-undo me-1"></i>Đã hoàn tiền</span>',
  };
  return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
}

// Hàm hiển thị thông báo
function showAlert(message, type = "info") {
  // Kiểm tra xem có thư viện alert nào không (Bootstrap toast, SweetAlert, v.v.)
  if (typeof Swal !== "undefined") {
    Swal.fire({
      icon: type === "danger" ? "error" : type,
      title: type === "danger" ? "Lỗi!" : "Thông báo",
      text: message,
      timer: 3000,
      showConfirmButton: false,
    });
  } else {
    alert(message);
  }
}

// ===== XỬ LÝ MODAL ĐẶT PHÒNG =====
// Event listener for view detail buttons
document.addEventListener("DOMContentLoaded", function () {
  // Handle click on view detail buttons
  document.addEventListener("click", function (e) {
    const viewDetailBtn = e.target.closest(".view-detail-btn");
    if (viewDetailBtn) {
      e.preventDefault();

      try {
        // Lấy dữ liệu đã encode base64
        const bookingDataBase64 = viewDetailBtn.getAttribute(
          "data-booking-base64"
        );

        if (!bookingDataBase64) {
          alert("Không tìm thấy thông tin đặt phòng!");
          return;
        }

        // Decode base64 và parse JSON
        const bookingDataJson = atob(bookingDataBase64);
        const data = JSON.parse(bookingDataJson);

        // Hiển thị modal
        showBookingDetails(data);
      } catch (error) {
        alert("Có lỗi khi tải thông tin đặt phòng. Vui lòng thử lại!");
      }
    }
  });
});

// Main function to show booking details in modal - ENHANCED VERSION
function showBookingDetails(bookingData) {
  try {
    const modalElement = document.getElementById("roomDetailModal");
    if (!modalElement) {
      alert("Không tìm thấy modal!");
      return;
    }

    const modal = new bootstrap.Modal(modalElement);

    // Helper functions
    function setElementText(id, text, defaultText = "N/A") {
      const element = document.getElementById(id);
      if (element) {
        element.textContent = text || defaultText;
      } else {
      }
    }

    function setElementHTML(id, html, defaultHtml = "") {
      const element = document.getElementById(id);
      if (element) {
        element.innerHTML = html || defaultHtml;
      } else {
      }
    }

    // ===== THÔNG TIN CƠ BẢN =====
    setElementText("modal-booking-id", bookingData.booking_id);
    setElementText("modal-room-number", bookingData.room_number);
    setElementText("modal-room-type", bookingData.room_type_name);
    setElementText("modal-floor", `Tầng ${bookingData.floor || "N/A"}`);
    setElementText("modal-quantity", `${bookingData.quantity || 1} khách`);

    // ===== THỜI GIAN =====
    setElementText("modal-booking-date", formatDate(bookingData.booking_date));
    setElementText("modal-checkin", formatDate(bookingData.check_in_date));
    setElementText("modal-checkout", formatDate(bookingData.check_out_date));
    setElementText("modal-nights", `${bookingData.nights || 0} đêm`);

    // ===== THANH TOÁN - FIXED VERSION =====
    const roomCharge = parseFloat(bookingData.room_charge) || 0;
    const serviceCharge = parseFloat(bookingData.service_charge) || 0;
    const vat = parseFloat(bookingData.vat) || 0;
    const otherFees = parseFloat(bookingData.other_fees) || 0;
    const deposit = parseFloat(bookingData.deposit) || 0;
    const totalAmount = parseFloat(bookingData.total_amount) || 0;

    // Tính discount từ note hoặc tính ngược từ các số liệu
    let discount = 0;

    // Cách 1: Parse từ note nếu có voucher
    if (bookingData.note && bookingData.note.includes("Giảm giá:")) {
      const discountMatch = bookingData.note.match(/Giảm giá:\s*([\d.,]+)/);
      if (discountMatch) {
        discount = parseFloat(discountMatch[1].replace(/[.,]/g, ""));
      }
    }

    // Cách 2: Tính ngược từ công thức
    // total_amount = (room_charge + service_charge - discount) + vat + other_fees
    // => discount = room_charge + service_charge + vat + other_fees - total_amount
    if (discount === 0) {
      const calculatedDiscount =
        roomCharge + serviceCharge + vat + otherFees - totalAmount;
      if (calculatedDiscount > 0) {
        discount = calculatedDiscount;
      }
    }

    // Tính subtotal (tạm tính trước VAT)
    const subtotal = roomCharge + serviceCharge - discount;

    // Hiển thị các khoản
    setElementText("modal-room-charge", formatMoney(roomCharge));
    setElementText("modal-service-charge", formatMoney(serviceCharge));
    setElementText("modal-vat", formatMoney(vat));
    setElementText("modal-deposit", formatMoney(deposit));
    setElementText("modal-total", formatMoney(totalAmount));
    setElementText("modal-subtotal", formatMoney(subtotal));

    // Hiển thị discount nếu có
    const discountRow = document.getElementById("modal-discount-row");
    if (discount > 0) {
      discountRow.style.display = "table-row";
      setElementText("modal-discount", "- " + formatMoney(discount));
    } else {
      discountRow.style.display = "none";
    }

    // Ẩn/hiện tiền dịch vụ
    const serviceChargeRow = document.getElementById(
      "modal-service-charge-row"
    );
    if (serviceCharge > 0) {
      serviceChargeRow.style.display = "table-row";
    } else {
      serviceChargeRow.style.display = "none";
    }

    // ===== LOG DEBUG =====
    console.log({
      roomCharge,
      serviceCharge,
      discount,
      subtotal,
      vat,
      otherFees,
      totalAmount,
      deposit,
      calculation: `${roomCharge} + ${serviceCharge} - ${discount} + ${vat} + ${otherFees} = ${totalAmount}`,
    });

    // ===== THÔNG TIN KHÁC =====
    setElementText(
      "modal-booking-method",
      formatBookingMethod(bookingData.booking_method)
    );
    setElementText(
      "modal-payment-method",
      formatPaymentMethod(bookingData.payment_method)
    );

    setElementHTML(
      "modal-booking-status",
      getBookingStatusBadge(bookingData.booking_status.toLowerCase())
    );
    setElementHTML(
      "modal-payment-status",
      getPaymentStatusBadge(bookingData.payment_status.toLowerCase())
    );

    // Thời gian thanh toán
    const paymentTimeRow = document.getElementById("modal-payment-time-row");
    if (paymentTimeRow) {
      if (bookingData.payment_time) {
        paymentTimeRow.style.display = "block";
        setElementText(
          "modal-payment-time",
          new Date(bookingData.payment_time).toLocaleString("vi-VN")
        );
      } else {
        paymentTimeRow.style.display = "none";
      }
    }

    // ===== HIỂN THỊ DỊCH VỤ =====
    const servicesSection = document.getElementById("modal-services-section");
    const servicesList = document.getElementById("modal-services-list");
    const serviceCount = document.getElementById("modal-service-count");

    if (servicesSection && servicesList && serviceCount) {
      if (
        bookingData.services &&
        Array.isArray(bookingData.services) &&
        bookingData.services.length > 0
      ) {
        servicesSection.style.display = "block";
        serviceCount.textContent = bookingData.services.length;

        servicesList.innerHTML = "";

        bookingData.services.forEach((service, index) => {
          const row = document.createElement("tr");

          const serviceName = service.service_name || "N/A";
          const serviceDesc = service.description
            ? `<br><small class="text-muted">${service.description}</small>`
            : "";
          const serviceType = service.service_type || "N/A";
          const serviceUnit = service.unit || "N/A";
          const servicePrice = formatMoney(service.unit_price || 0);

          row.innerHTML = `
            <td>${index + 1}</td>
            <td>
              <strong>${serviceName}</strong>
              ${serviceDesc}
            </td>
            <td>${serviceUnit}</td>
            <td class="text-end fw-bold">${servicePrice}</td>
          `;
          servicesList.appendChild(row);
        });
      } else {
        servicesSection.style.display = "none";
      }
    }

    // ===== YÊU CẦU ĐẶC BIỆT =====
    const specialRequest = document.getElementById("modal-special-request");
    if (specialRequest) {
      if (
        bookingData.special_request &&
        bookingData.special_request.trim() !== ""
      ) {
        specialRequest.style.display = "block";
        setElementText(
          "modal-special-request-text",
          bookingData.special_request
        );
      } else {
        specialRequest.style.display = "none";
      }
    }

    // ===== GHI CHÚ TỪ INVOICE =====
    const invoiceNote = document.getElementById("modal-invoice-note");
    if (invoiceNote) {
      if (bookingData.note && bookingData.note.trim() !== "") {
        invoiceNote.style.display = "block";
        setElementText("modal-invoice-note-text", bookingData.note);
      } else {
        invoiceNote.style.display = "none";
      }
    }

    // Hiển thị modal
    modal.show();
  } catch (error) {
    console.error("Error showing booking details:", error);
    alert("Có lỗi khi hiển thị chi tiết đặt phòng: " + error.message);
  }
}
// ===== XỬ LÝ MODAL DỊCH VỤ =====
document.addEventListener("click", function (e) {
  const viewServiceBtn = e.target.closest(".view-service-detail-btn");
  if (!viewServiceBtn) return;

  e.preventDefault();

  try {
    const serviceDataBase64 = viewServiceBtn.getAttribute(
      "data-service-base64"
    );
    if (!serviceDataBase64) {
      showAlert("Không tìm thấy thông tin dịch vụ!", "danger");
      return;
    }

    const serviceDataJson = atob(serviceDataBase64);
    const data = JSON.parse(serviceDataJson);

    showServiceDetails(data);
  } catch (error) {
    showAlert("Có lỗi khi tải thông tin dịch vụ. Vui lòng thử lại!", "danger");
  }
});

// Cache các phần tử modal để tối ưu hiệu suất
let serviceModal = null;
let modalElements = {};

function initServiceModal() {
  if (serviceModal) return serviceModal;

  const modalElement = document.getElementById("serviceDetailModal");
  if (!modalElement) {
    console.error("Modal element not found");
    return null;
  }

  serviceModal = new bootstrap.Modal(modalElement);

  // Cache tất cả các phần tử modal
  modalElements = {
    modalElement,
    invoiceId: document.getElementById("modal-service-invoice-id"),
    serviceName: document.getElementById("modal-service-name"),
    serviceType: document.getElementById("modal-service-type"),
    serviceDescription: document.getElementById("modal-service-description"),
    serviceQuantity: document.getElementById("modal-service-quantity"),
    bookingDate: document.getElementById("modal-service-booking-date"),
    usageDate: document.getElementById("modal-service-usage-date"),
    usageTime: document.getElementById("modal-service-usage-time"),
    servicePrice: document.getElementById("modal-service-price"),
    serviceVat: document.getElementById("modal-service-vat"),
    serviceTotal: document.getElementById("modal-service-total"),
    servicePayment: document.getElementById("modal-service-payment"),
    discountRow: document.getElementById("modal-service-discount-row"),
    serviceDiscount: document.getElementById("modal-service-discount"),
    noteSection: document.getElementById("modal-service-note"),
    noteText: document.getElementById("modal-service-note-text"),
    bookingMethod: document.getElementById("modal-booking-service-method"),
    paymentMethod: document.getElementById("modal-payment-service-method"),
    paymentTimeRow: document.getElementById("modal-payment-service-time-row"),
    paymentTime: document.getElementById("modal-payment-service-time"),
    bookingStatus: document.getElementById("modal-booking-service-status"),
    paymentStatus: document.getElementById("modal-payment-service-status"),
  };

  return serviceModal;
}

function showServiceDetails(serviceData) {
  try {
    // Khởi tạo modal nếu chưa có
    const modal = initServiceModal();
    if (!modal) {
      showAlert(
        "Không thể tải chi tiết dịch vụ. Vui lòng thử lại sau.",
        "danger"
      );
      return;
    }

    // Kiểm tra dữ liệu đầu vào
    if (!serviceData) {
      return;
    }

    const servicePrice =
      parseFloat(serviceData.unit_price || 0) *
      parseInt(serviceData.quantity || 1, 10);
    const discount = parseFloat(serviceData.discount) || 0;

    // Tính VAT = (servicePrice - discount) * 10%
    const subtotal = servicePrice - discount;
    const vat = subtotal * 0.1; // 10% VAT

    // Total = subtotal + VAT
    const total = subtotal + vat;

    const {
      invoiceId,
      serviceName,
      serviceType,
      serviceDescription,
      serviceQuantity,
      bookingDate,
      usageDate,
      usageTime,
      servicePrice: priceEl,
      serviceVat: vatEl,
      serviceTotal: totalEl,
      servicePayment: paymentEl,
      discountRow,
      serviceDiscount: discountEl,
      noteSection,
      noteText,
      bookingMethod,
      paymentMethod,
      paymentTimeRow,
      paymentTime,
      bookingStatus,
      paymentStatus,
    } = modalElements;

    // Điền thông tin cơ bản
    setElementValue(invoiceId, serviceData.invoice_id);
    setElementValue(serviceName, serviceData.service_name);
    setElementValue(serviceType, formatServiceType(serviceData.service_type));
    setElementValue(
      serviceDescription,
      serviceData.description || "Không có mô tả"
    );
    setElementValue(
      serviceQuantity,
      `${serviceData.quantity || 1} ${serviceData.unit || "người"}`
    );

    // Điền thông tin thời gian
    setElementValue(bookingDate, formatDate(serviceData.created_at));
    setElementValue(usageDate, formatDate(serviceData.usage_date));
    setElementValue(usageTime, serviceData.usage_time || "Theo yêu cầu");

    // Điền thông tin thanh toán
    setElementValue(priceEl, formatMoney(servicePrice));
    setElementValue(vatEl, formatMoney(vat));
    setElementValue(totalEl, formatMoney(total));
    setElementValue(paymentEl, formatPaymentMethod(serviceData.payment_method));

    // Xử lý giảm giá
    if (discount > 0 && discountEl && discountRow) {
      discountRow.style.display = "table-row";
      setElementValue(discountEl, `- ${formatMoney(discount)}`);
    } else if (discountRow) {
      discountRow.style.display = "none";
    }

    // Xử lý ghi chú
    if (noteSection && noteText) {
      const hasNote = serviceData.note && serviceData.note.trim() !== "";
      noteSection.style.display = hasNote ? "block" : "none";
      if (hasNote) {
        setElementValue(noteText, serviceData.note);
      }
    }

    // Điền phương thức đặt dịch vụ
    if (bookingMethod) {
      setElementValue(
        bookingMethod,
        formatBookingMethod(serviceData.booking_method)
      );
    }

    // Điền phương thức thanh toán
    if (paymentMethod) {
      setElementValue(
        paymentMethod,
        formatPaymentMethod(serviceData.payment_method)
      );
    }

    // Xử lý thời gian thanh toán
    if (paymentTimeRow && paymentTime) {
      if (serviceData.payment_time) {
        paymentTimeRow.style.display = "block";
        setElementValue(paymentTime, formatDateTime(serviceData.payment_time));
      } else {
        paymentTimeRow.style.display = "none";
      }
    }

    // Trạng thái đặt dịch vụ
    if (bookingStatus) {
      let baseBookingStatus = serviceData.booking_status
        ? serviceData.booking_status.toLowerCase()
        : "pending";
      bookingStatus.innerHTML = getBookingStatusBadge(baseBookingStatus);
    }

    // Trạng thái thanh toán
    if (paymentStatus) {
      let basePaymentStatus = serviceData.payment_status
        ? serviceData.payment_status.toLowerCase()
        : "unpaid";
      paymentStatus.innerHTML = getPaymentStatusBadge(basePaymentStatus);
    }

    // Hiển thị modal
    modal.show();
  } catch (error) {
    showAlert(
      "Có lỗi khi hiển thị chi tiết dịch vụ. Vui lòng thử lại sau.",
      "danger"
    );
  }
}

/**
 * Hàm khởi tạo phân trang cho bảng
 * @param {string} tableId - ID của bảng cần phân trang
 * @param {string} paginationContainerId - ID của container phân trang
 * @param {string} pageInfoId - ID của phần hiển thị thông tin trang
 * @param {string} prevButtonId - ID của nút trang trước
 * @param {string} nextButtonId - ID của nút trang sau
 * @param {number} itemsPerPage - Số dòng mỗi trang (mặc định: 5)
 */
function initPagination(
  tableId,
  paginationContainerId,
  pageInfoId,
  prevButtonId,
  nextButtonId,
  itemsPerPage = 5
) {
  const table = document.getElementById(tableId);
  if (!table) {
    return;
  }

  const tbody = table.getElementsByTagName("tbody")[0];
  if (!tbody) {
    console.warn(`Không tìm thấy tbody trong bảng ${tableId}`);
    return;
  }

  const rows = tbody.getElementsByTagName("tr");
  if (!rows || rows.length === 0) {
    console.warn(`Không có dòng dữ liệu nào trong bảng ${tableId}`);
    return;
  }

  let currentPage = 1;
  const paginationContainer = document.getElementById(paginationContainerId);
  const pageInfo = document.getElementById(pageInfoId);
  const prevButton = document.getElementById(prevButtonId);
  const nextButton = document.getElementById(nextButtonId);

  // Kiểm tra xem tất cả các phần tử cần thiết có tồn tại không
  if (!paginationContainer || !pageInfo || !prevButton || !nextButton) {
    console.warn(
      `Thiếu một số phần tử phân trang cần thiết cho bảng ${tableId}`
    );
    return;
  }

  // Chỉ khởi tạo phân trang nếu có nhiều hơn itemsPerPage dòng
  if (rows.length > itemsPerPage) {
    paginationContainer.style.display = "block";
    const totalPages = Math.ceil(rows.length / itemsPerPage);

    // Function to show the current page
    function showPage(page) {
      // Ẩn tất cả các dòng
      Array.from(rows).forEach((row) => {
        if (row && row.style) {
          row.style.display = "none";
        }
      });

      // Chỉ hiển thị các dòng của trang hiện tại
      const start = (page - 1) * itemsPerPage;
      const end = Math.min(start + itemsPerPage, rows.length);

      for (let i = start; i < end; i++) {
        if (rows[i] && rows[i].style) {
          rows[i].style.display = "";
        }
      }

      // Cập nhật thông tin phân trang
      if (pageInfo) {
        pageInfo.textContent = `Trang ${page} / ${totalPages}`;
      }

      // Cập nhật trạng thái các nút
      if (prevButton) prevButton.disabled = page === 1;
      if (nextButton) nextButton.disabled = page >= totalPages;
    }

    // Sự kiện cho nút trang trước
    if (prevButton) {
      prevButton.addEventListener("click", () => {
        if (currentPage > 1) {
          currentPage--;
          showPage(currentPage);
        }
      });
    }

    // Sự kiện cho nút trang sau
    if (nextButton) {
      nextButton.addEventListener("click", () => {
        if (currentPage < Math.ceil(rows.length / itemsPerPage)) {
          currentPage++;
          showPage(currentPage);
        }
      });
    }

    // Hiển thị trang đầu tiên khi khởi tạo
    showPage(1);
  } else {
    // Nếu ít hơn itemsPerPage dòng, ẩn phân trang
    paginationContainer.style.display = "none";
  }
}

// Sử dụng hàm chung để khởi tạo phân trang cho cả hai bảng
document.addEventListener("DOMContentLoaded", function () {
  // Khởi tạo phân trang cho bảng dịch vụ
  initPagination(
    "tableServiceInvoice",
    "servicePaginationContainer",
    "servicePageInfo",
    "servicePrevPage",
    "serviceNextPage",
    5
  );

  // Khởi tạo phân trang cho bảng phòng
  initPagination(
    "tableRoomInvoice",
    "paginationContainer",
    "pageInfo",
    "prevPage",
    "nextPage",
    5
  );
});
