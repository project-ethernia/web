<?php
declare(strict_types=1);

// HTTP 404 státusz beállítása
http_response_code(404);

// Kért útvonal (biztonságosan kezelve a hackerek ellen)
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$escapedUri = htmlspecialchars($uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>404 – Eltévedtél | ETHERNIA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
    
    <style>
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 70vh;
            text-align: center;
            padding: 2rem;
            position: relative;
            z-index: 2;
        }
        .error-code {
            font-family: 'Montserrat', sans-serif;
            font-size: 10rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 0;
            /* Vörösen izzó szöveg */
            background: linear-gradient(to right, #ef4444, #f43f5e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.4));
        }
        .error-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }
        .error-desc {
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            max-width: 500px;
            font-size: 1.1rem;
        }
        .bad-link {
            color: #f43f5e;
            word-break: break-all;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner glass" style="justify-content: center;">
        <a href="/" class="nav-brand" style="margin: 0 auto;">
            <span class="nav-logo-text" style="background: linear-gradient(135deg, #a855f7, #ec4899); -webkit-background-clip: text; color: transparent; letter-spacing: 0.1em; font-size: 1.5rem;">ETHERNIA</span>
        </a>
    </div>
</nav>

<main class="container">
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Eltévedtél az Ürességben...</h2>
        <p class="error-desc">Azt hiszem, egy hibás portálon léptél be. A keresett oldal (<span class="bad-link"><?= $escapedUri ?></span>) nem létezik, vagy áthelyeztük egy másik dimenzióba.</p>
        
        <a href="/" class="btn btn-glow" style="padding: 0.8rem 2rem; font-size: 1.1rem;">
            <span class="material-symbols-rounded" style="margin-right: 0.5rem; font-size: 1.2rem; vertical-align: middle;">home</span> 
            Vissza a biztonságba
        </a>
    </div>
</main>

<footer class="footer" style="margin-top: 0;">
    <div class="footer-content">
        <p class="copyright">&copy; <?= date('Y'); ?> ETHERNIA. Minden jog fenntartva.</p>
    </div>
</footer>

</body>
</html>