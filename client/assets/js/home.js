// Rooms
function moveSlide(direction) {
  const slider = document.getElementById("slider");
  const cardWidth = 350;
  const gap = 20;
  const scrollAmount = cardWidth + gap;

  slider.scrollBy({
    left: direction * scrollAmount,
    behavior: "smooth",
  });
}
//  experience
const experienceSlider = document.getElementById("experience-slider");
Object.keys(experienceSlides).forEach((key) => {
  const slide = experienceSlides[key];
  const card = document.createElement("div");
  card.className = "experience-card";
  card.innerHTML = `
    <img src="${slide.image}" alt="${slide.title}" />
    <div class="overlay"> 
      <h3>${slide.title}</h3>
    </div>
  `;
  experienceSlider.appendChild(card);
});
function moveExperience(direction) {
  const slider = document.getElementById("experience-slider");
  const cardWidth = 263;
  const gap = 20;
  const scrollAmount = cardWidth + gap;

  slider.scrollBy({
    left: direction * scrollAmount,
    behavior: "smooth",
  });
}
//review
function moveTestimonial(direction) {
  const slider = document.getElementById("testimonial-slider");
  const cardWidth = 350; // chiều rộng mỗi thẻ
  const gap = 20; // khoảng cách giữa các thẻ
  const scrollAmount = cardWidth + gap;

  slider.scrollBy({
    left: direction * scrollAmount,
    behavior: "smooth",
  });
}
// Ham tu dong cuon
function autoScrollSlider(sliderId, cardWidth, gap, interval = 5000) {
  const slider = document.getElementById(sliderId);
  const scrollAmount = cardWidth + gap;

  let currentIndex = 0;
  const totalCards = slider.children.length;

  // Tính số thẻ hiển thị dựa trên chiều rộng slider
  const visibleCards = Math.floor(slider.offsetWidth / scrollAmount);

  setInterval(() => {
    currentIndex++;

    if (currentIndex > totalCards - visibleCards) {
      currentIndex = 0;
      slider.scrollTo({
        left: 0,
        behavior: "smooth",
      });
    } else {
      slider.scrollBy({
        left: scrollAmount,
        behavior: "smooth",
      });
    }
  }, interval);
}

// Khởi động cho cả hai slider
window.addEventListener("DOMContentLoaded", () => {
  autoScrollSlider("experience-slider", 263, 20); // Trải nghiệm cao cấp
  autoScrollSlider("slider", 350, 20); // Phòng tốt nhất
  autoScrollSlider("testimonial-slider", 350, 20);
});
document.addEventListener("DOMContentLoaded", function () {
  const slides = document.querySelectorAll(".slide");

  slides.forEach((slide) => {
    slide.addEventListener("click", function () {
      const roomId = this.getAttribute("data-id");
      if (roomId) {
        window.location.href =
          "/My-Web-Hotel/client/index.php?page=room-detail&id=" + roomId;
      }
    });
  });
});
