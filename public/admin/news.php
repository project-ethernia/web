<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';
require_once __DIR__ . '/config/news_categories.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$currentUserId   = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$currentUsername = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';

// Egyszerű CRUD logika (hozzáadás, szerkesztés, törlés, láthatóság)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'edit') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $category = $_POST['category'] ?? 'INFO';
            $isVisible = isset($_POST['is_visible']) ? 1 : 0;

            if ($title === '' || $content === '') throw new Exception('Cím és tartalom kötelező!');
            if (!array_key_exists($category, NEWS_CATEGORIES)) $category = 'INFO';

            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO news (title, content, category, is_visible) VALUES (?, ?, ?, ?)');
                $stmt->execute([$title, $content, $category, $isVisible]);
                log_admin_action($pdo, $currentUserId, $currentUsername, "Új hír létrehozása: '{$title}'");
            } else {
                $stmt = $pdo->prepare('UPDATE news SET title=?, content=?, category=?, is_visible=? WHERE id=?');
                $stmt->execute([$title, $content, $category, $isVisible, $id]);
                log_admin_action($pdo, $currentUserId, $currentUsername, "Hír szerkesztése: '{$title}'");
            }
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'toggle_visible') {
            $id = (int)($_POST['id'] ?? 0);
            $isVisible = (int)($_POST['is_visible'] ?? 0);
            $stmt = $pdo->prepare('UPDATE news SET is_visible=? WHERE id=?');
            $stmt->execute([$isVisible, $id]);
            
            $stmt = $pdo->prepare('SELECT title FROM news WHERE id=?');
            $stmt->execute([$id]);
            $title = $stmt->fetchColumn() ?: 'Ismeretlen';
            $state = $isVisible ? 'látható' : 'rejtett';
            
            log_admin_action($pdo, $currentUserId, $currentUsername, "Hír láthatóság módosítása: '{$title}' -> {$state}");
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT title FROM news WHERE id=?');
            $stmt->execute([$id]);
            $title = $stmt->fetchColumn() ?: 'Ismeretlen';

            $stmt = $pdo->prepare('DELETE FROM news WHERE id=?');
            $stmt->execute([$id]);
            log_admin_action($pdo, $currentUserId, $currentUsername, "Hír törlése: '{$title}'");
            echo json_encode(['ok' => true]);
            exit;
        }

        throw new Exception('Ismeretlen művelet.');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$stmt = $pdo->query('SELECT * FROM news ORDER BY created_at DESC');
$newsList = $stmt->fetchAll();
$totalNews = count($newsList);
$visibleNews = count(array_filter($newsList, fn($n) => $n['is_visible'] == 1));

$currentNav = 'news';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA Admin – Hírek kezelése</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@100..700&display=block">
    <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/news.css?v=<?= time(); ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header glass-panel">
            <div class="header-text">
                <h1 class="admin-title">Hírek kezelése</h1>
                <p class="admin-subtitle">A nyitó oldalon megjelenő híreket tudod itt létrehozni és szerkeszteni.</p>
            </div>
            <div class="header-actions" style="display: flex; gap: 1rem; align-items: center;">
                <div class="stat-pill glass-panel">
                    <span>Összes: <strong class="text-white"><?= $totalNews; ?></strong> &nbsp;|&nbsp; Látható: <strong class="text-success"><?= $visibleNews; ?></strong></span>
                </div>
                <button type="button" class="btn btn-glow-red" id="btn-add-news">+ Új hír</button>
            </div>
        </header>

        <section class="admin-content glass-panel">
            <?php if (empty($newsList)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📰</div>
                    <h3>Nincs még egyetlen hír sem.</h3>
                    <button type="button" class="btn btn-outline" id="btn-add-news-empty">+ Írd meg az elsőt</button>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cím és Tartalom</th>
                                <th>Kategória</th>
                                <th>Dátum</th>
                                <th>Láthatóság</th>
                                <th class="text-right">Műveletek</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($newsList as $news): ?>
                                <?php 
                                    $visible = (int)$news['is_visible'] === 1; 
                                    $shortContent = mb_strimwidth(strip_tags($news['content']), 0, 50, '...');
                                ?>
                                <tr class="news-row" data-id="<?= $news['id']; ?>" data-title="<?= h($news['title']); ?>" data-content="<?= h($news['content']); ?>" data-category="<?= h($news['category']); ?>" data-visible="<?= $news['is_visible']; ?>">
                                    <td class="cell-order"><?= (int)$news['id']; ?></td>
                                    <td>
                                        <div class="news-title"><?= h($news['title']); ?></div>
                                        <div class="news-preview"><?= h($shortContent); ?></div>
                                    </td>
                                    <td><?= getCategoryBadge($news['category']); ?></td>
                                    <td class="cell-date"><?= date('Y. m. d. H:i', strtotime($news['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="toggle-btn <?= $visible ? 'active' : ''; ?> toggle-visibility" data-id="<?= $news['id']; ?>" data-visible="<?= $visible ? '1' : '0'; ?>">
                                            <div class="toggle-circle"></div>
                                        </button>
                                    </td>
                                    <td class="text-right cell-actions">
                                        <button type="button" class="btn btn-outline btn-sm btn-edit-news">Szerkeszt</button>
                                        <button type="button" class="btn btn-danger btn-sm btn-delete-news" data-id="<?= $news['id']; ?>">Töröl</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<div class="modal-overlay" id="news-modal">
    <div class="modal-container glass-panel">
        <button type="button" class="modal-close" aria-label="Bezárás">&times;</button>
        <form id="news-form" class="modal-form">
            <h2 class="modal-title" id="news-modal-title">Új hír írása</h2>
            
            <input type="hidden" name="action" id="news-action" value="add">
            <input type="hidden" name="id" id="news-id" value="">

            <div class="form-group">
                <label for="news-title">Hír címe</label>
                <input type="text" id="news-title" name="title" required autocomplete="off" placeholder="Pl.: Új szezon indul!">
            </div>

            <div class="form-group">
                <label for="news-category">Kategória</label>
                <select id="news-category" name="category">
                    <?php foreach (NEWS_CATEGORIES as $key => $catData): ?>
                        <option value="<?= h($key); ?>"><?= h($catData['name']); ?> (<?= h($key); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="news-content">Tartalom (HTML engedélyezett)</label>
                <textarea id="news-content" name="content" rows="6" required placeholder="Írd le a hír részleteit..."></textarea>
            </div>
            
            <div class="form-group" style="flex-direction: row; align-items: center; gap: 10px;">
                <label for="news-visible" style="margin:0;">Azonnal publikus legyen?</label>
                <input type="checkbox" id="news-visible" name="is_visible" value="1" checked style="width: auto;">
            </div>

            <div class="modal-footer">
                <div class="action-buttons" style="width:100%; justify-content: flex-end;">
                    <span class="error-text" id="news-error" hidden></span>
                    <button type="button" class="btn btn-outline" id="news-cancel">Mégse</button>
                    <button type="submit" class="btn btn-glow-red" id="news-submit-btn">Közzététel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="/admin/assets/js/news.js?v=<?= time(); ?>"></script>
</body>
</html>