/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    
    // 1. Auto-scroll a chat aljára
    const chatMsgs = document.getElementById("chat-messages");
    if (chatMsgs) {
        chatMsgs.scrollTop = chatMsgs.scrollHeight;
    }

    // 2. Fájlnév kiírása csatoláskor
    const fileInput = document.getElementById("chat-file-input") as HTMLInputElement | null;
    const fileDisplay = document.getElementById("file-name-display");
    
    if (fileInput && fileDisplay) {
        fileInput.addEventListener("change", function() {
            if (this.files && this.files.length > 0) {
                fileDisplay.textContent = "Csatolva: " + this.files[0].name;
            } else {
                fileDisplay.textContent = "";
            }
        });
    }

    // 3. Textarea dinamikus magasság és Enter-es küldés
    const chatTextarea = document.querySelector(".chat-textarea") as HTMLTextAreaElement | null;
    
    if (chatTextarea) {
        chatTextarea.addEventListener("input", function() {
            this.style.height = "24px"; // Alaphelyzetbe állítás a méréshez
            this.style.height = (this.scrollHeight) + "px"; // Igazítás a tartalomhoz
        });

        chatTextarea.addEventListener("keydown", function(e: KeyboardEvent) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault(); 
                if (this.value.trim() !== '') {
                    const form = this.closest("form");
                    if (form) {
                        form.submit();
                    }
                }
            }
        });
    }
});