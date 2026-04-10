// ============================================================
// AUTO-FILL BOOKING DATA FROM SESSION STORAGE
// ============================================================
(function autoFillBookingData() {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", fillBookingForm);
  } else {
    fillBookingForm();
  }

  function fillBookingForm() {
    const bookingDataStr = sessionStorage.getItem("bookingData");
    if (!bookingDataStr) return;

    try {
      const bookingData = JSON.parse(bookingDataStr);

      // Điền thông tin phòng
      const roomNameInput = document.getElementById("roomName");
      const roomTypeSelect = document.getElementById("roomType");
      const guestCountInput = document.getElementById("guestCount");
      const childCountInput = document.getElementById("childCount");
      const roomPriceInput = document.getElementById("roomPrice");
      const checkinInput = document.getElementById("checkinTime");
      const checkoutInput = document.getElementById("checkoutTime");

      if (roomNameInput && bookingData.roomName) {
        roomNameInput.value = bookingData.roomName;
      }

      if (roomTypeSelect && bookingData.roomType) {
        roomTypeSelect.value = bookingData.roomType;
      }

      if (guestCountInput && bookingData.adults) {
        guestCountInput.value = bookingData.adults;
      }

      if (childCountInput) {
        childCountInput.value = bookingData.children || 0;
      }

      if (checkinInput && bookingData.checkin) {
        checkinInput.value = bookingData.checkin;
      }

      if (checkoutInput && bookingData.checkout) {
        checkoutInput.value = bookingData.checkout;
      }

      if (roomPriceInput && bookingData.roomPrice) {
        roomPriceInput.value = bookingData.roomPrice;
      }

      const form = document.getElementById("roomBookingForm");
      if (form && bookingData.roomId) {
        form.dataset.roomId = bookingData.roomId;
      }

      setTimeout(() => {
        updateSummary();
        // Chỉ load voucher khi đã đăng nhập
        if (
          window.IS_LOGGED_IN &&
          typeof loadAvailableVouchers === "function"
        ) {
          setTimeout(() => loadAvailableVouchers(), 300);
        }
      }, 200);
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

function formatDateTime(dateString) {
  const date = new Date(dateString);
  return date.toLocaleString("vi-VN", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function calculateNights(checkin, checkout) {
  const start = new Date(checkin);
  const end = new Date(checkout);
  const diffTime = Math.abs(end - start);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays;
}

// ============================================================
// CALCULATION FUNCTIONS
// ============================================================
function calculateServiceTotal() {
  const checkboxes = document.querySelectorAll(
    'input[name="services"]:checked'
  );
  let total = 0;
  const checkin = document.getElementById("checkinTime").value;
  const checkout = document.getElementById("checkoutTime").value;
  const nights = checkin && checkout ? calculateNights(checkin, checkout) : 1;

  checkboxes.forEach((cb) => {
    const price = parseInt(cb.dataset.price);
    const serviceName = cb.value;

    // Dịch vụ theo đêm
    if (
      serviceName.toLowerCase().includes("breakfast") ||
      serviceName.toLowerCase().includes("minibar") ||
      serviceName.toLowerCase().includes("bữa sáng")
    ) {
      total += price * nights;
    } else {
      total += price;
    }
  });

  return total;
}

function updateSummary() {
  const roomPriceInput = document.getElementById("roomPrice");
  const checkinInput = document.getElementById("checkinTime");
  const checkoutInput = document.getElementById("checkoutTime");

  if (!roomPriceInput || !checkinInput || !checkoutInput) return;

  const roomPricePerNight =
    parseInt(roomPriceInput.value.replace(/[^\d]/g, "")) || 0;
  const checkin = checkinInput.value;
  const checkout = checkoutInput.value;

  let nights = 1;
  let totalRoomPrice = roomPricePerNight;

  if (checkin && checkout) {
    nights = calculateNights(checkin, checkout);
    totalRoomPrice = roomPricePerNight * nights;

    const nightCountDisplay = document.getElementById("nightCountDisplay");
    if (nightCountDisplay) {
      nightCountDisplay.textContent = nights;
    }
  }

  const serviceTotal = calculateServiceTotal();
  const discount = currentDiscount || 0;
  // Calculate VAT based on amount after discount
  const subtotalBeforeVat = Math.max(
    0,
    totalRoomPrice + serviceTotal - discount
  );
  const vat = Math.round(subtotalBeforeVat * 0.1);
  const total = subtotalBeforeVat + vat;
  const deposit = Math.round(total * 0.3);

  document.getElementById("summaryRoomPrice").textContent =
    formatCurrency(totalRoomPrice);
  document.getElementById("summaryServicePrice").textContent =
    formatCurrency(serviceTotal);
  document.getElementById("summaryDiscount").textContent =
    discount > 0 ? "- " + formatCurrency(discount) : "0 ₫";

  const vatDisplay = document.getElementById("summaryVat");
  if (vatDisplay) vatDisplay.textContent = formatCurrency(vat);

  document.getElementById("summaryTotal").textContent = formatCurrency(total);
  document.getElementById("summaryDeposit").textContent =
    formatCurrency(deposit);
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

  const checkinInput = document.getElementById("checkinTime");
  const checkoutInput = document.getElementById("checkoutTime");
  const roomPriceInput = document.getElementById("roomPrice");

  if (!checkinInput || !checkoutInput || !roomPriceInput) {
    return;
  }

  const checkin = checkinInput.value;
  const checkout = checkoutInput.value;

  if (!checkin || !checkout) {
    const container = document.getElementById("voucherListContainer");
    if (container) container.style.display = "none";
    return;
  }

  const roomPricePerNight =
    parseInt(roomPriceInput.value.replace(/[^\d]/g, "")) || 0;
  const nights = calculateNights(checkin, checkout);
  const totalRoomPrice = roomPricePerNight * nights;
  const serviceTotal = calculateServiceTotal();
  const totalAmount = totalRoomPrice + serviceTotal;

  const form = document.getElementById("roomBookingForm");
  const roomId = form?.dataset?.roomId || null;

  const selectedServices = Array.from(
    document.querySelectorAll('input[name="services"]:checked')
  )
    .map((cb) => cb.dataset.serviceId)
    .filter(Boolean);

  const params = new URLSearchParams({
    apply_to: "room",
    total_amount: totalAmount,
    nights: nights,
    num_rooms: 1,
  });

  if (roomId) params.append("room_id", roomId);
  if (selectedServices.length > 0)
    params.append("service_ids", selectedServices.join(","));

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

  const roomPriceInput = document.getElementById("roomPrice");
  const checkinInput = document.getElementById("checkinTime");
  const checkoutInput = document.getElementById("checkoutTime");

  if (!roomPriceInput || !checkinInput || !checkoutInput) return;

  const roomPricePerNight =
    parseInt(roomPriceInput.value.replace(/[^\d]/g, "")) || 0;
  const nights =
    checkinInput.value && checkoutInput.value
      ? calculateNights(checkinInput.value, checkoutInput.value)
      : 1;
  const totalRoomPrice = roomPricePerNight * nights;
  const serviceTotal = calculateServiceTotal();

  let subtotal = 0;
  if (selectedVoucher.apply_to === "room") {
    subtotal = totalRoomPrice;
  } else if (selectedVoucher.apply_to === "service") {
    subtotal = serviceTotal;
  } else {
    subtotal = totalRoomPrice + serviceTotal;
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
  document.getElementById("discountDisplay").classList.add("active");
  appliedPromoCode = selectedVoucher.code;
  updateSummary();
}

function applyPromoCode() {
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
    document.getElementById("discountDisplay").classList.remove("active");
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
    if (discountDisplay) discountDisplay.classList.remove("active");

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
// VALIDATION
// ============================================================
function validateGuestCount() {
  const form = document.getElementById("roomBookingForm");
  const guestCountInput = document.getElementById("guestCount");
  const childCountInput = document.getElementById("childCount");

  if (!form || !guestCountInput || !childCountInput) return true;

  const capacity = parseInt(form.dataset.capacity) || 10;
  const guests = parseInt(guestCountInput.value) || 0;
  const children = parseInt(childCountInput.value) || 0;
  const total = guests + children;

  if (total <= 0) {
    alert("❌ Tổng số khách phải lớn hơn 0!");
    guestCountInput.value = 1;
    childCountInput.value = 0;
    updateSummary();
    return false;
  }

  if (total > capacity) {
    alert(
      `❌ Tổng số khách (${total} người) vượt quá sức chứa (${capacity} người)!`
    );
    guestCountInput.value = Math.max(1, capacity);
    childCountInput.value = 0;
    updateSummary();
    return false;
  }

  return true;
}

// ============================================================
// FORM SUBMISSION
// ============================================================
// Thêm vào đầu hàm submit
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("roomBookingForm");

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!validateGuestCount()) return;

      const roomPriceInput = document.getElementById("roomPrice");
      const checkinInput = document.getElementById("checkinTime");
      const checkoutInput = document.getElementById("checkoutTime");

      const roomPricePerNight =
        parseInt(String(roomPriceInput.value).replace(/[^\d]/g, "")) || 0;
      const checkin = checkinInput.value;
      const checkout = checkoutInput.value;

      const nights = calculateNights(checkin, checkout);
      const totalRoomPrice = roomPricePerNight * nights;
      const serviceTotal = calculateServiceTotal();
      const discount = currentDiscount || 0;
      const subtotalBeforeVat = Math.max(
        0,
        totalRoomPrice + serviceTotal - discount
      );
      const vat = Math.round(subtotalBeforeVat * 0.1);
      const total = subtotalBeforeVat + vat;
      const deposit = Math.round(total * 0.3);

      const selectedServices = [];
      document
        .querySelectorAll('input[name="services"]:checked')
        .forEach((cb) => {
          const label = document.querySelector(`label[for="${cb.id}"]`);
          const serviceName = label
            .querySelector(".service-name")
            .textContent.trim();
          const servicePrice =
            parseInt(String(cb.dataset.price).replace(/[^\d]/g, "")) || 0;
          const serviceId = parseInt(cb.dataset.serviceId) || 0;

          let finalPrice = servicePrice;
          if (
            serviceName.toLowerCase().includes("breakfast") ||
            serviceName.toLowerCase().includes("minibar") ||
            serviceName.toLowerCase().includes("bữa sáng")
          ) {
            finalPrice = servicePrice * nights;
          }

          selectedServices.push({
            serviceId: serviceId,
            name: serviceName,
            price: finalPrice,
          });
        });

      // Kiểm tra xem có form khách vãng lai không
      const walkInFullNameEl = document.getElementById("walkInFullName");
      const isWalkIn = walkInFullNameEl !== null;

      const formData = {
        roomId: form.dataset.roomId || null,
        roomName: document.getElementById("roomName").value,
        roomType: document.getElementById("roomType").value,
        guestCount: parseInt(document.getElementById("guestCount").value) || 0,
        childCount: parseInt(document.getElementById("childCount").value) || 0,
        checkinTime: checkin,
        checkoutTime: checkout,
        roomPrice: totalRoomPrice,
        roomPricePerNight: roomPricePerNight,
        serviceTotal: serviceTotal,
        services: selectedServices,
        discount: discount,
        vat: vat,
        promoCode: appliedPromoCode,
        voucherId: selectedVoucherId,
        promoDescription: selectedVoucher ? selectedVoucher.name : "",
        deposit: deposit,
        total: total,
        paymentMethod: document.getElementById("paymentMethod").value,
        notes: document.getElementById("notes").value,
      };

      // Thêm thông tin khách vãng lai nếu có
      if (isWalkIn) {
        // Validate thông tin khách vãng lai
        const walkInFullName = walkInFullNameEl.value.trim();
        const walkInPhone = document.getElementById("walkInPhone").value.trim();
        const walkInEmail = document.getElementById("walkInEmail").value.trim();
        const walkInIdNumber = document
          .getElementById("walkInIdNumber")
          .value.trim();

        // Validation
        if (!walkInFullName || walkInFullName.length < 2) {
          alert("Vui lòng nhập họ và tên (tối thiểu 2 ký tự)");
          walkInFullNameEl.focus();
          return;
        }

        if (!/^[A-Za-zÀ-ỹ\s]{2,50}$/.test(walkInFullName)) {
          alert("Họ và tên chỉ được chứa chữ cái và khoảng trắng");
          walkInFullNameEl.focus();
          return;
        }

        if (!walkInPhone || !/^[0-9]{10,11}$/.test(walkInPhone)) {
          alert("Vui lòng nhập số điện thoại hợp lệ (10-11 chữ số)");
          document.getElementById("walkInPhone").focus();
          return;
        }

        if (!walkInEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(walkInEmail)) {
          alert("Vui lòng nhập email hợp lệ");
          document.getElementById("walkInEmail").focus();
          return;
        }

        if (!walkInIdNumber || !/^[0-9]{9,12}$/.test(walkInIdNumber)) {
          alert("Vui lòng nhập số CMND/CCCD hợp lệ (9-12 chữ số)");
          document.getElementById("walkInIdNumber").focus();
          return;
        }

        formData.walkInFullName = walkInFullName;
        formData.walkInPhone = walkInPhone;
        formData.walkInEmail = walkInEmail;
        formData.walkInIdNumber = walkInIdNumber;
        formData.walkInAddress =
          document.getElementById("walkInAddress").value || "";
        formData.isWalkIn = true;

        // Khách vãng lai không được dùng voucher
        formData.voucherId = null;
        formData.discount = 0;
        formData.promoCode = "";
        selectedVoucherId = null;
        selectedVoucher = null;
        currentDiscount = 0;
        appliedPromoCode = "";
      } else {
        formData.isWalkIn = false;
      }

      if (new Date(formData.checkoutTime) <= new Date(formData.checkinTime)) {
        alert("Ngày check-out phải sau ngày check-in!");
        return;
      }

      showInvoice(formData);
    });
  }

  // Event listeners
  const roomPriceInput = document.getElementById("roomPrice");
  const checkinInput = document.getElementById("checkinTime");
  const checkoutInput = document.getElementById("checkoutTime");
  const serviceCheckboxes = document.querySelectorAll('input[name="services"]');
  const guestCountInput = document.getElementById("guestCount");
  const childCountInput = document.getElementById("childCount");

  if (roomPriceInput) roomPriceInput.addEventListener("input", updateSummary);

  if (checkinInput) {
    checkinInput.addEventListener("change", () => {
      updateSummary();
      loadAvailableVouchers();
    });
  }

  if (checkoutInput) {
    checkoutInput.addEventListener("change", () => {
      updateSummary();
      loadAvailableVouchers();
    });
  }

  serviceCheckboxes.forEach((cb) => {
    cb.addEventListener("change", () => {
      updateSummary();
      loadAvailableVouchers();
      if (selectedVoucher) calculateVoucherDiscount();
    });
  });

  if (guestCountInput) {
    guestCountInput.addEventListener("change", () => {
      validateGuestCount();
      updateSummary();
    });
  }

  if (childCountInput) {
    childCountInput.addEventListener("change", () => {
      validateGuestCount();
      updateSummary();
    });
  }
});

// ============================================================
// INVOICE FUNCTIONS
// ============================================================
function showInvoice(data) {
  currentBookingData = data;

  document.getElementById("bookingForm").style.display = "none";
  document.getElementById("invoiceContainer").classList.add("active");
  document.getElementById("invoiceContainer").style.display = "block";

  // Điền dữ liệu hiển thị
  document.getElementById("invoiceRoomName").textContent = data.roomName;
  document.getElementById("invoiceRoomType").textContent = data.roomType;
  document.getElementById("invoiceGuestCount").textContent =
    data.guestCount + " người";
  document.getElementById("invoiceChildCount").textContent =
    data.childCount + " người";

  document.getElementById("invoiceCheckinTime").textContent = formatDateTime(
    data.checkinTime
  );
  document.getElementById("invoiceCheckoutTime").textContent = formatDateTime(
    data.checkoutTime
  );
  const nights = calculateNights(data.checkinTime, data.checkoutTime);
  document.getElementById("invoiceNightCount").textContent = nights + " đêm";

  if (data.services.length > 0) {
    document.getElementById("invoiceServicesSection").style.display = "block";
    const servicesList = document.getElementById("invoiceServicesList");
    servicesList.innerHTML = "";
    data.services.forEach((service) => {
      const item = document.createElement("div");
      item.className = "service-list-item";
      item.innerHTML = `
        <span>${service.name}</span>
        <span style="font-weight: 600;">${formatCurrency(
          service.price / 100
        )}</span>
      `;
      servicesList.appendChild(item);
    });
  } else {
    document.getElementById("invoiceServicesSection").style.display = "none";
  }

  if (data.promoCode) {
    document.getElementById("invoicePromotionSection").style.display = "block";
    document.getElementById(
      "invoicePromotion"
    ).textContent = `${data.promoCode} - ${data.promoDescription}`;
  } else {
    document.getElementById("invoicePromotionSection").style.display = "none";
  }

  if (data.notes) {
    document.getElementById("invoiceNotesSection").style.display = "block";
    document.getElementById("invoiceNotes").textContent = data.notes;
  } else {
    document.getElementById("invoiceNotesSection").style.display = "none";
  }

  document.getElementById("invoiceRoomPrice").textContent = formatCurrency(
    data.roomPrice
  );

  if (data.serviceTotal > 0) {
    document.getElementById("invoiceServicePriceRow").style.display = "flex";
    document.getElementById("invoiceServicePrice").textContent = formatCurrency(
      data.serviceTotal
    );
  } else {
    document.getElementById("invoiceServicePriceRow").style.display = "none";
  }

  if (data.discount > 0) {
    document.getElementById("invoiceDiscountRow").style.display = "flex";
    document.getElementById("invoiceDiscount").textContent =
      "- " + formatCurrency(data.discount);
  } else {
    document.getElementById("invoiceDiscountRow").style.display = "none";
  }

  document.getElementById("invoiceVat").textContent = formatCurrency(data.vat);

  document.getElementById("invoiceTotalAmount").textContent = formatCurrency(
    data.total
  );
  document.getElementById("invoiceDeposit").textContent = formatCurrency(
    data.deposit
  );
  document.getElementById("invoicePaymentMethod").textContent =
    data.paymentMethod;

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

  // Điền dữ liệu vào hidden inputs
  document.getElementById("hiddenRoomId").value = data.roomId || "";
  document.getElementById("hiddenCheckinTime").value = data.checkinTime;
  document.getElementById("hiddenCheckoutTime").value = data.checkoutTime;
  document.getElementById("hiddenNotes").value = data.notes || "";
  document.getElementById("hiddenDeposit").value = data.deposit;
  document.getElementById("hiddenRoomPrice").value = data.roomPrice;
  document.getElementById("hiddenServiceTotal").value = data.serviceTotal;
  document.getElementById("hiddenDiscount").value = data.discount;
  document.getElementById("hiddenVat").value = data.vat;
  document.getElementById("hiddenTotal").value = data.total;
  document.getElementById("hiddenVoucherId").value = data.voucherId || "";
  document.getElementById("hiddenPaymentMethod").value = data.paymentMethod;
  document.getElementById("hiddenGuestCount").value = data.guestCount;
  document.getElementById("hiddenChildCount").value = data.childCount;

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

  // Điền services dưới dạng JSON string
  if (data.services && data.services.length > 0) {
    document.getElementById("hiddenServices").value = JSON.stringify(
      data.services
    );
  } else {
    document.getElementById("hiddenServices").value = "";
  }

  window.scrollTo({ top: 0, behavior: "smooth" });
}

function editBooking() {
  document.getElementById("bookingForm").style.display = "block";
  document.getElementById("invoiceContainer").classList.remove("active");
  document.getElementById("invoiceContainer").style.display = "none";
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function saveInvoice() {
  const invoice = document.getElementById("invoiceContainer");
  const opt = {
    margin: 0.5,
    filename: "hoa-don-dat-phong.pdf",
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
  const hasBookingData = sessionStorage.getItem("bookingData");

  if (!hasBookingData) {
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const checkinDate = new Date(tomorrow);
    checkinDate.setHours(14, 0, 0);
    const checkinInput = document.getElementById("checkinTime");
    if (checkinInput && !checkinInput.value) {
      checkinInput.value = checkinDate.toISOString().slice(0, 16);
    }

    const checkoutDate = new Date(tomorrow);
    checkoutDate.setDate(checkoutDate.getDate() + 1);
    checkoutDate.setHours(12, 0, 0);
    const checkoutInput = document.getElementById("checkoutTime");
    if (checkoutInput && !checkoutInput.value) {
      checkoutInput.value = checkoutDate.toISOString().slice(0, 16);
    }
  }

  updateSummary();

  setTimeout(() => {
    const checkinInput = document.getElementById("checkinTime");
    const checkoutInput = document.getElementById("checkoutTime");
    if (
      checkinInput &&
      checkoutInput &&
      checkinInput.value &&
      checkoutInput.value
    ) {
      loadAvailableVouchers();
    }
  }, 500);
});
