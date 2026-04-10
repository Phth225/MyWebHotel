// AJAX tim kiem dich vu
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const sortSelect = document.getElementById("sort");
  const serviceContainer = document.querySelector(".services-main");
  const categoryCheckboxes = document.querySelectorAll(
    'input[name="service_type[]"]'
  );

  let typingTimer;
  const delay = 400;

  function loadServices() {
    const keyword = searchInput.value.trim();
    const sort = sortSelect.value;

    // Lấy tất cả danh mục đã chọn
    const selectedCategories = Array.from(categoryCheckboxes)
      .filter((checkbox) => checkbox.checked)
      .map((checkbox) => checkbox.value);

    // Tạo query string
    let url = `/My-Web-Hotel/client/controller/search_service.php?keyword=${encodeURIComponent(
      keyword
    )}&sort=${encodeURIComponent(sort)}`;

    // Thêm categories vào URL
    if (selectedCategories.length > 0) {
      selectedCategories.forEach((category) => {
        url += `&categories[]=${encodeURIComponent(category)}`;
      });
    }

    // Gửi request đến PHP
    fetch(url)
      .then((res) => res.text())
      .then((data) => {
        serviceContainer.innerHTML = data;
      })
      .catch((err) => console.error("Lỗi tải dữ liệu:", err));
  }

  // Realtime tìm kiếm
  searchInput.addEventListener("input", function () {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(loadServices, delay);
  });

  // Khi thay đổi sắp xếp
  sortSelect.addEventListener("change", loadServices);

  // Khi tick/untick checkbox danh mục
  categoryCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", loadServices);
  });

  // Tải danh sách ban đầu
  loadServices();
});
