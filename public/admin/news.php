<?php
session_start();

/* --- HIBÁK (fejlesztésnél hasznos, élesben kikapcsolhatod) --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

/* --- BEJELENTKEZETT FELHASZNÁLÓ NEVE (szerző) --- */
$currentUser = 'Ismeretlen';
if (!empty($_SESSION['admin_username'])) {
    $currentUser = $_SESSION['admin_username'];
} elseif (!empty($_SESSION['username'])) {
    $currentUser = $_SESSION['username'];
}

/* --- DB BEÁLLÍTÁSOK --- */
$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'ethernia';
$DB_PASS = 'LrKqjfTKc3Q5H6e1Ohuo';

function get_pdo() {
    static $pdo = null;
    global $DB_DSN, $DB_USER, $DB_PASS;
    if ($pdo === null) {
        $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ));
    }
    return $pdo;
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* --- LOG FUNKCIÓ --- */
require_once __DIR__ . '/log.php';

/* ---------------- AJAX RÉSZ: SAVE / DELETE / TOGGLE_VISIBLE ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $pdo    = get_pdo();
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // logoláshoz aktuális admin ID + név
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

            $isNew = ($id === null);

            if ($isNew) {
                // ÚJ HÍR: dátum = mai nap, szerző = bejelentkezett user
                $dateDisplay = date('Y. m. d.');
                $author      = $currentUser;

                $stmt = $pdo->prepare("
                    INSERT INTO news (title, tag, date_display, short_text, full_text, order_index, is_visible, author)
                    VALUES (:title, :tag, :date_display, :short_text, :full_text, :order_index, :is_visible, :author)
                ");
                $stmt->bindValue(':date_display', $dateDisplay);
                $stmt->bindValue(':author', $author);
            } else {
                // LÉTEZŐ HÍR: dátum + szerző nem változik
                $stmt = $pdo->prepare("
                    UPDATE news
                    SET title = :title,
                        tag = :tag,
                        short_text = :short_text,
                        full_text = :full_text,
                        order_index = :order_index,
                        is_visible = :is_visible
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

                // LOG: új hír
                try {
                    log_admin_action(
                        $pdo,
                        $adminId,
                        $adminName,
                        "Új hír létrehozása: '{$title}'",
                        [
                            'news_id'    => $id,
                            'tag'        => $tag,
                            'visible'    => $is_visible,
                            'order_idx'  => $order_index,
                        ]
                    );
                } catch (Throwable $e) {
                    // log hiba ignorálva
                }
            } else {
                // LOG: hír módosítása
                try {
                    log_admin_action(
                        $pdo,
                        $adminId,
                        $adminName,
                        "Hír módosítása: '{$title}'",
                        [
                            'news_id'    => $id,
                            'tag'        => $tag,
                            'visible'    => $is_visible,
                            'order_idx'  => $order_index,
                        ]
                    );
                } catch (Throwable $e) {
                    // log hiba ignorálva
                }
            }

            echo json_encode(array('ok' => true, 'id' => $id));
            exit;
        }

        if ($action === 'delete') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                throw new Exception('Hiányzó ID.');
            }

            // cím lekérdezése a loghoz
            $titleForLog = null;
            $stmt = $pdo->prepare("SELECT title FROM news WHERE id = :id");
            $stmt->execute([':id' => $id]);
            if ($row = $stmt->fetch()) {
                $titleForLog = $row['title'];
            }

            $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
            $stmt->execute(array(':id' => $id));

            // LOG: törlés
            try {
                log_admin_action(
                    $pdo,
                    $adminId,
                    $adminName,
                    "Hír törlése: " . ($titleForLog ? "'{$titleForLog}'" : "ID={$id}"),
                    ['news_id' => $id]
                );
            } catch (Throwable $e) {
                // log hiba ignorálva
            }

            echo json_encode(array('ok' => true));
            exit;
        }

        if ($action === 'toggle_visible') {
            $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $isVisible = isset($_POST['is_visible']) ? (int)$_POST['is_visible'] : 0;

            if ($id <= 0) {
                throw new Exception('Hiányzó ID a láthatóság állításához.');
            }

            // cím lekérdezése a loghoz
            $titleForLog = null;
            $stmt = $pdo->prepare("SELECT title FROM news WHERE id = :id");
            $stmt->execute([':id' => $id]);
            if ($row = $stmt->fetch()) {
                $titleForLog = $row['title'];
            }

            $stmt = $pdo->prepare("UPDATE news SET is_visible = :is_visible WHERE id = :id");
            $stmt->execute(array(
                ':is_visible' => $isVisible,
                ':id'         => $id
            ));

            $stateText = $isVisible ? 'látható' : 'rejtett';

            // LOG: láthatóság módosítása
            try {
                log_admin_action(
                    $pdo,
                    $adminId,
                    $adminName,
                    "Hír láthatóság módosítása: " . ($titleForLog ? "'{$titleForLog}'" : "ID={$id}") . " → {$stateText}",
                    [
                        'news_id'  => $id,
                        'visible'  => $isVisible,
                        'state'    => $stateText,
                    ]
                );
            } catch (Throwable $e) {
                // log hiba ignorálva
            }

            echo json_encode(array('ok' => true, 'id' => $id, 'is_visible' => $isVisible));
            exit;
        }

        throw new Exception('Ismeretlen művelet.');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(array('ok' => false, 'error' => $e->getMessage()));
        exit;
    }
}

/* ---------------- GET: LISTA / ADMIN FELÜLET ---------------- */

$pdo = get_pdo();

$stmt = $pdo->query("SELECT * FROM news ORDER BY order_index ASC, created_at DESC");
$news = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Hírek</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="/admin/assets/css/news.css?v=4">
</head>
<body class="admin-body">
  <div class="admin-layout">

    <?php
      // közös sidebar include
      $activePage      = 'news';
      $currentUsername = $currentUser;
      require __DIR__ . '/_sidebar.php';
    ?>

    <!-- FŐ TARTALOM -->
    <div class="admin-main">
      <header class="admin-header">
        <div>
          <h1 class="admin-title">Hírek kezelése</h1>
          <p class="admin-subtitle">
            A nyitó oldalon megjelenő híreket tudod itt létrehozni, szerkeszteni és elrejteni.
          </p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-add-news">
          + Új hír
        </button>
      </header>

      <section class="admin-section">
        <?php if (empty($news)): ?>
          <div class="admin-empty">
            <p>Még nincs egyetlen hír sem.</p>
            <button type="button" class="btn btn-primary" id="btn-add-news-empty">
              + Hozd létre az első hírt
            </button>
          </div>
        <?php else: ?>
          <div class="admin-table-wrapper">
            <table class="admin-table">
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
                    data-id="<?php echo h($row['id']); ?>"
                    data-title="<?php echo h($row['title']); ?>"
                    data-tag="<?php echo h($row['tag']); ?>"
                    data-date_display="<?php echo h($row['date_display']); ?>"
                    data-short_text="<?php echo h($row['short_text']); ?>"
                    data-full_text="<?php echo h($row['full_text']); ?>"
                    data-order_index="<?php echo (int)$row['order_index']; ?>"
                    data-is_visible="<?php echo (int)$row['is_visible']; ?>"
                    data-author="<?php echo h($row['author']); ?>"
                  >
                    <td class="cell-order">
                      <span class="order-value"><?php echo (int)$row['order_index']; ?></span>
                    </td>
                    <td class="cell-title">
                      <div class="title-main"><?php echo h($row['title']); ?></div>
                      <div class="title-sub"><?php echo h($row['short_text']); ?></div>
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
                      <span class="<?php echo $tagClass; ?>">
                        <?php echo h($tag); ?>
                      </span>
                    </td>
                    <td class="cell-date">
                      <?php echo h($row['date_display']); ?>
                    </td>
                    <td class="cell-author">
                      <?php echo h($row['author']); ?>
                    </td>
                    <td class="cell-visible">
                      <?php $visible = (int)$row['is_visible'] === 1; ?>
                      <button
                        type="button"
                        class="visibility-toggle <?php echo $visible ? 'is-on' : 'is-off'; ?>"
                        data-id="<?php echo (int)$row['id']; ?>"
                        data-visible="<?php echo $visible ? '1' : '0'; ?>"
                        aria-pressed="<?php echo $visible ? 'true' : 'false'; ?>"
                        title="<?php echo $visible ? 'Látható – kattints az elrejtéshez' : 'Rejtett – kattints a megjelenítéshez'; ?>"
                      >
                        <span class="toggle-knob"></span>
                      </button>
                    </td>
                    <td class="cell-actions">
                      <button type="button" class="btn btn-sm btn-secondary btn-edit">Szerkesztés</button>
                      <button type="button" class="btn btn-sm btn-danger btn-delete">Törlés</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>

  <!-- MODAL: HÍR LÉTREHOZÁSA / SZERKESZTÉSE -->
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
          <label for="news-short">Rövid szöveg (slider kártyára)</label>
          <textarea id="news-short" name="short_text" rows="3"></textarea>
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
  <script src="/admin/assets/js/news.js?v=2"></script>
</body>
</html>
