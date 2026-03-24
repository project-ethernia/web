<?php
session_start();

// 1. Törlünk minden munkamenet változót
$_SESSION = [];

// 2. Töröljük a session sütit is a böngészőből a teljes biztonság érdekében
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Megsemmisítjük magát a munkamenetet
session_destroy();

// 4. Irányítás a login oldalra
$redirectUrl = '/auth/login.php';

// Ha kaptunk URL paramétert (pl. ?error=timeout a 30 perces lejárat miatt), azt továbbvisszük!
if (!empty($_GET)) {
    $queryString = http_build_query($_GET);
    $redirectUrl .= '?' . $queryString;
}

header('Location: ' . $redirectUrl);
exit;