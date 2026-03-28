/// <reference lib="dom" />

declare function showToast(type: string, msg: string): void;
declare function ethConfirm(msg: string, cb: Function): void;

const refreshAdminTable = async () => {
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
};

const executeAdminAction = async (action: string, id: number) => {
    try {
        const res = await fetch('/admin/api/admin_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, id })
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast('success', data.message);
            refreshAdminTable(); 
        } else {
            showToast('error', data.message || 'Hiba történt a művelet során.');
        }
    } catch (err) {
        console.error(err);
        showToast('error', 'Hálózati hiba történt az API hívás közben!');
    }
};

const doAdminAction = (action: string, id: number, confirmMessage?: string) => {
    if (confirmMessage) {
        ethConfirm(confirmMessage, () => executeAdminAction(action, id));
    } else {
        executeAdminAction(action, id);
    }
};

const handleAddAdmin = async (e: Event) => {
    e.preventDefault(); 
    
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
            form.reset(); 
            refreshAdminTable(); 
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
};

(window as any).doAdminAction = doAdminAction;
(window as any).handleAddAdmin = handleAddAdmin;