<?php
session_start();
require_once __DIR__ . '/database.php';

$stmt = $pdo->query("SELECT * FROM news WHERE is_visible = 1 ORDER BY created_at DESC");
$newsList = $stmt->fetchAll();

$NEWS_CATEGORIES = [
    'INFO' => ['name' => 'Információ', 'color' => '#3b82f6', 'icon' => 'info'],
    'UPDATE' => ['name' => 'Frissítés', 'color' => '#22c55e', 'icon' => 'update'],
    'EVENT' => ['name' => 'Esemény', 'color' => '#f59e0b', 'icon' => 'event']
];

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Ethernia | Főoldal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
</head>
<body class="public-body">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="main-content">
    <div class="hero-section">
        <h1 class="hero-title">ETHERNIA <span class="text-primary">REBORN</span></h1>
        <p class="hero-subtitle">Üdvözlünk a szerver hivatalos weboldalán. Fedezd fel a legújabb híreket és frissítéseket!</p>
    </div>

    <div class="news-container">
        <?php if(empty($newsList)): ?>
            <div class="empty-news glass">
                <span class="material-symbols-rounded">article</span>
                <h3>Jelenleg nincsenek hírek</h3>
                <p>Nézz vissza később a legújabb információkért!</p>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach($newsList as $news): ?>
                    <?php $cat = $NEWS_CATEGORIES[$news['category']] ?? $NEWS_CATEGORIES['INFO']; ?>
                    <article class="news-card glass">
                        <?php if(!empty($news['image_url'])): ?>
                            <div class="news-image" style="background-image: url('<?= h($news['image_url']) ?>');">
                                <div class="news-cat-badge" style="background: <?= $cat['color'] ?>;">
                                    <span class="material-symbols-rounded"><?= $cat['icon'] ?></span> <?= h($cat['name']) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="news-content">
                            <?php if(empty($news['image_url'])): ?>
                                <div class="news-meta">
                                    <span class="news-cat-text" style="color: <?= $cat['color'] ?>;">
                                        <span class="material-symbols-rounded"><?= $cat['icon'] ?></span> <?= h($cat['name']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <h2 class="news-title"><?= h($news['title']) ?></h2>
                            <p class="news-short"><?= nl2br(h($news['short_text'])) ?></p>
                            
                            <div class="news-footer">
                                <div class="news-author">
                                    <img src="https://minotar.net/helm/<?= h($news['author']) ?>/24.png" alt="Author">
                                    <span><?= h($news['author']) ?></span>
                                </div>
                                <span class="news-date"><?= h($news['date_display']) ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="/assets/js/index.js?v=<?= time(); ?>"></script>
</body>
</html>