<link rel="stylesheet" href="/admin/assets/css/chat.css?v=<?= time(); ?>">

<div id="admin-chat-widget">
    <button id="chat-toggle-btn" class="chat-fab ripple-btn">
        <span class="material-symbols-rounded">chat</span>
        <span id="chat-badge" class="chat-badge" style="display:none;"></span>
    </button>

    <div id="chat-panel" class="glass-panel">
        <div class="chat-header">
            <div class="chat-header-info">
                <span class="material-symbols-rounded">forum</span>
                <strong>Stáb Chat</strong>
            </div>
            <button id="chat-close-btn" class="material-symbols-rounded chat-close-icon">close</button>
        </div>
        
        <div class="chat-messages" id="chat-messages">
            </div>
        
        <form id="chat-form" class="chat-input-area">
            <input type="text" id="chat-input" placeholder="Írj valamit a stábnak..." autocomplete="off" required>
            <button type="submit" class="btn-glow-red send-btn"><span class="material-symbols-rounded">send</span></button>
        </form>
    </div>
</div>

<script src="/admin/assets/js/chat.js?v=<?= time(); ?>"></script>