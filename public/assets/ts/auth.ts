/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {

    // 1. JELSZÓ EGYEZÉS VALÓS IDŐBEN (A jelszómérőt töröltük!)
    const passInput = document.getElementById('password') as HTMLInputElement | null;
    const passConfirmInput = document.getElementById('password_confirm') as HTMLInputElement | null;
    const matchIcon = document.getElementById('match-icon') as HTMLElement | null;

    if (passInput && passConfirmInput && matchIcon) {
        const checkMatch = () => {
            if (passConfirmInput.value.length === 0) {
                matchIcon.textContent = '';
                return;
            }
            if (passInput.value === passConfirmInput.value) {
                matchIcon.textContent = 'check_circle'; // Zöld Pipa
                matchIcon.style.color = '#22c55e';
            } else {
                matchIcon.textContent = 'cancel'; // Piros X
                matchIcon.style.color = '#ef4444';
            }
        };

        // Mindkét mező változásakor ellenőrizzük
        passInput.addEventListener('input', checkMatch);
        passConfirmInput.addEventListener('input', checkMatch);
    }
});