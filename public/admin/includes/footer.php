</main>
</div>

<div id="eth-confirm-modal" class="eth-modal-overlay">
    <div class="modal-container glass" style="max-width: 420px; text-align: center; padding: 2.5rem 2rem;">
        <span class="material-symbols-rounded" style="font-size: 4rem; color: var(--admin-warning); margin-bottom: 1rem; filter: drop-shadow(0 0 10px rgba(245, 158, 11, 0.4));">warning</span>
        <h3 style="color: #fff; margin-bottom: 0.5rem; font-family: var(--font-heading); font-size: 1.4rem;">Megerősítés szükséges</h3>
        <p id="eth-confirm-text" style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.5;"></p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button id="eth-confirm-cancel" class="btn-action btn-back" style="min-width: 120px;">Mégse</button>
            <button id="eth-confirm-ok" class="btn-action btn-danger" style="min-width: 120px;">Igen, biztosan</button>
        </div>
    </div>
</div>

<script>
    // GLOBÁLIS TOAST ÉRTESÍTŐ
    window.showToast = function(type, message) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        let icon = 'info';
        if (type === 'success') icon = 'check_circle';
        if (type === 'error') icon = 'error';
        if (type === 'warning') icon = 'warning';
        
        toast.innerHTML = '<span class="material-symbols-rounded">' + icon + '</span> ' + message;
        container.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // GLOBÁLIS MEGERŐSÍTŐ ABLAK (Native confirm() helyett!)
    window.ethConfirm = function(message, onConfirm) {
        const modal = document.getElementById('eth-confirm-modal');
        const textObj = document.getElementById('eth-confirm-text');
        const btnOk = document.getElementById('eth-confirm-ok');
        const btnCancel = document.getElementById('eth-confirm-cancel');

        if (!modal || !textObj || !btnOk || !btnCancel) return;

        textObj.innerText = message;
        modal.classList.add('active');

        const close = () => { modal.classList.remove('active'); };

        btnCancel.onclick = close;
        btnOk.onclick = () => {
            close();
            if (typeof onConfirm === 'function') onConfirm();
        };
    };
</script>

<script src="/admin/assets/js/globals.js?v=<?= time(); ?>"></script>
<script src="/admin/assets/js/sidebar.js?v=<?= time(); ?>"></script>
<?php if (!empty($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
        <script src="<?= $js ?>?v=<?= time(); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>