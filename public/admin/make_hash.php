<?php
// /admin/make_hash.php
// Ideiglenes tool: írd be alul a jelszót, nyisd meg böngészőből, kimásolod a hash-t.

$password = 'ethernia123';  // PL: EtHeRnIa2025!

$hash = password_hash($password, PASSWORD_DEFAULT);
?><!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>Hash generátor</title>
</head>
<body>
  <p>Jelszó: <code><?php echo htmlspecialchars($password, ENT_QUOTES, 'UTF-8'); ?></code></p>
  <p>Hash:</p>
  <pre><?php echo htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?></pre>
</body>
</html>
