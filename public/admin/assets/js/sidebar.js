"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    var rippleButtons = document.querySelectorAll(".ripple-btn");
    rippleButtons.forEach(function (button) {
        button.addEventListener("click", function (e) {
            var rect = button.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var y = e.clientY - rect.top;
            var circle = document.createElement("span");
            circle.classList.add("ripple");
            circle.style.left = "".concat(x, "px");
            circle.style.top = "".concat(y, "px");
            var diameter = Math.max(rect.width, rect.height);
            circle.style.width = circle.style.height = "".concat(diameter, "px");
            button.appendChild(circle);
            setTimeout(function () {
                circle.remove();
            }, 600);
        });
    });
});
