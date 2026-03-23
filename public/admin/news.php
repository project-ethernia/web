<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $adminName = $currentUser;

    try {
        if ($action === 'save') {
            $id          = (isset($_POST['id']) && $_POST['id'] !== '') ? (int)$_POST['id'] : null;
            $title       = isset($_POST['title']) ? trim($_POST['title']) : '';
            $tag         = isset($_POST['tag']) ? trim($_POST['tag']) : 'Info';
            $short       = isset($_POST['short_text']) ? trim($_POST['short_text']) : '';
            $full        = isset($_POST['full_text']) ? trim($_POST['full_text']) : '';
            $order_index = isset($_POST['order_index']) ? (int)$_POST['order_index'] : 0;
            $is_visible  = isset($_POST['is_visible']) ? 1 : 0;

            if ($title === '') {
                throw new Exception('A cím kötelező.');
            }

            if (mb_strlen($short, 'UTF-8') > 100) {
                throw new Exception('A rövid szöveg legfeljebb 100 karakter lehet.');
            }

            $isNew = ($id === null);

            if ($isNew) {
                $dateDisplay = date('Y. m. d.');
                $author      = $currentUser;

                $stmt = $pdo->prepare("
                    INSERT INTO news (title, tag, date_display, short_text, full_text, order_index, is_visible, author)
                    VALUES (:title, :tag, :date_display, :short_text, :full_text, :order_index, :is_visible, :author)
                ");
                $stmt->bindValue(':date_display', $dateDisplay);
                $stmt->bindValue(':author', $author);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE news
                    SET title       = :title,
                        tag         = :tag,
                        short_text  = :short_text,
                        full_text   = :full_text,
                        order_index = :order_index,
                        is_visible  = :is_visible
                    WHERE id = :id
                ");
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            }

            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':tag', $tag);
            $stmt->bindValue(':short_text', $short);
            $stmt->bindValue(':full_text', $full);
            $stmt->bindValue(':order_index', $order_index, PDO::PARAM_INT);
            $stmt->bindValue(':is_visible', $is_visible, PDO::PARAM_INT);
            $stmt->execute();

            if ($isNew) {
                $id = (int)$pdo->lastInsertId();
                try {
                    log_admin_action(
                        $pdo,
                        $adminId,
                        $adminName,
                        "Új hír létrehozása: '{$title}'",
                        [
                            'news_id'   => $id,
                            'tag'       => $tag,
                            'visible'   => $is_visible,
                            'order_idx' => $order_index,
                        ]
                    );
                } catch (Throwable $e) {}
            } else {
                try {
                    log_admin_action(
                        $pdo,
                        $adminId,
                        $adminName,
                        "Hír módosítása: '{$title}'",
                        [
                            'news_id'   => $id,
                            'tag'       => $tag,
                            'visible'   => $is_visible,
                            'order_idx' => $order_index,
                        ]
                    );
                } catch (Throwable $e) {}
            }

            echo json_encode(['ok' => true, 'id' => $id]);
            exit;
        }

        if ($action === 'delete') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                throw new Exception('Hiányzó ID.');
            }

            $titleForLog = null;
            $stmt = $pdo->prepare("SELECT title FROM news WHERE id = :id");
            $stmt->execute([':id' => $id]);
            if ($row = $stmt->fetch()) {
                $titleForLog = $row['title'];
            }

            $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
            $stmt->execute([':id' => $id]);

            try {
                log_admin_action(
                    $pdo,
                    $adminId,
                    $adminName,
                    "Hír törlése: " . ($titleForLog ? "'{$titleForLog}'" : "ID={$id}"),
                    ['news_id' => $id]
                );
            } catch (Throwable $e) {}

            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'toggle_visible') {
            $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $isVisible = isset($_POST['is_visible']) ? (int)$_POST['is_visible'] : 0;

            if ($id <= 0) {
                throw new Exception('Hiányzó ID a láthatóság állításához.');
            }

            $titleForLog = null;
            $stmt = $pdo->prepare("SELECT title FROM news WHERE id = :id");
            $stmt->execute([':id' => $id]);
            if ($row = $stmt->fetch()) {
                $titleForLog = $row['title'];
            }

            $stmt = $pdo->prepare("UPDATE news SET is_visible = :is_visible WHERE id = :id");
            $stmt->execute([
                ':is_visible' => $isVisible,
                ':id'         => $id
            ]);

            $stateText = $isVisible ? 'látható' : 'rejtett';

            try {
                log_admin_action(
                    $pdo,
                    $adminId,
                    $adminName,
                    "Hír láthatóság módosítása: " . ($titleForLog ? "'{$titleForLog}'" : "ID={$id}") . " → {$stateText}",
                    [
                        'news_id' => $id,
                        'visible' => $isVisible,
                        'state'   => $stateText,
                    ]
                );
            } catch (Throwable $e) {}

            echo json_encode(['ok' => true, 'id' => $id, 'is_visible' => $isVisible]);
            exit;
        }

        throw new Exception('Ismeretlen művelet.');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$stmt = $pdo->query("SELECT * FROM news ORDER BY order_index ASC, created_at DESC");
$news = $stmt->fetchAll();

$totalNews   = count($news);
$visibleNews = 0;
foreach ($news as $n) {
    if (!empty($n['is_visible'])) {
        $visibleNews++;
    }
}

$currentNav = 'news';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA Admin – Hírek kezelése</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/news.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
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
            <div class="header-actions">
                <div class="stat-pill glass-panel">
                    <span>Összes: <strong><?= (int)$totalNews; ?></strong></span>
                    <span class="divider">|</span>
                    <span>Látható: <strong class="text-success"><?= (int)$visibleNews; ?></strong></span>
                </div>
                <button type="button" class="btn btn-glow-red" id="btn-add-news">+ Új hír</button>
            </div>
        </header>

        <section class="admin-content glass-panel">
            <?php if (empty($news)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📰</div>
                    <h3>Nincs még egyetlen hír sem.</h3>
                    <p>Kattints a gombra, és oszd meg az első frissítést a játékosokkal!</p>
                    <button type="button" class="btn btn-glow-red" id="btn-add-news-empty">+ Első hír létrehozása</button>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cím és tartalom</th>
                                <th>Kategória</th>
                                <th>Dátum</th>
                                <th>Láthatóság</th>
                                <th class="text-right">Műveletek</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($news as $row): ?>
                                <?php
                                $tag = $row['tag'] ? $row['tag'] : 'Info';
                                $tagLower = mb_strtolower($tag, 'UTF-8');
                                $tagClass = 'badge-default';
                                if (strpos($tagLower, 'event') !== false) $tagClass = 'badge-event';
                                elseif (strpos($tagLower, 'info') !== false) $tagClass = 'badge-info';
                                elseif (strpos($tagLower, 'teszt') !== false || strpos($tagLower, 'test') !== false) $tagClass = 'badge-test';
                                $visible = (int)$row['is_visible'] === 1;
                                ?>
                                <tr data-id="<?= h($row['id']); ?>"
                                    data-title="<?= h($row['title']); ?>"
                                    data-tag="<?= h($row['tag']); ?>"
                                    data-date_display="<?= h($row['date_display']); ?>"
                                    data-short_text="<?= h($row['short_text']); ?>"
                                    data-full_text="<?= h($row['full_text']); ?>"
                                    data-order_index="<?= (int)$row['order_index']; ?>"
                                    data-is_visible="<?= (int)$row['is_visible']; ?>"
                                    data-author="<?= h($row['author']); ?>">
                                    
                                    <td class="cell-order"><?= (int)$row['order_index']; ?></td>
                                    <td class="cell-content">
                                        <div class="cell-title"><?= h($row['title']); ?></div>
                                        <div class="cell-desc"><?= h($row['short_text']); ?></div>
                                    </td>
                                    <td><span class="badge <?= $tagClass; ?>"><?= h($tag); ?></span></td>
                                    <td class="cell-date"><?= h($row['date_display']); ?></td>
                                    <td>
                                        <button type="button" class="toggle-btn <?= $visible ? 'active' : ''; ?>" data-id="<?= (int)$row['id']; ?>" data-visible="<?= $visible ? '1' : '0'; ?>">
                                            <div class="toggle-circle"></div>
                                        </button>
                                    </td>
                                    <td class="text-right cell-actions">
                                        <button type="button" class="btn btn-outline btn-sm btn-edit">Szerkeszt</button>
                                        <button type="button" class="btn btn-danger btn-sm btn-delete">Töröl</button>
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
            <h2 id="news-modal-title" class="modal-title">Új hír létrehozása</h2>
            
            <input type="hidden" name="id" id="news-id">
            <input type="hidden" name="action" value="save">

            <div class="form-grid">
                <div class="form-group">
                    <label for="news-title">Cím</label>
                    <input type="text" id="news-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="news-tag">Kategória (Tag)</label>
                    <select id="news-tag" name="tag">
                        <option value="Újdonság">Újdonság</option>
                        <option value="Event">Event</option>
                        <option value="Info">Info</option>
                        <option value="Teszt">Teszt</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="news-short">Rövid leírás (Kártyán jelenik meg, max 100 karakter)</label>
                <textarea id="news-short" name="short_text" rows="2" maxlength="100"></textarea>
            </div>

            <div class="form-group">
                <label for="news-full">Teljes cikk (A felugró ablakban jelenik meg)</label>
                <textarea id="news-full" name="full_text" rows="5"></textarea>
            </div>

            <div class="form-grid align-end">
                <div class="form-group">
                    <label for="news-order">Sorrend (0 a legelső)</label>
                    <input type="number" id="news-order" name="order_index" value="0">
                </div>
                <div class="form-group checkbox-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="news-visible" name="is_visible" checked>
                        <span class="checkmark"></span>
                        Publikus (Látható a főoldalon)
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <div class="meta-info">
                    Szerző: <strong id="news-meta-author">-</strong> | Dátum: <strong id="news-meta-date">-</strong>
                </div>
                <div class="action-buttons">
                    <span class="error-text" id="news-error" hidden></span>
                    <button type="button" class="btn btn-outline" id="news-cancel">Mégse</button>
                    <button type="submit" class="btn btn-glow-red">Mentés</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    window.ETHERNIA_ADMIN_USER = <?php echo json_encode($currentUser); ?>;
</script>
<script src="/admin/assets/js/news.js?v=<?= time(); ?>"></script>
</body>
</html>