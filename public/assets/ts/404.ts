/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const timerEl = document.getElementById('redirect-timer');
    
    if (timerEl) {
        // Kiolvassuk a kezdőértéket a HTML-ből (10)
        let secondsLeft = parseInt(timerEl.textContent || '10', 10);
        
        const interval = setInterval(() => {
            secondsLeft--;
            timerEl.textContent = secondsLeft.toString();
            
            if (secondsLeft <= 0) {
                clearInterval(interval);
                // Lejárt az idő, visszairányítjuk a főoldalra
                window.location.href = '/';
            }
        }, 1000);
    }
});