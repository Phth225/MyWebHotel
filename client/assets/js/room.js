// hero
document.getElementById("exploreBtn").addEventListener("click", function () {
  const roomList = document.querySelector(".rooms-header");
  if (roomList) {
    roomList.scrollIntoView({ behavior: "smooth" });
  }
});

// AJAX tim kiem phong
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const sortSelect = document.getElementById("sort");
  const roomContainer = document.querySelector(".rooms-main");
  const roomTypeCheckboxes = document.querySelectorAll(
    'input[name="room_type[]"]'
  );

  let typingTimer;
  const delay = 400;

  function loadRooms() {
    const keyword = searchInput.value.trim();
    const sort = sortSelect.value;

    // Lấy các loại phòng được chọn
    const selectedTypes = [];
    roomTypeCheckboxes.forEach((checkbox) => {
      if (checkbox.checked) {
        selectedTypes.push(checkbox.value);
      }
    });

    // Tạo query string
    const params = new URLSearchParams();
    params.append("keyword", keyword);
    params.append("sort", sort);

    // Thêm các loại phòng đã chọn vào params
    selectedTypes.forEach((type) => {
      params.append("room_types[]", type);
    });

    // Gửi request đến PHP
    fetch(
      `/My-Web-Hotel/client/controller/search_room.php?${params.toString()}`
    )
      .then((res) => res.text())
      .then((data) => {
        roomContainer.innerHTML = data;
      })
      .catch((err) => console.error("Lỗi tải dữ liệu:", err));
  }

  // Realtime tìm kiếm
  searchInput.addEventListener("input", function () {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(loadRooms, delay);
  });

  // Khi thay đổi sắp xếp
  sortSelect.addEventListener("change", loadRooms);

  // Khi thay đổi checkbox danh mục
  roomTypeCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", loadRooms);
  });

  // Tải danh sách ban đầu
  loadRooms();
});
