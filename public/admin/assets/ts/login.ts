/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    // Megkeressük a bejelentkező vagy a 2FA formot
    const loginForm = document.querySelector('form') as HTMLFormElement | null;
    
    if (loginForm) {
        // Hozzáadjuk az Overlay HTML-t a form köré
        const formContainer = loginForm.closest('.glass') || loginForm; 
        
        // Létrehozzuk az overlay-t
        const overlay = document.createElement('div');
        overlay.className = 'auth-overlay';
        overlay.innerHTML = `
            <div class="auth-spinner"></div>
            <div class="auth-text" id="auth-status-text">Hitelesítés folyamatban...</div>
        `;
        
        // Relatívvá tesszük a szülőt, hogy az overlay rátakarjon
        (formContainer as HTMLElement).style.position = 'relative';
        (formContainer as HTMLElement).style.overflow = 'hidden';
        formContainer.appendChild(overlay);

        // Elkapjuk a beküldést
        loginForm.addEventListener('submit', (e) => {
            // CSAK AKKOR ÁLLÍTJUK MEG, HA MÉG NEM VOLT ANIMÁLVA
            if (!loginForm.dataset.validated) {
                e.preventDefault(); // Megállítjuk a normál küldést
                
                // Gomb letiltása, overlay megjelenítése
                const submitBtn = loginForm.querySelector('button[type="submit"], input[type="submit"]') as HTMLButtonElement | HTMLInputElement | null;
                if (submitBtn) submitBtn.disabled = true;
                overlay.classList.add('active');
                
                // Mű-ellenőrzés (2 másodperc)
                setTimeout(() => {
                    const statusText = document.getElementById('auth-status-text');
                    if(statusText) {
                        statusText.style.color = 'var(--admin-success)';
                        statusText.innerHTML = '<span class="material-symbols-rounded" style="vertical-align: bottom;">check_circle</span> Hitelesítve!';
                    }
                    
                    // További fél másodperc a "Sikeres" szöveg elolvasására, majd a tényleges beküldés
                    setTimeout(() => {
                        loginForm.dataset.validated = "true"; // Megjelöljük, hogy túl vagyunk az animáción
                        
                        // JAVÍTÁS: Biztosítjuk, hogy a backend megkapja a gomb (submit) nevét és értékét is!
                        if (submitBtn && submitBtn.name) {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = submitBtn.name;
                            hiddenInput.value = submitBtn.value || '1';
                            loginForm.appendChild(hiddenInput);
                        }
                        
                        loginForm.submit(); // Tényleges beküldés a PHP-nak
                    }, 500);

                }, 2000); // 2000ms = 2 másodperc fake töltés
            }
        });
    }
});