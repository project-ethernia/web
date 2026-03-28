/// <reference lib="dom" />

declare function showToast(type: string, message: string): void;

document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.getElementById("news-tbody");
    const searchInput = document.getElementById("news-search") as HTMLInputElement | null;
    const form = document.getElementById("news-form") as HTMLFormElement | null;
    let debounceTimer: number;
    
    // JAVÍTÁS: Itt tároljuk a híreket memóriában, így nem kell a HTML kódot összetörnünk vele!
    let currentNewsList: any[] = []; 

    async function loadNews(query: string = '') {
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;"><span class="material-symbols-rounded spinning" style="font-size: 2rem;">refresh</span><br><br>Betöltés...</td></tr>';

        try {
            const res = await fetch(`/admin/api/get_news.php?q=${encodeURIComponent(query)}`);
            const json = await res.json();

            if (json.status === 'success') {
                tbody.innerHTML = '';
                if (json.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">Nincs a keresésnek megfelelő találat.</td></tr>';
                    return;
                }

                currentNewsList = json.data; // Eltároljuk a friss listát a szerkesztéshez

                json.data.forEach((news: any) => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover-row';
                    const isPub = Number(news.is_published) === 1;
                    const visibilityBtn = isPub 
                        ? `<button class="toggle-visibility active" title="Elrejtés" onclick="doNewsAction('toggle', ${news.id}, 0)"><span class="material-symbols-rounded">visibility</span></button>`
                        : `<button class="toggle-visibility inactive" title="Közzététel" onclick="doNewsAction('toggle', ${news.id}, 1)"><span class="material-symbols-rounded">visibility_off</span></button>`;

                    tr.innerHTML = `
                        <td class="td-id">#${String(news.id).padStart(3, '0')}</td>
                        <td>
                            <div class="news-title">${news.title}</div>
                            <span class="cat-badge" style="background: rgba(255,255,255,0.1);">${news.category}</span>
                        </td>
                        <td class="td-muted">${news.author_name || 'Ismeretlen'}</td>
                        <td>${visibilityBtn}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-sm btn-edit" onclick="editNews(${news.id})"><span class="material-symbols-rounded">edit</span></button>
                                <button class="btn-sm btn-danger" onclick="doNewsAction('delete', ${news.id})"><span class="material-symbols-rounded">delete</span></button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--admin-red);">Hálózati hiba történt.</td></tr>`;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadNews((e.target as HTMLInputElement).value), 300);
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const payload = {
                action: formData.get('action'),
                id: formData.get('id'),
                title: formData.get('title'),
                category: formData.get('category'),
                content: formData.get('content'),
                is_published: formData.get('is_published') ? 1 : 0
            };

            const btn = form.querySelector('button[type="submit"]') as HTMLButtonElement | null;
            if (btn) { btn.disabled = true; btn.innerText = "Mentés..."; }

            try {
                const res = await fetch('/admin/api/news_action.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    if(typeof showToast === 'function') showToast('success', data.message); else alert(data.message);
                    form.reset();
                    
                    const actionInput = document.getElementById('news-action') as HTMLInputElement | null;
                    if(actionInput) actionInput.value = 'add';
                    
                    const headerText = document.querySelector('.form-panel .panel-header h2') as HTMLElement | null;
                    if (headerText) headerText.innerHTML = '<span class="material-symbols-rounded">add_circle</span> Új hír írása';
                    
                    loadNews();
                } else {
                    if(typeof showToast === 'function') showToast('error', data.message); else alert(data.message);
                }
            } catch (err) {
                alert("Hálózati hiba a mentés során.");
            } finally {
                if (btn) { btn.disabled = false; btn.innerText = "Közzététel / Mentés"; }
            }
        });
    }

    // Gomb funkciók globális elérése a DOM-ból
    (window as any).doNewsAction = async (action: string, id: number, state?: number) => {
        if (action === 'delete' && !confirm("Biztosan törlöd a hírt?")) return;
        try {
            const res = await fetch('/admin/api/news_action.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, id, state })
            });
            const data = await res.json();
            if (data.status === 'success') {
                if(typeof showToast === 'function') showToast('success', data.message);
                loadNews();
            } else {
                if(typeof showToast === 'function') showToast('error', data.message);
            }
        } catch(e) {
            console.error("Hiba az akció során:", e);
        }
    };

    // JAVÍTÁS: Biztonságos hír betöltés ID alapján
    (window as any).editNews = (id: number) => {
        const news = currentNewsList.find((n: any) => Number(n.id) === id);
        if (!news) return;

        const actionInput = document.getElementById('news-action') as HTMLInputElement | null;
        if (actionInput) actionInput.value = 'edit';
        
        const idInput = document.getElementById('news-id') as HTMLInputElement | null;
        if (idInput) idInput.value = news.id;
        
        const titleInput = document.getElementById('news-title') as HTMLInputElement | null;
        if (titleInput) titleInput.value = news.title;
        
        const catInput = document.getElementById('news-category') as HTMLInputElement | null;
        if (catInput) catInput.value = news.category;
        
        const contentInput = document.getElementById('news-content') as HTMLTextAreaElement | null;
        if (contentInput) contentInput.value = news.content;
        
        const pubInput = document.getElementById('news-published') as HTMLInputElement | null;
        if (pubInput) pubInput.checked = (Number(news.is_published) === 1);
        
        const headerText = document.querySelector('.form-panel .panel-header h2') as HTMLElement | null;
        if (headerText) headerText.innerHTML = '<span class="material-symbols-rounded" style="color: var(--admin-info);">edit</span> Hír szerkesztése';
        
        // Felgördülünk, hogy az admin egyből lássa a formot
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    loadNews();
});