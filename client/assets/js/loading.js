// Tự động ẩn loading sau khi trang tải xong
window.addEventListener("load", function () {
  setTimeout(function () {
    const overlay = document.getElementById("loadingOverlay");
    overlay.classList.add("fade-out");

    // Xóa element sau khi animation hoàn thành
    setTimeout(function () {
      overlay.remove();
    }, 150);
  }, 150); // Delay 500ms trước khi bắt đầu fade out
});
