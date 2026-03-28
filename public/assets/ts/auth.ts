/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    if (!document.getElementById('spin-anim')) {
        const style = document.createElement('style');
        style.id = 'spin-anim';
        style.textContent = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
        document.head.appendChild(style);
    }

    const forms = document.querySelectorAll("form");
    
    forms.forEach(form => {
        const isLogin = window.location.pathname.includes('login') || form.action.includes('login');
        
        if (isLogin) {
            form.addEventListener("submit", async (e) => {
                e.preventDefault(); 
                
                const btn = form.querySelector("button[type='submit']") as HTMLButtonElement | null;
                if (btn) btn.disabled = true;

                const loaderHTML = `
                    <div id="enterprise-loader" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(7, 5, 15, 0.95); backdrop-filter: blur(15px); z-index: 999999; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; opacity: 0; transition: opacity 0.3s ease;">
                        <span class="material-symbols-rounded" style="font-size: 5rem; color: var(--eth-primary); animation: spin 1s linear infinite;">sync</span>
                        <h2 style="margin-top: 2rem; font-family: 'Poppins', sans-serif; letter-spacing: 0.15em; font-size: 1.5rem; color: var(--eth-primary); text-shadow: 0 0 20px var(--eth-primary-glow); text-transform: uppercase;">Hitelesítés folyamatban</h2>
                        <p style="color: #94a3b8; font-family: 'Outfit', sans-serif; margin-top: 0.5rem; font-size: 1.1rem;">Kérjük, várj a biztonságos csatlakozásra...</p>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', loaderHTML);
                
                const loader = document.getElementById('enterprise-loader');
                if (loader) {
                    setTimeout(() => { loader.style.opacity = '1'; }, 10);
                }

                try {
                    const formData = new FormData(form);
                    const res = await fetch(form.action || window.location.href, {
                        method: form.method || 'POST',
                        body: formData
                    });
                    
                    setTimeout(() => {
                        window.location.href = res.url; 
                    }, 2500);

                } catch (err) {
                    if ((window as any).Toast) {
                        (window as any).Toast.error("Hálózati hiba történt!");
                    }
                    setTimeout(() => { window.location.reload(); }, 1500);
                }
            });
        }
    });
});