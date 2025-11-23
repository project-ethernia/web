<?php
session_start();

/* --- Hibák ideiglenes kiírása, ha gond van (fejlesztéshez jó) --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
 * Jogosultság ellenőrzést ide tudsz rakni, ha kell:
 * if (empty($_SESSION['is_admin'])) {
 *     header('Location: /login.php');
 *     exit;
 * }
 */

// --- Bejelentkezett felhasználó neve (szerző) ---
$currentUser = 'Ismeretlen';
if (isset($_SESSION['admin_username']) && $_SESSION['admin_username'] !== '') {
    $currentUser = $_SESSION['admin_username'];
} elseif (isset($_SESSION['username']) && $_SESSION['username'] !== '') {
    $currentUser = $_SESSION['username'];
}

// --- DB beállítások: TÖLTSD KI SAJÁT ADATOKKAL ---
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

// ---------- AJAX mentés / törlés ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $pdo    = get_pdo();
    $action = isset($_POST['action']) ? $_POST['action'] : '';

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

            if ($id === null) {
                // ÚJ HÍR: dátum = mai nap, szerző = bejelentkezett user
                $dateDisplay = date('Y. m. d.');
                $author      = $currentUser;

                $stmt = $pdo->prepare(
                    "INSERT INTO news (title, tag, date_display, short_text, full_text, order_index, is_visible, author)
                     VALUES (:title, :tag, :date_display, :short_text, :full_text, :order_index, :is_visible, :author)"
                );
                $stmt->bindValue(':date_display', $dateDisplay);
                $stmt->bindValue(':author', $author);
            } else {
                // MEGLÉVŐ HÍR: dátum + szerző nem változik
                $stmt = $pdo->prepare(
                    "UPDATE news
                     SET title = :title,
                         tag = :tag,
                         short_text = :short_text,
                         full_text = :full_text,
                         order_index = :order_index,
                         is_visible = :is_visible
                     WHERE id = :id"
                );
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            }

            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':tag', $tag);
            $stmt->bindValue(':short_text', $short);
            $stmt->bindValue(':full_text', $full);
            $stmt->bindValue(':order_index', $order_index, PDO::PARAM_INT);
            $stmt->bindValue(':is_visible', $is_visible, PDO::PARAM_INT);
            $stmt->execute();

            if ($id === null) {
                $id = (int)$pdo->lastInsertId();
            }

            echo json_encode(array('ok' => true, 'id' => $id));
            exit;
        }

        if ($action === 'delete') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                throw new Exception('Hiányzó ID.');
            }
            $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
            $stmt->execute(array(':id' => $id));
            echo json_encode(array('ok' => true));
            exit;
        }

        throw new Exception('Ismeretlen művelet.');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(array('ok' => false, 'error' => $e->getMessage()));
        exit;
    }
}

// ---------- GET: lista megjelenítése ----------
$pdo  = get_pdo();
$stmt = $pdo->query("SELECT * FROM news ORDER BY order_index ASC, created_at DESC");
$news = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Hírek</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/admin/news.css">
</head>
<body>
  <header class="admin-header">
    <div class="admin-header-inner">
      <div class="admin-logo">ETHERNIA <span>Admin</span></div>
      <div class="admin-header-right">
        Bejelentkezve: <strong><?php echo htmlspecialchars($currentUser, ENT_QUOTES, 'UTF-8'); ?></strong>
      </div>
    </div>
  </header>

  <main class="admin-main">
    <section class="admin-section">
      <div class="admin-section-header">
        <div>
          <h1>Hírek kezelése</h1>
          <p class="admin-section-sub">
            Itt tudod szerkeszteni azokat a híreket, amik a bejelentkezési oldalon a felső sliderben megjelennek.
            A dátum és a szerző automatikusan kerül mentésre.
          </p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-add-news">
          + Új hír
        </button>
      </div>

      <div class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Cím</th>
              <th>Tag</th>
              <th>Dátum</th>
              <th>Szerző</th>
              <th>Sorrend</th>
              <th>Látható</th>
              <th>Műveletek</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($news)): ?>
            <tr>
              <td colspan="8" class="admin-table-empty">
                Még nincs egyetlen hír sem. Kattints az „Új hír” gombra a létrehozáshoz.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($news as $row): ?>
              <tr
                data-id="<?php echo htmlspecialchars($row['id']); ?>"
                data-title="<?php echo htmlspecialchars($row['title']); ?>"
                data-tag="<?php echo htmlspecialchars($row['tag']); ?>"
                data-date_display="<?php echo htmlspecialchars($row['date_display']); ?>"
                data-short_text="<?php echo htmlspecialchars($row['short_text']); ?>"
                data-full_text="<?php echo htmlspecialchars($row['full_text']); ?>"
                data-order_index="<?php echo (int)$row['order_index']; ?>"
                data-is_visible="<?php echo (int)$row['is_visible']; ?>"
                data-author="<?php echo htmlspecialchars($row['author']); ?>"
              >
                <td>#<?php echo (int)$row['id']; ?></td>
                <td class="title-cell"><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['tag']); ?></td>
                <td><?php echo htmlspecialchars($row['date_display']); ?></td>
                <td><?php echo htmlspecialchars($row['author']); ?></td>
                <td><?php echo (int)$row['order_index']; ?></td>
                <td><?php echo $row['is_visible'] ? 'Igen' : 'Nem'; ?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-secondary btn-edit">Szerkesztés</button>
                  <button type="button" class="btn btn-sm btn-danger btn-delete">Törlés</button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <!-- HÍR SZERKESZTŐ MODAL -->
  <div class="modal" id="news-modal" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true">
      <button type="button" class="modal-close" aria-label="Bezárás">×</button>

      <form class="modal-content" id="news-form">
        <h2 id="news-modal-title">Új hír</h2>

        <input type="hidden" name="id" id="news-id">
        <input type="hidden" name="action" value="save">

        <div class="form-group">
          <label for="news-title">Cím</label>
          <input type="text" id="news-title" name="title" required>
        </div>

        <div class="form-row">
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
          <span style="margin-left: 1rem;">Dátum: <strong id="news-meta-date">Mentés után</strong></span>
        </p>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" id="news-cancel">Mégse</button>
          <button type="submit" class="btn btn-primary">Mentés</button>
        </div>

        <p class="form-error" id="news-error" hidden></p>
      </form>
    </div>
  </div>

  <script>
    // átadjuk a bejelentkezett nevet JS-nek is, ha kellene később
    window.ETHERNIA_ADMIN_USER = <?php echo json_encode($currentUser); ?>;
  </script>
  <script src="/admin/news.js"></script>
</body>
</html>
