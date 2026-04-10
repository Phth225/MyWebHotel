(function () {
  const slides = document.getElementById("slides");
  const total = slides.children.length;
  let idx = 0;
  const dotsWrap = document.getElementById("dots");

  function renderDots() {
    for (let i = 0; i < total; i++) {
      const d = document.createElement("div");
      d.className = "dot" + (i === 0 ? " active" : "");
      d.dataset.index = i;
      d.addEventListener("click", () => {
        goTo(i);
      });
      dotsWrap.appendChild(d);
    }
  }

  function update() {
    slides.style.transform = "translateX(" + -idx * 100 + "%)";
    Array.from(dotsWrap.children).forEach((dot, i) =>
      dot.classList.toggle("active", i === idx)
    );
  }
  function next() {
    idx = (idx + 1) % total;
    update();
  }
  function prev() {
    idx = (idx - 1 + total) % total;
    update();
  }
  function goTo(i) {
    idx = i;
    update();
  }

  document.getElementById("next").addEventListener("click", next);
  document.getElementById("prev").addEventListener("click", prev);

  renderDots();
  let timer = setInterval(next, 5000);
  // pause on hover
  const slider = document.querySelector(".slider");
  slider.addEventListener("mouseenter", () => clearInterval(timer));
  slider.addEventListener(
    "mouseleave",
    () => (timer = setInterval(next, 5000))
  );
})();
