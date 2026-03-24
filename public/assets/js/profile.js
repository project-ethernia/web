"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", () => {
    // Jelszó Egyezés Ellenőrző a Profil oldalon
    const newPassInput = document.getElementById('new_password');
    const newPassConfInput = document.getElementById('new_password_confirm');
    const matchIcon = document.getElementById('prof-match-icon');
    if (newPassInput && newPassConfInput && matchIcon) {
        const checkMatch = () => {
            if (newPassConfInput.value.length === 0) {
                matchIcon.textContent = '';
                return;
            }
            if (newPassInput.value === newPassConfInput.value) {
                matchIcon.textContent = 'check_circle'; // Zöld Pipa
                matchIcon.style.color = '#22c55e';
            }
            else {
                matchIcon.textContent = 'cancel'; // Piros X
                matchIcon.style.color = '#ef4444';
            }
        };
        newPassInput.addEventListener('input', checkMatch);
        newPassConfInput.addEventListener('input', checkMatch);
    }
});
