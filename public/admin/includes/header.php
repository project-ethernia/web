<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'ETHERNIA Admin' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    <style>
        .admin-layout { display: flex; min-height: 100vh; overflow: hidden; width: 100%; }
        .admin-main { flex: 1; min-width: 0; padding: 2rem; display: flex; flex-direction: column; gap: 2rem; height: 100vh; overflow-y: auto; box-sizing: border-box; }
        .admin-header { padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; border-radius: 12px; }
        .admin-content { display: flex; flex-direction: column; gap: 2rem; flex: 1; }
    </style>
    <?php if (!empty($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>?v=<?= time(); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($extra_scripts_head)): ?>
        <?php foreach ($extra_scripts_head as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    <main class="admin-main"></main>