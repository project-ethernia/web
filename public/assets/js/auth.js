"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", () => {
    // 1. ÉLŐ JELSZÓ-ERŐSSÉG MÉRŐ
    const passInput = document.getElementById('password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    if (passInput && strengthBar && strengthText) {
        passInput.addEventListener('input', () => {
            const val = passInput.value;
            let strength = 0;
            if (val.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = 'Írj be egy jelszót...';
                strengthText.style.color = 'var(--text-muted)';
                return;
            }
            // Pontrendszer:
            if (val.length >= 6)
                strength += 25; // Alaphossz
            if (val.length >= 10)
                strength += 25; // Jó hosszú
            if (/[A-Z]/.test(val))
                strength += 25; // Tartalmaz nagybetűt
            if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val))
                strength += 25; // Szám ÉS speciális karakter
            // Vizuális frissítés (A globális CSS változókat is használhatnánk itt JS-ből, de a hex kód is jó)
            strengthBar.style.width = `${strength}%`;
            if (strength <= 25) {
                strengthBar.style.background = '#ef4444'; // Piros
                strengthText.textContent = 'Gyenge';
                strengthText.style.color = '#ef4444';
            }
            else if (strength <= 75) {
                strengthBar.style.background = '#eab308'; // Sárga
                strengthText.textContent = 'Közepes';
                strengthText.style.color = '#eab308';
            }
            else {
                strengthBar.style.background = '#22c55e'; // Zöld
                strengthText.textContent = 'Erős';
                strengthText.style.color = '#22c55e';
            }
        });
    }
    // 2. JELSZÓ EGYEZÉS VALÓS IDŐBEN
    const passConfirmInput = document.getElementById('password_confirm');
    const matchIcon = document.getElementById('match-icon');
    if (passInput && passConfirmInput && matchIcon) {
        const checkMatch = () => {
            if (passConfirmInput.value.length === 0) {
                matchIcon.textContent = '';
                return;
            }
            if (passInput.value === passConfirmInput.value) {
                matchIcon.textContent = 'check_circle'; // Zöld Pipa
                matchIcon.style.color = '#22c55e';
            }
            else {
                matchIcon.textContent = 'cancel'; // Piros X
                matchIcon.style.color = '#ef4444';
            }
        };
        passInput.addEventListener('input', checkMatch);
        passConfirmInput.addEventListener('input', checkMatch);
    }
});
