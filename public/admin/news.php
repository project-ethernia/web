<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

$currentUser = 'Ismeretlen';
if (!empty($_SESSION['admin_username'])) {
    $currentUser = $_SESSION['admin_username'];
} elseif (!empty($_SESSION['username'])) {
    $currentUser = $_SESSION['username'];
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    $adminId   = !empty($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
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
                } catch (Throwable $e) {
                }
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
                } catch (Throwable $e) {
                }
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
            } catch (Throwable $e) {
            }

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
            } catch (Throwable $e) {
            }

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
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300;400;500&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-layout">

    <?php require __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main news-main">
        <header class="admin-page-header news-page-header">
            <div>
                <h1 class="admin-page-title">Hírek kezelése</h1>
                <p class="admin-page-subtitle">
                    A nyitó oldalon megjelenő híreket tudod itt létrehozni, szerkeszteni és elrejteni.
                </p>
            </div>
            <div class="news-header-right">
                <span class="pill-counter">
                    Összes: <?= (int)$totalNews; ?> · Látható: <?= (int)$visibleNews; ?>
                </span>
                <button type="button" class="btn btn-primary" id="btn-add-news">
                    + Új hír
                </button>
            </div>
        </header>

        <section class="news-section">
            <div class="card card-news-list">
                <div class="card-header card-header-flex">
                    <div>
                        <h2 class="card-title">Hírek listája</h2>
                        <p class="card-subtitle">
                            Itt látod az összes hírt, sorrenddel és láthatósággal együtt.
                        </p>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (empty($news)): ?>
                        <div class="admin-empty">
                            <p>Még nincs egyetlen hír sem.</p>
                            <button type="button" class="btn btn-primary" id="btn-add-news-empty">
                                + Hozd létre az első hírt
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table news-table">
                                <thead>
                                <tr>
                                    <th>Sorrend</th>
                                    <th>Cím</th>
                                    <th>Tag</th>
                                    <th>Dátum</th>
                                    <th>Szerző</th>
                                    <th>Látható</th>
                                    <th>Műveletek</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($news as $row): ?>
                                    <tr
                                        data-id="<?= h($row['id']); ?>"
                                        data-title="<?= h($row['title']); ?>"
                                        data-tag="<?= h($row['tag']); ?>"
                                        data-date_display="<?= h($row['date_display']); ?>"
                                        data-short_text="<?= h($row['short_text']); ?>"
                                        data-full_text="<?= h($row['full_text']); ?>"
                                        data-order_index="<?= (int)$row['order_index']; ?>"
                                        data-is_visible="<?= (int)$row['is_visible']; ?>"
                                        data-author="<?= h($row['author']); ?>"
                                    >
                                        <td class="cell-order">
                                            <span class="order-value"><?= (int)$row['order_index']; ?></span>
                                        </td>
                                        <td class="cell-title">
                                            <div class="title-main"><?= h($row['title']); ?></div>
                                            <div class="title-sub"><?= h($row['short_text']); ?></div>
                                        </td>
                                        <td class="cell-tag">
                                            <?php
                                            $tag = $row['tag'] ? $row['tag'] : 'Info';
                                            $tagLower = mb_strtolower($tag, 'UTF-8');
                                            $tagClass = 'tag-pill';
                                            if (strpos($tagLower, 'event') !== false) {
                                                $tagClass .= ' tag-pill-event';
                                            } elseif (strpos($tagLower, 'info') !== false) {
                                                $tagClass .= ' tag-pill-info';
                                            }
                                            ?>
                                            <span class="<?= $tagClass; ?>">
                                                <?= h($tag); ?>
                                            </span>
                                        </td>
                                        <td class="cell-date">
                                            <?= h($row['date_display']); ?>
                                        </td>
                                        <td class="cell-author">
                                            <?= h($row['author']); ?>
                                        </td>
                                        <td class="cell-visible">
                                            <?php $visible = (int)$row['is_visible'] === 1; ?>
                                            <button
                                                type="button"
                                                class="visibility-toggle <?= $visible ? 'is-on' : 'is-off'; ?>"
                                                data-id="<?= (int)$row['id']; ?>"
                                                data-visible="<?= $visible ? '1' : '0'; ?>"
                                                aria-pressed="<?= $visible ? 'true' : 'false'; ?>"
                                                title="<?= $visible
                                                    ? 'Látható – kattints az elrejtéshez'
                                                    : 'Rejtett – kattints a megjelenítéshez'; ?>"
                                            >
                                                <span class="toggle-knob"></span>
                                            </button>
                                        </td>
                                        <td class="cell-actions">
                                            <button type="button" class="btn btn-sm btn-secondary btn-edit">
                                                Szerkesztés
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger btn-delete">
                                                Törlés
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</div>

<div class="modal" id="news-modal" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true">
        <button type="button" class="modal-close" aria-label="Bezárás">×</button>

        <form class="modal-content" id="news-form">
            <h2 id="news-modal-title">Új hír</h2>

            <input type="hidden" name="id" id="news-id">
            <input type="hidden" name="action" value="save">

            <div class="form-row">
                <div class="form-group">
                    <label for="news-title">Cím</label>
                    <input type="text" id="news-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="news-tag">Tag</label>
                    <select id="news-tag" name="tag">
                        <option value="Újdonság">Újdonság</option>
                        <option value="Event">Event</option>
                        <option value="Info">Info</option>
                        <option value="Teszt">Teszt</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="news-short">Rövid szöveg (max. 100 karakter)</label>
                <textarea id="news-short" name="short_text" rows="3" maxlength="100"></textarea>
            </div>

            <div class="form-group">
                <label for="news-full">Hosszú szöveg (Részletek ablakba)</label>
                <textarea id="news-full" name="full_text" rows="5"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="news-order">Sorrend</label>
                    <input type="number" id="news-order" name="order_index" value="0">
                </div>
                <div class="form-group form-group-checkbox">
                    <label class="checkbox-inline">
                        <input type="checkbox" id="news-visible" name="is_visible" checked>
                        <span>Látható a nyitó oldalon</span>
                    </label>
                </div>
            </div>

            <p class="form-meta">
                <span>Közzétette: <strong id="news-meta-author">Mentés után</strong></span>
                <span class="meta-separator">·</span>
                <span>Dátum: <strong id="news-meta-date">Mentés után</strong></span>
            </p>

            <div class="modal-actions">
                <p class="form-error" id="news-error" hidden></p>
                <div class="actions-right">
                    <button type="button" class="btn btn-secondary" id="news-cancel">Mégse</button>
                    <button type="submit" class="btn btn-primary">Mentés</button>
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
