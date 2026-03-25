<?php
$current_page = 'news';
require_once __DIR__ . '/includes/core.php';

// JOGOSULTSÁG ELLENŐRZÉS
if (!hasPermission($admin_role, 'manage_news') && !hasPermission($admin_role, 'all')) {
    header('Location: /admin/index.php?error=no_permission');
    exit;
}

$msg = '';
$msgType = '';

$NEWS_CATEGORIES = [
    'INFO' => ['name' => 'Információ', 'color' => '#3b82f6', 'icon' => 'info'],
    'UPDATE' => ['name' => 'Frissítés', 'color' => '#22c55e', 'icon' => 'update'],
    'EVENT' => ['name' => 'Esemény', 'color' => '#f59e0b', 'icon' => 'event']
];

// --- HÍR HOZZÁADÁSA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_news') {
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? 'INFO';
    $short_text = trim($_POST['short_text'] ?? '');
    $full_text = trim($_POST['full_text'] ?? '');
    $image_url = trim($_POST['image_url'] ?? ''); 
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;

    if ($title && $short_text && $full_text) {
        try {
            $tag = ucfirst(strtolower($category));
            $date_display = date('Y. m. d.');
            $author = $admin_name;

            $stmt = $pdo->prepare("INSERT INTO news (title, category, tag, date_display, short_text, full_text, is_visible, author, image_url, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$title, $category, $tag, $date_display, $short_text, $full_text, $is_visible, $author, $image_url ?: null]);
            
            $msg = "A hír sikeresen közzétéve!";
            $msgType = "success";
            log_admin_action($pdo, $admin_id, $admin_name, "Új hír közzétéve: " . $title);
        } catch (PDOException $e) {
            $msg = "Adatbázis hiba: " . $e->getMessage();
            $msgType = "error";
        }
    } else {
        $msg = "Kérlek töltsd ki a kötelező mezőket (Cím, Rövid leírás, Teljes tartalom)!";
        $msgType = "error";
    }
}

// --- HÍR SZERKESZTÉSE (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_news') {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? 'INFO';
    $short_text = trim($_POST['short_text'] ?? '');
    $full_text = trim($_POST['full_text'] ?? '');
    $image_url = trim($_POST['image_url'] ?? ''); 
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;

    if ($id && $title && $short_text && $full_text) {
        try {
            $tag = ucfirst(strtolower($category));

            $stmt = $pdo->prepare("UPDATE news SET title=?, category=?, tag=?, short_text=?, full_text=?, is_visible=?, image_url=? WHERE id=?");
            $stmt->execute([$title, $category, $tag, $short_text, $full_text, $is_visible, $image_url ?: null, $id]);
            
            $msg = "A hír sikeresen frissítve!";
            $msgType = "success";
            log_admin_action($pdo, $admin_id, $admin_name, "Hír szerkesztve. ID: " . $id);        } catch (PDOException $e) {
            $msg = "Adatbázis hiba: " . $e->getMessage();
            $msgType = "error";
        }
    } else {
        $msg = "Kérlek töltsd ki a kötelező mezőket!";
        $msgType = "error";
    }
}

// --- HÍR TÖRLÉSE ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([(int)$_GET['delete']]);
    $msg = "A hír sikeresen törölve a rendszerből.";
    $msgType = "success";
    log_admin_action($pdo, $admin_id, $admin_name, "Hír véglegesen törölve. ID: " . (int)$_GET['delete']);}

// --- LÁTHATÓSÁG (VISIBLE) VÁLTÁSA ---
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $pdo->prepare("UPDATE news SET is_visible = NOT is_visible WHERE id = ?")->execute([(int)$_GET['toggle']]);
    header('Location: /admin/news.php');
    exit;
}

// --- SZERKESZTENDŐ HÍR BETÖLTÉSE (GET) ---
$editNews = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editNews = $stmt->fetch();
}

// Kilistázzuk a meglévő híreket
$stmt = $pdo->query("SELECT * FROM news ORDER BY created_at DESC");
$newsList = $stmt->fetchAll();
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
                    <h1>Hírek & Bejelentések</h1>
                    <p class="subtitle">A Főoldalon megjelenő bejegyzések kezelése</p>
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
                        <h2><span class="material-symbols-rounded">list_alt</span> Közzétett Hírek</h2>
                    </div>
                    
                    <?php if(empty($newsList)): ?>
                        <div class="empty-state">
                            <span class="material-symbols-rounded">article</span>
                            <p>Még nem hoztál létre egyetlen hírt sem.</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Cím és Részlet</th>
                                    <th>Kategória</th>
                                    <th>Láthatóság</th>
                                    <th>Dátum</th>
                                    <th>Műveletek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($newsList as $n): ?>
                                    <?php 
                                        $catInfo = $NEWS_CATEGORIES[$n['category']] ?? ['name' => $n['category'], 'color' => '#64748b'];
                                        $isVisible = (int)$n['is_visible'] === 1;
                                    ?>
                                    <tr class="hover-row <?= ($editNews && $editNews['id'] === $n['id']) ? 'editing-row' : '' ?>">
                                        <td>
                                            <div class="news-title"><?= h($n['title']) ?></div>
                                            <div class="news-snippet"><?= h(mb_strimwidth(strip_tags($n['short_text']), 0, 50, '...')) ?></div>
                                        </td>
                                        <td>
                                            <span class="cat-badge" style="color: <?= $catInfo['color'] ?>; background: <?= $catInfo['color'] ?>20; border: 1px solid <?= $catInfo['color'] ?>50;">
                                                <?= h($catInfo['name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?toggle=<?= $n['id'] ?>" class="toggle-visibility <?= $isVisible ? 'active' : 'inactive' ?>" title="Láthatóság átváltása">
                                                <span class="material-symbols-rounded"><?= $isVisible ? 'visibility' : 'visibility_off' ?></span>
                                            </a>
                                        </td>
                                        <td class="td-muted"><?= h($n['date_display']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit=<?= $n['id'] ?>" class="btn-sm btn-edit" title="Szerkesztés">
                                                    <span class="material-symbols-rounded">edit</span>
                                                </a>
                                                <a href="?delete=<?= $n['id'] ?>" class="btn-sm btn-danger" title="Törlés" onclick="ethConfirm(event, 'Biztosan véglegesen törlöd ezt a hírt?', this.href);">
                                                    <span class="material-symbols-rounded">delete</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="admin-panel glass form-panel" <?= $editNews ? 'style="border-color: #3b82f6; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);"' : '' ?>>
                    <div class="panel-header">
                        <h2>
                            <span class="material-symbols-rounded" <?= $editNews ? 'style="color: #3b82f6;"' : '' ?>><?= $editNews ? 'edit_note' : 'post_add' ?></span> 
                            <?= $editNews ? 'Hír Szerkesztése' : 'Új Hír Írása' ?>
                        </h2>
                    </div>
                    <div class="panel-body">
                        <form method="POST" action="/admin/news.php" class="add-news-form">
                            <input type="hidden" name="action" value="<?= $editNews ? 'edit_news' : 'add_news' ?>">
                            
                            <?php if ($editNews): ?>
                                <input type="hidden" name="id" value="<?= $editNews['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="input-group">
                                <label>Hír Címe</label>
                                <input type="text" name="title" class="admin-input" required autocomplete="off" placeholder="Pl.: Megjelent a legújabb frissítés!" value="<?= $editNews ? h($editNews['title']) : '' ?>">
                            </div>

                            <div class="input-group">
                                <label>Kategória</label>
                                <div class="role-grid">
                                    <?php foreach ($NEWS_CATEGORIES as $key => $data): ?>
                                        <?php
                                            $isChecked = false;
                                            if ($editNews) {
                                                if ($editNews['category'] === $key) $isChecked = true;
                                            } else {
                                                if ($key === 'INFO') $isChecked = true;
                                            }
                                        ?>
                                        <label class="role-card" style="--role-color: <?= $data['color'] ?>;">
                                            <input type="radio" name="category" value="<?= $key ?>" required <?= $isChecked ? 'checked' : '' ?>>
                                            <div class="role-content">
                                                <span class="material-symbols-rounded"><?= $data['icon'] ?></span>
                                                <span><?= h($data['name']) ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="input-group">
                                <label>Kép URL (Opcionális)</label>
                                <input type="text" name="image_url" class="admin-input" placeholder="https://.../kep.png" value="<?= $editNews ? h($editNews['image_url']) : '' ?>">
                            </div>

                            <div class="input-group">
                                <label>Rövid Leírás (Bevezető)</label>
                                <textarea name="short_text" class="admin-input" rows="2" required placeholder="Néhány mondatos összefoglaló a kártyákhoz..."><?= $editNews ? h($editNews['short_text']) : '' ?></textarea>
                            </div>

                            <div class="input-group">
                                <label>Teljes Tartalom (HTML engedélyezett)</label>
                                <textarea name="full_text" class="admin-input" rows="6" required placeholder="Itt fejtheted ki a részleteket..."><?= $editNews ? h($editNews['full_text']) : '' ?></textarea>
                            </div>

                            <div class="input-group row-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="is_visible" <?= (!$editNews || (int)$editNews['is_visible'] === 1) ? 'checked' : '' ?>>
                                    <span class="checkmark"></span>
                                    Azonnal publikálva (Látható)
                                </label>
                            </div>

                            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                                <?php if ($editNews): ?>
                                    <a href="/admin/news.php" class="btn-action btn-back" style="flex: 1; text-align: center; justify-content: center; padding-left: 1.5rem;">
                                        <span class="material-symbols-rounded">close</span> Mégse
                                    </a>
                                <?php endif; ?>
                                <button type="submit" class="btn-action <?= $editNews ? 'btn-edit-submit' : 'btn-claim' ?>" style="flex: <?= $editNews ? '2' : '1' ?>;">
                                    <span class="material-symbols-rounded"><?= $editNews ? 'save' : 'send' ?></span>
                                    <?= $editNews ? 'Módosítások Mentése' : 'Hír Közzététele' ?>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script src="/admin/assets/js/sidebar.js?v=<?= time(); ?>"></script>
</body>
</html>