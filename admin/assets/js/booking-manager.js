// Hàm này sẽ hiển thị hoặc ẩn các trường form tương ứng
function toggleBookingFields() {
  const bookingTypeEl = document.getElementById("bookingType");
  if (!bookingTypeEl) return;
  
  const type = bookingTypeEl.value;
  const roomFields = document.getElementById("roomBookingFields");
  const serviceFields = document.getElementById("serviceBookingFields");

  // Reset display
  if (roomFields) roomFields.style.display = "none";
  if (serviceFields) serviceFields.style.display = "none";

  // Hiển thị form tương ứng
  if (type === "room" && roomFields) {
    roomFields.style.display = "block";
  } else if (type === "service" && serviceFields) {
    serviceFields.style.display = "block";
  }
}

// Khởi tạo trạng thái ban đầu khi Modal được load
document.addEventListener("DOMContentLoaded", () => {
  // Gắn sự kiện để reset trạng thái form khi modal đóng
  const bookingModal = document.getElementById("addBookingModal");
  if (bookingModal) {
    bookingModal.addEventListener("hidden.bs.modal", function () {
      const bookingTypeEl = document.getElementById("bookingType");
      if (bookingTypeEl) bookingTypeEl.value = ""; // Reset select box
      toggleBookingFields(); // Reset hiển thị form
    });
  }

  // Gọi lần đầu để đảm bảo ẩn khi trang load (nếu modal mở sẵn)
  toggleBookingFields();
});
// Giả lập dữ liệu Booking (Thực tế sẽ gọi API)
const mockBookings = [
  {
    id: 1,
    type: "room",
    guest: "Nguyễn Văn A",
    phone: "0901234567",
    total: "3,500,000 VNĐ",
    status: "Chờ xác nhận",
    statusClass: "bg-warning",
    roomType: "Phòng 101 - Deluxe Room",
    checkIn: "15/11/2025",
    checkOut: "18/11/2025",
    guests: "2",
    notes: "Giường đôi, tầng cao.",
  },
  {
    id: 2,
    type: "service",
    guest: "Trần Thị B",
    phone: "0912345678",
    total: "1,200,000 VNĐ",
    status: "Đã xác nhận",
    statusClass: "bg-success",
    serviceName: "Spa & Massage",
    serviceDesc: "Gói VIP 90 phút",
    dateTime: "16/11/2025 - 14:00",
    guests: "1",
    notes: "Phòng riêng, muốn thêm tinh dầu.",
  },
  {
    id: 3,
    type: "room",
    guest: "Lê Văn C",
    phone: "0923456789",
    total: "2,800,000 VNĐ",
    status: "Đã nhận phòng",
    statusClass: "bg-info",
    roomType: "Phòng 310 - Suite Room",
    checkIn: "17/11/2025",
    checkOut: "19/11/2025",
    guests: "2",
    notes: "Không.",
  },
  {
    id: 4,
    type: "service",
    guest: "Phạm Thị D",
    phone: "0934567890",
    total: "8,500,000 VNĐ",
    status: "Đã xác nhận",
    statusClass: "bg-success",
    serviceName: "Nhà Hàng",
    serviceDesc: "Tiệc sinh nhật",
    dateTime: "18/11/2025 - 19:00",
    guests: "10",
    notes: "Cần trang trí bong bóng.",
  },
  {
    id: 5,
    type: "room",
    guest: "Hoàng Văn E",
    phone: "0945678901",
    total: "5,200,000 VNĐ",
    status: "Đã hủy",
    statusClass: "bg-danger",
    roomType: "Phòng 202 - Family Room",
    checkIn: "20/11/2025",
    checkOut: "25/11/2025",
    guests: "3",
    notes: "Khách hủy không rõ lý do.",
  },
  {
    id: 7,
    type: "room",
    guest: "Bùi Văn G",
    phone: "0967890123",
    total: "1,500,000 VNĐ",
    status: "Đã trả phòng",
    statusClass: "bg-secondary",
    roomType: "Phòng 405 - Standard Room",
    checkIn: "10/11/2025",
    checkOut: "12/11/2025",
    guests: "1",
    notes: "Phòng đã được dọn dẹp.",
  },
];

function setupViewBookingDetailModal() {
  const modalEl = document.getElementById("viewBookingDetailModal");
  modalEl.addEventListener("show.bs.modal", (event) => {
    const button = event.relatedTarget;
    const bookingId = button.getAttribute("data-booking-id");

    // Tìm dữ liệu booking demo
    const bookingData = mockBookings.find((b) => b.id === parseInt(bookingId));

    if (!bookingData) {
      alert("Không tìm thấy chi tiết booking này (ID: " + bookingId + ")");
      return;
    }

    // --- Cập nhật thông tin chung ---
    document.getElementById("detail-booking-id").textContent = bookingData.id;
    document.getElementById("detail-guest-name").textContent =
      bookingData.guest;
    document.getElementById("detail-guest-phone").textContent =
      bookingData.phone;
    document.getElementById("detail-total-price").textContent =
      bookingData.total;

    const statusBadge = document.getElementById("detail-status-badge");
    statusBadge.textContent = bookingData.status;

    // Xóa hết class badge cũ và thêm class mới
    statusBadge.className = "badge";
    statusBadge.classList.add(bookingData.statusClass);

    // Ẩn tất cả các khối chi tiết trước
    const roomInfo = document.getElementById("detail-room-info");
    const serviceInfo = document.getElementById("detail-service-info");
    roomInfo.style.display = "none";
    serviceInfo.style.display = "none";

    // --- Cập nhật thông tin chi tiết theo loại booking ---
    if (bookingData.type === "room") {
      roomInfo.style.display = "block";
      document.getElementById("detail-room-type").textContent =
        bookingData.roomType;
      document.getElementById("detail-check-in-date").textContent =
        bookingData.checkIn;
      document.getElementById("detail-check-out-date").textContent =
        bookingData.checkOut;
      document.getElementById("detail-room-guests").textContent =
        bookingData.guests + " người";
      document.getElementById("detail-room-notes").textContent =
        bookingData.notes || "Không có";
    } else if (bookingData.type === "service") {
      serviceInfo.style.display = "block";
      document.getElementById("detail-service-name").textContent =
        bookingData.serviceName;
      document.getElementById("detail-service-description").textContent =
        bookingData.serviceDesc;
      document.getElementById("detail-service-datetime").textContent =
        bookingData.dateTime;
      document.getElementById("detail-service-guests").textContent =
        bookingData.guests + " người";
      document.getElementById("detail-service-notes").textContent =
        bookingData.notes || "Không có";
    }
  });
}

// Thêm setupViewBookingDetailModal() vào sự kiện DOMContentLoaded
document.addEventListener("DOMContentLoaded", () => {
  // Hàm hiện/ẩn form thêm booking (đã có)
  toggleBookingFields();

  // Khởi tạo Modal Xem Chi Tiết
  const viewModal = document.getElementById("viewBookingDetailModal");
  if (viewModal) {
    setupViewBookingDetailModal();
  }

  // Gắn sự kiện để reset trạng thái form khi modal đóng
  const bookingModal = document.getElementById("addBookingModal");
  if (bookingModal) {
    bookingModal.addEventListener("hidden.bs.modal", function () {
      const bookingTypeEl = document.getElementById("bookingType");
      if (bookingTypeEl) bookingTypeEl.value = "";
      toggleBookingFields();
    });
  }
});
