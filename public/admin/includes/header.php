<?php
require_once __DIR__ . '/../../includes/csrf.php';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <title><?= htmlspecialchars($page_title ?? 'ETHERNIA Admin') ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    
    <link rel="stylesheet" href="/admin/assets/css/globals.css?v=<?= time(); ?>">
    
    <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
    
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    
    <?php if (!empty($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>?v=<?= time(); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    <main class="admin-main"></main>