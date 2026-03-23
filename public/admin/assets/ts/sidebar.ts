/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
  const rippleButtons = document.querySelectorAll<HTMLElement>(".ripple-btn");

  rippleButtons.forEach((button) => {
    button.addEventListener("click", function (e: MouseEvent) {
      const rect = button.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      const circle = document.createElement("span");
      circle.classList.add("ripple");
      circle.style.left = `${x}px`;
      circle.style.top = `${y}px`;

      const diameter = Math.max(rect.width, rect.height);
      circle.style.width = circle.style.height = `${diameter}px`;

      button.appendChild(circle);

      setTimeout(() => {
        circle.remove();
      }, 600);
    });
  });
});