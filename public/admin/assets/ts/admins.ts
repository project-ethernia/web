/// <reference lib="dom" />

// Toast értesítő függvény
function showToast(type: 'success' | 'error' | 'warning' | 'info', message: string) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'info';
    if (type === 'success') icon = 'check_circle';
    if (type === 'error') icon = 'error';
    if (type === 'warning') icon = 'warning';

    toast.innerHTML = `<span class="material-symbols-rounded">${icon}</span> ${message}`;
    container.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Gombnyomások (Törlés, 2FA) kezelése
async function doAdminAction(action: string, id: number, confirmMessage?: string) {
    if (confirmMessage && !confirm(confirmMessage)) return;

    try {
        const res = await fetch('/admin/api/admin_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, id })
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast('success', data.message);
            refreshAdminTable(); // Táblázat frissítése
        } else {
            showToast('error', data.message || 'Hiba történt a művelet során.');
        }
    } catch (err) {
        console.error(err);
        showToast('error', 'Hálózati hiba történt az API hívás közben!');
    }
}

// Űrlap (Hozzáadás) beküldésének kezelése
async function handleAddAdmin(e: Event) {
    e.preventDefault(); // Megakadályozzuk az oldal újratöltését!
    
    const form = e.target as HTMLFormElement;
    const formData = new FormData(form);
    
    const payload = {
        action: 'add',
        username: formData.get('username'),
        password: formData.get('password'),
        role: formData.get('role')
    };

    const submitBtn = form.querySelector('button[type="submit"]') as HTMLButtonElement;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Feldolgozás...';
    }

    try {
        const res = await fetch('/admin/api/admin_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast('success', data.message);
            form.reset(); // Ürítjük a formot
            refreshAdminTable(); // Táblázat frissítése
        } else {
            showToast('error', data.message || 'Hiba történt a hozzáadáskor.');
        }
    } catch (err) {
        console.error(err);
        showToast('error', 'Hálózati hiba történt!');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-symbols-rounded">add_circle</span> Hozzáadás';
        }
    }
}

// Élő táblázat frissítő mágia
async function refreshAdminTable() {
    try {
        const htmlRes = await fetch(window.location.href);
        const htmlText = await htmlRes.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');
        
        const currentList = document.querySelector('.list-panel');
        const newList = doc.querySelector('.list-panel');
        if (currentList && newList) {
            currentList.innerHTML = newList.innerHTML;
        }
    } catch (err) {
        console.error('Hiba a táblázat frissítésekor', err);
    }
}

// Kötjük a globális window objektumhoz, hogy a HTMLből hívhatóak legyenek
(window as any).doAdminAction = doAdminAction;
(window as any).handleAddAdmin = handleAddAdmin;