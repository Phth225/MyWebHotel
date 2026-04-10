// === Giữ theme ngay khi tải trang (trước khi DOM render xong) ===
;(function () {
  const savedTheme = localStorage.getItem("theme")
  if (savedTheme === "dark") {
    document.documentElement.classList.add("dark")
  }
})()

// === Sau khi DOM đã tải ===
window.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar")
  const toggleTheme = document.getElementById("theme-toggle")
  const collapseBtn = document.querySelector(".toggle-sidebar")
  const collapseIcon = document.getElementById("collapse-icon")
  const menuItems = document.querySelectorAll(".menu .menu-item")

  // Áp dụng lại theme từ localStorage
  const savedTheme = localStorage.getItem("theme")
  if (savedTheme === "dark") {
    document.body.classList.add("dark")
    sidebar?.classList.add("dark")
    if (toggleTheme) toggleTheme.checked = true
  }

  // Toggle Dark/Light mode
  if (toggleTheme) {
    toggleTheme.addEventListener("change", () => {
      const isDark = toggleTheme.checked
      document.body.classList.toggle("dark", isDark)
      sidebar?.classList.toggle("dark", isDark)
      document.documentElement.classList.toggle("dark", isDark)
      localStorage.setItem("theme", isDark ? "dark" : "light")
    })
  }

  // Nút thu gọn sidebar
  collapseBtn?.addEventListener("click", () => {
    sidebar?.classList.toggle("collapsed")
    if (sidebar?.classList.contains("collapsed")) {
      collapseIcon?.classList.replace(
        "fa-angle-double-left",
        "fa-angle-double-right"
      )
    } else {
      collapseIcon?.classList.replace(
        "fa-angle-double-right",
        "fa-angle-double-left"
      )
    }
  })

  // Điều hướng menu
  menuItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      e.preventDefault()
      const page = item.getAttribute("data-page")
      if (!page) return

      // Xử lý logout
      if (page === "logout") {
        handleLogout(e)
        return false
      }

      // Cập nhật trạng thái active
      menuItems.forEach((el) => el.classList.remove("active"))
      item.classList.add("active")

      // Giữ theme khi chuyển trang
      const currentTheme = localStorage.getItem("theme") || "light"
      const url =
        "/My-Web-Hotel/admin/index.php?page=" +
        encodeURIComponent(page) +
        "&theme=" +
        encodeURIComponent(currentTheme)

      window.location.href = url
      return false
    })
  })
})

// Hàm xử lý logout (có thể gọi từ onclick hoặc từ event listener)
function handleLogout(event) {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  if (confirm("Bạn có chắc chắn muốn đăng xuất?")) {
    window.location.href = "/My-Web-Hotel/admin/pages/logout.php"
  }
  return false
}
