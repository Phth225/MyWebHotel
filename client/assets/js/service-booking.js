// ============================================================
// AUTO-FILL SERVICE BOOKING DATA FROM SESSION STORAGE
// ============================================================
(function autoFillServiceBookingData() {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", fillServiceBookingForm);
  } else {
    fillServiceBookingForm();
  }

  function fillServiceBookingForm() {
    const bookingDataStr = sessionStorage.getItem("serviceBookingData");
    if (!bookingDataStr) return;

    try {
      const bookingData = JSON.parse(bookingDataStr);

      const dateInput = document.getElementById("serviceDate");
      const timeInput = document.getElementById("serviceTime");
      const quantityInput = document.getElementById("numberOfPeople");

      if (dateInput && bookingData.date) {
        dateInput.value = bookingData.date;
      }

      if (timeInput && bookingData.time) {
        timeInput.value = bookingData.time;
      }

      if (quantityInput && bookingData.guests) {
        quantityInput.value = bookingData.guests;
      }

      setTimeout(() => {
        updateSummary();
        if (
          window.IS_LOGGED_IN &&
          typeof loadAvailableVouchers === "function"
        ) {
          setTimeout(() => loadAvailableVouchers(), 300);
        }
      }, 200);

      // Clear after use
      sessionStorage.removeItem("serviceBookingData");
    } catch (error) {}
  }
})();

// ============================================================
// GLOBAL VARIABLES
// ============================================================
let currentDiscount = 0;
let appliedPromoCode = "";
let selectedVoucherId = null;
let selectedVoucher = null;
let availableVouchers = [];
let currentBookingData = null;

// ============================================================
// UTILITY FUNCTIONS
// ============================================================
function formatCurrency(amount) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(amount);
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("vi-VN", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
}

function formatTime(timeString) {
  return timeString;
}

function generateBookingCode() {
  const date = new Date();
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  const random = Math.floor(Math.random() * 1000)
    .toString()
    .padStart(3, "0");
  return `SV${year}${month}${day}${random}`;
}

// ============================================================
// CALCULATION FUNCTIONS
// ============================================================
function calculateServicePrice() {
  if (!SERVER_DATA || !SERVER_DATA.service) return 0;

  const basePrice = parseFloat(SERVER_DATA.service.price) || 0;
  const quantity =
    parseInt(document.getElementById("numberOfPeople")?.value) || 1;

  return basePrice * quantity;
}

function updateSummary() {
  const serviceTotal = calculateServicePrice();
  const discount = currentDiscount || 0;
  // FIXED: VAT phải tính sau khi trừ giảm giá
  const afterDiscount = Math.max(0, serviceTotal - discount);
  const vat = Math.round(afterDiscount * 0.1);
  const total = afterDiscount + vat;

  // Update summary display
  const totalPriceEl = document.getElementById("totalPrice");
  const vatAmountEl = document.getElementById("vatAmount");
  const summaryDiscountEl = document.getElementById("summaryDiscount");
  const finalTotalEl = document.getElementById("finalTotal");

  if (totalPriceEl) totalPriceEl.textContent = formatCurrency(serviceTotal);
  if (vatAmountEl) vatAmountEl.textContent = formatCurrency(vat);
  if (summaryDiscountEl)
    summaryDiscountEl.textContent =
      discount > 0 ? "- " + formatCurrency(discount) : "0 ₫";
  if (finalTotalEl) finalTotalEl.textContent = formatCurrency(total);
}

// ============================================================
// VOUCHER FUNCTIONS
// ============================================================
function loadAvailableVouchers() {
  // Chỉ load voucher khi đã đăng nhập
  if (!window.IS_LOGGED_IN) {
    const container = document.getElementById("voucherListContainer");
    if (container) container.style.display = "none";
    return;
  }

  const dateInput = document.getElementById("serviceDate");
  const timeInput = document.getElementById("serviceTime");

  if (!dateInput || !timeInput) {
    return;
  }

  const date = dateInput.value;
  const time = timeInput.value;

  if (!date || !time) {
    const container = document.getElementById("voucherListContainer");
    if (container) container.style.display = "none";
    return;
  }

  const serviceTotal = calculateServicePrice();
  const vat = Math.round(serviceTotal * 0.1);
  const totalAmount = serviceTotal + vat;

  let serviceId = null;
  if (typeof SERVER_DATA !== "undefined" && SERVER_DATA.service) {
    serviceId = SERVER_DATA.service.service_id;
  }

  const params = new URLSearchParams({
    apply_to: "service",
    total_amount: totalAmount,
  });

  if (serviceId) {
    params.append("service_id", serviceId);
  }

  fetch(`/My-Web-Hotel/client/controller/get-vouchers.php?${params}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.vouchers && data.vouchers.length > 0) {
        availableVouchers = data.vouchers;
        renderVoucherList(data.vouchers);
        document.getElementById("voucherListContainer").style.display = "block";
      } else {
        document.getElementById("voucherListContainer").style.display = "none";
      }
    })
    .catch((error) => {
      document.getElementById("voucherListContainer").style.display = "none";
    });
}

function renderVoucherList(vouchers) {
  const voucherList = document.getElementById("voucherList");
  if (!voucherList) return;

  voucherList.innerHTML = "";

  vouchers.forEach((voucher) => {
    const voucherItem = document.createElement("div");
    voucherItem.className = "voucher-item";
    voucherItem.style.cssText = `
      display: flex;
      align-items: center;
      padding: 10px 12px;
      margin-bottom: 8px;
      border: 1px solid #deb666;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s;
      background: #fffcf0;
    `;

    const discountText =
      voucher.discount_type === "percent"
        ? `Giảm ${voucher.discount_value}%`
        : `Giảm ${formatCurrency(voucher.discount_value)}`;

    const maxDiscountText = voucher.max_discount
      ? ` (tối đa ${formatCurrency(voucher.max_discount)})`
      : "";

    voucherItem.innerHTML = `
      <input type="radio" name="voucher" value="${voucher.voucher_id}" 
             id="voucher_${voucher.voucher_id}" 
             style="margin-right: 12px; cursor: pointer; width: 18px; height: 18px; flex-shrink: 0;"
             onchange="selectVoucher(${voucher.voucher_id})">
      <label for="voucher_${
        voucher.voucher_id
      }" style="flex: 1; cursor: pointer; margin: 0; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <span style="font-weight: 700; color: #4caf50; font-size: 16px; min-width: 120px; font-family: 'Courier New', monospace; letter-spacing: 0.5px;">
          ${voucher.code}
        </span>
        <span style="font-size: 15px; color: #333; flex: 1; min-width: 150px;">
          ${voucher.name}${
      voucher.is_featured
        ? '<span style="color: #ff6b6b; margin-left: 5px;">⭐</span>'
        : ""
    }
        </span>
        <span style="font-size: 15px; color: #4caf50; font-weight: 600; white-space: nowrap;">
          ${discountText}${maxDiscountText}
        </span>
      </label>
    `;

    voucherItem.addEventListener("mouseenter", function () {
      if (!this.querySelector('input[type="radio"]').checked) {
        this.style.borderColor = "#b69854";
        this.style.backgroundColor = "#fff8dc";
      }
    });

    voucherItem.addEventListener("mouseleave", function () {
      const radio = this.querySelector('input[type="radio"]');
      if (!radio || !radio.checked) {
        this.style.borderColor = "#deb666";
        this.style.backgroundColor = "#fffcf0";
      }
    });

    voucherItem.addEventListener("click", function (e) {
      if (e.target.type !== "radio") {
        const radio = this.querySelector('input[type="radio"]');
        if (radio) {
          radio.checked = true;
          selectVoucher(parseInt(radio.value));
        }
      }
    });

    voucherList.appendChild(voucherItem);
  });
}

function selectVoucher(voucherId) {
  selectedVoucherId = voucherId;
  selectedVoucher = availableVouchers.find((v) => v.voucher_id === voucherId);

  if (selectedVoucher) {
    document.getElementById("promoCode").value = selectedVoucher.code;
    calculateVoucherDiscount();

    // Highlight voucher được chọn
    document.querySelectorAll(".voucher-item").forEach((item) => {
      const radio = item.querySelector('input[type="radio"]');
      if (radio && parseInt(radio.value) === voucherId) {
        item.style.borderColor = "#b69854";
        item.style.backgroundColor = "#ffefd5";
        item.style.boxShadow = "0 2px 6px rgba(76, 175, 80, 0.15)";
      } else {
        item.style.borderColor = "#deb666";
        item.style.backgroundColor = "#fffcf0";
        item.style.boxShadow = "none";
      }
    });
  }
}

function calculateVoucherDiscount() {
  if (!selectedVoucher) {
    currentDiscount = 0;
    updateSummary();
    return;
  }

  const serviceTotal = calculateServicePrice();
  const vat = Math.round(serviceTotal * 0.1);
  const totalBeforeDiscount = serviceTotal + vat;

  let subtotal = serviceTotal;

  // Kiểm tra apply_to
  if (selectedVoucher.apply_to === "room") {
    alert("Voucher này chỉ áp dụng cho đặt phòng!");
    document.getElementById(`voucher_${selectedVoucherId}`).checked = false;
    selectedVoucherId = null;
    selectedVoucher = null;
    currentDiscount = 0;
    updateSummary();
    return;
  }

  if (subtotal < selectedVoucher.min_order) {
    alert(
      `Voucher yêu cầu đơn hàng tối thiểu ${formatCurrency(
        selectedVoucher.min_order
      )}`
    );
    document.getElementById(`voucher_${selectedVoucherId}`).checked = false;
    selectedVoucherId = null;
    selectedVoucher = null;
    currentDiscount = 0;
    updateSummary();
    return;
  }

  if (selectedVoucher.discount_type === "percent") {
    currentDiscount = Math.round(
      (subtotal * selectedVoucher.discount_value) / 100
    );
    if (
      selectedVoucher.max_discount &&
      currentDiscount > selectedVoucher.max_discount
    ) {
      currentDiscount = selectedVoucher.max_discount;
    }
  } else {
    currentDiscount = selectedVoucher.discount_value;
  }

  document.getElementById("discountAmount").textContent =
    formatCurrency(currentDiscount);
  document.getElementById("discountDisplay").style.display = "block";
  appliedPromoCode = selectedVoucher.code;
  updateSummary();
}

function applyPromoCode() {
  // Chỉ cho phép áp dụng voucher khi đã đăng nhập
  if (!window.IS_LOGGED_IN) {
    alert("Vui lòng đăng nhập để sử dụng voucher.");
    const promoInput = document.getElementById("promoCode");
    if (promoInput) promoInput.value = "";
    return;
  }

  const promoInput = document.getElementById("promoCode");
  const code = promoInput.value.trim().toUpperCase();

  if (!code) {
    alert("Vui lòng nhập mã khuyến mãi!");
    return;
  }

  const voucher = availableVouchers.find((v) => v.code.toUpperCase() === code);

  if (voucher) {
    const radio = document.getElementById(`voucher_${voucher.voucher_id}`);
    if (radio) {
      radio.checked = true;
      selectVoucher(voucher.voucher_id);
      alert(`✅ Áp dụng thành công!\n${voucher.name}`);
    } else {
      alert("❌ Voucher không khả dụng!");
    }
  } else {
    alert("❌ Mã khuyến mãi không hợp lệ!");
    currentDiscount = 0;
    appliedPromoCode = "";
    selectedVoucherId = null;
    selectedVoucher = null;
    document.getElementById("discountDisplay").style.display = "none";
    updateSummary();
  }
}
function initCloseVoucherBtn() {
  const closeBtn = document.querySelector("#discountDisplay .btn-close");
  if (!closeBtn) return;

  closeBtn.addEventListener("click", function () {
    // Reset các biến liên quan đến voucher
    currentDiscount = 0;
    appliedPromoCode = "";
    selectedVoucherId = null;
    selectedVoucher = null;

    // Reset giao diện hiển thị giảm giá
    const discountAmount = document.getElementById("discountAmount");
    if (discountAmount) discountAmount.textContent = "0 ₫";

    const discountDisplay = document.getElementById("discountDisplay");
    if (discountDisplay) {
      discountDisplay.classList.remove("active");
      discountDisplay.style.display = "none"; // Ẩn khối giảm giá
    }

    // Xóa text trong ô nhập mã khuyến mãi
    const promoInput = document.getElementById("promoCode");
    if (promoInput) promoInput.value = "";

    // Bỏ chọn radio voucher nếu có
    document.querySelectorAll('input[name="voucher"]').forEach((radio) => {
      radio.checked = false;
    });

    // Cập nhật lại tổng kết
    updateSummary();
  });
}

// Gọi hàm này sau khi render xong voucher list hoặc khi trang load
document.addEventListener("DOMContentLoaded", initCloseVoucherBtn);
// ============================================================
// FORM SUBMISSION
// ============================================================
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("serviceBookingForm");

  // Event listeners
  const quantityInput = document.getElementById("numberOfPeople");
  const dateInput = document.getElementById("serviceDate");
  const timeInput = document.getElementById("serviceTime");

  function validateDateTime() {
    const dateVal = dateInput.value;
    const timeVal = timeInput.value;

    if (!dateVal || !timeVal) return true; // Let submit handle empty check

    const selectedDateTime = new Date(`${dateVal}T${timeVal}`);
    const now = new Date();

    if (selectedDateTime < now) {
      showPaymentModal_Notification(
        "Vui lòng chọn thời gian trong tương lai! Ngày giờ đã được reset."
      );

      // Reset logic - maybe set to tomorrow same time or clear?
      // For now, just clear
      dateInput.value = "";
      timeInput.value = "";
      return false;
    }
    return true;
  }

  // Helper to show modal (reusing logic from showPaymentModal but simpler)
  function showPaymentModal_Notification(msg) {
    const msgEl = document.getElementById("notificationMessage");
    if (msgEl) msgEl.innerHTML = msg;

    const modalEl = document.getElementById("notificationModal");
    if (modalEl) {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();

      // Reset buttons to default state just in case
      const footer = modalEl.querySelector(".modal-footer");
      // Hide confirm button since this is just info
      const confirmBtn = document.getElementById("confirmPaymentBtn");
      if (confirmBtn) confirmBtn.style.display = "none";

      // Restore when hidden
      modalEl.addEventListener(
        "hidden.bs.modal",
        function () {
          if (confirmBtn) confirmBtn.style.display = "block";
        },
        { once: true }
      );
    } else {
      alert(msg);
    }
  }

  if (dateInput) {
    dateInput.addEventListener("change", validateDateTime);
  }
  if (timeInput) {
    timeInput.addEventListener("change", validateDateTime);
  }

  // Hook into existing listeners if any
  // ...

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const serviceDate = document.getElementById("serviceDate").value;
      const serviceTime = document.getElementById("serviceTime").value;
      const quantity =
        parseInt(document.getElementById("numberOfPeople").value) || 1;
      const paymentMethod = document.getElementById("paymentMethod").value;
      const notes = document.getElementById("notes").value;

      if (!serviceDate || !serviceTime) {
        alert("Vui lòng chọn ngày và giờ sử dụng dịch vụ!");
        return;
      }

      if (!paymentMethod) {
        alert("Vui lòng chọn phương thức thanh toán!");
        return;
      }

      // Validate thời gian phải ở tương lai
      const selectedDateTime = new Date(`${serviceDate}T${serviceTime}`);
      const now = new Date();
      if (selectedDateTime <= now) {
        alert("Thời gian sử dụng phải ở tương lai!");
        return;
      }

      const serviceTotal = calculateServicePrice();
      const discount = currentDiscount || 0;
      const vat = Math.round(Math.max(0, serviceTotal - discount) * 0.1);
      const total = Math.max(0, serviceTotal - discount + vat);

      // Kiểm tra xem có form khách vãng lai không
      const walkInFullNameEl = document.getElementById("walkInFullName");
      const isWalkIn = walkInFullNameEl !== null;

      // Khách vãng lai không được sử dụng voucher
      const voucherIdToSubmit =
        isWalkIn || !window.IS_LOGGED_IN ? null : selectedVoucherId;

      const formData = {
        serviceId: form.dataset.serviceId || null,
        serviceName: SERVER_DATA?.service?.service_name || "",
        serviceDate: serviceDate,
        serviceTime: serviceTime,
        quantity: quantity,
        servicePrice: serviceTotal,
        vat: vat,
        discount: isWalkIn ? 0 : discount, // Khách vãng lai không có discount từ voucher
        total: isWalkIn ? serviceTotal + vat : total,
        promoCode: isWalkIn ? "" : appliedPromoCode,
        voucherId: voucherIdToSubmit,
        promoDescription: isWalkIn
          ? ""
          : selectedVoucher
          ? selectedVoucher.name
          : "",
        paymentMethod: paymentMethod,
        notes: notes,
        bookingCode: generateBookingCode(),
      };

      // Thêm thông tin khách vãng lai nếu có
      if (isWalkIn) {
        formData.walkInFullName = walkInFullNameEl.value || "";
        formData.walkInPhone =
          document.getElementById("walkInPhone").value || "";
        formData.walkInEmail =
          document.getElementById("walkInEmail").value || "";
        formData.walkInIdNumber =
          document.getElementById("walkInIdNumber").value || "";
        formData.walkInAddress =
          document.getElementById("walkInAddress").value || "";
      }

      showInvoice(formData);
    });
  }

  // Event listeners - using variables defined above

  if (quantityInput) {
    quantityInput.addEventListener("input", () => {
      updateSummary();
      if (window.IS_LOGGED_IN) {
        loadAvailableVouchers();
      }
      if (selectedVoucher) calculateVoucherDiscount();
    });
    quantityInput.addEventListener("change", () => {
      updateSummary();
      if (window.IS_LOGGED_IN) {
        loadAvailableVouchers();
      }
      if (selectedVoucher) calculateVoucherDiscount();
    });
  }

  if (dateInput) {
    dateInput.addEventListener("change", () => {
      updateSummary();
      if (window.IS_LOGGED_IN) {
        loadAvailableVouchers();
      }
    });
  }

  if (timeInput) {
    timeInput.addEventListener("change", () => {
      updateSummary();
      if (window.IS_LOGGED_IN) {
        loadAvailableVouchers();
      }
    });
  }

  // Set min date
  const today = new Date().toISOString().split("T")[0];
  if (dateInput) {
    dateInput.min = today;
    if (!dateInput.value) {
      dateInput.value = today;
    }
  }
});

// ============================================================
// INVOICE FUNCTIONS
// ============================================================
function showInvoice(data) {
  currentBookingData = data;

  document.getElementById("bookingForm").style.display = "none";
  document.getElementById("invoiceContainer").style.display = "block";

  // Hiển thị thông tin khách vãng lai nếu có
  if (data.walkInFullName) {
    const invoiceWalkInName = document.getElementById("invoiceWalkInName");
    const invoiceWalkInEmail = document.getElementById("invoiceWalkInEmail");
    const invoiceWalkInPhone = document.getElementById("invoiceWalkInPhone");
    const invoiceWalkInIdNumber = document.getElementById(
      "invoiceWalkInIdNumber"
    );

    if (invoiceWalkInName) invoiceWalkInName.textContent = data.walkInFullName;
    if (invoiceWalkInEmail)
      invoiceWalkInEmail.textContent = data.walkInEmail || "-";
    if (invoiceWalkInPhone) invoiceWalkInPhone.textContent = data.walkInPhone;
    if (invoiceWalkInIdNumber)
      invoiceWalkInIdNumber.textContent = data.walkInIdNumber;
  }

  // Điền dữ liệu hiển thị vào invoice
  document.getElementById("invoiceServiceName").textContent = data.serviceName;

  // Thông tin chi tiết dịch vụ
  const detailsHTML = `
    <div class="invoice-row">
      <span class="invoice-label">Số người:</span>
      <span class="invoice-value">${data.quantity} người</span>
    </div>
  `;
  document.getElementById("invoiceServiceDetails").innerHTML = detailsHTML;

  // Thời gian
  document.getElementById("invoiceDate").textContent = formatDate(
    data.serviceDate
  );
  document.getElementById("invoiceTime").textContent = data.serviceTime;
  document.getElementById("invoiceQuantity").textContent =
    data.quantity + " người";

  // Khuyến mãi
  if (data.promoCode) {
    document.getElementById("invoicePromotionSection").style.display = "block";
    document.getElementById(
      "invoicePromotion"
    ).textContent = `${data.promoCode} - ${data.promoDescription}`;
  } else {
    document.getElementById("invoicePromotionSection").style.display = "none";
  }

  // Ghi chú
  if (data.notes) {
    document.getElementById("invoiceNotesSection").style.display = "block";
    document.getElementById("invoiceNotes").textContent = data.notes;
  } else {
    document.getElementById("invoiceNotesSection").style.display = "none";
  }

  // Thanh toán
  document.getElementById("invoiceSubtotal").textContent = formatCurrency(
    data.servicePrice
  );
  document.getElementById("invoiceVAT").textContent = formatCurrency(data.vat);

  if (data.discount > 0) {
    document.getElementById("invoiceDiscountRow").style.display = "flex";
    document.getElementById("invoiceDiscount").textContent =
      "- " + formatCurrency(data.discount);
  } else {
    document.getElementById("invoiceDiscountRow").style.display = "none";
  }

  document.getElementById("invoiceTotal").textContent = formatCurrency(
    data.total
  );
  document.getElementById("invoicePayment").textContent = data.paymentMethod;

  // Điền dữ liệu vào hidden inputs để submit
  document.getElementById("hiddenServiceId").value = data.serviceId || "";
  document.getElementById("hiddenServiceDate").value = data.serviceDate;
  document.getElementById("hiddenServiceTime").value = data.serviceTime;
  document.getElementById("hiddenQuantity").value = data.quantity;
  document.getElementById("hiddenNotes").value = data.notes || "";
  document.getElementById("hiddenServicePrice").value = data.servicePrice;
  document.getElementById("hiddenVAT").value = data.vat;
  document.getElementById("hiddenDiscount").value = data.discount;
  document.getElementById("hiddenTotal").value = data.total;
  // Chỉ set voucherId nếu đã đăng nhập
  const isWalkIn = document.getElementById("walkInFullName") !== null;
  document.getElementById("hiddenVoucherId").value =
    isWalkIn || !window.IS_LOGGED_IN ? "" : data.voucherId || "";
  document.getElementById("hiddenPaymentMethod").value = data.paymentMethod;

  // Điền thông tin khách vãng lai vào hidden inputs nếu có
  if (data.walkInFullName) {
    const hiddenWalkInFullName = document.getElementById(
      "hiddenWalkInFullName"
    );
    const hiddenWalkInPhone = document.getElementById("hiddenWalkInPhone");
    const hiddenWalkInEmail = document.getElementById("hiddenWalkInEmail");
    const hiddenWalkInIdNumber = document.getElementById(
      "hiddenWalkInIdNumber"
    );
    const hiddenWalkInAddress = document.getElementById("hiddenWalkInAddress");

    if (hiddenWalkInFullName)
      hiddenWalkInFullName.value = data.walkInFullName || "";
    if (hiddenWalkInPhone) hiddenWalkInPhone.value = data.walkInPhone || "";
    if (hiddenWalkInEmail) hiddenWalkInEmail.value = data.walkInEmail || "";
    if (hiddenWalkInIdNumber)
      hiddenWalkInIdNumber.value = data.walkInIdNumber || "";
    if (hiddenWalkInAddress)
      hiddenWalkInAddress.value = data.walkInAddress || "";
  }

  window.scrollTo({ top: 0, behavior: "smooth" });
}

function editBooking() {
  document.getElementById("bookingForm").style.display = "block";
  document.getElementById("invoiceContainer").style.display = "none";
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function saveInvoice() {
  const invoice = document.getElementById("invoiceContainer");
  const opt = {
    margin: 0.5,
    filename: "hoa-don-dich-vu.pdf",
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: "in", format: "a4", orientation: "portrait" },
  };
  if (window.html2pdf) {
    window.html2pdf().from(invoice).set(opt).save();
  } else {
    alert("Chức năng lưu hóa đơn cần thư viện html2pdf!");
  }
}

// ============================================================
// INITIALIZE
// ============================================================
window.addEventListener("DOMContentLoaded", function () {
  updateSummary();

  setTimeout(() => {
    const dateInput = document.getElementById("serviceDate");
    const timeInput = document.getElementById("serviceTime");
    if (dateInput && timeInput && dateInput.value && timeInput.value) {
      if (window.IS_LOGGED_IN) {
        loadAvailableVouchers();
      }
    }
  }, 500);
});
