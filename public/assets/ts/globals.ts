/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    // Globális Visszaszámláló Timer a Navbarhoz
    const timerEl = document.getElementById('countdown-timer');
    if (timerEl) {
        let seconds = parseInt(timerEl.getAttribute('data-seconds') || '3600', 10);
        
        const updateTimer = () => {
            if (seconds > 0) seconds--;
            let m = Math.floor(seconds / 60).toString().padStart(2, '0');
            let s = (seconds % 60).toString().padStart(2, '0');
            timerEl.innerText = `${m}:${s}`;
            
            if (seconds <= 0) {
                window.location.href = '/auth/logout.php';
            }
        };
        
        updateTimer();
        setInterval(updateTimer, 1000);
    }
});

// Globális megerősítő ablak (pl. gombokhoz, ha később kellene)
function ethConfirm(e: Event, msg: string, url: string): void {
    e.preventDefault();
    if (confirm(msg)) {
        window.location.href = url;
    }
}

// Window objektumhoz rendeljük, hogy a HTML onclick="ethConfirm(...)" elérje
(window as any).ethConfirm = ethConfirm;