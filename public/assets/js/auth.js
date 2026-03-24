"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", () => {
    // 1. JELSZÓ EGYEZÉS VALÓS IDŐBEN (A jelszómérőt töröltük!)
    const passInput = document.getElementById('password');
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
        // Mindkét mező változásakor ellenőrizzük
        passInput.addEventListener('input', checkMatch);
        passConfirmInput.addEventListener('input', checkMatch);
    }
});
