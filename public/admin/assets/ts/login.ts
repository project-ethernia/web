/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    if (!document.getElementById('spin-anim')) {
        const style = document.createElement('style');
        style.id = 'spin-anim';
        style.textContent = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
        document.head.appendChild(style);
    }

    const form = document.querySelector("form.login-form") as HTMLFormElement | null;
    
    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault(); 
            
            const btn = form.querySelector("button[type='submit']") as HTMLButtonElement | null;
            if (btn) btn.disabled = true;

            const loaderHTML = `
                <div id="admin-loader" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #0b0710; backdrop-filter: blur(15px); z-index: 999999; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; opacity: 0; transition: opacity 0.3s ease;">
                    <span class="material-symbols-rounded" style="font-size: 5rem; color: #ef4444; filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.4)); animation: spin 1s linear infinite;">admin_panel_settings</span>
                    <h2 style="margin-top: 2rem; font-family: 'Poppins', sans-serif; letter-spacing: 0.15em; font-size: 1.5rem; color: #ef4444; text-shadow: 0 0 20px rgba(239, 68, 68, 0.4); text-transform: uppercase;">Rendszer Betöltése</h2>
                    <p style="color: #94a3b8; font-family: 'Outfit', sans-serif; margin-top: 0.5rem; font-size: 1.1rem;">Titkosított csatorna felépítése...</p>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', loaderHTML);
            
            const loader = document.getElementById('admin-loader');
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
                    (window as any).Toast.error("Kapcsolati hiba a szerverrel!");
                }
                setTimeout(() => { window.location.reload(); }, 1500);
            }
        });
    }
});