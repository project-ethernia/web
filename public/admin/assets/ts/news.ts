/// <reference lib="dom" />

declare function showToast(type: string, msg: string): void;
declare function ethConfirm(msg: string, cb: Function): void;

// Mivel az onclick események a HTML stringben vannak, szólni kell a TS-nek, hogy léteznek.
declare function doNewsAction(action: string, id: number, state?: number): void;
declare function editNews(id: number): void;

document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.getElementById("news-tbody");
    const searchInput = document.getElementById("news-search") as HTMLInputElement | null;
    const form = document.getElementById("news-form") as HTMLFormElement | null;
    let debounceTimer: number;
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

                currentNewsList = json.data; 

                json.data.forEach((news: any) => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover-row';
                    const isPub = Number(news.is_published) === 1;
                    
                    const visibilityBtn = isPub 
                        ? `<button type="button" class="toggle-visibility active" style="cursor: pointer;" title="Kattints az elrejtéshez" onclick="doNewsAction('toggle', ${news.id}, 0)"><span class="material-symbols-rounded">visibility</span></button>`
                        : `<button type="button" class="toggle-visibility inactive" style="cursor: pointer;" title="Kattints a közzétételhez" onclick="doNewsAction('toggle', ${news.id}, 1)"><span class="material-symbols-rounded">visibility_off</span></button>`;

                    let badgeClass = 'default';
                    if (news.category === 'Karbantartás') badgeClass = 'info';
                    else if (news.category === 'Frissítés') badgeClass = 'success';
                    else if (news.category === 'Bejelentés') badgeClass = 'warning';
                    else if (news.category === 'Esemény') badgeClass = 'error';

                    tr.innerHTML = `
                        <td class="td-id">#${String(news.id).padStart(3, '0')}</td>
                        <td>
                            <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 0.4rem; color: #fff;">${news.title}</div>
                            <span class="badge ${badgeClass}">${news.category}</span>
                        </td>
                        <td class="td-muted">${news.author_name || 'Ismeretlen'}</td>
                        <td>${visibilityBtn}</td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn-sm btn-edit" onclick="editNews(${news.id})"><span class="material-symbols-rounded">edit</span></button>
                                <button type="button" class="btn-sm btn-danger" onclick="doNewsAction('delete', ${news.id})"><span class="material-symbols-rounded">delete</span></button>
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
                snippet: formData.get('snippet'),
                content: formData.get('content'),
                image_url: formData.get('image_url'),
                is_published: formData.get('is_published') ? 1 : 0
            };

            const btn = form.querySelector('button[type="submit"]') as HTMLButtonElement | null;
            if (btn) { btn.disabled = true; btn.innerText = "Mentés..."; }

            try {
                const res = await fetch('/admin/api/news_action.php', {
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    showToast('success', data.message);
                    form.reset();
                    
                    const actionInput = document.getElementById('news-action') as HTMLInputElement | null;
                    if(actionInput) actionInput.value = 'add';
                    
                    const defaultRadio = document.querySelector('input[name="category"][value="Karbantartás"]') as HTMLInputElement | null;
                    if(defaultRadio) defaultRadio.checked = true;

                    const headerText = document.getElementById('form-header-text');
                    const headerIcon = document.getElementById('form-header-icon');
                    if (headerText && headerIcon) {
                        headerText.innerText = 'Új hír írása';
                        headerIcon.innerText = 'add_circle';
                        headerIcon.style.color = 'var(--admin-red)';
                    }
                    
                    loadNews();
                } else {
                    showToast('error', data.message);
                }
            } catch (err) {
                showToast('error', "Hálózati hiba a mentés során.");
            } finally {
                if (btn) { btn.disabled = false; btn.innerText = "Közzététel / Mentés"; }
            }
        });
    }

    async function executeAction(action: string, id: number, state?: number) {
        try {
            const res = await fetch('/admin/api/news_action.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, id, state })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast('success', data.message);
                loadNews(); 
            } else {
                showToast('error', data.message);
            }
        } catch(e) {
            showToast('error', "Hiba az akció során.");
        }
    }

    (window as any).doNewsAction = (action: string, id: number, state?: number) => {
        if (action === 'delete') {
            ethConfirm("Biztosan véglegesen törlöd ezt a hírt?", () => executeAction(action, id, state));
        } else {
            executeAction(action, id, state);
        }
    };

    (window as any).editNews = (id: number) => {
        const news = currentNewsList.find((n: any) => Number(n.id) === id);
        if (!news) return;

        const actionInput = document.getElementById('news-action') as HTMLInputElement | null;
        if (actionInput) actionInput.value = 'edit';
        
        const idInput = document.getElementById('news-id') as HTMLInputElement | null;
        if (idInput) idInput.value = news.id;
        
        const titleInput = document.getElementById('news-title') as HTMLInputElement | null;
        if (titleInput) titleInput.value = news.title;
        
        const catRadio = document.querySelector(`input[name="category"][value="${news.category}"]`) as HTMLInputElement | null;
        if (catRadio) catRadio.checked = true;
        
        const snippetInput = document.getElementById('news-snippet') as HTMLTextAreaElement | null;
        if (snippetInput) snippetInput.value = news.snippet || '';
        
        const contentInput = document.getElementById('news-content') as HTMLTextAreaElement | null;
        if (contentInput) contentInput.value = news.content || '';
        
        const imageInput = document.getElementById('news-image') as HTMLInputElement | null;
        if (imageInput) imageInput.value = news.image_url || '';
        
        const pubInput = document.getElementById('news-published') as HTMLInputElement | null;
        if (pubInput) pubInput.checked = (Number(news.is_published) === 1);
        
        const headerText = document.getElementById('form-header-text');
        const headerIcon = document.getElementById('form-header-icon');
        if (headerText && headerIcon) {
            headerText.innerText = 'Hír szerkesztése';
            headerIcon.innerText = 'edit';
            headerIcon.style.color = 'var(--admin-info)';
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    loadNews();
});