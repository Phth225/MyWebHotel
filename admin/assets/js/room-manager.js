// Đối tượng lưu trữ trạng thái slider cho từng modal
const sliderStates = new Map();

// Hàm lấy hoặc tạo trạng thái slider cho modal
function getSliderState(modalId) {
  if (!sliderStates.has(modalId)) {
    const modal = document.getElementById(modalId);
    if (!modal) return null;

    const sliderTrack = modal.querySelector(".slider-track");
    const thumbnails = modal.querySelectorAll(".gallery-item");
    const thumbnailGallery = modal.querySelector(".thumbnail-gallery");

    if (!sliderTrack) return null;

    sliderStates.set(modalId, {
      currentSlide: 0,
      totalSlides: thumbnails.length,
      sliderTrack,
      thumbnails,
      thumbnailGallery,
    });
  }
  return sliderStates.get(modalId);
}

// Hàm cập nhật giao diện slider
function updateSlider(modalId) {
  const state = getSliderState(modalId);
  if (!state || !state.sliderTrack) return;

  // Cập nhật vị trí slide
  state.sliderTrack.style.transform = `translateX(-${
    state.currentSlide * 100
  }%)`;

  // Cập nhật trạng thái active cho thumbnails
  if (state.thumbnails && state.thumbnails.length > 0) {
    state.thumbnails.forEach((thumb, index) => {
      if (thumb) {
        thumb.classList.toggle("active", index === state.currentSlide);
      }
    });
  }

  // Tự động cuộn thumbnail gallery
  if (
    state.thumbnailGallery &&
    state.thumbnails &&
    state.thumbnails[state.currentSlide]
  ) {
    const activeThumb = state.thumbnails[state.currentSlide];
    const thumbnailWidth = activeThumb.offsetWidth + 10;
    const galleryWidth = state.thumbnailGallery.offsetWidth;
    const scrollPosition =
      state.currentSlide * thumbnailWidth -
      galleryWidth / 2 +
      thumbnailWidth / 2;

    state.thumbnailGallery.scrollTo({
      left: scrollPosition,
      behavior: "smooth",
    });
  }
}

// Hàm chuyển slide
function moveSlide(direction, modalId) {
  const state = getSliderState(modalId);
  if (!state) return;

  state.currentSlide += direction;

  // Lặp lại từ đầu nếu vượt quá giới hạn
  if (state.currentSlide < 0) {
    state.currentSlide = state.totalSlides - 1;
  } else if (state.currentSlide >= state.totalSlides) {
    state.currentSlide = 0;
  }

  updateSlider(modalId);
}

// Hàm nhảy đến slide cụ thể
function goToSlide(index, modalId) {
  const state = getSliderState(modalId);
  if (!state || index < 0 || index >= state.totalSlides) return;

  state.currentSlide = index;
  updateSlider(modalId);
}

// Hàm xử lý sự kiện swipe cho touch devices
function initTouchSwipe(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  let touchStartX = 0;
  let touchEndX = 0;
  const slider = modal.querySelector(".main-image-slider");

  if (!slider) return;

  slider.addEventListener(
    "touchstart",
    (e) => {
      touchStartX = e.changedTouches[0].screenX;
    },
    { passive: true }
  );

  slider.addEventListener(
    "touchend",
    (e) => {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe(modalId);
    },
    { passive: true }
  );

  function handleSwipe() {
    if (touchEndX < touchStartX - 50) {
      moveSlide(1, modalId);
    } else if (touchEndX > touchStartX + 50) {
      moveSlide(-1, modalId);
    }
  }
}

// Khởi tạo sự kiện khi DOM đã tải xong
document.addEventListener("DOMContentLoaded", function () {
  // Xử lý sự kiện khi modal được hiển thị
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("shown.bs.modal", function () {
      const modalId = this.id;
      if (
        modalId.startsWith("viewRoomModal") ||
        modalId.startsWith("viewRoomTypeModal")
      ) {
        // Khởi tạo slider khi modal được mở
        getSliderState(modalId);
        // Cập nhật slider lần đầu
        updateSlider(modalId);
        // Khởi tạo sự kiện touch
        initTouchSwipe(modalId);
      }
    });
  });

  // Xử lý sự kiện bàn phím
  document.addEventListener("keydown", function (e) {
    const activeModal = document.querySelector(".modal.show");
    if (!activeModal) return;

    const modalId = activeModal.id;
    if (
      !modalId.startsWith("viewRoomModal") &&
      !modalId.startsWith("viewRoomTypeModal")
    )
      return;

    if (e.key === "ArrowLeft") {
      moveSlide(-1, modalId);
      e.preventDefault();
    } else if (e.key === "ArrowRight") {
      moveSlide(1, modalId);
      e.preventDefault();
    }
  });
});

// Cập nhật giao diện slider
function updateSlider(modalId) {
  const state = getSliderState(modalId);
  if (!state || !state.sliderTrack || !state.thumbnails) return;

  // Di chuyển slide chính
  state.sliderTrack.style.transform = `translateX(-${
    state.currentSlide * 100
  }%)`;

  // Cập nhật thumbnail đang active
  state.thumbnails.forEach((thumb, index) => {
    if (index === state.currentSlide) {
      thumb.classList.add("active");
    } else {
      thumb.classList.remove("active");
    }
  });

  // Tự động cuộn thumbnail gallery
  if (state.thumbnailGallery && state.thumbnails[state.currentSlide]) {
    const activeThumb = state.thumbnails[state.currentSlide];
    const thumbnailWidth = activeThumb.offsetWidth + 10;
    const galleryWidth = state.thumbnailGallery.offsetWidth;
    const scrollPosition =
      state.currentSlide * thumbnailWidth -
      galleryWidth / 2 +
      thumbnailWidth / 2;
    state.thumbnailGallery.scrollTo({
      left: scrollPosition,
      behavior: "smooth",
    });
  }
}

// Khởi tạo slider cho modal cụ thể
function initSlider(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  const state = getSliderState(modalId);
  if (!state) return;

  // Tìm các phần tử trong modal hiện tại
  state.sliderTrack = modal.querySelector(".slider-track");
  state.thumbnails = modal.querySelectorAll(".gallery-item");
  state.thumbnailGallery = modal.querySelector(".thumbnail-gallery");
  state.totalSlides = state.thumbnails.length;
  state.currentSlide = 0;

  // Kích hoạt slide đầu tiên
  if (state.thumbnails.length > 0) {
    state.thumbnails[0].classList.add("active");
  }

  // Hỗ trợ vuốt trên màn hình cảm ứng
  const slider = modal.querySelector(".main-image-slider");
  if (slider) {
    let touchStartX = 0;
    let touchEndX = 0;

    const handleTouchStart = (e) => {
      touchStartX = e.changedTouches[0].screenX;
    };

    const handleTouchEnd = (e) => {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe(modalId);
    };

    const handleSwipe = () => {
      if (touchEndX < touchStartX - 50) {
        moveSlide(1, modalId);
      }
      if (touchEndX > touchStartX + 50) {
        moveSlide(-1, modalId);
      }
    };

    // Xóa các sự kiện cũ nếu có
    slider.removeEventListener("touchstart", handleTouchStart);
    slider.removeEventListener("touchend", handleTouchEnd);

    // Thêm sự kiện mới
    slider.addEventListener("touchstart", handleTouchStart);
    slider.addEventListener("touchend", handleTouchEnd);
  }

  // Cập nhật lại slider
  updateSlider(modalId);
}

// Các hàm quản lý phòng
function editRoom(roomId) {
  window.location.href = "index.php?page=room-manager&action=edit&id=" + roomId;
}

function viewRoom(roomId) {
  const modalId = "viewRoomModal" + roomId;
  // Khởi tạo slider cho modal này
  initSlider(modalId);

  // Hiển thị modal
  const viewModal = new bootstrap.Modal(document.getElementById(modalId));
  viewModal.show();
}

// Hàm xem chi tiết loại phòng
function viewRoomType(roomTypeId) {
  const modalId = "viewRoomTypeModal" + roomTypeId;
  // Khởi tạo slider cho modal này
  initSlider(modalId);

  // Hiển thị modal
  const viewModal = new bootstrap.Modal(document.getElementById(modalId));
  viewModal.show();
}

function saveRoom() {
  const addModal = bootstrap.Modal.getInstance(
    document.getElementById("addRoomModal")
  );
  if (addModal) {
    addModal.hide();
  }
}

function editRoomFromView(id) {
  const viewModal = bootstrap.Modal.getInstance(
    document.getElementById("viewRoomModal" + id)
  );
  if (viewModal) {
    viewModal.hide();
  }
  window.location.href = "index.php?page=room-manager&action=edit&id=" + id;
}

// Tìm kiếm và lọc
const searchInput = document.getElementById("searchInput");
if (searchInput) {
  searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();
    const table = document.getElementById("roomsTable");
    if (!table) return;
    const rows = table.getElementsByTagName("tr");

    for (let i = 1; i < rows.length; i++) {
      const row = rows[i];
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? "" : "none";
    }
  });
}

// Lọc theo trạng thái và loại phòng
const statusFilter = document.getElementById("statusFilter");
const typeFilter = document.getElementById("typeFilter");

if (statusFilter) {
  statusFilter.addEventListener("change", filterTable);
}

if (typeFilter) {
  typeFilter.addEventListener("change", filterTable);
}

function filterTable() {
  const statusValue = document.getElementById("statusFilter").value;
  const typeValue = document.getElementById("typeFilter").value;
  const table = document.getElementById("roomsTable");
  const rows = table.getElementsByTagName("tr");

  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const statusText = row.cells[4].textContent.toLowerCase();
    const typeText = row.cells[2].textContent.toLowerCase();

    let showRow = true;

    // Lọc theo trạng thái
    if (statusValue) {
      if (statusValue === "available" && !statusText.includes("có sẵn")) {
        showRow = false;
      } else if (
        statusValue === "occupied" &&
        !statusText.includes("đã thuê")
      ) {
        showRow = false;
      } else if (
        statusFilter === "maintenance" &&
        !statusText.includes("bảo trì")
      ) {
        showRow = false;
      }
    }

    // Lọc theo loại phòng
    if (typeFilter) {
      if (typeFilter === "standard" && !typeText.includes("standard")) {
        showRow = false;
      } else if (typeFilter === "deluxe" && !typeText.includes("deluxe")) {
        showRow = false;
      } else if (typeFilter === "suite" && !typeText.includes("suite")) {
        showRow = false;
      }
    }

    row.style.display = showRow ? "" : "none";
  }
}

const resetFilter = document.getElementById("resetFilter");
if (resetFilter) {
  resetFilter.addEventListener("click", function () {
    if (searchInput) searchInput.value = "";
    if (statusFilter) statusFilter.value = "";
    if (typeFilter) typeFilter.value = "";

    const table = document.getElementById("roomsTable");
    if (!table) return;
    const rows = table.getElementsByTagName("tr");

    for (let i = 1; i < rows.length; i++) {
      rows[i].style.display = "";
    }
  });
}

function removeImage(btn) {
  btn.parentElement.remove();
  // Cập nhật lại file input
  updateFileInput();
}
