<?php
// === ETHERNIA ADMIN - KÖZPONTI NAPLÓZÓ MOTOR ===

function log_admin_action(PDO $pdo, ?int $adminId, string $username, string $action, array $context = []): void {
    // 1. Valódi IP megszerzése (Cloudflare támogatással)
    $ip = '0.0.0.0';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    $ua  = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 250) : 'unknown';
    $ctx = $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, username, action, context, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$adminId, $username, $action, $ctx, $ip, $ua]);
    } catch (PDOException $e) {
    }
}
?>