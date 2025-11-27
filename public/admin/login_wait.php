<?php
session_start();

if (empty($_SESSION['admin_pending_login_request_id']) || empty($_SESSION['admin_pending_admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$pendingUsername = isset($_SESSION['admin_pending_username']) ? (string)$_SESSION['admin_pending_username'] : '';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Jóváhagyás folyamatban</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/login.css?v=<?= time(); ?>">
</head>
<body class="public-body">
  <main class="auth-page">
    <section class="auth-card">
      <h1 class="auth-title auth-title-center">Jóváhagyás Discordon</h1>
      <?php if ($pendingUsername): ?>
        <p class="auth-footnote" style="margin-top:4px;margin-bottom:6px;font-size:0.85rem;">
          Folyamatban lévő bejelentkezés: <strong><?= h($pendingUsername); ?></strong>
        </p>
      <?php endif; ?>
      <p class="auth-footnote" style="margin-top:0;margin-bottom:12px;font-size:0.8rem;">
        Nyisd meg a Discordot. A bot üzenetet küldött neked, ahol egy gombbal jóváhagyhatod vagy elutasíthatod a belépést.
      </p>

      <div id="wait-message" class="auth-footnote" style="font-size:0.82rem;">
        Várakozás a jóváhagyásra…
      </div>
      <div id="wait-error" class="alert alert-error" style="margin-top:10px;display:none;"></div>

      <p class="auth-footnote" style="margin-top:18px;">
        Ha nem te kezdeményezted ezt a belépést, azonnal változtasd meg a jelszavad.
      </p>

      <p class="auth-footnote">
        <a href="/admin/login.php">← Vissza a bejelentkezéshez</a>
      </p>
    </section>
  </main>

  <script>
    const errorBox = document.getElementById('wait-error');
    const waitMessage = document.getElementById('wait-message');

    async function checkStatus() {
      try {
        const res = await fetch('/admin/login_status.php', { cache: 'no-store' });
        if (!res.ok) return;
        const data = await res.json();

        if (data.status === 'approved' && data.redirect) {
          window.location.href = data.redirect;
        } else if (data.status === 'rejected' || data.status === 'expired') {
          waitMessage.style.display = 'none';
          errorBox.style.display = 'block';
          errorBox.textContent = data.message || 'A bejelentkezési kérés nem lett jóváhagyva.';
          setTimeout(function () {
            window.location.href = '/admin/login.php';
          }, 4000);
        }
      } catch (e) {
      }
    }

    setInterval(checkStatus, 3000);
  </script>
</body>
</html>
