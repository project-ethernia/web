/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    
    // Kép URL Előnézet betöltése valós időben
    const urlInput = document.getElementById("news-img-input") as HTMLInputElement | null;
    const imgPreview = document.getElementById("news-img-preview") as HTMLImageElement | null;

    if (urlInput && imgPreview) {
        urlInput.addEventListener("input", () => {
            const url = urlInput.value.trim();
            if (url !== "") {
                imgPreview.src = url;
                imgPreview.style.display = "block";
            } else {
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
    const textarea = document.getElementById("news-textarea") as HTMLTextAreaElement | null;
    if (textarea) {
        textarea.addEventListener("input", function() {
            this.style.height = "150px"; // Alapméret
            if (this.scrollHeight > 150) {
                this.style.height = (this.scrollHeight) + "px";
            }
        });
    }
});