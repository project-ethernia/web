"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    // Kép URL Előnézet betöltése valós időben
    var urlInput = document.getElementById("news-img-input");
    var imgPreview = document.getElementById("news-img-preview");
    if (urlInput && imgPreview) {
        urlInput.addEventListener("input", function () {
            var url = urlInput.value.trim();
            if (url !== "") {
                imgPreview.src = url;
                imgPreview.style.display = "block";
            }
            else {
                imgPreview.style.display = "none";
                imgPreview.src = "";
            }
        });
        // Ha frissítéskor már van benne adat (pl. back gomb)
        if (urlInput.value.trim() !== "") {
            imgPreview.src = urlInput.value.trim();
            imgPreview.style.display = "block";
        }
    }
    // Textarea Auto-Resize (hogy kényelmes legyen hosszabb cikkeket is írni)
    var textarea = document.getElementById("news-textarea");
    if (textarea) {
        textarea.addEventListener("input", function () {
            this.style.height = "150px"; // Alapméret
            if (this.scrollHeight > 150) {
                this.style.height = (this.scrollHeight) + "px";
            }
        });
    }
});
