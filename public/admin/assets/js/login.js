"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    // Megkeressük a bejelentkező vagy a 2FA formot
    var loginForm = document.querySelector('form');
    if (loginForm) {
        // Hozzáadjuk az Overlay HTML-t a form köré
        var formContainer = loginForm.closest('.glass') || loginForm;
        // Létrehozzuk az overlay-t
        var overlay_1 = document.createElement('div');
        overlay_1.className = 'auth-overlay';
        overlay_1.innerHTML = "\n            <div class=\"auth-spinner\"></div>\n            <div class=\"auth-text\" id=\"auth-status-text\">Hiteles\u00EDt\u00E9s folyamatban...</div>\n        ";
        // Relatívvá tesszük a szülőt, hogy az overlay rátakarjon
        formContainer.style.position = 'relative';
        formContainer.style.overflow = 'hidden';
        formContainer.appendChild(overlay_1);
        // Elkapjuk a beküldést
        loginForm.addEventListener('submit', function (e) {
            // CSAK AKKOR ÁLLÍTJUK MEG, HA MÉG NEM VOLT ANIMÁLVA
            if (!loginForm.dataset.validated) {
                e.preventDefault(); // Megállítjuk a normál küldést
                // Gomb letiltása, overlay megjelenítése
                var submitBtn_1 = loginForm.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn_1)
                    submitBtn_1.disabled = true;
                overlay_1.classList.add('active');
                // Mű-ellenőrzés (2 másodperc)
                setTimeout(function () {
                    var statusText = document.getElementById('auth-status-text');
                    if (statusText) {
                        statusText.style.color = 'var(--admin-success)';
                        statusText.innerHTML = '<span class="material-symbols-rounded" style="vertical-align: bottom;">check_circle</span> Hitelesítve!';
                    }
                    // További fél másodperc a "Sikeres" szöveg elolvasására, majd a tényleges beküldés
                    setTimeout(function () {
                        loginForm.dataset.validated = "true"; // Megjelöljük, hogy túl vagyunk az animáción
                        // JAVÍTÁS: Biztosítjuk, hogy a backend megkapja a gomb (submit) nevét és értékét is!
                        if (submitBtn_1 && submitBtn_1.name) {
                            var hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = submitBtn_1.name;
                            hiddenInput.value = submitBtn_1.value || '1';
                            loginForm.appendChild(hiddenInput);
                        }
                        loginForm.submit(); // Tényleges beküldés a PHP-nak
                    }, 500);
                }, 2000); // 2000ms = 2 másodperc fake töltés
            }
        });
    }
});
