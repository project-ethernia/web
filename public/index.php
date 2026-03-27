<?php
session_start();
require_once __DIR__ . '/database.php';

$currentUser = $_SESSION['username'] ?? 'Vendég';
$current_page = 'home';
$page_title = 'Ethernia | Főoldal';
$extra_css = ['/assets/css/index.css'];
$extra_js = ['/assets/js/index.js'];

$stmt = $pdo->query("SELECT * FROM news WHERE is_visible = 1 ORDER BY created_at DESC LIMIT 6");
$newsList = $stmt->fetchAll();

$NEWS_CATEGORIES = [
    'INFO' => ['name' => 'Információ', 'color' => '#3b82f6', 'icon' => 'info', 'class' => 'tag-info'],
    'UPDATE' => ['name' => 'Frissítés', 'color' => '#22c55e', 'icon' => 'update', 'class' => 'tag-update'],
    'EVENT' => ['name' => 'Esemény', 'color' => '#f59e0b', 'icon' => 'event', 'class' => 'tag-event']
];

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/hero.php';
?>

<main class="container">
    <section class="section" id="hirek">
        <div class="section-header">
            <h2 class="section-title">Legfrissebb <span class="text-primary">Hírek</span></h2>
            <div class="title-line"></div>
            <p class="section-subtitle">Ne maradj le a szerver legújabb frissítéseiről és eseményeiről!</p>
        </div>

        <?php if(empty($newsList)): ?>
            <div class="empty-state glass">
                <span class="material-symbols-rounded">article</span>
                <h3>Jelenleg nincsenek hírek</h3>
                <p>Nézz vissza később a legújabb információkért!</p>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach($newsList as $news): ?>
                    <?php $cat = $NEWS_CATEGORIES[$news['category']] ?? $NEWS_CATEGORIES['INFO']; ?>
                    
                    <article class="news-card glass hover-lift" onclick="openNewsModal(<?= $news['id'] ?>)">
                        <?php if(!empty($news['image_url'])): ?>
                            <div class="news-image" style="background-image: url('<?= h($news['image_url']) ?>');">
                                <div class="news-cat-badge" style="background: <?= $cat['color'] ?>;">
                                    <span class="material-symbols-rounded"><?= $cat['icon'] ?></span> <?= h($cat['name']) ?>
                                </div>
                            </div>
                            <div class="news-card-body">
                                <h3 class="news-title"><?= h($news['title']) ?></h3>
                                <p class="news-text"><?= nl2br(h($news['short_text'])) ?></p>
                                <div class="news-footer">
                                    <div class="author"><img src="https://minotar.net/helm/<?= h($news['author']) ?>/24.png" alt="Author"><span><?= h($news['author']) ?></span></div>
                                    <span class="date"><?= h($news['date_display']) ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="news-card-body">
                                <div class="news-meta">
                                    <span class="badge <?= $cat['class'] ?>"><span class="material-symbols-rounded" style="font-size:1rem; vertical-align:middle; margin-right:4px;"><?= $cat['icon'] ?></span> <?= h($cat['name']) ?></span>
                                </div>
                                <h3 class="news-title"><?= h($news['title']) ?></h3>
                                <p class="news-text"><?= nl2br(h($news['short_text'])) ?></p>
                                <div class="news-footer">
                                    <div class="author"><img src="https://minotar.net/helm/<?= h($news['author']) ?>/24.png" alt="Author"><span><?= h($news['author']) ?></span></div>
                                    <span class="date"><?= h($news['date_display']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="news-action">
                <a href="/news.php" class="btn btn-outline-glow">Összes hír megtekintése</a>
            </div>
        <?php endif; ?>
    </section>

    <section class="section">
        <div class="features-grid">
            <div class="feature-card glass hover-lift">
                <div class="feature-icon glow-primary"><span class="material-symbols-rounded">speed</span></div>
                <h3>Akadásmentes Játék</h3>
                <p>Szerverünk a legújabb technológiákon fut, hogy biztosítsuk a folyamatos, lagmentes játékélményt mindenki számára.</p>
            </div>
            <div class="feature-card glass hover-lift">
                <div class="feature-icon glow-primary"><span class="material-symbols-rounded">group</span></div>
                <h3>Remek Közösség</h3>
                <p>Csatlakozz egy barátságos, segítőkész magyar közösséghez, ahol mindig találsz társaságot a játékhoz!</p>
            </div>
            <div class="feature-card glass hover-lift">
                <div class="feature-icon glow-primary"><span class="material-symbols-rounded">security</span></div>
                <h3>Folyamatos Fejlesztés</h3>
                <p>Admin csapatunk éjjel-nappal dolgozik a szerver fejlesztésén és a hibák javításán a maximális élményért.</p>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>