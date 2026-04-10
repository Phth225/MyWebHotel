// Star Rating (giữ nguyên code cũ)
const stars = document.querySelectorAll("#starRating i");
const ratingInput = document.getElementById("ratingInput");

if (stars && ratingInput) {
  stars.forEach((star) => {
    star.addEventListener("click", function () {
      const rating = this.getAttribute("data-rating");
      ratingInput.value = rating;

      stars.forEach((s) => {
        s.classList.remove("fas", "active");
        s.classList.add("far");
      });

      for (let i = 0; i < rating; i++) {
        stars[i].classList.remove("far");
        stars[i].classList.add("fas", "active");
      }
    });

    star.addEventListener("mouseenter", function () {
      const rating = this.getAttribute("data-rating");
      for (let i = 0; i < rating; i++) {
        if (!stars[i].classList.contains("active")) {
          stars[i].classList.remove("far");
          stars[i].classList.add("fas");
        }
      }
    });

    star.addEventListener("mouseleave", function () {
      const currentRating = ratingInput.value;
      stars.forEach((s, index) => {
        if (index >= currentRating) {
          if (!s.classList.contains("active")) {
            s.classList.remove("fas");
            s.classList.add("far");
          }
        }
      });
    });
  });
}

// Image Preview (giữ nguyên)
function previewImages(event) {
  const preview = document.getElementById("imagePreview");
  preview.innerHTML = "";
  const files = event.target.files;

  for (let i = 0; i < files.length; i++) {
    const file = files[i];
    const reader = new FileReader();

    reader.onload = function (e) {
      const div = document.createElement("div");
      div.className = "preview-item";
      div.innerHTML = `
        <img src="${e.target.result}">
        <button type="button" class="remove-btn" onclick="removePreview(this)">
          <i class="fas fa-times"></i>
        </button>
      `;
      preview.appendChild(div);
    };

    reader.readAsDataURL(file);
  }
}

function removePreview(btn) {
  btn.parentElement.remove();
}

// Open Image Modal (giữ nguyên)
function openImageModal(src) {
  document.getElementById("modalImage").src = src;
  new bootstrap.Modal(document.getElementById("imageModal")).show();
}

// ===== MỚI: REVIEW ACTIONS MENU =====

// Toggle menu hành động
function toggleReviewActions(btn, reviewId, isOwner) {
  const menu = btn.nextElementSibling;

  // Đóng tất cả menu khác
  document.querySelectorAll(".review-actions-menu").forEach((m) => {
    if (m !== menu) m.classList.remove("show");
  });

  // Toggle menu hiện tại
  menu.classList.toggle("show");

  // Thêm event listener để đóng khi click bên ngoài
  if (menu.classList.contains("show")) {
    setTimeout(() => {
      document.addEventListener("click", closeMenuOnClickOutside);
    }, 0);
  }
}

// Đóng menu khi click bên ngoài
function closeMenuOnClickOutside(e) {
  if (!e.target.closest(".review-actions")) {
    document.querySelectorAll(".review-actions-menu").forEach((menu) => {
      menu.classList.remove("show");
    });
    document.removeEventListener("click", closeMenuOnClickOutside);
  }
}

// Sao chép nội dung bình luận
async function copyReviewContent(reviewId) {
  const reviewCard = document.querySelector(`[data-review-id="${reviewId}"]`);
  if (!reviewCard) return;

  const reviewText = reviewCard.querySelector(".review-text");
  if (!reviewText) return;

  const content = reviewText.innerText;

  try {
    await navigator.clipboard.writeText(content);
    showNotification("Đã sao chép nội dung bình luận", "success");
  } catch (err) {
    // Fallback cho trình duyệt cũ
    const textArea = document.createElement("textarea");
    textArea.value = content;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    document.body.appendChild(textArea);
    textArea.select();

    try {
      document.execCommand("copy");
      showNotification("Đã sao chép nội dung bình luận", "success");
    } catch (err) {
      showNotification("Không thể sao chép nội dung", "error");
    }

    document.body.removeChild(textArea);
  }

  // Đóng menu
  document.querySelectorAll(".review-actions-menu").forEach((menu) => {
    menu.classList.remove("show");
  });
}

// Hiển thị confirm dialog
function confirmDeleteReview(reviewId) {
  // Đóng menu
  document.querySelectorAll(".review-actions-menu").forEach((menu) => {
    menu.classList.remove("show");
  });

  // Hiển thị confirm modal
  const confirmModal = document.getElementById("confirmDeleteModal");
  if (confirmModal) {
    const modal = new bootstrap.Modal(confirmModal);

    // Lưu reviewId vào data attribute
    confirmModal.setAttribute("data-review-id", reviewId);

    modal.show();
  }
}

// Đóng confirm dialog
function closeConfirmDialog() {
  const confirmModal = document.getElementById("confirmDeleteModal");
  if (confirmModal) {
    const modal = bootstrap.Modal.getInstance(confirmModal);
    if (modal) {
      modal.hide();
    }
  }
}

// Xử lý khi nhấn nút xác nhận xóa
function handleConfirmDelete() {
  const confirmModal = document.getElementById("confirmDeleteModal");
  const reviewId = confirmModal.getAttribute("data-review-id");

  if (reviewId) {
    deleteReview(parseInt(reviewId));
  }

  closeConfirmDialog();
}

// Xóa bình luận
async function deleteReview(reviewId) {
  try {
    const formData = new FormData();
    formData.append("review_id", reviewId);

    const response = await fetch("controller/delete-review.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      // Xóa review card khỏi DOM với animation
      const reviewCard = document.querySelector(
        `[data-review-id="${reviewId}"]`
      );
      if (reviewCard) {
        reviewCard.style.opacity = "0";
        reviewCard.style.transform = "translateX(-20px)";
        reviewCard.style.transition = "all 0.3s ease";

        setTimeout(() => {
          reviewCard.remove();

          // Kiểm tra xem còn review nào không
          const remainingReviews =
            document.querySelectorAll(".review-card").length;
          if (remainingReviews === 0) {
            const reviewsList = document.getElementById("reviewsList");
            if (reviewsList) {
              reviewsList.innerHTML = `
                <div class="text-center py-5">
                  <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                  <p class="mt-3 text-muted">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
                </div>
              `;
            }
          }

          // Cập nhật tổng số reviews nếu có
          const totalReviewsInput = document.getElementById("totalReviews");
          if (totalReviewsInput) {
            const currentTotal = parseInt(totalReviewsInput.value);
            totalReviewsInput.value = currentTotal - 1;
          }
        }, 300);
      }

      // Hiển thị thông báo thành công
      showNotification(data.message, "success");

      // Reload lại trang sau 1.5s để cập nhật rating trung bình
      setTimeout(() => {
        window.location.reload();
      }, 1500);
    } else {
      showNotification(data.message, "error");
    }
  } catch (error) {
    console.error("Error:", error);
    showNotification("Có lỗi xảy ra khi xóa bình luận", "error");
  }
}

// Hiển thị notification modal
function showNotification(message, type = "success") {
  const modal = document.getElementById("notificationModal");
  const messageDiv = document.getElementById("notificationMessage");
  const modalTitle = document.getElementById("notificationModalLabel");

  if (modal && messageDiv && modalTitle) {
    // Cập nhật nội dung
    if (type === "success") {
      modalTitle.innerHTML =
        '<i class="fas fa-check-circle me-2"></i>Thành Công';
      messageDiv.innerHTML = `<p class="mt-3 mb-0">${message}</p>`;
    } else {
      modalTitle.innerHTML =
        '<i class="fas fa-exclamation-circle me-2"></i>Lỗi';
      messageDiv.innerHTML = `<p class="mt-3 mb-0">${message}</p>`;
    }

    // Hiển thị modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    // Tự động đóng sau 3 giây nếu thành công
    if (type === "success") {
      setTimeout(() => {
        bsModal.hide();
      }, 3000);
    }
  }
}

// Hiển thị toast notification (giữ lại cho các thông báo nhỏ khác nếu cần)
function showToast(message, type = "success") {
  // Xóa toast cũ nếu có
  const oldToast = document.querySelector(".toast-notification");
  if (oldToast) oldToast.remove();

  const toast = document.createElement("div");
  toast.className = `toast-notification ${type}`;
  toast.innerHTML = `
    <i class="fas fa-${
      type === "success" ? "check-circle" : "exclamation-circle"
    }"></i>
    <span>${message}</span>
  `;

  document.body.appendChild(toast);

  // Tự động xóa sau 3 giây
  setTimeout(() => {
    toast.style.opacity = "0";
    toast.style.transform = "translateY(20px)";
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// Load More Reviews (giữ nguyên + cập nhật để thêm actions menu)
function loadMoreReviews() {
  const offsetInput = document.getElementById("currentOffset");
  const totalReviewsInput = document.getElementById("totalReviews");
  const loadMoreBtn = document.getElementById("loadMoreBtn");
  const reviewsList = document.getElementById("reviewsList");

  if (!offsetInput || !totalReviewsInput || !loadMoreBtn || !reviewsList) {
    console.error("Required elements not found");
    return;
  }

  const currentOffset = parseInt(offsetInput.value);
  const totalReviews = parseInt(totalReviewsInput.value);

  if (currentOffset >= totalReviews) {
    loadMoreBtn.style.display = "none";
    return;
  }

  loadMoreBtn.disabled = true;
  loadMoreBtn.innerHTML =
    '<i class="fas fa-spinner fa-spin me-2"></i>Đang tải...';

  fetch(`controller/load-more-reviews.php?offset=${currentOffset}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.reviews.length > 0) {
        data.reviews.forEach((review) => {
          const reviewCard = createReviewCard(review);
          reviewsList.insertAdjacentHTML("beforeend", reviewCard);
        });

        const newOffset = currentOffset + data.count;
        offsetInput.value = newOffset;

        if (newOffset >= totalReviews) {
          loadMoreBtn.style.display = "none";
        } else {
          loadMoreBtn.disabled = false;
          loadMoreBtn.innerHTML =
            '<i class="fas fa-chevron-down me-2"></i>Xem thêm đánh giá';
        }
      } else {
        loadMoreBtn.style.display = "none";
      }
    })
    .catch((error) => {
      console.error("Error loading reviews:", error);
      loadMoreBtn.disabled = false;
      loadMoreBtn.innerHTML =
        '<i class="fas fa-chevron-down me-2"></i>Xem thêm đánh giá';
      alert("Có lỗi xảy ra khi tải đánh giá. Vui lòng thử lại!");
    });
}

// Tạo HTML cho review card (cập nhật để thêm actions menu)
function createReviewCard(review) {
  let starsHTML = "";
  for (let i = 0; i < review.rating; i++) {
    starsHTML += '<i class="fas fa-star"></i>';
  }
  for (let i = review.rating; i < 5; i++) {
    starsHTML += '<i class="far fa-star"></i>';
  }

  let imagesHTML = "";
  if (review.images && review.images.length > 0) {
    imagesHTML = '<div class="review-images">';
    review.images.forEach((imageUrl) => {
      imagesHTML += `<img src="${escapeHtml(
        imageUrl
      )}" alt="Review Image" onclick="openImageModal(this.src)">`;
    });
    imagesHTML += "</div>";
  }

  const avatarUrl =
    review.avatar && review.avatar.trim() !== ""
      ? escapeHtml(review.avatar)
      : "/My-Web-Hotel/client/assets/images/user1.jpg";

  const date = new Date(review.created_at);
  const formattedDate = `${String(date.getDate()).padStart(2, "0")}/${String(
    date.getMonth() + 1
  ).padStart(2, "0")}/${date.getFullYear()}`;

  // Menu hành động
  const actionsMenuHTML = `
    <div class="review-actions">
      <button class="review-actions-btn" onclick="toggleReviewActions(this, ${
        review.review_id
      }, ${review.is_owner})">
        <i class="fas fa-ellipsis-v"></i>
      </button>
      <div class="review-actions-menu">
        ${
          review.is_owner
            ? `
          <button class="review-actions-menu-item delete" onclick="confirmDeleteReview(${review.review_id})">
            <i class="fas fa-trash-alt"></i>
            <span>Xóa bình luận</span>
          </button>
        `
            : ""
        }
        <button class="review-actions-menu-item" onclick="copyReviewContent(${
          review.review_id
        })">
          <i class="fas fa-copy"></i>
          <span>Sao chép nội dung</span>
        </button>
      </div>
    </div>
  `;

  return `
    <div class="review-card" data-review-id="${review.review_id}">
      <div class="review-header">
        <img src="${avatarUrl}"
          alt="Avatar" class="review-avatar"
          onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'50\\' height=\\'50\\'%3E%3Ccircle cx=\\'25\\' cy=\\'25\\' r=\\'25\\' fill=\\'%23ddd\\'/%3E%3Ctext x=\\'50%25\\' y=\\'50%25\\' text-anchor=\\'middle\\' dy=\\'.3em\\' fill=\\'%23999\\' font-size=\\'20\\'%3E%3F%3C/text%3E%3C/svg%3E'">
        <div class="review-info flex-grow-1">
          <h5>${escapeHtml(review.full_name)}</h5>
          <div class="review-date">
            <i class="far fa-clock me-1"></i>
            ${formattedDate}
          </div>
        </div>
        <div class="review-rating">
          ${starsHTML}
        </div>
        ${actionsMenuHTML}
      </div>
      <div class="review-text">
        ${escapeHtml(review.comment).replace(/\n/g, "<br>")}
      </div>
      ${imagesHTML}
    </div>
  `;
}

function escapeHtml(text) {
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  };
  return text.replace(/[&<>"']/g, (m) => map[m]);
}
