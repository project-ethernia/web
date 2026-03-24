<?php
http_response_code(404);

session_start();
$isLoggedIn = !empty($_SESSION['is_user']) && $_SESSION['is_user'] === true;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>404 - Eltévedtél | ETHERNIA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/404.css?v=<?= time(); ?>">
</head>
<body>

    <div class="error-container glass">
        <div class="error-code">404</div>
        <h1 class="error-title">Eltévedtél a Végtelenben!</h1>
        <p class="error-desc">Úgy tűnik, rossz portálon léptél be. Az oldal, amit keresel, nem létezik, vagy elnyelte a Void.</p>
        
        <div class="redirect-info">
            Automatikus visszatérés a főoldalra <span id="redirect-timer">10</span> másodperc múlva...
        </div>

        <a href="/" class="btn btn-glow">
            <span class="material-symbols-rounded">home</span>
            Vissza a biztonságba
        </a>
    </div>

    <script src="/assets/js/404.js?v=<?= time(); ?>"></script>
</body>
</html>