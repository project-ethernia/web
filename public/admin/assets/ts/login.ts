/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    // 1. Avatar betöltése név beírása után
    const userInp = document.getElementById('username-input') as HTMLInputElement | null;
    const avatarImg = document.getElementById('dynamic-avatar') as HTMLImageElement | null;

    if (userInp && avatarImg) {
        userInp.addEventListener('blur', () => {
            const username = userInp.value.trim();
            if (username.length > 2) {
                avatarImg.src = `https://minotar.net/helm/${username}/80.png`;
            }
        });
    }

    // 2. Visszaszámláló óra (Timer) ha le van tiltva a fiók
    const timerEl = document.getElementById('countdown') as HTMLElement | null;
    
    if (timerEl) {
        const endData = timerEl.dataset.end;
        if (endData) {
            const endTime = parseInt(endData, 10) * 1000;
            
            const timerInterval = setInterval(() => {
                const now = new Date().getTime();
                const distance = endTime - now;
                
                if (distance <= 0) {
                    clearInterval(timerInterval);
                    // Ha lejárt, frissítsük az oldalt, hogy visszajöjjön a login form
                    window.location.href = '/admin/login.php'; 
                } else {
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    timerEl.innerHTML = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
                }
            }, 1000);
        }
    }
});