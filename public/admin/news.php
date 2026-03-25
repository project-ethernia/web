<?php
$current_page = 'news';
require_once __DIR__ . '/includes/core.php';

// JOGOSULTSÁG ELLENŐRZÉS
if (!hasPermission($admin_role, 'manage_news')) {
    header('Location: /admin/index.php?error=no_permission');
    exit;
}

$msg = '';
$msgType = '';

// Hír Kategóriák (Ezt akár a configba is kiteheted később)
$NEWS_CATEGORIES = [
    'update' => ['name' => 'Frissítés', 'icon' => 'update', 'color' => '#3b82f6'],
    'event' => ['name' => 'Esemény', 'icon' => 'event_star', 'color' => '#f59e0b'],
    'maintenance' => ['name' => 'Karbantartás', 'icon' => 'build', 'color' => '#ef4444'],
    'info' => ['name' => 'Információ', 'icon' => 'info', 'color' => '#22c55e']
];

// --- MŰVELET: Új Hír közzététele ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_news') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'info');
    $image_url = trim($_POST['image_url'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title && $content && isset($NEWS_CATEGORIES[$category])) {
        try {
            // Feltételezzük, hogy van author_id és image_url oszlopod. Ha nincs, a try-catch megfogja a hibát!
            $insert = $pdo->prepare("INSERT INTO news (title, category, content, image_url, author_id) VALUES (?, ?, ?, ?, ?)");
            // Ha a te adatbázisodban nincs author_id vagy image_url, cseréld le a fenti sort erre:
            // $insert = $pdo->prepare("INSERT INTO news (title, category, content) VALUES (?, ?, ?)"); és vedd ki az utolsó 2 paramétert az execute-ból!
            $insert->execute([$title, $category, $content, $image_url, $admin_id]);
            
            $msg = "A cikk sikeresen publikálva lett!";
            $msgType = "success";
        } catch (PDOException $e) {
            $msg = "Adatbázis hiba (Lehet, hogy hiányzik az 'image_url' vagy 'author_id' oszlop a news táblából): " . $e->getMessage();
            $msgType = "error";
        }
    } else {
        $msg = "A Cím és a Tartalom kitöltése kötelező!";
        $msgType = "error";
    }
}

// --- MŰVELET: Hír törlése ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $target_id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$target_id]);
        $msg = "Cikk sikeresen eltávolítva.";
        $msgType = "success";
    } catch (PDOException $e) {
        $msg = "Hiba a törlés során: " . $e->getMessage();
        $msgType = "error";
    }
}

// Hírek lekérdezése (próbáljuk meg lekérni az író nevét is, ha van author_id)
try {
    $stmt = $pdo->query("SELECT n.*, a.username as author_name FROM news n LEFT JOIN admins a ON n.author_id = a.id ORDER BY n.created_at DESC");
    $news_items = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ha nincs author_id a táblában, sima lekérdezés:
    $stmt = $pdo->query("SELECT * FROM news ORDER BY created_at DESC");
    $news_items = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Hírek Kezelése | ETHERNIA Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/news.css?v=<?= time(); ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <header class="admin-header glass">
            <div class="header-left">
                <span class="material-symbols-rounded header-icon">newspaper</span>
                <div>
                    <h1>Hírek és Közlemények</h1>
                    <p class="subtitle">Szerver hírek, események és frissítések publikálása</p>
                </div>
            </div>
            <div class="admin-user-info">
                Bejelentkezve mint: <strong class="text-red"><?= h($admin_name) ?></strong>
            </div>
        </header>

        <div class="admin-content">
            
            <?php if ($msg): ?>
                <div class="alert-box <?= $msgType ?>">
                    <span class="material-symbols-rounded"><?= $msgType === 'success' ? 'check_circle' : 'error' ?></span>
                    <?= h($msg) ?>
                </div>
            <?php endif; ?>

            <div class="split-layout">
                
                <div class="admin-panel glass list-panel">
                    <div class="panel-header">
                        <h2><span class="material-symbols-rounded">article</span> Publikált Cikkek</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kategória & Cím</th>
                                <th>Szerző</th>
                                <th>Dátum</th>
                                <th>Művelet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($news_items)): ?>
                                <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">Még nincsenek publikált hírek.</td></tr>
                            <?php else: ?>
                                <?php foreach ($news_items as $item): ?>
                                    <?php 
                                        $cat_key = $item['category'] ?? 'info';
                                        $cat = $NEWS_CATEGORIES[$cat_key] ?? $NEWS_CATEGORIES['info'];
                                        $author = $item['author_name'] ?? 'Rendszer';
                                        $date = date('Y.m.d. H:i', strtotime($item['created_at']));
                                    ?>
                                    <tr class="hover-row">
                                        <td class="td-id"><strong>#<?= $item['id'] ?></strong></td>
                                        <td>
                                            <div class="td-cat" style="color: <?= $cat['color'] ?>;">
                                                <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle;"><?= $cat['icon'] ?></span>
                                                <?= h($cat['name']) ?>
                                            </div>
                                            <div class="td-subject" style="margin-top: 4px;"><?= h($item['title']) ?></div>
                                        </td>
                                        <td>
                                            <div class="player-cell">
                                                <img src="https://minotar.net/helm/<?= h($author) ?>/24.png" class="player-head">
                                                <?= h($author) ?>
                                            </div>
                                        </td>
                                        <td class="td-muted"><?= $date ?></td>
                                        <td>
                                            <a href="?delete=<?= $item['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Biztosan törlöd ezt a cikket?');" title="Törlés">
                                                <span class="material-symbols-rounded">delete</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-panel glass form-panel">
                    <div class="panel-header">
                        <h2><span class="material-symbols-rounded">edit_document</span> Új Cikk Írása</h2>
                    </div>
                    <div class="panel-body">
                        <form method="POST" action="/admin/news.php" class="news-form">
                            <input type="hidden" name="action" value="add_news">
                            
                            <div class="input-group">
                                <label>Cikk Címe</label>
                                <input type="text" name="title" class="admin-input" required autocomplete="off" placeholder="Pl.: Érkezik a 2.0-ás Frissítés!">
                            </div>

                            <div class="input-group">
                                <label>Kategória</label>
                                <div class="role-grid">
                                    <?php foreach ($NEWS_CATEGORIES as $key => $data): ?>
                                        <label class="role-card" style="--role-color: <?= $data['color'] ?>;">
                                            <input type="radio" name="category" value="<?= $key ?>" required <?= $key === 'info' ? 'checked' : '' ?>>
                                            <div class="role-content">
                                                <span class="material-symbols-rounded"><?= $data['icon'] ?></span>
                                                <span><?= h($data['name']) ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="input-group">
                                <label>Borítókép (Opcionális URL)</label>
                                <input type="url" name="image_url" id="news-img-input" class="admin-input" placeholder="https://imgur.com/kep.png">
                                <img id="news-img-preview" src="" alt="Előnézet" style="display: none; width: 100%; border-radius: 8px; margin-top: 0.5rem; border: 1px solid var(--border-glass);">
                            </div>

                            <div class="input-group">
                                <label>Cikk Tartalma (HTML / Szöveg)</label>
                                <textarea name="content" class="admin-textarea" id="news-textarea" required placeholder="Írd le a részleteket..."></textarea>
                            </div>

                            <button type="submit" class="btn-action btn-claim" style="width: 100%; margin-top: 1rem; border-color: #3b82f6; color: #3b82f6;">
                                <span class="material-symbols-rounded">publish</span>
                                Publikálás
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script src="/admin/assets/js/sidebar.js?v=<?= time(); ?>"></script>
<script src="/admin/assets/js/news.js?v=<?= time(); ?>"></script>
</body>
</html>