let currentSlide = 0;
const slides = document.querySelectorAll(".slide");
const thumbnails = document.querySelectorAll(".gallery-item");
const sliderTrack = document.getElementById("sliderTrack");
const thumbnailGallery = document.getElementById("thumbnailGallery");
const totalSlides = slides.length;

function updateSlider() {
  // Update main slider
  sliderTrack.style.transform = `translateX(-${currentSlide * 100}%)`;

  // Update active thumbnail
  thumbnails.forEach((thumb, index) => {
    thumb.classList.remove("active");
    if (index === currentSlide) {
      thumb.classList.add("active");
    }
  });

  // Scroll thumbnail gallery to show active thumbnail
  const activeThumb = thumbnails[currentSlide];
  const thumbnailWidth = activeThumb.offsetWidth + 10; // width + gap
  const galleryWidth = thumbnailGallery.offsetWidth;
  const scrollPosition =
    currentSlide * thumbnailWidth - galleryWidth / 2 + thumbnailWidth / 2;
  thumbnailGallery.scrollTo({
    left: scrollPosition,
    behavior: "smooth",
  });
}

function moveSlide(direction) {
  currentSlide += direction;

  // Loop around
  if (currentSlide < 0) {
    currentSlide = totalSlides - 1;
  } else if (currentSlide >= totalSlides) {
    currentSlide = 0;
  }

  updateSlider();
}

function goToSlide(index) {
  currentSlide = index;
  updateSlider();
}

// Keyboard navigation
document.addEventListener("keydown", function (e) {
  if (e.key === "ArrowLeft") {
    moveSlide(-1);
  } else if (e.key === "ArrowRight") {
    moveSlide(1);
  }
});

// Touch swipe support
let touchStartX = 0;
let touchEndX = 0;
const slider = document.querySelector(".main-image-slider");

slider.addEventListener("touchstart", (e) => {
  touchStartX = e.changedTouches[0].screenX;
});

slider.addEventListener("touchend", (e) => {
  touchEndX = e.changedTouches[0].screenX;
  handleSwipe();
});

function handleSwipe() {
  if (touchEndX < touchStartX - 50) {
    moveSlide(1);
  }
  if (touchEndX > touchStartX + 50) {
    moveSlide(-1);
  }
}

// Xử lý nút "Xem Chi Tiết" trong suggestion cards
document.querySelectorAll(".view-btn").forEach((btn) => {
  btn.addEventListener("click", function (e) {
    e.preventDefault();

    // Lấy href từ thẻ <a>
    const href = this.getAttribute("href");

    // Kiểm tra href có tồn tại không
    if (href && href !== "#") {
      // Chuyển hướng đến trang chi tiết phòng
      window.location.href = href;
    } else {
    }
  });
});

// Lấy ngày giờ hiện tại format YYYY-MM-DDTHH:mm theo giờ địa phương
const now = new Date();
const year = now.getFullYear();
const month = String(now.getMonth() + 1).padStart(2, '0');
const day = String(now.getDate()).padStart(2, '0');
const hours = String(now.getHours()).padStart(2, '0');
const minutes = String(now.getMinutes()).padStart(2, '0');
const today = `${year}-${month}-${day}T${hours}:${minutes}`;

const checkin = document.getElementById("checkin");
const checkout = document.getElementById("checkout");

// Không cho chọn ngày quá khứ
if (checkin) checkin.min = today;
if (checkout) checkout.min = today;

// Khi người dùng chọn ngày nhận phòng
if (checkin) {
  checkin.addEventListener("change", function () {
    const checkinDate = checkin.value;

    // Gán min cho checkout bằng checkin
    if (checkout) checkout.min = checkinDate;

    // Nếu ngày checkout hiện tại < checkin → tự động cập nhật
    if (checkout && checkout.value && checkout.value < checkinDate) {
      checkout.value = checkinDate;
    }
  });
}
